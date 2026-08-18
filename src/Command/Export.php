<?php

namespace Clue\GraphComposer\Command;

use Clue\GraphComposer\Graph\GraphComposer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Export extends Command
{
    use FilterTrait;

    protected function configure(): void
    {
        $this->setName('export')
            ->setDescription('Export dependency graph image for given project directory')
            ->addArgument('dir', InputArgument::OPTIONAL, 'Path to project directory to scan', '.')
            ->addArgument('output', InputArgument::OPTIONAL, 'Path to output image file')

            // add output format option. default value MUST NOT be given, because default is to overwrite with output extension
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Image format (svg, png, jpeg)')

            ->addOption('filter', 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Package filter pattern')
            ->addOption('strict-filter', null, InputOption::VALUE_NONE, 'Whether to filter dependency edges too')
            ->addOption('level', null, InputOption::VALUE_REQUIRED, 'Package filter level')

            ->addOption('dev', null, InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE, 'Whether require-dev dependencies should be shown')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $graph = new GraphComposer($input->getArgument('dir'), null);

        $target = $input->getArgument('output');
        if ($target !== null) {
            if (is_dir($target)) {
                $target = rtrim($target, '/') . '/graph-composer.svg';
            }

            $filename = basename($target);
            $pos = strrpos($filename, '.');
            if ($pos !== false && isset($filename[$pos + 1])) {
                // extension found and not empty
                $graph->setFormat(substr($filename, $pos + 1));
            }
        }

        $format = $input->getOption('format');
        if ($format !== null) {
            $graph->setFormat($format);
        }

        $path = $graph->getImagePath(
            $this->createFilter($input, $output)
        );

        if ($target !== null) {
            rename($path, $target);
        } else {
            readfile($path);
        }

        return Command::SUCCESS;
    }
}
