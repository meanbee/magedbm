<?php
namespace Meanbee\Magedbm\Command;

use Aws\Credentials\CredentialProvider;
use Aws\Exception\CredentialsException;
use N98\Magento\Application as MagerunApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCommand extends Command {

    const AWS_DIR_PATH          = '/.aws';
    const AWS_CREDENTIALS_PATH  = self::AWS_DIR_PATH . '/credentials';
    const AWS_CONFIG_PATH       = self::AWS_DIR_PATH . '/config';
    const APP_DIR_PATH          = '/.magedbm';
    const APP_CONFIG_PATH       = self::APP_DIR_PATH . '/config';
    const TMP_PATH              = '/tmp/magedbm';

    /** @var InputInterface $input */
    protected $input;
    /** @var OutputInterface $output */
    protected $output;

    /** @var \N98\Magento\Application $magerun */
    protected $magerun;

    /**
     * Set the input interface for this command.
     *
     * @param InputInterface $input
     *
     * @return $this
     */
    public function setInput(InputInterface $input) {
        $this->input = $input;

        return $this;
    }

    /**
     * Get the input interface.
     *
     * @return InputInterface
     */
    public function getInput() {
        return $this->input;
    }

    /**
     * Set the output interface for this command.
     *
     * @param OutputInterface $output
     *
     * @return $this
     */
    public function setOutput(OutputInterface $output) {
        $this->output = $output;

        return $this;
    }

    /**
     * Get the output interface.
     *
     * @return OutputInterface
     */
    public function getOutput() {
        return $this->output;
    }

    /**
     * Get an instance of the Magerun app.
     *
     * @return \N98\Magento\Application
     */
    public function getMagerun() {
        if (!$this->magerun) {
            $this->magerun = new MagerunApplication($this->getApplication()->getAutoloader());
            $this->magerun->init();
        }

        return $this->magerun;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output) {
        parent::initialize($input, $output);

        $this->setInput($input);
        $this->setOutput($output);
        $this->validateConfiguration();
    }


    /**
     * Check for AWS Credentials
     */
    protected function validateConfiguration()
    {
        if ($this instanceof ConfigureCommand) {
            return true;
        }

        try {
            $provider = CredentialProvider::defaultProvider();
            $creds = $provider()->wait();
        } catch (CredentialsException $e) {
            $this->getOutput()->writeln('<error>No AWS credentials found.  Please run configure first.</error>');
            exit;
        }
    }

    protected function getAppDirPath()
    {
        return getenv('HOME') . self::APP_DIR_PATH;
    }

    protected function getAppConfigPath()
    {
        return getenv('HOME') . self::APP_CONFIG_PATH;
    }

    /**
     * @return string
     */
    protected function getAwsDirPath()
    {
        return getenv('HOME') . self::AWS_DIR_PATH;
    }

    /**
     * @return string
     */
    protected function getAwsCredentialsPath()
    {
        return getenv('HOME') . self::AWS_CREDENTIALS_PATH;
    }

    /**
     * @return string
     */
    protected function getAwsConfigPath()
    {
        return getenv('HOME') . self::AWS_CONFIG_PATH;
    }


}