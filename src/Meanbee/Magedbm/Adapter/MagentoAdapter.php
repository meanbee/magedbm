<?php

namespace Meanbee\Magedbm\Adapter;

use Ifsnop\Mysqldump\Mysqldump;
use N98\Magento\Application as MageRunApplication;
use Meanbee\Magedbm\Api\FrameworkInterface;
use Meanbee\Magedbm\Api\FrameworkConfigurationInterface;
use N98\Util\Console\Helper\DatabaseHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class MagentoAdapter implements FrameworkInterface
{

    use DbDumpTrait;

    const DEFAULT_COMPRESSION = 'gzip';

    /**
     * @var \N98\Magento\Application
     */
    protected $mageRun;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var FrameworkConfigurationInterface
     */
    protected $configuration;

    /**
     * MagentoAdapter constructor.
     *
     * @todo remove output dependency.
     * @todo remove magerun dependency.
     *
     * @param MageRunApplication              $mageRun
     * @param OutputInterface                 $output
     * @param FrameworkConfigurationInterface $configuration
     */
    public function __construct(
        MageRunApplication $mageRun,
        OutputInterface $output,
        FrameworkConfigurationInterface $configuration
    )
    {
        $this->mageRun = $mageRun;
        $this->output = $output;
        $this->configuration = $configuration;
    }

    /**
     * Import database into framework.
     *
     * @param string $filePath
     *
     * @throws \Exception
     * @return $this
     */
    public function importDatabase($filePath)
    {
        /** @var \N98\Magento\Command\Database\ImportCommand $dump */
        $importCommand = $this->getMagerun()->find("db:import");

        $params = array(
            'filename'      => $filePath,
            '--compression' => static::DEFAULT_COMPRESSION,
        );

        if ($returnCode = $importCommand->run(new ArrayInput($params), $this->getOutput())) {
            throw new \Exception('magerun db:import failed to import database.');
        }

        return $this;
    }

    /**
     * Get an instance of the MageRun app.
     *
     * @return \N98\Magento\Application
     */
    public function getMageRun()
    {
        return $this->mageRun;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return FrameworkConfigurationInterface
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Get tables which should be stripped.
     *
     * @return array
     */
    protected function getStrippedTables()
    {
        // Use Magerun for getting DB details
        $magerun = $this->getMagerun();

        // Exec must be unavailable so use PHP alternative (match output)
        $dbHelper = new DatabaseHelper();
        $dbHelper->setHelperSet($magerun->getHelperSet());
        $dbHelper->detectDbSettings(new NullOutput());
        $magerunConfig = $magerun->getConfig();
        return $dbHelper->resolveTables(
            explode(' ', $this->getConfiguration()->getStripMode()),
            $dbHelper->getTableDefinitions($magerunConfig['commands']['N98\Magento\Command\Database\DumpCommand'])
        );
    }
}
