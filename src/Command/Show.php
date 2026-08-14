<?php

namespace Clue\GraphComposer\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Clue\GraphComposer\Graph\GraphComposer;

class Show extends Command
{
    use FilterTrait;

    protected function configure(): void
    {
        $this->setName('show')
            ->setDescription('Show dependency graph image for given project directory')
            ->addArgument('dir', InputArgument::OPTIONAL, 'Path to project directory to scan', '.')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Image format (svg, png, jpeg)', 'svg')

            ->addOption('filter', 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Package filter pattern')
            ->addOption('level', null, InputOption::VALUE_REQUIRED, 'Package filter level')

            ->addOption('dev', null, InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE, 'Whether require-dev dependencies should be shown');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $graph = new GraphComposer($input->getArgument('dir'), null);
        $graph->setFormat($input->getOption('format'));

        $graph->displayGraph(
            $this->createFilter($input, $output)
        );

        return Command::SUCCESS;
    }
}
