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
    name: 'app:verify-password-hash',
    description: 'Verifies password hash in database',
)]
class VerifyPasswordHashCommand extends Command
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

        $output->writeln('Current password hash in database: ' . $user->getPassword());
        
        // Create a new hash for comparison
        $newHash = $this->passwordHasher->hashPassword($user, 'password123');
        $output->writeln('New hash for "password123": ' . $newHash);
        
        // Test if current password is valid
        $isValid = $this->passwordHasher->isPasswordValid($user, 'password123');
        $output->writeln('Is current password valid? ' . ($isValid ? 'Yes' : 'No'));

        if (!$isValid) {
            $output->writeln('Updating password hash...');
            $user->setPassword($newHash);
            $this->entityManager->flush();
            $output->writeln('Password hash updated in database.');
        }

        return Command::SUCCESS;
    }
} 