<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Repository\CampusRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/campus')]
class CampusController extends AbstractController
{
    #[Route('/', name: 'app_campus_index', methods: ['GET'])]
    public function index(CampusRepository $campusRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('campus/index.html.twig', [
            'campuses' => $campusRepository->findAll(),
        ]);
    }

    #[Route('/campus/new', name: 'app_campus_new', methods: ['POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('create_campus', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_campus_index');
        }

        $nom = $request->request->get('nom');
        if (!$nom) {
            $this->addFlash('error', 'Le nom du campus est requis.');
            return $this->redirectToRoute('app_campus_index');
        }

        $campus = new Campus();
        $campus->setNom($nom);

        $entityManager->persist($campus);
        $entityManager->flush();

        $this->addFlash('success', 'Campus créé avec succès.');
        return $this->redirectToRoute('app_campus_index');
    }

    #[Route('/campus/{id}/edit', name: 'app_campus_edit', methods: ['POST'])]
    public function edit(
        Request $request, 
        Campus $campus, 
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('edit_campus'.$campus->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_campus_index');
        }

        $nom = $request->request->get('nom');
        if (!$nom) {
            $this->addFlash('error', 'Le nom du campus est requis.');
            return $this->redirectToRoute('app_campus_index');
        }

        $campus->setNom($nom);
        $entityManager->flush();

        $this->addFlash('success', 'Campus modifié avec succès.');
        return $this->redirectToRoute('app_campus_index');
    }

    #[Route('/campus/{id}/delete', name: 'app_campus_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Campus $campus, 
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete_campus'.$campus->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_campus_index');
        }

        if (!$campus->getParticipants()->isEmpty()) {
            $this->addFlash('error', 'Impossible de supprimer un campus qui a des participants.');
            return $this->redirectToRoute('app_campus_index');
        }

        $entityManager->remove($campus);
        $entityManager->flush();

        $this->addFlash('success', 'Campus supprimé avec succès.');
        return $this->redirectToRoute('app_campus_index');
    }

    #[Route('/campus/{id}/users', name: 'app_campus_users')]
    public function users(Campus $campus): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('campus/users.html.twig', [
            'campus' => $campus,
            'users' => $campus->getParticipants(),
        ]);
    }
} 