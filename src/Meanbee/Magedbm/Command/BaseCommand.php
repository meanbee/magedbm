<?php
namespace Meanbee\Magedbm\Command;

use Aws\S3\S3Client;
use N98\Magento\Application as MagerunApplication;
use Piwik\Ini\IniReader;
use Piwik\Ini\IniReadingException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCommand extends Command
{

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

    /** @var \Aws\S3\S3Client $s3Client */
    protected $s3Client;

    protected $config;

    /**
     * Set the input interface for this command.
     *
     * @param InputInterface $input
     *
     * @return $this
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * Get the input interface.
     *
     * @return InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Set the output interface for this command.
     *
     * @param OutputInterface $output
     *
     * @return $this
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Get the output interface.
     *
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Get an instance of the Magerun app.
     *
     * @return \N98\Magento\Application
     */
    public function getMagerun()
    {
        if (!$this->magerun) {
            $this->magerun = new MagerunApplication($this->getApplication()->getAutoloader());
            $this->magerun->init();
        }

        return $this->magerun;
    }

    /**
     * Provide authenticated S3 client if available.
     *
     * @param null $region
     *
     * @return \Aws\S3\S3Client
     */
    public function getS3Client($region = null)
    {
        if (!$this->s3Client) {
            if (!$region) {
                $iniReader = new IniReader();

                try {
                    $config = $iniReader->readFile($this->getAwsConfigPath());
                    $region = $config['default']['region'];
                } catch (IniReadingException $e) {
                    $this->getOutput()->writeln('<error>Unable to read config. Try running `configure` again.</error>');
                }
            }

            try {
                // Upload to S3.
                $this->s3Client = S3Client::factory(array(
                    'region'  => $region
                ));
            } catch (CredentialsException $e) {
                $this->getOutput()->writeln('<error>AWS credentials failed</error>');
            }
        }

        return $this->s3Client;
    }

    /**
     * Merge provided config with config from file
     *
     * @throws IniReadingException
     */
    public function getConfig(InputInterface $input)
    {
        if (!$this->config) {
            $iniReader = new IniReader();
            try {
                $config = $iniReader->readFile($this->getAppConfigPath());
                $this->config = $config['default'];

                foreach ($input->getOptions() as $option => $value) {
                    if ($value) {
                        $this->config[$option] = $value;
                    }
                }

            } catch (IniReadingException $e) {
                $this->getOutput()->writeln('<error>Unable to read config. Try running `configure` again.</error>');
            }
        }

        return $this->config;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->setInput($input);
        $this->setOutput($output);
        $this->validateConfiguration();
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

    /**
     * Check for AWS Credentials
     */
    protected function validateConfiguration()
    {
        if ($this instanceof ConfigureCommand) {
            return true;
        }

        $configure = new ConfigureCommand();
        return $configure->isConfigured();
    }
}
