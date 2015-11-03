<?php
namespace Meanbee\Magedbm\Command;

use Piwik\Ini\IniReader;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
            ->setDescription('Configure with Amazon Credentials.')
            ->addOption(
                '--key',
                '-k',
                InputOption::VALUE_REQUIRED,
                'AWS Access Key ID'
            )
            ->addOption(
                '--secret',
                '-s',
                InputOption::VALUE_REQUIRED,
                'AWS Secret Access Key'
            )
            ->addOption(
                'region',
                '-r',
                InputOption::VALUE_REQUIRED,
                'Default AWS Region'
            )
            ->addOption(
                'bucket',
                '-b',
                InputOption::VALUE_REQUIRED,
                'Default AWS S3 Bucket'
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
     * @param InputInterface $input
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

        if (!is_writeable($this->getAwsDirPath())) {
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

        if ($input->getOption('key') && $input->getOption('secret')) {
            $credentials = array(
                'default' => array(
                    'aws_access_key_id' => $input->getOption('key'),
                    'aws_secret_access_key' => $input->getOption('secret')
                )
            );

            $writer->writeToFile($this->getAwsCredentialsPath(), $credentials);
            $this->getOutput()->writeln('<info>Successfully configured AWS credentials.</info>');
        } elseif (!file_exists($this->getAwsCredentialsPath())) {
            $this->getOutput()->writeln('<error>No AWS credentials were found, nor provided.</error>');
        }

        if ($input->getOption('region')) {
            $config = array(
                'default' => array(
                    'region' => $input->getOption('region')
                )
            );

            $writer->writeToFile($this->getAwsConfigPath(), $config);
            $this->getOutput()->writeln('<info>Successfully configured AWS region config.</info>');
        } else if (!file_exists($this->getAwsConfigPath())) {
            $this->getOutput()->writeln('<error>No AWS config was found, nor provided.</error>');
        }

        if ($input->getOption('bucket')) {
            $magedbmconfig = array(
                'default' => array(
                    'bucket' => $input->getOption('bucket')
                )
            );

            $writer->writeToFile($this->getAppConfigPath(), $magedbmconfig);
            $this->getOutput()->writeln('<info>Successfully configured magedbm config.</info>');
        } elseif (!file_exists($this->getAppConfigPath())) {
            $this->getOutput()->writeln('<error>No MageDBM was found, nor provided.</error>');
        }
    }

    /**
     * Check if configuration already exists;
     *
     * @return bool
     */
    public function isConfigured()
    {
        if (file_exists($this->getAwsCredentialsPath()) && file_exists($this->getAwsConfigPath()) &&
            file_exists($this->getAppConfigPath())
        ) {
            return true;
        }

        return false;
    }
}