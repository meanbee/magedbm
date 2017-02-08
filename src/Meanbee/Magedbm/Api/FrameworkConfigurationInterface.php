<?php

namespace Meanbee\Magedbm\Api;

interface FrameworkConfigurationInterface
{

    /**
     * Should we strip client data from the database.
     *
     * @return string
     */
    public function getStripMode();

    /**
     * Get the file path.
     *
     * @return string
     */
    public function getFilePath();

    /**
     * Get the data source name.
     *
     * A data source name (DSN) is a data structure that contains the information about a specific database that
     * an Open Database Connectivity ( ODBC ) driver needs in order to connect to it.
     *
     * @return string
     */
    public function getDataSourceName();

    /**
     * Get the database name.
     *
     * @return string
     */
    public function getDatabaseName();

    /**
     * Get the database user name.
     *
     * @return string
     */
    public function getDatabaseUserName();

    /**
     * Get the database password.
     *
     * @return string
     */
    public function getDatabasePassword();

    /**
     * Get the database host.
     *
     * @return string
     */
    public function getDatabaseHost();

    /**
     * Get the database port.
     *
     * @return string
     */
    public function getDatabasePort();
}
