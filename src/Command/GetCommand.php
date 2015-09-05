<?php
namespace Meanbee\Magedbm\Command;

use Aws\Exception\AwsException;
use Aws\Exception\CredentialsException;
use Aws\S3\Exception\S3Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Aws\Credentials\CredentialProvider;
use Aws\S3\S3Client;
use Piwik\Ini\IniReader;

class GetCommand extends BaseCommand
{
    protected $filename;

    /**
     * Configure the command parameters.
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('get')
            ->setDescription('Get database from Amazon S3')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Project identifier'
            )
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'Filename'
            )
            ->addOption(
                '--drop-tables',
                '-d',
                InputOption::VALUE_NONE,
                'Drop tables before import'
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!file_exists($this->getFilePath($input))) {
            // Download database from s3 if doesn't exist in tmp directory

            $iniReader = new IniReader();
            $config = $iniReader->readFile($this->getAwsConfigPath());
            $region = $input->getOption('region') ? $input->getOption('region') : $config['default']['region'];

            $magedbmConfig = $iniReader->readFile($this->getAppConfigPath());
            $bucket = $input->getOption('bucket') ? $input->getOption('bucket') : $magedbmConfig['default']['bucket'];

            try {
                // Upload to S3.
                $s3 = new S3Client([
                    'version'     => 'latest',
                    'region'      => $region,
                    'credentials' => CredentialProvider::defaultProvider(),
                ]);

                $this->getOutput()->writeln('<info>Downloading Database</info>');

                /** @var \Aws\Result $result */
                $result = $s3->getObject(array(
                    'Bucket' => $bucket,
                    'Key'    => $input->getArgument('name') . '/' . $input->getArgument('file'),
                    'SaveAs' => $this->getFilePath($input)
                ));

            } catch (CredentialsException $e) {
                $this->getOutput()->writeln('<error>AWS credentials failed</error>');
                exit;
            } catch (AwsException $e) {
                $this->getOutput()->writeln(sprintf('<error>Failed to download from S3. Error code %s.</error>', $e->getAwsErrorCode()));
                exit;
            }
        }


        try {
            /** @var \N98\Magento\Command\Database\ImportCommand $dump */
            $importCommand = $this->getMagerun()->find("db:import");
        } catch (\InvalidArgumentException $e) {
            throw new \Exception("'magerun db:import' command not found. Missing dependencies?");
        }

        $params = array(
            'filename'         => $this->getFilePath($input),
            '--compression'    => 'gzip',
        );

        if ($input->getOption('drop-tables')) {
            $params['--drop-tables'] = true;
        }

        if ($returnCode = $importCommand->run(new ArrayInput($params), $output)) {
            throw new \Exception("magerun db:import failed to import database.");
        }

        $this->cleanUp();
    }


    protected function getFilePath($input)
    {
        // Create tmp directory if doesn't exist
        if (!file_exists(self::TMP_PATH) && !is_dir(self::TMP_PATH)) {
            mkdir(self::TMP_PATH, 0700);
        }

        $filename = $input->getArgument('file');

        return sprintf('%s/%s', self::TMP_PATH, $filename);
    }

    /**
     * Cleanup tmp directory
     */
    protected function cleanUp()
    {
        array_map('unlink', glob(self::TMP_PATH . '/*'));
    }
}