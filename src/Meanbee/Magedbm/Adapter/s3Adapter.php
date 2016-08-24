<?php

namespace Meanbee\Magedbm\Adapter;

use Aws\S3\S3Client;
use Meanbee\Magedbm\Api\StorageInterface;
use Meanbee\Magedbm\Configuration\s3Configuration;

class s3Adapter implements StorageInterface
{

    /**
     * @var S3Client
     */
    protected $client;

    /**
     * @var s3Configuration
     */
    protected $configuration;

    /**
     * s3Adapter constructor.
     *
     * @param S3Client        $client
     * @param s3Configuration $configuration
     */
    public function __construct(S3Client $client, s3Configuration $configuration)
    {
        $this->client = $client;
        $this->configuration = $configuration;
    }

    /**
     * Delete the current backup.
     *
     * @throws \Exception
     * @return $this
     */
    public function delete()
    {
        $this->client->delete($this->getConfig()->getBucketName(), $this->getConfig()->getName());

        return $this;
    }

    /**
     * Download the current backup.
     *
     * @throws \Exception
     * @return $this
     */
    public function get()
    {
        $this->client->getObject(array(
            'Bucket' => $this->getConfig()->getBucketName(),
            'Key' => $this->getConfig()->getName() . '/' . $this->getConfig()->getFile(),
            'SaveAs' => $this->getConfig()->getFilePath()
        ));
    }

    /**
     * Upload the current backup.
     *
     * @param string $filePath
     *
     * @return $this
     */
    public function put($filePath)
    {
        $this->client->putObject(array(
            'Bucket' => $this->getConfig()->getBucketName(),
            'Key' => $this->getConfig()->getName() . '/' . $this->getConfig()->getFileName(),
            'SourceFile' => $this->getConfig()->getFilePath(),
        ));

        return $this;
    }

    /**
     * List available backups.
     *
     * @throws \Exception
     * @return \Iterator
     */
    public function getAll()
    {
        return $this->client->getIterator(
            'ListObjects',
            array('Bucket' => $this->getConfig()->getBucketName(), 'Prefix' => $this->getConfig()->getName())
        );
    }

    /**
     * Delete s3 backups which match regex.
     *
     * @param string $regex
     *
     * @return $this
     */
    public function deleteMatchingObjects($regex)
    {
        return $this->client->deleteMatchingObjects($this->getConfig()->getBucketName(), $this->getConfig()->getName(), $regex);
    }

    /**
     * Get the config.
     *
     * @return s3Configuration
     */
    public function getConfig()
    {
        return $this->configuration;
    }
}
