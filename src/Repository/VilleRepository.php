<?php

namespace App\Repository;

use App\Entity\Ville;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VilleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ville::class);
    }

    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('v');

        if (!empty($filters['search'])) {
            $qb->andWhere('v.nom LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['codePostal'])) {
            $qb->andWhere('v.codePostal LIKE :codePostal')
               ->setParameter('codePostal', $filters['codePostal'] . '%');
        }

        if (!empty($filters['departement'])) {
            $qb->andWhere('v.codePostal LIKE :departement')
               ->setParameter('departement', substr($filters['departement'], 0, 2) . '%');
        }

        if (!empty($filters['region'])) {
            $qb->andWhere('v.region LIKE :region')
               ->setParameter('region', '%' . $filters['region'] . '%');
        }

        return $qb->orderBy('v.nom', 'ASC')
                  ->getQuery()
                  ->getResult();
    }
} 