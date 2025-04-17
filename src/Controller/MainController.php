<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Repository\SortieRepository;
use App\Repository\ParticipantRepository;
use App\Repository\CampusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    public function __construct(
        private SortieRepository $sortieRepository,
        private ParticipantRepository $participantRepository,
        private CampusRepository $campusRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'app_main')]
    public function index(): Response
    {
        $user = $this->getUser();
        $userEvents = [];
        $stats = [];
        
        // Get general statistics
        $stats['totalEvents'] = $this->sortieRepository->count(['isArchived' => false]);
        $stats['totalUsers'] = $this->participantRepository->count(['actif' => true]);
        $stats['totalCampus'] = $this->campusRepository->count([]);
        
        if ($user) {
            // Get user's registered sorties directly from the user entity
            $userEvents = $user->getSortiesInscrit()->toArray();
            
            // Filter out archived sorties
            $userEvents = array_filter($userEvents, function($sortie) {
                return !$sortie->isArchived();
            });
            
            // Update status of each sortie
            foreach ($userEvents as $sortie) {
                $sortie->updateStatus();
            }
            $this->entityManager->flush();
        }

        return $this->render('main/index.html.twig', [
            'userEvents' => $userEvents,
            'stats' => $stats
        ]);
    }
} 