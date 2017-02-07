<?php
namespace Meanbee\Magedbm\Command;

use Aws\Common\Exception\InstanceProfileCredentialsException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends BaseCommand
{

    /**
     * Configure the command parameters.
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ls')
            ->setDescription('List available backups')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Project identifier'
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
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $s3 = $this->getS3Client($input->getOption('region'));
        $config = $this->getConfig($input);

        $name = $input->getArgument('name') ?: '';

        try {
            $results = $s3->getIterator(
                'ListObjects',
                array('Bucket' => $config['bucket'], 'Prefix' => $name)
            );

            $names = array();
            foreach ($results as $item) {
                $itemKeyChunks = explode('/', $item['Key']);

                // If name presented, show downloads for that name
                if ($name) {
                    // Get file size in MB
                    $fileSize = $item['Size'] / 1024 / 1024;

                    // If file size is less than 1MB display 1 decimal place
                    $fileSize = ($fileSize < 1) ? round($fileSize, 1) : round($fileSize);

                    $this->getOutput()->writeln(sprintf('%s %sMB', array_pop($itemKeyChunks), $fileSize));
                } else {
                    // Otherwise show uniqued list of available names
                    if (!in_array($itemKeyChunks[0], $names)) {
                        $names[] = $itemKeyChunks[0];
                        $this->getOutput()->writeln($itemKeyChunks[0]);
                    }
                }
            }

            if (!$results->count()) {
                // Credentials Exception would have been thrown by now, so now we can safely check for item count.
                $this->getOutput()->writeln(
                    sprintf('<error>No backups found for %s</error>', $input->getArgument('name'))
                );
            }
        } catch (InstanceProfileCredentialsException $e) {
            $this->getOutput()->writeln('<error>AWS credentials not found. Please run `configure` command.</error>');
        } catch (\Exception $e) {
            $this->getOutput()->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}
