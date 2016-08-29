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
     * Database export without using exec.
     *
     * @throws \Exception
     * @return $this
     */
    public function createBackup()
    {
        // Use Magerun for getting DB details
        $magerun = $this->getMagerun();

        // Exec must be unavailable so use PHP alternative (match output)
        $dbHelper = new DatabaseHelper();
        $dbHelper->setHelperSet($magerun->getHelperSet());
        $dbHelper->detectDbSettings(new NullOutput());
        $magerunConfig = $magerun->getConfig();
        $stripTables = $dbHelper->resolveTables(
            explode(' ', $this->getConfiguration()->getStripMode()),
            $dbHelper->getTableDefinitions($magerunConfig['commands']['N98\Magento\Command\Database\DumpCommand'])
        );

        try {
            $this->output->writeln(
                '<comment>
                No-data export for: <info>' . implode(' ', $stripTables) . '</info>
                </comment>'
            );

            $this->output->writeln(
                '<comment>
                Start dumping database <info>' . $this->getConfiguration()->getDatabaseName() . '</info> to file <info>'
                . $this->getConfiguration()->getFilePath() . '</info>
                </comment>'
            );

            // Dump Structure for tables that we are not to receive data from
            $dumpStructure = new Mysqldump(
                sprintf(
                    '%s;dbname=%s',
                    $this->getConfiguration()->getDataSourceName(),
                    $this->getConfiguration()->getDatabaseName()
                ),
                $this->getConfiguration()->getDatabaseUserName(),
                $this->getConfiguration()->getDatabasePassword(),
                array(
                    'include-tables' => $stripTables,
                    'no-data' => true,
                    'add-drop-table' => true,
                    'skip-triggers' => true,
                )
            );

            $dumpStructure->start($this->getConfiguration()->getFilePath() . '.structure');

            $dump = new Mysqldump(
                sprintf(
                    '%s;dbname=%s',
                    $this->getConfiguration()->getDataSourceName(),
                    $this->getConfiguration()->getDatabaseName()
                ),
                $this->getConfiguration()->getDatabaseUserName(),
                $this->getConfiguration()->getDatabasePassword(),
                array(
                'exclude-tables' => $stripTables,
                'add-drop-table' => true,
                'skip-triggers' => true,
            ));

            $dump->start($this->getConfiguration()->getFilePath() . '.data');

            // Now merge two files
            $fhData = fopen($this->getConfiguration()->getFilePath() . '.data', 'a+');
            $fhStructure = fopen($this->getConfiguration()->getFilePath() . '.structure', 'r');
            if ($fhData && $fhStructure) {
                while (!feof($fhStructure)) {
                    fwrite($fhData, fgets($fhStructure, 4096));
                }
            }

            fclose($fhStructure);

            // Gzip
            rewind($fhData);
            $zfh = gzopen($this->getConfiguration()->getFilePath(), 'wb');
            while (!feof($fhData)) {
                gzwrite($zfh, fgets($fhData, 4096));
            }
            gzclose($zfh);
            fclose($fhData);

        } catch (\Exception $e) {
            throw new \Exception("Unable to export database.");
        }
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
}
