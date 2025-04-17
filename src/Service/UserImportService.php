<?php

namespace App\Service;

use App\Entity\Participant;
use App\Repository\CampusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\EmailService;

class UserImportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private CampusRepository $campusRepository,
        private EmailService $emailService
    ) {}

    public function importFromCSV(string $csvContent): array
    {
        $lines = array_map('str_getcsv', explode("\n", $csvContent));
        $results = [
            'success' => [],
            'errors' => [],
            'warnings' => []
        ];

        // Skip the header row
        array_shift($lines);

        foreach ($lines as $i => $line) {
            if (empty($line[0])) continue; // Skip empty lines

            try {
                // Validate CSV format
                if (count($line) !== 7) {
                    throw new \Exception("La ligne doit contenir 7 colonnes");
                }

                [$email, $nom, $prenom, $telephone, $campusNom, $pseudo, $role] = $line;

                // Validate email domain
                if (!str_ends_with($email, '@campus-eni.fr')) {
                    throw new \Exception("L'email doit être un email @campus-eni.fr");
                }

                // Validate role
                if (!in_array($role, ['ROLE_USER', 'ROLE_ADMIN'])) {
                    throw new \Exception("Le rôle doit être soit ROLE_USER soit ROLE_ADMIN");
                }

                // Check if user already exists
                $existingUser = $this->entityManager->getRepository(Participant::class)
                    ->findOneBy(['email' => $email]);
                
                if ($existingUser) {
                    throw new \Exception("Un utilisateur avec cet email existe déjà");
                }

                // Find campus
                $campus = $this->campusRepository->findOneBy(['nom' => $campusNom]);
                if (!$campus) {
                    throw new \Exception("Campus '$campusNom' non trouvé");
                }

                // Create new user
                $participant = new Participant();
                $participant->setEmail($email)
                    ->setNom($nom)
                    ->setPrenom($prenom)
                    ->setTelephone($telephone)
                    ->setCampus($campus)
                    ->setPseudo($pseudo)
                    ->setActif(true)
                    ->setRoles([$role]);

                // Generate random password
                $password = bin2hex(random_bytes(4)); // 8 characters
                $participant->setPassword(
                    $this->passwordHasher->hashPassword($participant, $password)
                );

                $this->entityManager->persist($participant);
                
                // Envoyer l'email de bienvenue
                try {
                    $this->emailService->sendWelcomeEmail($participant, $password);
                } catch (\Exception $e) {
                    // On continue même si l'email n'a pas pu être envoyé
                    $results['warnings'][] = "Ligne " . ($i + 1) . ": L'utilisateur a été créé mais l'email n'a pas pu être envoyé";
                }
                
                $results['success'][] = [
                    'email' => $email,
                    'password' => $password,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'campus' => $campusNom
                ];
            } catch (\Exception $e) {
                $results['errors'][] = "Ligne " . ($i + 1) . ": " . $e->getMessage();
            }
        }

        if (!empty($results['success'])) {
            $this->entityManager->flush();
        }

        return $results;
    }
} 