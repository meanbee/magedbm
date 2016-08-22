<?php

namespace Meanbee\Magedbm\Api;

interface StorageInterface
{

    /**
     * Delete the current backup.
     *
     * @throws \Exception
     * @return $this
     */
    public function delete();

    /**
     * Download the current backup.
     *
     * @throws \Exception
     * @return $this
     */
    public function get();

    /**
     * Upload the current backup.
     *
     * @param string $filePath
     *
     * @return $this
     */
    public function put($filePath);

    /**
     * Get available backups.
     *
     * @throws \Exception
     * @return \Iterator
     */
    public function getAll();
}
