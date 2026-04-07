<?php

namespace App\Repository;

use App\Entity\Tournoi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tournoi>
 */
class TournoiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournoi::class);
    }

    /**
     * Charge le tournoi avec toutes ses relations en un minimum de requêtes SQL.
     */
    public function findForShow(int $id): ?Tournoi
    {
        return $this->createQueryBuilder('t')
            ->select('t', 'param', 'poule', 'p', 'pa', 'e', 'ep', 'epa')
            ->leftJoin('t.parametre', 'param')
            ->leftJoin('t.poules', 'poule')
            ->leftJoin('t.participants', 'p')
            ->leftJoin('p.adherent', 'pa')
            ->leftJoin('t.equipes', 'e')
            ->leftJoin('e.participants', 'ep')
            ->leftJoin('ep.adherent', 'epa')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
