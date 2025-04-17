<?php

namespace App\Command;

use App\Repository\SortieRepository;
use App\Service\SortieService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-sortie-status',
    description: 'Updates status of events and sends reminders',
)]
class UpdateSortieStatusCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SortieRepository $sortieRepository,
        private SortieService $sortieService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Update status for all active events
        $sorties = $this->sortieRepository->findAll();
        $updated = 0;

        foreach ($sorties as $sortie) {
            $oldStatus = $sortie->getEtat();
            $sortie->updateStatus();
            
            if ($oldStatus !== $sortie->getEtat()) {
                $updated++;
            }
        }

        $this->entityManager->flush();
        $io->success(sprintf('Updated status for %d events.', $updated));

        // Send reminders for upcoming events
        $upcomingEvents = $this->sortieRepository->findEventsNeedingReminders();
        foreach ($upcomingEvents as $event) {
            $this->sortieService->sendReminders($event);
        }

        // Archive old events
        $oldEvents = $this->sortieRepository->findEventsToArchive();
        foreach ($oldEvents as $event) {
            $event->setIsArchived(true);
        }
        
        $this->entityManager->flush();
        $io->success(sprintf('Archived %d old events.', count($oldEvents)));

        return Command::SUCCESS;
    }
} 