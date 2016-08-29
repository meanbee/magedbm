<?php

namespace Meanbee\Magedbm\Factory;

use Meanbee\LibMageConf\ConfigReader;
use Meanbee\Magedbm\Configuration\MagentoConfiguration;
use Symfony\Component\Console\Input\InputInterface;

class ConfigurationFactory
{

    /**
     * Create a `FrameworkConfigurationInterface`.
     *
     * @param string $configPath
     * @param string $filePath
     * @param bool   $strip
     *
     * @return MagentoConfiguration
     */
    static public function create(
        $configPath,
        $filePath,
        $strip = true
    )
    {
        return new MagentoConfiguration(new ConfigReader($configPath), $filePath, $strip);
    }
}
