<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use App\Job\JobStarter;

#[AsCommand(
    name: 'jobs:run',
    description: 'Run all jobs',
)]
class JobsRunCommand extends Command
{
    public function __construct(private JobStarter $jobStarter)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->jobStarter->runAllJobs();

        return Command::SUCCESS;
    }
}
