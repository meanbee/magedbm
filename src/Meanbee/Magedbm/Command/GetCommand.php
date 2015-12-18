<?php
namespace Meanbee\Magedbm\Command;

use Aws\Common\Exception\InstanceProfileCredentialsException;
use Aws\S3\Exception\NoSuchKeyException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
                'download-only',
                '-o',
                InputOption::VALUE_NONE,
                'Only download the backup to the current directory'
            )
            ->addOption(
                '--force',
                '-f',
                InputOption::VALUE_NONE,
                'Force import without interaction'
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
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Import overwrites databases so ask for confirmation.
        $dialog = $this->getHelper('dialog');
        if (!$input->getOption('force') && !$input->getOption('download-only')) {
            if (!$dialog->askConfirmation(
                $output,
                '<question>Are you sure you wish to overwrite local database [y/n]?</question>',
                false
            )
            ) {
                return;
            }
        }

        $s3 = $this->getS3Client($input->getOption('region'));
        $config = $this->getConfig($input);

        $file = $input->getOption('file');
        if (!$file) {
            $file = $this->getLatestFile($s3, $config, $input);
        }

        $this->downloadBackup($file, $s3, $config, $input);

        if ($input->getOption('download-only')) {
            $this->backupMove($file);
        } else {
            $this->backupImport($file, $input);
        }
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
        try {
            // Download latest available backup
            $results = $s3->getIterator('ListObjects',
                array('Bucket' => $config['bucket'], 'Prefix' => $input->getArgument('name'))
            );

            $newest = null;
            foreach ($results as $item) {
                if (is_null($newest) || $item['LastModified'] > $newest['LastModified']) {
                    $newest = $item;
                }
            }

            if (!$results->count()) {
                // Credentials Exception would have been thrown by now, so now we can safely check for item count.
                throw new \Exception('No backups found for ' . $input->getArgument('name'));
            }

        } catch (InstanceProfileCredentialsException $e) {
            $this->getOutput()->writeln('<error>AWS credentials not found. Please run `configure` command.</error>');
            exit;
        } catch (\Exception $e) {
            $this->getOutput()->writeln('<error>' . $e->getMessage() . '</error>');
            exit;
        }

        $itemKeyChunks = explode('/', $newest['Key']);
        return array_pop($itemKeyChunks);
    }

    /**
     * Download from S3 to tmp directory
     *
     * @param $s3
     * @param $config
     * @param $input
     */
    protected function downloadBackup($file, $s3, $config, $input)
    {
        $this->getOutput()->writeln(sprintf('<info>Downloading database %s</info>', $file));

        try {
            $s3->getObject(array(
                'Bucket' => $config['bucket'],
                'Key' => $input->getArgument('name') . '/' . $file,
                'SaveAs' => $this->getFilePath($file)
            ));
        } catch (NoSuchKeyException $e) {
            $this->getOutput()->writeln('<error>File such file found in S3 bucket.</error>');
            exit;
        }

        if (!file_exists($this->getFilePath($file))) {
            $this->getOutput()->writeln('<error>Failed to save file to local tmp directory.</error>');
            exit;
        }
    }

    /**
     * Import backup from tmp directory to local database
     *
     * @param $file
     * @param $input
     *
     * @throws \Exception
     */
    protected function backupImport($file, $input)
    {
        try {
            /** @var \N98\Magento\Command\Database\ImportCommand $dump */
            $importCommand = $this->getMagerun()->find("db:import");
        } catch (\InvalidArgumentException $e) {
            throw new \Exception("'magerun db:import' command not found. Missing dependencies?");
        }

        $params = array(
            'filename' => $this->getFilePath($file),
            '--compression' => 'gzip',
        );

        if ($input->getOption('drop-tables')) {
            $params['--drop-tables'] = true;
        }

        try {
            if ($returnCode = $importCommand->run(new ArrayInput($params), $this->getOutput())) {
                $this->getOutput()->writeln('<error>magerun db:import failed to import database.</error>');
            }
        } catch (\Exception $e) {
            $this->getOutput()->writeln($e->getMessage());
        }

        $this->cleanUp();
    }

    /**
     * Move backup from tmp directory to current working directory
     *
     * @param $file
     */
    protected function backupMove($file)
    {
        $filename = $this->getFilePath($file);
        $newFilename = getcwd() . '/' . $file;
        if (rename($filename, $newFilename)) {
            $this->getOutput()->writeln("<info>Downloaded to $newFilename.</info>");
        } else {
            $this->getOutput()->writeln("<error>Failed to move backup to current working directory. Check $filename</error>");
        }
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