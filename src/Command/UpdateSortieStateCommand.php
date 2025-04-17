<?php

namespace App\Command;

use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:update-sortie-state',
    description: 'Updates the state of a sortie and extends its registration deadline',
)]
class UpdateSortieStateCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sortie = $this->entityManager->getRepository(Sortie::class)->findOneBy(['nom' => 'Pique-nique au parc']);
        
        if (!$sortie) {
            $output->writeln('Sortie not found!');
            return Command::FAILURE;
        }

        // Extend registration deadline by 1 week
        $newDeadline = new \DateTime('2025-04-24');
        $sortie->setDateLimiteInscription($newDeadline);
        
        // Set state to Ouverte
        $sortie->setEtat(Sortie::ETAT_OUVERTE);
        
        $this->entityManager->flush();
        
        $output->writeln('Sortie updated successfully!');
        $output->writeln('New registration deadline: ' . $newDeadline->format('Y-m-d'));
        $output->writeln('New state: ' . $sortie->getEtat());
        
        return Command::SUCCESS;
    }
} 