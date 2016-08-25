<?php
namespace Meanbee\Magedbm\Command;

use Aws\Common\Exception\InstanceProfileCredentialsException;
use Meanbee\Magedbm\Api\StorageInterface;
use Meanbee\Magedbm\Factory\s3Factory;
use N98\Util\Console\Helper\DatabaseHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Ifsnop\Mysqldump\Mysqldump;

class PutCommand extends BaseCommand
{
    const HISTORY_COUNT = 5;

    protected $filename;

    /**
     * Configure the command parameters.
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('put')
            ->setDescription('Backup database to Amazon S3')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Project identifier'
            )
            ->addOption(
                '--strip',
                '-s',
                InputOption::VALUE_OPTIONAL,
                'Tables to exclude from export. Default is magerun\'s @development option.'
            )
            ->addOption(
                '--no-clean',
                null,
                InputOption::VALUE_NONE,
                'Do not remove old databases on S3.'
            )
            ->addOption(
                '--history-count',
                null,
                InputOption::VALUE_REQUIRED,
                'Database history count to keep on S3.'
            )
            ->addOption(
                '--region',
                '-r',
                InputOption::VALUE_REQUIRED,
                'Optionally specify region, otherwise default configuration will be used.'
            )
            ->addOption(
                '--bucket',
                '-b',
                InputOption::VALUE_REQUIRED,
                'Optionally specify bucket, otherwise default configuration will be used. '
            );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getConfig($input);

        $s3 = s3Factory::create(array(
            'region' => $input->getOption('region'),
            'bucket_name' => $config['bucket'],
            'name' => $input->getArgument('name')
        ));

        $this->createBackup($input, $output, $s3->getConfig()->getFilePath());

        try {
            $result = $s3->put();

            $this->getOutput()->writeln(
                sprintf(
                    '<info>%s database uploaded to %s.</info>',
                    $input->getArgument('name'),
                    $result->get('ObjectURL')
                )
            );

        } catch (InstanceProfileCredentialsException $e) {
            $this->cleanUp();
            $this->getOutput()->writeln('<error>AWS credentials not found. Please run `configure` command.</error>');
            exit;
        } catch (\Exception $e) {
            $this->cleanUp();
            $this->getOutput()->writeln(sprintf('<error>Failed to upload to S3. %s.</error>', $e->getMessage()));
            exit;
        }

        $this->cleanUp();

        if (!$input->getOption('no-clean')) {
            $this->maintainDatabaseHistory($input, $output, $s3);
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param StorageInterface $storage
     */
    protected function maintainDatabaseHistory(InputInterface $input, OutputInterface $output, StorageInterface $storage)
    {
        try {
            $results = $storage->getAll();

            $results = iterator_to_array($results, true);
            $historyCount = $input->getOption('history-count') ?: self::HISTORY_COUNT;
            $deleteCount = count($results) - $historyCount;

            for ($i = 0; $i < $deleteCount; $i++) {
                $storage->deleteMatchingObjects($results[$i]['Key']);
            }

        } catch (InstanceProfileCredentialsException $e) {
            $this->getOutput()->writeln('<error>AWS credentials not found. Please run `configure` command.</error>');
        } catch (\Exception $e) {
            $this->getOutput()->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->cleanUp();
    }

    /**
     * Get path for saving temporary backups
     *
     * @param $input
     *
     * @return string
     */
    protected function getFileName($input)
    {
        if (!$this->filename) {
            $name = $input->getArgument('name');
            $timestamp = date('Y-m-d_His');

            $this->filename = sprintf('%s-%s.sql.gz', $name, $timestamp);
        }

        return $this->filename;
    }

    /**
     * Cleanup tmp directory
     *
     * @param string $filePath
     */
    protected function cleanUp($filePath = self::TMP_PATH)
    {
        array_map('unlink', glob($filePath . '/*'));
    }

    /**
     * Database export without using exec.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $filePath
     *
     * @throws \Exception
     */
    private function createBackup(InputInterface $input, OutputInterface $output, $filePath)
    {
        // Use Magerun for getting DB details
        $magerun = $this->getMagerun();

        $stripOptions = $input->getOption('strip') ?: '@development';

        // Exec must be unavailable so use PHP alternative (match output)
        $dbHelper = new DatabaseHelper();
        $dbHelper->setHelperSet($magerun->getHelperSet());
        $dbHelper->detectDbSettings(new NullOutput());
        $magerunConfig = $magerun->getConfig();
        $stripTables = $dbHelper->resolveTables(
            explode(' ', $stripOptions),
            $dbHelper->getTableDefinitions($magerunConfig['commands']['N98\Magento\Command\Database\DumpCommand'])
        );

        $output->writeln(
            array(
                '',
                $magerun->getHelperSet()->get('formatter')->formatBlock(
                    'Dump MySQL Database (without exec)',
                    'bg=blue;fg=white',
                    true
                ),
                ''
            )
        );

        $dbSettings = $dbHelper->getDbSettings();
        $username = (string)$dbSettings['username'];
        $password = (string)$dbSettings['password'];
        $dbName = (string)$dbSettings['dbname'];

        try {
            $output->writeln(
                '<comment>No-data export for: <info>' . implode(' ', $stripTables) . '</info></comment>'
            );

            $output->writeln(
                '<comment>Start dumping database <info>' . $dbSettings['dbname'] . '</info> to file <info>'
                . $filePath . '</info>'
            );

            // Dump Structure for tables that we are not to receive data from
            $dumpStructure = new Mysqldump(
                sprintf('%s;dbname=%s', $dbHelper->dsn(), $dbName),
                $username,
                $password,
                array(
                    'include-tables' => $stripTables,
                    'no-data' => true,
                    'add-drop-table' => true,
                    'skip-triggers' => true,
                )
            );

            $dumpStructure->start($filePath . '.structure');

            $dump = new Mysqldump(sprintf('%s;dbname=%s', $dbHelper->dsn(), $dbName), $username, $password, array(
                'exclude-tables' => $stripTables,
                'add-drop-table' => true,
                'skip-triggers' => true,
            ));

            $dump->start($filePath . '.data');

            // Now merge two files
            $fhData = fopen($filePath . '.data', 'a+');
            $fhStructure = fopen($filePath . '.structure', 'r');
            if ($fhData && $fhStructure) {
                while (!feof($fhStructure)) {
                    fwrite($fhData, fgets($fhStructure, 4096));
                }
            }

            fclose($fhStructure);

            // Gzip
            rewind($fhData);
            $zfh = gzopen($filePath, 'wb');
            while (!feof($fhData)) {
                gzwrite($zfh, fgets($fhData, 4096));
            }
            gzclose($zfh);
            fclose($fhData);

        } catch (\Exception $e) {
            throw new \Exception("Unable to export database.");
        }
    }
}
