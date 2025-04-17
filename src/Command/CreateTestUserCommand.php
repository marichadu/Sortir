<?php

namespace App\Command;

use App\Entity\Campus;
use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-user',
    description: 'Creates a test user for development',
)]
class CreateTestUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Remove existing test user if it exists
        $existingUser = $this->entityManager->getRepository(Participant::class)->findOneBy(['email' => 'test@eni.fr']);
        if ($existingUser) {
            $this->entityManager->remove($existingUser);
            $this->entityManager->flush();
            $output->writeln('Removed existing test user.');
        }

        // Create a test campus if it doesn't exist
        $campus = $this->entityManager->getRepository(Campus::class)->findOneBy(['nom' => 'ENI Nantes']);
        if (!$campus) {
            $campus = new Campus();
            $campus->setNom('ENI Nantes');
            $this->entityManager->persist($campus);
        }

        // Create test user
        $user = new Participant();
        $user->setEmail('test@eni.fr');
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setTelephone('0123456789');
        $user->setCampus($campus);
        $user->setRoles(['ROLE_USER']);
        
        // Using a simpler password
        $plainPassword = '12345';
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        $user->setActif(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('Test user created successfully!');
        $output->writeln('Email: test@eni.fr');
        $output->writeln('Password: ' . $plainPassword);

        return Command::SUCCESS;
    }
} 