<?php

namespace Meanbee\Magedbm\Configuration;

use Meanbee\Magedbm\Api\FrameworkConfigurationInterface;

/**
 * Class Magento2Configuration
 *
 * @todo: once issue 1 is resolved on libmageconf we can remove this class
 *      see: https://github.com/meanbee/libmageconf/issues/1
 *
 * @package Meanbee\Magedbm\Configuration
 */
class Magento2Configuration implements FrameworkConfigurationInterface
{

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var string
     */
    protected $stripMode;

    /**
     * @var array
     */
    protected $env;

    /**
     * Magento2Configuration constructor.
     *
     * @param string $filePath
     * @param string $envFile
     * @param string $stripMode
     */
    public function __construct(
        $filePath,
        $envFile,
        $stripMode = '@development'
    )
    {
        $this->filePath = $filePath;
        $this->stripMode = $stripMode;
        $this->env = include $envFile;
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
     * A data source name (DSN) is a data structure that contains the information about a specific database that
     * an Open Database Connectivity ( ODBC ) driver needs in order to connect to it.
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
        return $this->env['db']['connection']['default']['dbname'];
    }

    /**
     * Get the database user name.
     *
     * @return string
     */
    public function getDatabaseUserName()
    {
        return $this->env['db']['connection']['default']['username'];
    }

    /**
     * Get the database password.
     *
     * @return string
     */
    public function getDatabasePassword()
    {
        return $this->env['db']['connection']['default']['password'];
    }

    /**
     * Get the database host.
     *
     * @return string
     */
    public function getDatabaseHost()
    {
        return $this->env['db']['connection']['default']['host'];
    }

    /**
     * Get the database port.
     *
     * @return string
     */
    public function getDatabasePort()
    {
        return '3306';
    }
}
