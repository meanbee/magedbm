<?php

namespace Meanbee\Magedbm\Configuration;

use Meanbee\LibMageConf\ConfigReader;
use N98\Magento\Application as MageRunApplication;
use Meanbee\Magedbm\Api\FrameworkConfigurationInterface;

class MagentoConfiguration implements FrameworkConfigurationInterface
{

    /**
     * @var ConfigReader
     */
    protected $configReader;

    /**
     * @var string
     */
    protected $stripMode;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var array
     */
    protected $strippedTables;

    /**
     * MagentoConfiguration constructor.
     *
     * @param ConfigReader $configReader
     * @param string       $filePath
     * @param string       $stripMode
     *
     */
    public function __construct(
        ConfigReader $configReader,
        $filePath,
        $stripMode = '@development'
    )
    {
        $this->configReader = $configReader;
        $this->stripMode = $stripMode;
        $this->filePath = $filePath;
    }

    /**
     * Should we strip client data from the database.
     *
     * @return string
     */
    public function getStripMode()
    {
        return $this->stripMode;
    }

    /**
     * Get the file path.
     *
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * Get the data source name.
     *
     * @return string
     */
    public function getDataSourceName()
    {
        return "mysql:host={$this->getDatabaseHost()};port={$this->getDatabasePort()};dbname={$this->getDatabaseName()}";
    }

    /**
     * Get the database name.
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->configReader->getDatabaseName();
    }

    /**
     * Get the database user name.
     *
     * @return string
     */
    public function getDatabaseUserName()
    {
        return $this->configReader->getDatabaseUsername();
    }

    /**
     * Get the database password.
     *
     * @return string
     */
    public function getDatabasePassword()
    {
        return $this->configReader->getDatabasePassword();
    }

    /**
     * Get the database host.
     *
     * @return string
     */
    public function getDatabaseHost()
    {
        return $this->configReader->getDatabaseHost();
    }

    /**
     * Get the database port.
     *
     * @see https://github.com/meanbee/libmageconf/issues/2
     * @todo implement logic
     *
     * @return string
     */
    public function getDatabasePort()
    {
        return '3306';
    }
}
