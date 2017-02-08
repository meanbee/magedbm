<?php

namespace Meanbee\Magedbm\Api;

interface FrameworkInterface
{

    /**
     * Database export without using exec.
     *
     * @throws \Exception
     * @return $this
     */
    public function createBackup();

    /**
     * Import database into framework.
     *
     * @param string $filePath
     *
     * @throws \Exception
     * @return $this
     */
    public function importDatabase($filePath);
}
