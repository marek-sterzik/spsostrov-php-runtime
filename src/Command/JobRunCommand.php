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
    name: 'job:run',
    description: 'Run a job',
)]
class JobRunCommand extends Command
{
    public function __construct(private JobStarter $jobStarter)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('uuid', InputArgument::IS_ARRAY, 'UUID of the job being run')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $uuids = $input->getArgument('uuid');

        if (empty($uuids)) {
            $this->jobStarter->runAllJobs();
        } else {
            while (!empty($uuids)) {
                $uuid = array_shift($uuids);
                if (!empty($uuids)) {
                    if (pcntl_fork() == 0) {
                        $this->jobStarter->runJob($uuid);
                        return Command::SUCCESS;
                    }
                } else {
                    $this->jobStarter->runJob($uuid);
                }
            }
        }

        return Command::SUCCESS;
    }
}
