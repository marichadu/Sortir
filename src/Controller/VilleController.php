<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\VilleFilterType;

#[Route('/ville')]
class VilleController extends AbstractController
{
    #[Route('/', name: 'app_ville_index', methods: ['GET'])]
    public function index(Request $request, VilleRepository $villeRepository): Response
    {
        $filterForm = $this->createForm(VilleFilterType::class);
        $filterForm->handleRequest($request);

        $villes = $villeRepository->findByFilters($filterForm->getData() ?: []);

        return $this->render('ville/index.html.twig', [
            'villes' => $villes,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/new', name: 'app_ville_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('create_ville', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_ville_index');
        }

        $ville = new Ville();
        $ville->setNom($request->request->get('nom'));
        $ville->setCodePostal($request->request->get('codePostal'));
        $ville->setRegion($request->request->get('region'));

        $entityManager->persist($ville);
        $entityManager->flush();

        $this->addFlash('success', 'Ville ajoutée avec succès.');
        return $this->redirectToRoute('app_ville_index');
    }

    #[Route('/{id}/edit', name: 'app_ville_edit', methods: ['POST'])]
    public function edit(Request $request, Ville $ville, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('edit_ville'.$ville->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_ville_index');
        }

        $ville->setNom($request->request->get('nom'));
        $ville->setCodePostal($request->request->get('codePostal'));
        $ville->setRegion($request->request->get('region'));

        $entityManager->flush();

        $this->addFlash('success', 'Ville modifiée avec succès.');
        return $this->redirectToRoute('app_ville_index');
    }

    #[Route('/{id}/delete', name: 'app_ville_delete', methods: ['POST'])]
    public function delete(Request $request, Ville $ville, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_ville'.$ville->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_ville_index');
        }

        // Vérifier si la ville est utilisée
        if (!$ville->getLieux()->isEmpty()) {
            $this->addFlash('error', 'Cette ville ne peut pas être supprimée car elle est utilisée par des lieux.');
            return $this->redirectToRoute('app_ville_index');
        }

        $entityManager->remove($ville);
        $entityManager->flush();

        $this->addFlash('success', 'Ville supprimée avec succès.');
        return $this->redirectToRoute('app_ville_index');
    }

    #[Route('/create-if-not-exists', name: 'app_ville_create_if_not_exists', methods: ['POST'])]
    public function createIfNotExists(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$this->isCsrfTokenValid('ville_create', $data['_token'])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 400);
        }

        $ville = $entityManager->getRepository(Ville::class)->findOneBy([
            'nom' => $data['nom'],
            'codePostal' => $data['code_postal']
        ]);

        if (!$ville) {
            $ville = new Ville();
            $ville->setNom($data['nom']);
            $ville->setCodePostal($data['code_postal']);
            $ville->setRegion($data['region'] ?? null);
            $ville->setDepartement($data['departement'] ?? null);
            
            $entityManager->persist($ville);
            $entityManager->flush();
        }

        return new JsonResponse([
            'success' => true,
            'ville' => [
                'id' => $ville->getId(),
                'nom' => $ville->getNom(),
                'codePostal' => $ville->getCodePostal(),
                'region' => $ville->getRegion(),
                'departement' => $ville->getDepartement()
            ]
        ]);
    }
} 