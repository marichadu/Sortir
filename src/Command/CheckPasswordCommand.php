<?php

namespace App\Command;

use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:check-password',
    description: 'Verifies password hashing for test user',
)]
class CheckPasswordCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->entityManager->getRepository(Participant::class)->findOneBy(['email' => 'test@eni.fr']);
        
        if (!$user) {
            $output->writeln('Test user not found!');
            return Command::FAILURE;
        }

        $output->writeln('Checking password for user: ' . $user->getEmail());
        $output->writeln('Current password hash: ' . $user->getPassword());
        
        $isValid = $this->passwordHasher->isPasswordValid($user, 'password123');
        $output->writeln('Password "password123" is ' . ($isValid ? 'valid' : 'invalid'));
        
        if (!$isValid) {
            $output->writeln('Trying to update password...');
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $this->entityManager->flush();
            $output->writeln('Password updated. New hash: ' . $user->getPassword());
        }

        return Command::SUCCESS;
    }
} 