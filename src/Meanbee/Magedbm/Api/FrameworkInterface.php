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
}
