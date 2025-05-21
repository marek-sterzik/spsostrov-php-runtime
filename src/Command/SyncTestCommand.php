<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use App\Sync\Sync;

#[AsCommand(
    name: 'sync:test',
    description: 'Make a synchronization test',
)]
class SyncTestCommand extends Command
{
    public function __construct(private Sync $sync)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->sync->isEnabled()) {
            if ($this->sync->testConnection()) {
                $io->success("Synchronization is working well.");
                return Command::SUCCESS;
            } else {
                $io->error("Synchronization is not working properly.");
                return Command::FAILURE;
            }
        } else {
            $io->warning("Synchronization is switched off.");
            return Command::SUCCESS;
        }
    }
}
