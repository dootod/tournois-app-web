<?php

namespace App\Controller;

use App\Entity\Tournoi;
use App\Entity\Parametre;
use App\Repository\TournoiRepository;
use App\Repository\MatchTourRepository;
use App\Repository\AdherentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/tournois')]
class TournoiController extends AbstractController
{
    public function __construct(
        private TournoiRepository $tournoiRepository,
        private MatchTourRepository $matchTourRepository,
        private AdherentRepository $adherentRepository,
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
    ) {}

    // GET /api/tournois
    #[Route('', name: 'tournoi_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $tournois = $this->tournoiRepository->findAll();
            $data = $this->serializer->serialize($tournois, 'json', ['groups' => 'tournoi:read']);
            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la récupération des tournois',
                'message' => 'Une erreur s\'est produite. Veuillez réessayer.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // GET /api/tournois/{id}
    #[Route('/{id}', name: 'tournoi_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            if ($id <= 0) {
                return $this->json([
                    'error' => 'ID invalide',
                    'message' => 'L\'ID du tournoi doit être un nombre positif'
                ], Response::HTTP_BAD_REQUEST);
            }

            $tournoi = $this->tournoiRepository->find($id);
            if (!$tournoi) {
                return $this->json([
                    'error' => 'Non trouvé',
                    'message' => 'Le tournoi avec l\'ID ' . $id . ' n\'existe pas'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = $this->serializer->serialize($tournoi, 'json', ['groups' => 'tournoi:read']);
            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la récupération',
                'message' => 'Une erreur s\'est produite. Veuillez réessayer.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // POST /api/tournois
    #[Route('', name: 'tournoi_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $content = $request->getContent();
            $data = json_decode($content, true);

            if (!is_array($data)) {
                return $this->json([
                    'error' => 'JSON invalide',
                    'message' => 'Les données envoyées ne sont pas au format JSON valide'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation des données requises
            if (empty($data['date'])) {
                return $this->json([
                    'error' => 'Champ requis manquant',
                    'message' => 'La date du tournoi est obligatoire'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation de la date
            try {
                $date = new \DateTime($data['date']);
                $today = new \DateTime();
                if ($date < $today) {
                    return $this->json([
                        'error' => 'Date invalide',
                        'message' => 'La date du tournoi doit être dans le futur'
                    ], Response::HTTP_BAD_REQUEST);
                }
            } catch (\Exception $e) {
                return $this->json([
                    'error' => 'Format de date invalide',
                    'message' => 'Utilisez le format YYYY-MM-DD'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation du prix de participation
            $price = $data['prix_participation'] ?? 0;
            if (!is_numeric($price) || $price < 0) {
                return $this->json([
                    'error' => 'Prix invalide',
                    'message' => 'Le prix de participation doit être un nombre positif'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation IBAN si présent
            if (!empty($data['iban'])) {
                if (!$this->validateIban($data['iban'])) {
                    return $this->json([
                        'error' => 'IBAN invalide',
                        'message' => 'L\'IBAN fourni n\'est pas valide'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            $tournoi = new Tournoi();
            $tournoi->setEquipe(isset($data['equipe']) ? (bool) $data['equipe'] : false);
            $tournoi->setEtat('ouvert');
            $tournoi->setDate($date);
            $tournoi->setPrixParticipation($price > 0 ? (string) $price : null);
            $tournoi->setIban($data['iban'] ?? null);

            // Création des paramètres par défaut
            if (isset($data['parametre']) || true) {
                $p = $data['parametre'] ?? [];
                $parametre = new Parametre();
                
                // Validation des paramètres
                $tempsCombat = $p['temps_combat'] ?? 5;
                $maxParticipants = $p['max_participants'] ?? 32;
                $nbTatamis = $p['nb_tatamis'] ?? 2;

                $parametre->setTempsCombat((string) max(1, $tempsCombat));
                $parametre->setMaxParticipants(max(4, $maxParticipants));
                $parametre->setNbTatamis(max(1, $nbTatamis));
                $parametre->setTournoi($tournoi);
                $tournoi->setParametre($parametre);
                $this->em->persist($parametre);
            }

            $this->em->persist($tournoi);
            $this->em->flush();

            $result = $this->serializer->serialize($tournoi, 'json', ['groups' => 'tournoi:read']);
            return new JsonResponse($result, Response::HTTP_CREATED, [], true);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la création',
                'message' => 'Une erreur s\'est produite lors de la création du tournoi'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // PUT /api/tournois/{id}
    #[Route('/{id}', name: 'tournoi_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            if ($id <= 0) {
                return $this->json([
                    'error' => 'ID invalide',
                    'message' => 'L\'ID du tournoi doit être un nombre positif'
                ], Response::HTTP_BAD_REQUEST);
            }

            $tournoi = $this->tournoiRepository->find($id);
            if (!$tournoi) {
                return $this->json([
                    'error' => 'Non trouvé',
                    'message' => 'Le tournoi avec l\'ID ' . $id . ' n\'existe pas'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            if (!is_array($data)) {
                return $this->json([
                    'error' => 'JSON invalide',
                    'message' => 'Les données envoyées ne sont pas au format JSON valide'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation et mise à jour de la date
            if (isset($data['date'])) {
                try {
                    $date = new \DateTime($data['date']);
                    $tournoi->setDate($date);
                } catch (\Exception $e) {
                    return $this->json([
                        'error' => 'Format de date invalide',
                        'message' => 'Utilisez le format YYYY-MM-DD'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Validation et mise à jour de l'état
            if (isset($data['etat'])) {
                $etatsValides = ['ouvert', 'en_cours', 'termine', 'annule'];
                if (!in_array($data['etat'], $etatsValides)) {
                    return $this->json([
                        'error' => 'État invalide',
                        'message' => 'États acceptés: ' . implode(', ', $etatsValides)
                    ], Response::HTTP_BAD_REQUEST);
                }
                $tournoi->setEtat($data['etat']);
            }

            // Validation prix
            if (array_key_exists('prix_participation', $data)) {
                $price = $data['prix_participation'];
                if ($price !== null && (!is_numeric($price) || $price < 0)) {
                    return $this->json([
                        'error' => 'Prix invalide',
                        'message' => 'Le prix doit être un nombre positif ou null'
                    ], Response::HTTP_BAD_REQUEST);
                }
                $tournoi->setPrixParticipation($price > 0 ? (string) $price : null);
            }

            // Validation IBAN
            if (array_key_exists('iban', $data)) {
                if ($data['iban'] !== null && !$this->validateIban($data['iban'])) {
                    return $this->json([
                        'error' => 'IBAN invalide',
                        'message' => 'L\'IBAN fourni n\'est pas valide'
                    ], Response::HTTP_BAD_REQUEST);
                }
                $tournoi->setIban($data['iban']);
            }

            $this->em->flush();

            $result = $this->serializer->serialize($tournoi, 'json', ['groups' => 'tournoi:read']);
            return new JsonResponse($result, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la mise à jour',
                'message' => 'Une erreur s\'est produite lors de la mise à jour du tournoi'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // PATCH /api/tournois/{id}/etat
    #[Route('/{id}/etat', name: 'tournoi_etat', methods: ['PATCH'])]
    public function updateEtat(int $id, Request $request): JsonResponse
    {
        try {
            if ($id <= 0) {
                return $this->json([
                    'error' => 'ID invalide',
                    'message' => 'L\'ID du tournoi doit être un nombre positif'
                ], Response::HTTP_BAD_REQUEST);
            }

            $tournoi = $this->tournoiRepository->find($id);
            if (!$tournoi) {
                return $this->json([
                    'error' => 'Non trouvé',
                    'message' => 'Le tournoi avec l\'ID ' . $id . ' n\'existe pas'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            if (!is_array($data)) {
                return $this->json([
                    'error' => 'JSON invalide',
                    'message' => 'Les données envoyées ne sont pas au format JSON valide'
                ], Response::HTTP_BAD_REQUEST);
            }

            $etatsValides = ['ouvert', 'en_cours', 'termine', 'annule'];

            if (empty($data['etat'])) {
                return $this->json([
                    'error' => 'Champ manquant',
                    'message' => 'Le champ "etat" est obligatoire'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!in_array($data['etat'], $etatsValides)) {
                return $this->json([
                    'error' => 'État invalide',
                    'message' => 'États acceptés: ' . implode(', ', $etatsValides)
                ], Response::HTTP_BAD_REQUEST);
            }

            $tournoi->setEtat($data['etat']);
            $this->em->flush();

            return $this->json([
                'success' => true,
                'message' => 'État mis à jour avec succès',
                'etat' => $tournoi->getEtat()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la mise à jour',
                'message' => 'Une erreur s\'est produite'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // DELETE /api/tournois/{id}
    #[Route('/{id}', name: 'tournoi_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            if ($id <= 0) {
                return $this->json([
                    'error' => 'ID invalide',
                    'message' => 'L\'ID du tournoi doit être un nombre positif'
                ], Response::HTTP_BAD_REQUEST);
            }

            $tournoi = $this->tournoiRepository->find($id);
            if (!$tournoi) {
                return $this->json([
                    'error' => 'Non trouvé',
                    'message' => 'Le tournoi avec l\'ID ' . $id . ' n\'existe pas'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->em->remove($tournoi);
            $this->em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Tournoi supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la suppression',
                'message' => 'Une erreur s\'est produite lors de la suppression du tournoi'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // GET /api/tournois/{id}/participants
    #[Route('/{id}/participants', name: 'tournoi_participants', methods: ['GET'])]
    public function participants(int $id): JsonResponse
    {
        try {
            if ($id <= 0) {
                return $this->json(['error' => 'ID invalide'], Response::HTTP_BAD_REQUEST);
            }

            $tournoi = $this->tournoiRepository->find($id);
            if (!$tournoi) {
                return $this->json([
                    'error' => 'Tournoi non trouvé',
                    'message' => 'Le tournoi avec l\'ID ' . $id . ' n\'existe pas'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = $this->serializer->serialize($tournoi->getParticipants(), 'json', ['groups' => 'participant:read']);
            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la récupération'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // GET /api/tournois/{id}/poules
    #[Route('/{id}/poules', name: 'tournoi_poules', methods: ['GET'])]
    public function poules(int $id): JsonResponse
    {
        try {
            if ($id <= 0) {
                return $this->json(['error' => 'ID invalide'], Response::HTTP_BAD_REQUEST);
            }

            $tournoi = $this->tournoiRepository->find($id);
            if (!$tournoi) {
                return $this->json([
                    'error' => 'Tournoi non trouvé',
                    'message' => 'Le tournoi avec l\'ID ' . $id . ' n\'existe pas'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = $this->serializer->serialize($tournoi->getPoules(), 'json', ['groups' => 'poule:read']);
            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la récupération'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // GET /api/tournois/{id}/equipes
    #[Route('/{id}/equipes', name: 'tournoi_equipes', methods: ['GET'])]
    public function equipes(int $id): JsonResponse
    {
        try {
            if ($id <= 0) {
                return $this->json(['error' => 'ID invalide'], Response::HTTP_BAD_REQUEST);
            }

            $tournoi = $this->tournoiRepository->find($id);
            if (!$tournoi) {
                return $this->json([
                    'error' => 'Tournoi non trouvé',
                    'message' => 'Le tournoi avec l\'ID ' . $id . ' n\'existe pas'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = $this->serializer->serialize($tournoi->getEquipes(), 'json', ['groups' => 'equipe:read']);
            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la récupération'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Valide un numéro IBAN
     */
    private function validateIban(string $iban): bool
    {
        // Supprimer les espaces
        $iban = str_replace(' ', '', $iban);
        $iban = strtoupper($iban);
        
        // Vérifier le format général: 
        // - 2 lettres (code pays)
        // - 2 chiffres (checksum)
        // - 10-30 caractères alphanumériques (numéro de compte)
        // Total: 14-34 caractères
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{10,30}$/', $iban)) {
            return false;
        }
        
        return true;
    }

    // GET /api/tournois/{id}/show — Tout en 1 seul appel (optimisé)
    #[Route('/{id}/show', name: 'tournoi_show_full', methods: ['GET'])]
    public function showFull(int $id): JsonResponse
    {
        $tournoi = $this->tournoiRepository->findForShow($id);
        if (!$tournoi) {
            return $this->json(['message' => 'Tournoi non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $matchs    = $this->matchTourRepository->findByTournoiWithJoins($id);
        $adherents = $this->adherentRepository->findAll();

        return $this->json([
            'tournoi'      => json_decode($this->serializer->serialize($tournoi, 'json', ['groups' => 'tournoi:read']), true),
            'participants' => json_decode($this->serializer->serialize($tournoi->getParticipants(), 'json', ['groups' => 'participant:read']), true),
            'equipes'      => json_decode($this->serializer->serialize($tournoi->getEquipes(), 'json', ['groups' => 'equipe:read']), true),
            'matchs'       => json_decode($this->serializer->serialize($matchs, 'json', ['groups' => 'matchtour:read']), true),
            'adherents'    => json_decode($this->serializer->serialize($adherents, 'json', ['groups' => 'adherent:read']), true),
        ]);
    }

    // GET /api/tournois/{id}/matchs — Tous les matchs avec scores embarqués
    #[Route('/{id}/matchs', name: 'tournoi_matchs', methods: ['GET'])]
    public function matchs(int $id): JsonResponse
    {
        $tournoi = $this->tournoiRepository->find($id);
        if (!$tournoi) {
            return $this->json(['message' => 'Tournoi non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $matchs = [];
        foreach ($tournoi->getPoules() as $poule) {
            foreach ($poule->getMatchTours() as $match) {
                $matchs[] = $match;
            }
        }

        $data = $this->serializer->serialize($matchs, 'json', ['groups' => 'matchtour:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // GET /api/tournois/{id}/matchs/elimination — Matchs phase finale
    #[Route('/{id}/matchs/elimination', name: 'tournoi_matchs_elimination', methods: ['GET'])]
    public function matchsElimination(int $id): JsonResponse
    {
        $tournoi = $this->tournoiRepository->find($id);
        if (!$tournoi) {
            return $this->json(['message' => 'Tournoi non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $matchs = [];
        foreach ($tournoi->getPoules() as $poule) {
            foreach ($poule->getMatchTours() as $match) {
                if ($match->getPhase() === 'elimination') {
                    $matchs[] = $match;
                }
            }
        }

        // Matchs sans poule (phase finale directe)
        // Note: pour simplifier, on stocke les matchs élimination dans une poule "FINALE" ou sans poule
        // Ici on retourne tous les matchs de type elimination via les poules

        $data = $this->serializer->serialize($matchs, 'json', ['groups' => 'matchtour:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // GET /api/tournois/{id}/check-tatami-conflict
    #[Route('/{id}/check-tatami-conflict', name: 'tournoi_check_tatami_conflict', methods: ['GET'])]
    public function checkTatamiConflict(int $id, Request $request): JsonResponse
    {
        try {
            $tournoi = $this->tournoiRepository->find($id);
            if (!$tournoi) {
                return $this->json(['conflict' => false, 'message' => 'Tournoi non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $tatami = (int) $request->query->get('tatami');
            $heureDebut = $request->query->get('heure_debut');
            $heureFin = $request->query->get('heure_fin');
            $excludeMatchId = $request->query->get('exclude_match_id');

            if (!$tatami || !$heureDebut || !$heureFin) {
                return $this->json(['conflict' => false, 'message' => 'Paramètres manquants'], Response::HTTP_BAD_REQUEST);
            }

            $hd = \DateTime::createFromFormat('H:i', $heureDebut);
            $hf = \DateTime::createFromFormat('H:i', $heureFin);

            if (!$hd || !$hf) {
                return $this->json(['conflict' => false, 'message' => 'Format d\'heure invalide'], Response::HTTP_BAD_REQUEST);
            }

            $hasConflict = $this->matchTourRepository->hasConflictingTatami($id, $tatami, $hd, $hf);

            // Si on doit exclure un match (mise à jour), vérifier s'il y a toujours un conflit sans ce match
            if ($hasConflict && $excludeMatchId) {
                $conflicts = $this->matchTourRepository->createQueryBuilder('m')
                    ->select('m.id')
                    ->join('m.poule', 'poule')
                    ->join('poule.tournoi', 't')
                    ->where('t.id = :tournoiId')
                    ->andWhere('m.id != :matchId')
                    ->andWhere('m.tatami = :tatami')
                    ->andWhere('m.heure_debut IS NOT NULL')
                    ->andWhere('m.heure_fin IS NOT NULL')
                    ->andWhere('m.heure_debut < :heureFin')
                    ->andWhere('m.heure_fin > :heureDebut')
                    ->setParameter('tournoiId', $id)
                    ->setParameter('matchId', (int)$excludeMatchId)
                    ->setParameter('tatami', $tatami)
                    ->setParameter('heureDebut', $hd)
                    ->setParameter('heureFin', $hf)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getResult();
                $hasConflict = !empty($conflicts);
            }

            return $this->json(['conflict' => $hasConflict], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'conflict' => false,
                'error' => 'Erreur lors de la vérification',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
