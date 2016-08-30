<?php

namespace Meanbee\Magedbm\Adapter;

use Meanbee\Magedbm\Api\FrameworkConfigurationInterface;
use Meanbee\Magedbm\Api\FrameworkInterface;
use Meanbee\Magedbm\Repository\Strip;
use Symfony\Component\Console\Output\OutputInterface;

class Magento2Adapter implements FrameworkInterface
{

    use DbDumpTrait;

    /**
     * @var FrameworkConfigurationInterface
     */
    protected $configuration;

    /**
     * @var \Magento\Framework\ObjectManagerInterface|null
     */
    protected $_magentoObjectManager;

    /**
     * @var \Magento\Framework\App\Bootstrap|null
     */
    protected $_magentoBootstrap;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_magentoResourceConnection;

    /**
     * @var string
     */
    protected $_magentoRootDir;

    /**
     * Magento2Adapter constructor.
     *
     * @param FrameworkConfigurationInterface $configuration
     * @param OutputInterface                 $output
     * @param string                          $directory
     *
     * @throws \Exception
     */
    public function __construct(
        FrameworkConfigurationInterface $configuration,
        OutputInterface $output,
        $directory
    )
    {
        $this->configuration = $configuration;
        $this->output = $output;
        $this->_magentoRootDir = $this->findMagentoRootDir($directory);
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

    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getStrippedTables()
    {
        $strippedTables = array();
        foreach (Strip::get($this->configuration->getStripMode()) as $tableName) {
            $strippedTables[] = $this->getResourceConnection()->getTableName($tableName);
        }

        return $strippedTables;
    }

    /**
     * @return \Magento\Framework\App\ResourceConnection
     */
    protected function getResourceConnection()
    {
        if (!$this->_magentoResourceConnection) {
            $this->_magentoResourceConnection = $this->getObjectManager()->create('\Magento\Framework\App\ResourceConnection');
        }

        return $this->_magentoResourceConnection;
    }

    /**
     * Given a start directory, work upwards and attempt to identify the Magento root directory.  Throws an
     * exception if it can't be found.
     *
     * @param string $start_directory
     * @return string
     * @throws \Exception
     */
    public function findMagentoRootDir($start_directory)
    {
        $ds = DIRECTORY_SEPARATOR;
        $directory_tree = explode($ds, $start_directory);

        while (count($directory_tree) > 0) {

            $current_directory = join($ds, $directory_tree);
            $current_search_location = join($ds, array_merge($directory_tree, array('app', 'bootstrap.php')));

            if (file_exists($current_search_location)) {
                return $current_directory;
            }

            array_pop($directory_tree);
        }

        throw new \Exception("Unable to locate Magento 2 root");
    }

    /**
     * Get the framework config implementation.
     *
     * @return FrameworkConfigurationInterface
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Get the output.
     *
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return \Magento\Framework\ObjectManagerInterface
     */
    protected function getObjectManager()
    {
        if (!$this->_magentoObjectManager) {
            $this->_magentoObjectManager = $this->getBootstrap()->getObjectManager();
        }

        return $this->_magentoObjectManager;
    }

    /**
     * @return \Magento\Framework\App\Bootstrap
     */
    protected function getBootstrap()
    {
        if (!$this->_magentoBootstrap) {
            if (!class_exists('\Magento\Framework\App\Bootstrap')) {
                $mage_filename = $this->_magentoRootDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
                require_once $mage_filename;
            }

            $this->_magentoBootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
        }

        return $this->_magentoBootstrap;
    }
}
