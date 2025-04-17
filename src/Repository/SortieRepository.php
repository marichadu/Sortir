<?php

namespace App\Repository;

use App\Entity\Sortie;
use App\Entity\Participant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    public function findByFilters(array $filters, Participant $user): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.organisateur', 'o')
            ->leftJoin('s.campus', 'c')
            ->leftJoin('s.participants', 'p')
            ->addSelect('o', 'c', 'p');

        // Filter by campus
        if (!empty($filters['campus'])) {
            $qb->andWhere('c.id = :campusId')
               ->setParameter('campusId', $filters['campus']);
        }

        // Filter by search term
        if (!empty($filters['search'])) {
            $qb->andWhere('s.nom LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Filter by date range
        if (!empty($filters['dateDebut'])) {
            $qb->andWhere('s.dateHeureDebut >= :dateDebut')
               ->setParameter('dateDebut', $filters['dateDebut']);
        }
        if (!empty($filters['dateFin'])) {
            $qb->andWhere('s.dateHeureDebut <= :dateFin')
               ->setParameter('dateFin', $filters['dateFin']);
        }

        // Filter by organizer
        if (!empty($filters['isOrganisateur'])) {
            $qb->andWhere('s.organisateur = :userId')
               ->setParameter('userId', $user);
        }

        // Filter by registration
        if (!empty($filters['isInscrit'])) {
            $qb->andWhere(':user MEMBER OF s.participants')
               ->setParameter('user', $user);
        }

        if (!empty($filters['isNotInscrit'])) {
            $qb->andWhere(':user NOT MEMBER OF s.participants')
               ->setParameter('user', $user);
        }

        // Filter by past events
        if (!empty($filters['isPassed'])) {
            $qb->andWhere('s.dateHeureDebut <= :now')
               ->setParameter('now', new \DateTime());
        } else {
            $qb->andWhere('s.dateHeureDebut > :now')
               ->setParameter('now', new \DateTime());
        }

        // Don't show archived events by default
        if (empty($filters['showArchived'])) {
            $qb->andWhere('s.isArchived = false');
        }

        // Order by date
        $qb->orderBy('s.dateHeureDebut', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findUpcomingEvents(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.dateHeureDebut > :now')
            ->andWhere('s.etat = :etatOuvert')
            ->setParameter('now', new \DateTime())
            ->setParameter('etatOuvert', Sortie::ETAT_OUVERTE)
            ->orderBy('s.dateHeureDebut', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    public function findEventsNeedingReminders(): array
    {
        $reminderDate = new \DateTime('+48 hours');
        
        return $this->createQueryBuilder('s')
            ->andWhere('s.dateHeureDebut BETWEEN :now AND :reminder')
            ->andWhere('s.etat = :etatOuvert')
            ->setParameter('now', new \DateTime())
            ->setParameter('reminder', $reminderDate)
            ->setParameter('etatOuvert', Sortie::ETAT_OUVERTE)
            ->getQuery()
            ->getResult();
    }

    public function findEventsToArchive(): array
    {
        $archiveDate = new \DateTime('-1 month');
        
        return $this->createQueryBuilder('s')
            ->andWhere('s.dateHeureDebut < :archiveDate')
            ->andWhere('s.isArchived = false')
            ->setParameter('archiveDate', $archiveDate)
            ->getQuery()
            ->getResult();
    }

    public function findByParticipant(Participant $participant): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.organisateur', 'o')
            ->leftJoin('s.campus', 'c')
            ->leftJoin('s.participants', 'p')
            ->addSelect('o', 'c', 'p')
            ->andWhere(':participant MEMBER OF s.participants')
            ->setParameter('participant', $participant)
            ->andWhere('s.isArchived = false')
            ->orderBy('s.dateHeureDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 