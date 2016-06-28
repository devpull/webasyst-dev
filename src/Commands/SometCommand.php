<?php namespace Wbs\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SometCommand extends Command
{

    public function configure()
    {
        $this->setName('SayHi')
            ->setDescription('Some description here')
            ->addArgument('name', InputArgument::REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $message = "Some say hi and hello world. <info>Hi {$input->getArgument('name')}</info>";

        $output->writeln($message);
    }

}
