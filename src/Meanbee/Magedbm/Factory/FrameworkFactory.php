<?php

namespace Meanbee\Magedbm\Factory;

use Meanbee\LibMageConf\ConfigReader;
use N98\Magento\Application as MageRunApplication;
use Composer\Autoload\ClassLoader;
use Meanbee\Magedbm\Adapter\MagentoAdapter;
use Meanbee\Magedbm\Api\FrameworkInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FrameworkFactory
{

    /**
     * Create an instance of FrameworkInterface
     *
     * @param OutputInterface  $output
     * @param string           $filePath
     * @param ClassLoader|null $autoloader
     * @param string           $stripMode
     *
     * @return MagentoAdapter
     */
    static public function create(
        OutputInterface $output,
        $filePath,
        ClassLoader $autoloader = null,
        $stripMode = '@development'
    )
    {
        $mageRun = new MageRunApplication($autoloader);
        $mageRun->init();

        $configPath = $mageRun->getMagentoRootFolder() . '/app/etc/local.xml';

        $configuration = ConfigurationFactory::create($configPath, $filePath, $stripMode);

        return new MagentoAdapter($mageRun, $output, $configuration);
    }
}
