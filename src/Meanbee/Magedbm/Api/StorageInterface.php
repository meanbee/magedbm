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
     * Delete s3 backups which match regex.
     *
     * @param string $regex
     *
     * @param string $prefix
     *
     * @return $this
     */
    public function deleteMatchingObjects($regex = '', $prefix = '');

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
     * @return mixed
     */
    public function put();

    /**
     * Get available backups.
     *
     * @throws \Exception
     * @return \Iterator
     */
    public function getAll();
}
