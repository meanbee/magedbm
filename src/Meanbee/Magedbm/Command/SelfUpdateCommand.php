<?php
namespace Meanbee\Magedbm\Command;

use Herrera\Json\Exception\FileException;
use Herrera\Phar\Update\Manager;
use Herrera\Phar\Update\Manifest;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdateCommand extends BaseCommand
{

    const MANIFEST_FILE = 'https://s3-eu-west-1.amazonaws.com/magedbm-releases/manifest.json';

    /**
     * Configure the command parameters.
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('phar:update')
            ->setAliases(array('selfupdate', 'self-update'))
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                'Updates to version-number (i.e. 1.3.2). When omitted will update to the latest version'
            )
            ->addOption('major', 'm', InputOption::VALUE_NONE, 'Allow major upgrade')
            ->setDescription('Update the version of magedbm.');
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->createManager($output);
        $version = $input->getArgument('version')
            ? $input->getArgument('version')
            : $this->getApplication()->getVersion();
        $allowMajor = $input->getOption('major');

        $this->updateCurrentVersion($manager, $version, $allowMajor, $output);
    }

    /**
     * Returns manager instance or exit with status code 1 on failure.
     *
     * @param OutputInterface $output
     *
     * @return \Herrera\Phar\Update\Manager
     */
    private function createManager(OutputInterface $output)
    {
        try {
            return new Manager(Manifest::loadFile(self::MANIFEST_FILE));
        } catch (FileException $e) {
            $output->writeln('<error>Unable to search for updates.</error>');
            exit(1);
        }
    }

    /**
     * Updates current version.
     *
     * @param Manager $manager
     * @param string $version
     * @param bool|null $allowMajor
     * @param OutputInterface $output
     *
     * @return void
     */
    private function updateCurrentVersion(
        Manager $manager,
        $version,
        $allowMajor,
        OutputInterface $output
    ) {
    
        if ($manager->update($version, $allowMajor)) {
            $output->writeln('<info>Updated to latest version.</info>');
        } else {
            $output->writeln('<comment>Already up-to-date.</comment>');
        }
    }
}
