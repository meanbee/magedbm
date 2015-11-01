<?php
namespace Meanbee\Magedbm\Command;

use Aws\Common\Exception\InstanceProfileCredentialsException;
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
        $this->createBackup($input, $output);

        $s3 = $this->getS3Client($input->getOption('region'));
        $config = $this->getConfig($input);

        try {
            $result = $s3->putObject(array(
                'Bucket' => $config['bucket'],
                'Key' => $input->getArgument('name') . '/' . $this->getFileName($input),
                'SourceFile' => $this->getFilePath($input),
            ));

            $this->getOutput()->writeln(sprintf('<info>%s database uploaded to %s.</info>',
                $input->getArgument('name'), $result->get('ObjectURL')));

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
            $this->maintainDatabaseHistory($input, $output, $s3, $config);
        }
    }

    /**
     * @param $input
     * @param $output
     * @param $s3
     * @param $config
     */
    protected function maintainDatabaseHistory($input, $output, $s3, $config)
    {
        try {
            $results = $s3->getIterator(
                'ListObjects',
                array('Bucket' => $config['bucket'], 'Prefix' => $input->getArgument('name'), 'sort_results' => true)
            );

            $results = iterator_to_array($results, true);
            $historyCount = $input->getOption('history-count') ?: self::HISTORY_COUNT;
            $deleteCount = count($results) - $historyCount;

            for ($i = 0; $i < $deleteCount; $i++) {
                $s3->deleteMatchingObjects($config['bucket'], $results[$i]['Key']);
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
     * Get file location in tmp dir
     * @param $input
     *
     * @return string
     */
    protected function getFilePath($input)
    {
        // Create tmp directory if doesn't exist
        if (!file_exists(self::TMP_PATH) && !is_dir(self::TMP_PATH)) {
            mkdir(self::TMP_PATH, 0700);
        }

        $filename = $this->getFileName($input);

        return sprintf('%s/%s', self::TMP_PATH, $filename);
    }

    /**
     * Cleanup tmp directory
     */
    protected function cleanUp()
    {
        array_map('unlink', glob(self::TMP_PATH . '/*'));
    }

    /**
     * Create database backup in tmp directory.
     * Use magerun db:dump if available. Otherwise use php alternative if exec not available.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     */
    private function createBackup(InputInterface $input, OutputInterface $output)
    {
        $magerun = $this->getMagerun();
        $filePath = $this->getFilePath($input);

        try {
            /** @var \N98\Magento\Command\Database\DumpCommand $dumpCommand */
            $dumpCommand = $magerun->find("db:dump");

            $dumpInput = new ArrayInput(array(
                'filename' => $filePath,
                '--strip' => '@development',
                '--compression' => 'gzip',
            ));

            if ($dumpCommand->run($dumpInput, $output)) {
                throw new \Exception("magerun db:dump failed to create backup..");
            }
        } catch (\InvalidArgumentException $e) {

            // Exec must be unavailable so use PHP alternative (match output)
            $dbHelper = new DatabaseHelper();
            $dbHelper->setHelperSet($magerun->getHelperSet());
            $dbHelper->detectDbSettings(new NullOutput());
            $magerunConfig = $magerun->getConfig();
            $stripTables = $dbHelper->resolveTables(explode(' ', '@development'),
                $dbHelper->getTableDefinitions($magerunConfig['commands']['N98\Magento\Command\Database\DumpCommand'])
            );

            $output->writeln(array('',
                $magerun->getHelperSet()->get('formatter')->formatBlock('Dump MySQL Database (without exec)',
                    'bg=blue;fg=white', true), '',
            ));

            $dbSettings = $dbHelper->getDbSettings();
            $username = (string)$dbSettings['username'];
            $password = (string)$dbSettings['password'];
            $dbName = (string)$dbSettings['dbname'];

            try {
                $dump = new Mysqldump(sprintf('%s;dbname=%s', $dbHelper->dsn(), $dbName), $username, $password, array(
                    'compress' => Mysqldump::GZIP,
                    'exclude-tables' => $stripTables
                ));

                $output->writeln('<comment>No-data export for: <info>' . implode(' ', $stripTables)
                    . '</info></comment>'
                );

                $output->writeln('<comment>Start dumping database <info>' . $dbSettings['dbname']
                    . '</info> to file <info>' . $filePath . '</info>'
                );

                $dump->start($filePath);
            } catch (\Exception $e) {
                throw new \Exception("Unable to export database.");
            }

            $output->writeln('<info>Finished</info>');
        }
    }
}
