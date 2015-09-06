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

class PutCommand extends BaseCommand
{
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
        try {
            /** @var \N98\Magento\Command\Database\DumpCommand $dump */
            $dumpCommand = $this->getMagerun()->find("db:dump");
        } catch (\InvalidArgumentException $e) {
            throw new \Exception("'magerun db:dump' command not found. Missing dependencies?");
        }

        $dumpInput = new ArrayInput(array(
            'filename'       => $this->getFilePath($input),
            '--strip'        => '@development',
            '--compression'  => 'gzip',
        ));

        if ($returnCode = $dumpCommand->run($dumpInput, $output)) {
            throw new \Exception("magerun db:dump failed to create backup..");
        }

        $s3 = $this->getS3Client($input->getOption('region'));
        $config = $this->getConfig($input);

        try {
            /** @var \Aws\Result $result */
            $result = $s3->putObject(array(
                'Bucket'     => $config['bucket'],
                'Key'        => $input->getArgument('name') . '/' . $this->getFileName($input),
                'SourceFile' => $this->getFilePath($input),
            ));

            $this->getOutput()->writeln(sprintf('<info>%s database uploaded to %s</info>',
                $input->getArgument('name'), $result->get('ObjectURL')));
        } catch (AwsException $e) {
            $this->getOutput()->writeln(sprintf('Failed to upload to S3. Error code %s.', $e->getAwsErrorCode()));
        } finally {
            $this->cleanUp();
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output) {
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
}