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

class DeleteCommand extends BaseCommand
{

    /**
     * Configure the command parameters.
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('rm')
            ->setDescription('Delete backups')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Project identifier'
            )
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'Filename (regex available)'
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
        $iniReader = new IniReader();
        $config = $iniReader->readFile($this->getAwsConfigPath());
        $region = $input->getOption('region') ? $input->getOption('region') : $config['default']['region'];

        $magedbmConfig = $iniReader->readFile($this->getAppConfigPath());
        $bucket = $input->getOption('bucket') ? $input->getOption('bucket') : $magedbmConfig['default']['bucket'];

        try {
            // Upload to S3.
            $s3 = new S3Client([
                'version' => 'latest',
                'region'  => $region,
                'credentials' => CredentialProvider::defaultProvider(),
            ]);
        } catch (CredentialsException $e) {
            $this->getOutput()->writeln('<error>AWS credentials failed</error>');
        }

        try {
            $regex = sprintf('/^%s\/%s$/', $input->getArgument('name'), $input->getArgument('file'));
            $s3->deleteMatchingObjects($bucket, $input->getArgument('name'), $regex);

            $this->getOutput()->writeln('<info>Backup deleted.</info>');

        } catch (AwsException $e) {
            $this->getOutput()->writeln(sprintf('<error>Failed to delete backup. Error code %s.</error>', $e->getAwsErrorCode()));
        }
    }

}