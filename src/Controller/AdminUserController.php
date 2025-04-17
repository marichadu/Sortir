<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\AdminUserRegistrationFormType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'admin_users_')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(ParticipantRepository $participantRepository): Response
    {
        $users = $participantRepository->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $user = new Participant();
        $form = $this->createForm(AdminUserRegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            
            // Encode the plain password
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $plainPassword
                )
            );

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été créé avec succès.');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Request $request, Participant $user): Response
    {
        $form = $this->createForm(AdminUserRegistrationFormType::class, $user, [
            'require_password' => false
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('plainPassword')->getData()) {
                $user->setPassword(
                    $this->passwordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été modifié avec succès.');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete')]
    public function delete(Participant $user): Response
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->addFlash('success', 'L\'utilisateur a été supprimé avec succès.');

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/bulk-delete', name: 'bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('bulk_delete_users', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_users_index');
        }

        $userIds = $request->request->all('users');
        if (empty($userIds)) {
            $this->addFlash('error', 'Aucun utilisateur sélectionné.');
            return $this->redirectToRoute('admin_users_index');
        }

        $users = $this->entityManager->getRepository(Participant::class)->findBy(['id' => $userIds]);
        $currentUser = $this->getUser();
        $deletedCount = 0;

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

            $this->entityManager->remove($user);
            $deletedCount++;
        }

        $this->entityManager->flush();

        if ($deletedCount > 0) {
            $this->addFlash('success', "{$deletedCount} utilisateur(s) supprimé(s) avec succès.");
        }

        return $this->redirectToRoute('admin_users_index');
    }
} 