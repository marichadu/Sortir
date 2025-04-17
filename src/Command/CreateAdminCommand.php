<?php

namespace App\Command;

use App\Entity\Campus;
use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a test admin user',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get the default campus
        $campus = $this->entityManager->getRepository(Campus::class)->findOneBy([]);
        
        if (!$campus) {
            $io->error('No campus found. Please run app:setup-initial-data first.');
            return Command::FAILURE;
        }

        // Create new admin user
        $admin = new Participant();
        $admin->setEmail('admin@sortir.com');
        $admin->setNom('Admin');
        $admin->setPrenom('Super');
        $admin->setTelephone('0123456789');
        $admin->setCampus($campus);
        $admin->setPseudo('admin');
        $admin->setActif(true);
        $admin->setAdministrateur(true);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setCampus($campus);

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedPassword);

        try {
            $this->entityManager->persist($admin);
            $this->entityManager->flush();

            $io->success('Admin user created successfully!');
            $io->table(
                ['Email', 'Password', 'Campus'],
                [['admin@sortir.com', 'admin123', $campus->getNom()]]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error creating admin user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 