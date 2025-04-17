<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\SortieRepository;
use App\Service\SortieService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Lieu;
use App\Form\LieuType;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\VilleRepository;
use App\Entity\Ville;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Form\SortieFilterType;

#[Route('/sortie')]
class SortieController extends AbstractController
{
    public function __construct(
        private SortieService $sortieService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'app_sortie_index', methods: ['GET'])]
    public function index(Request $request, SortieRepository $sortieRepository): Response
    {
        $filterForm = $this->createForm(SortieFilterType::class);
        $filterForm->handleRequest($request);

        // Initialize filters with empty array if no form data
        $filters = $filterForm->getData() ?: [];
        
        // Get sorties with filters
        $sorties = $sortieRepository->findByFilters(
            $filters,
            $this->getUser()
        );

        // Update status for each sortie
        foreach ($sorties as $sortie) {
            $sortie->updateStatus();
        }
        $this->entityManager->flush();

        return $this->render('sortie/index.html.twig', [
            'sorties' => $sorties ?? [],
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/new', name: 'app_sortie_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $sortie = new Sortie();
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sortie->setOrganisateur($this->getUser());
            $sortie->setCampus($this->getUser()->getCampus());
            
            $this->entityManager->persist($sortie);
            $this->entityManager->flush();

            $this->addFlash('success', 'La sortie a été créée avec succès.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        return $this->render('sortie/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/lieu/new', name: 'app_lieu_new_ajax', methods: ['POST'])]
    public function newLieu(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        
        if (isset($data['lieu'])) {
            $data = $data['lieu'];
        }

        $lieu = new Lieu();
        $lieu->setNom($data['nom']);
        $lieu->setRue($data['rue']);
        $lieu->setLatitude($data['latitude']);
        $lieu->setLongitude($data['longitude']);

        // Get or create the Ville
        $ville = $entityManager->getRepository(Ville::class)->find($data['ville']);
        if (!$ville) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ville non trouvée'
            ], 400);
        }
        
        $lieu->setVille($ville);

        try {
            $entityManager->persist($lieu);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'id' => $lieu->getId(),
                'nom' => $lieu->getNom(),
                'message' => 'Lieu créé avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la création du lieu: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}', name: 'app_sortie_show', methods: ['GET'])]
    public function show(Sortie $sortie): Response
    {
        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sortie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        // Check if user is the organizer
        if ($this->getUser() !== $sortie->getOrganisateur()) {
            throw new AccessDeniedException('Vous n\'êtes pas l\'organisateur de cette sortie.');
        }

        // Check if sortie can be edited
        if ($sortie->getEtat() !== 'Créée') {
            throw new AccessDeniedException('Cette sortie ne peut plus être modifiée.');
        }

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La sortie a été modifiée avec succès.');
            return $this->redirectToRoute('app_sortie_index');
        }

        return $this->render('sortie/edit.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/publish', name: 'app_sortie_publish', methods: ['POST'])]
    public function publish(Request $request, Sortie $sortie): Response
    {
        if (!$this->isCsrfTokenValid('publish'.$sortie->getId(), $request->request->get('_token'))) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        if ($sortie->getOrganisateur() !== $this->getUser()) {
            throw new AccessDeniedException('Vous n\'êtes pas l\'organisateur de cette sortie.');
        }

        $sortie->setEtat(Sortie::ETAT_OUVERTE);
        $this->entityManager->flush();

        $this->addFlash('success', 'La sortie a été publiée.');
        return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
    }

    #[Route('/{id}/register', name: 'app_sortie_register', methods: ['POST'])]
    public function register(Request $request, Sortie $sortie): Response
    {
        if (!$this->isCsrfTokenValid('register'.$sortie->getId(), $request->request->get('_token'))) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        try {
            $this->sortieService->register($sortie, $this->getUser());
            $this->addFlash('success', 'Vous êtes inscrit à la sortie.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
    }

    #[Route('/{id}/unregister', name: 'app_sortie_unregister', methods: ['POST'])]
    public function unregister(Request $request, Sortie $sortie): Response
    {
        if (!$this->isCsrfTokenValid('unregister'.$sortie->getId(), $request->request->get('_token'))) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        try {
            $this->sortieService->unregister($sortie, $this->getUser());
            $this->addFlash('success', 'Vous êtes désinscrit de la sortie.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
    }

    #[Route('/{id}/cancel', name: 'app_sortie_cancel', methods: ['POST'])]
    public function cancel(Request $request, Sortie $sortie): Response
    {
        if (!$this->isCsrfTokenValid('cancel'.$sortie->getId(), $request->request->get('_token'))) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        $motif = $request->request->get('motif');
        if (!$motif) {
            $this->addFlash('error', 'Le motif d\'annulation est requis.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        try {
            $this->sortieService->cancel($sortie, $motif, $this->getUser());
            $this->addFlash('success', 'La sortie a été annulée.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
    }
} 