<?php

namespace Meanbee\Magedbm\Factory;

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
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param ClassLoader     $autoloader
     *
     * @return FrameworkInterface
     */
    static public function create(
        InputInterface $input,
        OutputInterface $output,
        ClassLoader $autoloader = null
    )
    {
        $mageRun = new MageRunApplication($autoloader);
        $mageRun->init();

        return new MagentoAdapter($mageRun, $output);
    }
}
