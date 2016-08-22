<?php

namespace Meanbee\Magedbm\Factory;

use Aws\S3\S3Client;
use Meanbee\Magedbm\Adapter\s3Adapter;
use Meanbee\Magedbm\Configuration\s3Configuration;
use Piwik\Ini\IniReader;

class s3Factory
{

    const AWS_CONFIG_PATH = '/.aws/config';
    const KEY_BUCKET_NAME = 'bucket_name';

    protected static $config = array(
        'bucket_name' => null,
        'name' => null,
        'file' => null,
        'tmp_path' => null,
        'region' => null
    );

    /**
     * @param array $config
     *
     * @return s3Adapter
     */
    public static function create($config = array())
    {
        $config = array_merge(static::$config, $config);

        /**
         * Extract the variables passed in the array. We ensure they exist
         * by setting them with an array_merge.
         *
         * @var string|null $bucket_name
         * @var string|null $name
         * @var string|null $file
         * @var string|null $tmp_path
         * @var mixed|null  $region
         */
        extract($config);

        $config = new s3Configuration($bucket_name, $name, $file, $tmp_path);

        $s3 = static::getS3Client($region);

        return new s3Adapter($s3, $config);
    }

    /**
     * @param null $region
     *
     * @return S3Client
     * @throws \Exception
     */
    protected static function getS3Client($region = null)
    {
        if (!$region) {
            $iniReader = new IniReader();
            $config = $iniReader->readFile(static::getAwsConfigPath());
            $region = $config['default']['region'];
        }

        $s3Client = S3Client::factory(array(
            'region' => $region
        ));

        return $s3Client;
    }

    /**
     * @return string
     */
    protected static function getAwsConfigPath()
    {
        return getenv('HOME') . self::AWS_CONFIG_PATH;
    }
}
