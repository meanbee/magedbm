<?php

namespace Meanbee\Magedbm\Adapter;

use N98\Magento\Application as MageRunApplication;
use Meanbee\Magedbm\Api\FrameworkInterface;
use Symfony\Component\Console\Input\ArrayInput;
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
     * MagentoAdapter constructor.
     * @todo remove output dependency.
     * @todo remove magerun dependency.
     *
     * @param MageRunApplication $mageRun
     * @param OutputInterface    $output
     */
    public function __construct(MageRunApplication $mageRun, OutputInterface $output)
    {
        $this->mageRun = $mageRun;
        $this->output = $output;
    }

    /**
     * Database export without using exec.
     *
     * @throws \Exception
     * @return $this
     */
    public function createBackup()
    {
        // TODO: Implement createBackup() method.
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
}
