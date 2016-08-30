<?php

namespace Meanbee\Magedbm\Factory;

use Meanbee\LibMageConf\ConfigReader;
use Meanbee\Magedbm\Adapter\Magento2Adapter;
use N98\Magento\Application as MageRunApplication;
use Composer\Autoload\ClassLoader;
use Meanbee\Magedbm\Adapter\MagentoAdapter;
use Meanbee\Magedbm\Api\FrameworkInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FrameworkFactory
{

    /**
     * Create an instance of FrameworkInterface
     *
     * @param OutputInterface  $output
     * @param InputInterface   $input
     * @param string           $filePath
     * @param ClassLoader|null $autoloader
     * @param string           $stripMode
     *
     * @return MagentoAdapter
     */
    static public function create(
        OutputInterface $output,
        InputInterface $input,
        $filePath,
        ClassLoader $autoloader = null,
        $stripMode = '@development'
    )
    {
        if ($input->getOption('magento2')) {
           return static::createMagento2($output, $input, $filePath, $stripMode);
        }

        return static::createMagento($output, $filePath, $autoloader, $stripMode);
    }

    /**
     * Create an instance of FrameworkInterface for Magento 1.x
     *
     * @param OutputInterface $output
     * @param string          $filePath
     * @param ClassLoader     $autoloader
     * @param string          $stripMode
     *
     * @return MagentoAdapter
     */
    public static function createMagento(OutputInterface $output, $filePath, ClassLoader $autoloader, $stripMode)
    {
        $mageRun = new MageRunApplication($autoloader);
        $mageRun->init();

        $configPath = $mageRun->getMagentoRootFolder() . '/app/etc/local.xml';

        $configuration = ConfigurationFactory::create(null, $configPath, $filePath, $stripMode);

        return new MagentoAdapter($mageRun, $output, $configuration);
    }

    /**
     * Create an instance for Magento 2.x
     *
     * @param OutputInterface $output
     * @param InputInterface  $input
     * @param string          $filePath
     * @param string          $stripMode
     *
     * @return Magento2Adapter
     */
    public static function createMagento2(OutputInterface $output, InputInterface $input, $filePath, $stripMode)
    {
        $configPath = $input->getOption('magento-root') . '/app/etc/env.php';

        $configuration = ConfigurationFactory::create($input, $configPath, $filePath, $stripMode);

        return new Magento2Adapter($configuration, $output, $input->getOption('magento-root'));
    }
}
