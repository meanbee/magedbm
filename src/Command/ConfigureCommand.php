<?php
namespace Meanbee\Magedbm\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Aws\Credentials\CredentialProvider;
use Aws\Exception\CredentialsException;
use Piwik\Ini\IniWriter;

class ConfigureCommand extends BaseCommand
{

    /**
     * Configure the command parameters.
     */
    protected function configure()
    {
        $this
            ->setName('configure')
            ->setDescription('Configure with Amazon Credentials')
            ->addArgument(
                'key',
                InputArgument::REQUIRED,
                'AWS Key'
            )
            ->addArgument(
                'secret',
                InputArgument::REQUIRED,
                'AWS Secret'
            )
            ->addArgument(
                'region',
                InputArgument::REQUIRED,
                'Default Region'
            )
            ->addArgument(
                'bucket',
                InputArgument::REQUIRED,
                'Default Bucket'
            )
            ->addOption(
                '--force',
                '-f',
                InputOption::VALUE_NONE,
                'Overwrite current credentials'
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
        if ($this->isConfigured() && !$input->getOption('force')) {
            $this->getOutput()->writeln('<error>AWS credentials detected, use --force to overwrite.</error>');
            exit;
        }

        $writer = new IniWriter();

        if (!is_dir($this->getAwsDirPath())) {
            mkdir($this->getAwsDirPath());
        }

        if(!is_writeable($this->getAwsDirPath())) {
            $this->getOutput()->writeln('<error>Unable to write AWS credentials.  Please manually add to ~/.aws/credentials');
            exit;
        }

        if (!is_dir($this->getAppDirPath())) {
            mkdir($this->getAppDirPath());
        }

        if (!is_writeable($this->getAppDirPath())) {
            $this->getOutput()->writeln('<error>Unable to write Magedbm config.  Please manually add to ~/.magedbm/config');
            exit;
        }

        $credentials = array(
            'default' => array(
                'aws_access_key_id' => $input->getArgument('key'),
                'aws_secret_access_key' => $input->getArgument('secret')
            )
        );
        $config = array(
            'default' => array(
                'region' => $input->getArgument('region')
            )
        );
        $magedbmconfig = array(
            'default' => array(
                'bucket' => $input->getArgument('bucket')
            )
        );

        $writer->writeToFile($this->getAwsCredentialsPath(), $credentials);
        $writer->writeToFile($this->getAwsConfigPath(), $config);
        $writer->writeToFile($this->getAppConfigPath(), $magedbmconfig);

        $this->getOutput()->writeln('<info>Successfully configured.</info>');
    }

    /**
     * Check if configuration already exists;
     *
     * @return bool
     */
    protected function isConfigured()
    {
        try {
            $provider = CredentialProvider::defaultProvider();
            $creds = $provider()->wait();
        } catch (CredentialsException $e) {
            return false;
        }

        return true;
    }
}