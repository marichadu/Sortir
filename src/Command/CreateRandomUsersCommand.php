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
    name: 'app:create-random-users',
    description: 'Creates 3 random users for testing',
)]
class CreateRandomUsersCommand extends Command
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

        // Get or create default campus
        $campus = $this->entityManager->getRepository(Campus::class)->findOneBy(['nom' => 'ENI Nantes']);
        if (!$campus) {
            $campus = new Campus();
            $campus->setNom('ENI Nantes');
            $this->entityManager->persist($campus);
            $this->entityManager->flush();
        }

        // Random user data
        $users = [
            [
                'email' => 'alice@campus-eni.fr',
                'nom' => 'Dubois',
                'prenom' => 'Alice',
                'telephone' => '0612345678',
                'roles' => ['ROLE_USER'],
            ],
            [
                'email' => 'bob@campus-eni.fr',
                'nom' => 'Martin',
                'prenom' => 'Bob',
                'telephone' => '0623456789',
                'roles' => ['ROLE_USER'],
            ],
            [
                'email' => 'charlie@campus-eni.fr',
                'nom' => 'Bernard',
                'prenom' => 'Charlie',
                'telephone' => '0634567890',
                'roles' => ['ROLE_USER'],
            ],
        ];

        $createdUsers = [];

        foreach ($users as $userData) {
            // Check if user already exists
            $existingUser = $this->entityManager->getRepository(Participant::class)
                ->findOneBy(['email' => $userData['email']]);

            if ($existingUser) {
                $io->note(sprintf('User %s already exists, skipping...', $userData['email']));
                continue;
            }

            $user = new Participant();
            $user->setEmail($userData['email']);
            $user->setNom($userData['nom']);
            $user->setPrenom($userData['prenom']);
            $user->setTelephone($userData['telephone']);
            $user->setRoles($userData['roles']);
            $user->setCampus($campus);
            $user->setActif(true);

            // Set a simple password: 'password123'
            $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $createdUsers[] = [
                'email' => $user->getEmail(),
                'password' => 'password123',
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
            ];
        }

        $this->entityManager->flush();

        $io->success('Random users created successfully!');
        
        // Display created users in a table
        $io->table(
            ['Email', 'Password', 'Nom', 'Prénom'],
            array_map(function($user) {
                return [
                    $user['email'],
                    $user['password'],
                    $user['nom'],
                    $user['prenom'],
                ];
            }, $createdUsers)
        );

        return Command::SUCCESS;
    }
} 