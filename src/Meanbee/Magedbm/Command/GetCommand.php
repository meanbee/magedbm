<?php
namespace Meanbee\Magedbm\Command;

use Aws\Common\Exception\InstanceProfileCredentialsException;
use Aws\S3\Exception\NoSuchKeyException;
use Meanbee\Magedbm\Api\StorageInterface;
use Meanbee\Magedbm\Factory\FrameworkFactory;
use Meanbee\Magedbm\Factory\s3Factory;
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
                'Drop tables before import.  Deprecated since 1.4.0 as all exports now drop tables automatically.'
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

        $config = $this->getConfig($input);

        $file = $input->getOption('file');

        $s3 = s3Factory::create(array(
            'bucket_name' => $config['bucket'],
            'file' => $file,
            'name' => $input->getArgument('name')
        ));

        if (!$file) {
            $file = $this->getLatestFile($s3, $input);
            $s3->getConfig()->setFile($file);
        }

        $this->downloadBackup($s3, $file);

        if ($input->getOption('download-only')) {
            $this->backupMove($file);
        } else {
            $this->backupImport($file, $input);
        }
    }

    /**
     * Get latest file for a project
     *
     * @param StorageInterface $s3
     * @param InputInterface $input
     *
     * @return mixed
     */
    protected function getLatestFile(StorageInterface $s3, InputInterface $input)
    {
        try {
            // Download latest available backup
            $results = $s3->getAll();

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
     * @param StorageInterface $storage
     * @param string $file
     */
    protected function downloadBackup(StorageInterface $storage, $file)
    {
        $this->getOutput()->writeln(sprintf('<info>Downloading database %s</info>', $file));

        try {
            $storage->get();
        } catch (NoSuchKeyException $e) {
            $this->getOutput()->writeln('<error>No such file found in S3 bucket.</error>');
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
     * @param string $file
     * @param InputInterface $input
     *
     * @throws \Exception
     */
    protected function backupImport($file, InputInterface $input)
    {
        $framework = FrameworkFactory::create($input, $this->getOutput(), $this->getApplication()->getAutoloader());

        try {
            $framework->importDatabase($this->getFilePath($file));
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

        if (!is_writable(getcwd())) {
            $this->getOutput()->writeln("<info>Unable to write to current working directory. Check $filename</info>");
            return;
        }

        $result = rename($filename, $newFilename);

        if ($result && file_exists($newFilename)) {
            $this->getOutput()->writeln("<info>Downloaded to $newFilename</info>");
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
