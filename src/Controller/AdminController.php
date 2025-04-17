<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use App\Repository\CampusRepository;
use App\Repository\VilleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\UserImportService;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(
        ParticipantRepository $participantRepository,
        SortieRepository $sortieRepository,
        CampusRepository $campusRepository,
        VilleRepository $villeRepository
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/dashboard.html.twig', [
            'total_users' => $participantRepository->count([]),
            'active_users' => $participantRepository->count(['actif' => true]),
            'total_sorties' => $sortieRepository->count([]),
            'total_campus' => $campusRepository->count([]),
            'total_villes' => $villeRepository->count([]),
        ]);
    }

    #[Route('/users', name: 'app_admin_users')]
    public function users(ParticipantRepository $participantRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/users.html.twig', [
            'users' => $participantRepository->findAll(),
        ]);
    }

    #[Route('/users/import', name: 'app_admin_import_users', methods: ['POST'])]
    public function importUsers(Request $request, UserImportService $userImportService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('import_users', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_users');
        }

        $csvFile = $request->files->get('csvFile');
        if (!$csvFile) {
            $this->addFlash('error', 'Aucun fichier n\'a été uploadé.');
            return $this->redirectToRoute('app_admin_users');
        }

        try {
            $results = $userImportService->importFromCSV(file_get_contents($csvFile->getPathname()));

            if (!empty($results['success'])) {
                $this->addFlash('success', count($results['success']) . ' utilisateurs importés avec succès.');
                
                // Create a summary of imported users with their passwords
                $summary = "Utilisateurs importés :\n";
                foreach ($results['success'] as $user) {
                    $summary .= sprintf("Email: %s, Mot de passe: %s\n", $user['email'], $user['password']);
                }
                $this->addFlash('info', $summary);
            }

            if (!empty($results['warnings'])) {
                foreach ($results['warnings'] as $warning) {
                    $this->addFlash('warning', $warning);
                }
            }

            if (!empty($results['errors'])) {
                foreach ($results['errors'] as $error) {
                    $this->addFlash('error', $error);
                }
            }

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'import : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/activate', name: 'app_admin_user_activate', methods: ['POST'])]
    public function activateUser(
        Participant $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('activate'.$user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_users');
        }

        $user->setActif(true);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur activé avec succès.');
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/deactivate', name: 'app_admin_user_deactivate', methods: ['POST'])]
    public function deactivateUser(
        Participant $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('deactivate'.$user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_users');
        }

        // Empêcher la désactivation de son propre compte
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas désactiver votre propre compte.');
            return $this->redirectToRoute('app_admin_users');
        }

        $user->setActif(false);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur désactivé avec succès.');
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(
        Participant $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_users');
        }

        // Empêcher la suppression de son propre compte
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_admin_users');
        }

        // Vérifier si l'utilisateur est organisateur de sorties
        $sorties = $user->getSortiesOrganisees();
        if (!$sorties->isEmpty()) {
            $this->addFlash('error', 'Cet utilisateur ne peut pas être supprimé car il est organisateur de sorties.');
            return $this->redirectToRoute('app_admin_users');
        }

        // Supprimer l'utilisateur de toutes les sorties où il est inscrit
        foreach ($user->getSortiesInscrit() as $sortie) {
            $sortie->removeParticipant($user);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/bulk-delete', name: 'app_admin_bulk_delete_users', methods: ['POST'])]
    public function bulkDeleteUsers(
        Request $request, 
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('bulk_delete_users', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_users');
        }

        $userIds = $request->request->all('users');
        if (empty($userIds)) {
            $this->addFlash('error', 'Aucun utilisateur sélectionné.');
            return $this->redirectToRoute('app_admin_users');
        }

        $users = $entityManager->getRepository(Participant::class)->findBy(['id' => $userIds]);
        $currentUser = $this->getUser();
        $deletedCount = 0;
        $emailErrors = [];

        foreach ($users as $user) {
            // Ne pas supprimer l'utilisateur actuel
            if ($user === $currentUser) {
                continue;
            }

            // Vérifier si l'utilisateur est organisateur de sorties
            if (!$user->getSortiesOrganisees()->isEmpty()) {
                $this->addFlash('warning', "L'utilisateur {$user->getEmail()} n'a pas pu être supprimé car il est organisateur de sorties.");
                continue;
            }

            // Supprimer l'utilisateur de toutes les sorties où il est inscrit
            foreach ($user->getSortiesInscrit() as $sortie) {
                $sortie->removeParticipant($user);
            }

            // Envoyer l'email de notification avant de supprimer l'utilisateur
            try {
                $email = (new Email())
                    ->from('noreply@sortir.com')
                    ->to($user->getEmail())
                    ->subject('Votre compte a été supprimé')
                    ->html($this->renderView('email/account_deleted.html.twig', [
                        'user' => $user
                    ]));

                $mailer->send($email);
            } catch (\Exception $e) {
                $emailErrors[] = $user->getEmail();
            }

            $entityManager->remove($user);
            $deletedCount++;
        }

        $entityManager->flush();

        if ($deletedCount > 0) {
            $this->addFlash('success', "{$deletedCount} utilisateur(s) supprimé(s) avec succès.");
        }

        if (!empty($emailErrors)) {
            $this->addFlash('warning', "Les emails n'ont pas pu être envoyés aux utilisateurs suivants : " . implode(', ', $emailErrors));
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/sorties', name: 'app_admin_sorties')]
    public function sorties(SortieRepository $sortieRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/sorties.html.twig', [
            'sorties' => $sortieRepository->findAll(),
        ]);
    }
} 