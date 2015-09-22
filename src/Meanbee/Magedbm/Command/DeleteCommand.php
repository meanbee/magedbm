<?php
namespace Meanbee\Magedbm\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteCommand extends BaseCommand
{

    /**
     * Configure the command parameters.
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('rm')
            ->setDescription('Delete backups')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Project identifier'
            )
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'Filename (regex available)'
            )
            ->addOption(
                '--region',
                '-r',
                InputOption::VALUE_REQUIRED,
                'Optionally specify region, otherwise default configuration will be used.'
            )
            ->addOption(
                '--bucket',
                '-b',
                InputOption::VALUE_REQUIRED,
                'Optionally specify bucket, otherwise default configuration will be used. '
            );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $s3 = $this->getS3Client($input->getOption('region'));
        $config = $this->getConfig($input);

        try {
            $regex = sprintf('/^%s\/%s$/', $input->getArgument('name'), $input->getArgument('file'));
            $s3->deleteMatchingObjects($config['bucket'], $input->getArgument('name'), $regex);

            $this->getOutput()->writeln(sprintf('<info>%s deleted.</info>', $input->getArgument('file')));

        } catch (\Exception $e) {
            $this->getOutput()->writeln(sprintf('<error>Failed to delete backup. %s.</error>', $e->getMessage()));
        }
    }
}
