<?php

namespace App\Repository;

use App\Entity\MatchTour;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MatchTour>
 */
class MatchTourRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MatchTour::class);
    }

    /**
     * Charge tous les matchs d'un tournoi avec toutes leurs relations en un minimum de requêtes SQL.
     */
    public function findByTournoiWithJoins(int $tournoiId): array
    {
        return $this->createQueryBuilder('m')
            ->select('m', 'poule', 'p1', 'a1', 'p2', 'a2', 'e1', 'e2', 's', 'sp', 'spa')
            ->join('m.poule', 'poule')
            ->join('poule.tournoi', 't')
            ->leftJoin('m.participant1', 'p1')
            ->leftJoin('p1.adherent', 'a1')
            ->leftJoin('m.participant2', 'p2')
            ->leftJoin('p2.adherent', 'a2')
            ->leftJoin('m.equipe1', 'e1')
            ->leftJoin('m.equipe2', 'e2')
            ->leftJoin('m.scores', 's')
            ->leftJoin('s.participant', 'sp')
            ->leftJoin('sp.adherent', 'spa')
            ->where('t.id = :tournoiId')
            ->setParameter('tournoiId', $tournoiId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie s'il existe un conflit de tatami (même tatami utilisé au même moment)
     * pour un tournoi donné.
     *
     * @return bool true s'il y a un conflit, false sinon
     */
    public function hasConflictingTatami(int $tournoiId, int $tatami, \DateTime $heureDebut, \DateTime $heureFin): bool
    {
        $result = $this->createQueryBuilder('m')
            ->select('m.id')
            ->join('m.poule', 'poule')
            ->join('poule.tournoi', 't')
            ->where('t.id = :tournoiId')
            ->andWhere('m.tatami = :tatami')
            ->andWhere('m.heure_debut IS NOT NULL')
            ->andWhere('m.heure_fin IS NOT NULL')
            // Vérifier le chevauchement : debut1 < fin2 AND fin1 > debut2
            ->andWhere('m.heure_debut < :heureFin')
            ->andWhere('m.heure_fin > :heureDebut')
            ->setParameter('tournoiId', $tournoiId)
            ->setParameter('tatami', $tatami)
            ->setParameter('heureDebut', $heureDebut)
            ->setParameter('heureFin', $heureFin)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return !empty($result);
    }
}
