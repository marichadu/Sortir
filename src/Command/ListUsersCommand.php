<?php

namespace App\Command;

use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:list-users',
    description: 'Lists all users in the database',
)]
class ListUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->entityManager->getRepository(Participant::class)->findAll();

        if (empty($users)) {
            $output->writeln('No users found in the database.');
            return Command::SUCCESS;
        }

        $output->writeln('Users in database:');
        foreach ($users as $user) {
            $output->writeln(sprintf(
                '- %s %s (%s) - Roles: %s - Active: %s',
                $user->getPrenom(),
                $user->getNom(),
                $user->getEmail(),
                implode(', ', $user->getRoles()),
                $user->isActif() ? 'Yes' : 'No'
            ));
        }

        return Command::SUCCESS;
    }
} 