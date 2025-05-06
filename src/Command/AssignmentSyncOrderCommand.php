<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface as EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\NativeQuery;
use App\Assignment\AssignmentState;

#[AsCommand(
    name: 'assignment:sync-order',
    description: 'Sync order in assignments according to the current order model',
)]
class AssignmentSyncOrderCommand extends Command
{
    public function __construct(private EntityManager $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $query = $this->createMysqlSyncQuery();
        $query->execute();

        $io->success('assignment order successfully synced');

        return Command::SUCCESS;
    }

    private function createMysqlSyncQuery(): NativeQuery
    {
        $params = [];
        $command = "UPDATE assignment SET main_order = CASE";
        foreach (AssignmentState::cases() as $state) {
            $command .= " WHEN state = ? THEN ?";
            $params[] = $state->value;
            $params[] = $state->getParam("order");
        }
        $command .= " ELSE 0 END";
        $query = $this->entityManager->createNativeQuery($command, new ResultSetMapping());
        foreach ($params as $index => $param) {
            $query->setParameter($index + 1, $param);
        }
        return $query;
    }
}
