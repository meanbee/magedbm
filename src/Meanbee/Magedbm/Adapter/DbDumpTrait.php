<?php

namespace Meanbee\Magedbm\Adapter;

use Ifsnop\Mysqldump\Mysqldump;
use Meanbee\Magedbm\Api\FrameworkConfigurationInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait DbDumpTrait
{

    /**
     * Get tables which should be stripped.
     *
     * @return array
     */
    abstract protected function getStrippedTables();

    /**
     * Get the framework config implementation.
     *
     * @return FrameworkConfigurationInterface
     */
    abstract public function getConfiguration();

    /**
     * Get the output.
     *
     * @return OutputInterface
     */
    abstract public function getOutput();

    /**
     * Database export without using exec.
     *
     * @throws \Exception
     * @return $this
     */
    public function createBackup()
    {
        $stripTables = $this->getStrippedTables();

        try {
            $this->getOutput()->writeln(
                '<comment>
                No-data export for: <info>' . implode(' ', $stripTables) . '</info>
                </comment>'
            );

            $this->getOutput()->writeln(
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
}
