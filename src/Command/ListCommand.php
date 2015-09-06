<?php
namespace Meanbee\Magedbm\Command;

use Aws\Exception\AwsException;
use Aws\Exception\CredentialsException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Aws\Credentials\CredentialProvider;
use Aws\S3\S3Client;
use Piwik\Ini\IniReader;

class ListCommand extends BaseCommand
{

    /**
     * Configure the command parameters.
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ls')
            ->setDescription('List available backups')
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
        $s3 = $this->getS3Client($input->getOption('region'));
        $config = $this->getConfig($input);

        try {
            $results = $s3->getIterator(
                'ListObjects',
                array('Bucket' => $config['bucket'], 'Prefix' => $input->getArgument('name'))
            );

            if ($results) {
                $this->getOutput()->writeln('<info>Available backups:</info>');
            } else {
                $this->getOutput()->writeln(sprintf('<error>No backups found for %s</error>', $input->getArgument('name')));
                exit;
            }

            foreach ($results as $item) {
                $itemKeyChunks = explode('/', $item['Key']);
                $this->getOutput()->writeln(sprintf('%s %dMB', array_pop($itemKeyChunks) , $item['Size'] / 1024 / 1024));
            }

        } catch (AwsException $e) {
            $this->getOutput()->writeln(sprintf('<error>Failed to list backups. Error code %s.</error>', $e->getAwsErrorCode()));
        }
    }

}