<?php

namespace App\Command;

use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:import-users',
    description: 'Import users from a CSV file',
)]
class ImportUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Importation des utilisateurs');

        // Simulate CSV data
        $users = [
            ['pierre.martin@campus-eni.fr', 'db218b3d'],
            ['lucie.bernard@campus-eni.fr', 'e62962b4'],
            ['thomas.petit@campus-eni.fr', 'ce317d25'],
            ['sophie.robert@campus-eni.fr', '48f36b39'],
            ['antoine.richard@campus-eni.fr', 'd79195be'],
            ['julie.durand@campus-eni.fr', '4513b101'],
        ];

        $importedUsers = [];
        $errors = [];

        foreach ($users as $index => $userData) {
            try {
                $user = new Participant();
                $user->setEmail($userData[0]);
                $user->setPassword($this->passwordHasher->hashPassword($user, $userData[1]));
                $user->setNom('Nom' . ($index + 1));
                $user->setPrenom('Prénom' . ($index + 1));
                $user->setTelephone('0123456789');
                $user->setActif(true);
                $user->setRoles(['ROLE_USER']);

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                // Send welcome email
                $this->sendWelcomeEmail($user);

                $importedUsers[] = [
                    'email' => $userData[0],
                    'password' => $userData[1]
                ];
            } catch (\Exception $e) {
                $errors[] = "Ligne " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        // Display results
        if (!empty($importedUsers)) {
            $io->success(sprintf('%d utilisateurs importés avec succès.', count($importedUsers)));
            $io->writeln('Utilisateurs importés :');
            foreach ($importedUsers as $user) {
                $io->writeln(sprintf('Email: %s, Mot de passe: %s', $user['email'], $user['password']));
            }
        }

        if (!empty($errors)) {
            $io->warning('Des erreurs sont survenues :');
            foreach ($errors as $error) {
                $io->writeln($error);
            }
        }

        return Command::SUCCESS;
    }

    private function sendWelcomeEmail(Participant $user): void
    {
        try {
            $email = (new Email())
                ->from('noreply@sortir.com')
                ->to('marichadu5@gmail.com') // For testing, send to your email
                ->subject('Bienvenue sur Sortir.com')
                ->html($this->renderWelcomeEmailTemplate($user));

            $this->mailer->send($email);
        } catch (\Exception $e) {
            throw new \Exception('L\'utilisateur a été créé mais l\'email n\'a pas pu être envoyé');
        }
    }

    private function renderWelcomeEmailTemplate(Participant $user): string
    {
        return sprintf('
            <!DOCTYPE html>
            <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Bienvenue sur Sortir.com</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .footer { margin-top: 30px; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h2>Bienvenue sur Sortir.com</h2>
                        <p>Bonjour %s,</p>
                        <p>Votre compte a été créé avec succès sur Sortir.com.</p>
                        <p>Vous pouvez maintenant vous connecter avec votre email : %s</p>
                        <div class="footer">
                            <p>Cordialement,<br>L\'équipe Sortir.com</p>
                        </div>
                    </div>
                </body>
            </html>
        ', $user->getPrenom(), $user->getEmail());
    }
} 