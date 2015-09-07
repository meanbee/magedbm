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
            ->addOption(
                'file',
                null,
                InputOption::VALUE_REQUIRED,
                'File to import, otherwise latest downloaded'
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
        $s3 = $this->getS3Client($input->getOption('region'));
        $config = $this->getConfig($input);

        try {
            $file = $input->getOption('file');
            if (!$file) {
                $file = $this->getLatestFile($s3, $config, $input);
            }

            $this->getOutput()->writeln(sprintf('<info>Downloading database %s</info>', $file));

            $s3->getObject(array(
                'Bucket' => $config['bucket'],
                'Key'    => $input->getArgument('name') . '/' . $file,
                'SaveAs' => $this->getFilePath($file)
            ));

        } catch (AwsException $e) {
            $this->getOutput()->writeln(sprintf('<error>Failed to download from S3. Error code %s.</error>',
                $e->getAwsErrorCode()));
            exit;
        }

        try {
            /** @var \N98\Magento\Command\Database\ImportCommand $dump */
            $importCommand = $this->getMagerun()->find("db:import");
        } catch (\InvalidArgumentException $e) {
            throw new \Exception("'magerun db:import' command not found. Missing dependencies?");
        }

        $params = array(
            'filename'         => $this->getFilePath($file),
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

    /**
     * Get latest file for a project
     *
     * @param \Aws\S3\S3Client $s3
     * @param Array $config
     * @param InputInterface $input
     *
     * @return mixed
     */
    protected function getLatestFile($s3, $config, $input)
    {
        // Download latest available backup
        $results = $s3->getIterator('ListObjects',
            array('Bucket' => $config['bucket'], 'Prefix' => $input->getArgument('name'))
        );

        if (!$results) {
            $this->getOutput()->writeln(sprintf('<error>No backups found for %s</error>',
                $input->getArgument('name')));
        }

        $newest = null;
        foreach ($results as $item) {
            if (is_null($newest) || $item['LastModified'] > $newest['LastModified']) {
                $newest = $item;
            }
        }

        $itemKeyChunks = explode('/', $newest['Key']);
        return array_pop($itemKeyChunks);
    }

    /**
     * Get filepath in tmp directory
     *
     * @param $file
     *
     * @return string
     */
    protected function getFilePath($file)
    {
        // Create tmp directory if doesn't exist
        if (!file_exists(self::TMP_PATH) && !is_dir(self::TMP_PATH)) {
            mkdir(self::TMP_PATH, 0700);
        }

        return sprintf('%s/%s', self::TMP_PATH, $file);
    }

    /**
     * Cleanup tmp directory
     */
    protected function cleanUp()
    {
        array_map('unlink', glob(self::TMP_PATH . '/*'));
    }
}