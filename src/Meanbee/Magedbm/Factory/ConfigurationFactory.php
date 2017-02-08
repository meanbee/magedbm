<?php

namespace Meanbee\Magedbm\Factory;

use Meanbee\LibMageConf\ConfigReader;
use Meanbee\Magedbm\Configuration\MagentoConfiguration;
use Meanbee\Magedbm\Configuration\Magento2Configuration;
use Symfony\Component\Console\Input\InputInterface;

class ConfigurationFactory
{

    /**
     * Create a `FrameworkConfigurationInterface`.
     *
     * @todo once issue 1 for libmageconf is resolved we can simplify this factory.
     *
     * @param InputInterface $input
     * @param string         $configPath
     * @param string         $filePath
     * @param string         $strip
     *
     * @return MagentoConfiguration
     * @throws \Exception
     */
    static public function create(
        InputInterface $input = null,
        $configPath,
        $filePath,
        $strip = '@development'
    )
    {
        if ($input) {
            if ($input->getOption('magento2')) {
                if (!file_exists($configPath)) {
                    throw new \Exception('Cannot find magento env.php file.');
                }

                return new Magento2Configuration($filePath, $configPath, $strip);
            }
        }

        return new MagentoConfiguration(new ConfigReader($configPath), $filePath, $strip);
    }
}
