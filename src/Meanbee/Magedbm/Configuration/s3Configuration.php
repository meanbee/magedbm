<?php

namespace Meanbee\Magedbm\Configuration;

class s3Configuration
{

    /**
     * @var string
     */
    protected $bucketName;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $regex;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var string
     */
    protected $tmpPath;

    /**
     * @var string|null
     */
    protected $filename;

    /**
     * s3Configuration constructor.
     *
     * @param null|string $bucketName
     * @param null|string $name
     * @param null|string $regex
     * @param null|string $file
     * @param null|string $tmpPath
     */
    public function __construct($bucketName = null, $name = null, $regex = null, $file = null, $tmpPath = null)
    {
        $this->bucketName = $bucketName;
        $this->name = $name;
        $this->regex = $regex;
        $this->file = $file;
        $this->tmpPath = $tmpPath;
    }

    /**
     * @return null|string
     */
    public function getBucketName()
    {
        return $this->bucketName;
    }

    /**
     * @param string $bucketName
     *
     * @return $this
     */
    public function setBucketName($bucketName)
    {
        $this->bucketName = $bucketName;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getRegex()
    {
        return $this->regex;
    }

    /**
     * @param string $regex
     *
     * @return $this
     */
    public function setRegex($regex)
    {
        $this->regex = $regex;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getTmpPath()
    {
        return $this->tmpPath;
    }

    /**
     * @param string $tmpPath
     *
     * @return $this
     */
    public function setTmpPath($tmpPath)
    {
        $this->tmpPath = $tmpPath;

        return $this;
    }

    /**
     * @param string $file
     *
     * @return $this
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Get file path in tmp directory
     *
     * @return string
     */
    public function getFilePath()
    {
        // Create tmp directory if doesn't exist
        if (!file_exists($this->getTmpPath()) && !is_dir($this->getTmpPath())) {
            mkdir($this->getTmpPath(), 0700);
        }

        return sprintf('%s/%s', $this->getTmpPath(), $this->getFile());
    }

    /**
     * Get the file name.
     *
     * @return string
     */
    public function getFileName()
    {
        if (!$this->filename) {
            $timestamp = date('Y-m-d_His');

            $this->filename = sprintf('%s-%s.sql.gz', $this->getName(), $timestamp);
        }

        return $this->filename;
    }

}
