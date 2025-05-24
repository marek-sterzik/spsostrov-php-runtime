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

use App\Utility\SurnameGuesser;
use App\Repository\UserRepository;

#[AsCommand(
    name: 'surnames:guess',
    description: 'Guess surnames of users',
)]
class SurnamesGuessCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private SurnameGuesser $surnameGuesser,
        private EntityManager $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            "all",
            "a",
            InputOption::VALUE_NONE,
            "Sync surname guess on all users and not only these without surname guess"
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('all')) {
            $users = $this->userRepository->findAll();
        } else {
            $users = $this->userRepository->findAllWithoutSurnameGuess();
        }

        $flush = false;
        foreach ($users as $user) {
            $surnameGuess = $this->surnameGuesser->guessSurname($user->getName());
            $user->setGuessedSurname($surnameGuess);
            $flush = true;
        }

        if ($flush) {
            $this->entityManager->flush();
        }

        return Command::SUCCESS;
    }
}
