<?php
namespace Meanbee\Magedbm;

use Meanbee\Magedbm\Command\ConfigureCommand;
use Meanbee\Magedbm\Command\DeleteCommand;
use Meanbee\Magedbm\Command\GetCommand;
use Meanbee\Magedbm\Command\ListCommand;
use Meanbee\Magedbm\Command\PutCommand;

class Application extends \Symfony\Component\Console\Application
{

    const APP_NAME = 'Magedbm';
    const APP_VERSION = '1.0.0';

    protected $autoloader;

    public function __construct($autoloader = null)
    {
        parent::__construct(self::APP_NAME, self::APP_VERSION);

        if ($autoloader !== null) {
            $this->setAutoloader($autoloader);
        }

        $this->add(new ConfigureCommand());
        $this->add(new DeleteCommand());
        $this->add(new GetCommand());
        $this->add(new ListCommand());
        $this->add(new PutCommand());
    }

    public function getAutoloader()
    {
        return $this->autoloader;
    }

    public function setAutoloader($autoloader)
    {
        $this->autoloader = $autoloader;

        return $this;
    }
}