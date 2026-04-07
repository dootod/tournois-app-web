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
        $tournois = $this->tournoiRepository->findAll();
        $data = $this->serializer->serialize($tournois, 'json', ['groups' => 'tournoi:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // GET /api/tournois/{id}
    #[Route('/{id}', name: 'tournoi_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $tournoi = $this->tournoiRepository->find($id);
        if (!$tournoi) {
            return $this->json(['message' => 'Tournoi non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $data = $this->serializer->serialize($tournoi, 'json', ['groups' => 'tournoi:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // POST /api/tournois
    #[Route('', name: 'tournoi_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        $tournoi = new Tournoi();
        $tournoi->setEquipe(isset($data['equipe']) ? (bool) $data['equipe'] : false);
        $tournoi->setEtat($data['etat'] ?? 'ouvert');
        $tournoi->setDate(isset($data['date']) ? new \DateTime($data['date']) : new \DateTime());
        $tournoi->setPrixParticipation($data['prix_participation'] ?? null);
        $tournoi->setIban($data['iban'] ?? null);

        if (isset($data['parametre'])) {
            $p = $data['parametre'];
            $parametre = new Parametre();
            $parametre->setTempsCombat($p['temps_combat'] ?? '5.00');
            $parametre->setMinPoule($p['min_poule'] ?? 3);
            $parametre->setMaxParticipants($p['max_participants'] ?? 32);
            $parametre->setMaxPoule($p['max_poule'] ?? 6);
            $parametre->setNbTatamis($p['nb_tatamis'] ?? 2);
            $parametre->setTournoi($tournoi);
            $tournoi->setParametre($parametre);
            $this->em->persist($parametre);
        }

        $this->em->persist($tournoi);
        $this->em->flush();

        $result = $this->serializer->serialize($tournoi, 'json', ['groups' => 'tournoi:read']);
        return new JsonResponse($result, Response::HTTP_CREATED, [], true);
    }

    // PUT /api/tournois/{id}
    #[Route('/{id}', name: 'tournoi_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $tournoi = $this->tournoiRepository->find($id);
        if (!$tournoi) {
            return $this->json(['message' => 'Tournoi non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['etat'])) $tournoi->setEtat($data['etat']);
        if (isset($data['date'])) $tournoi->setDate(new \DateTime($data['date']));
        if (array_key_exists('prix_participation', $data)) $tournoi->setPrixParticipation($data['prix_participation']);
        if (array_key_exists('iban', $data)) $tournoi->setIban($data['iban']);

        $this->em->flush();

        $result = $this->serializer->serialize($tournoi, 'json', ['groups' => 'tournoi:read']);
        return new JsonResponse($result, Response::HTTP_OK, [], true);
    }

    // PATCH /api/tournois/{id}/etat
    #[Route('/{id}/etat', name: 'tournoi_etat', methods: ['PATCH'])]
    public function updateEtat(int $id, Request $request): JsonResponse
    {
        $tournoi = $this->tournoiRepository->find($id);
        if (!$tournoi) {
            return $this->json(['message' => 'Tournoi non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $etatsValides = ['ouvert', 'en_cours', 'termine', 'annule'];

        if (!isset($data['etat']) || !in_array($data['etat'], $etatsValides)) {
            return $this->json([
                'message' => 'État invalide. Valeurs acceptées : ' . implode(', ', $etatsValides)
            ], Response::HTTP_BAD_REQUEST);
        }

        $tournoi->setEtat($data['etat']);
        $this->em->flush();

        return $this->json(['message' => 'État mis à jour', 'etat' => $tournoi->getEtat()]);
    }

    // DELETE /api/tournois/{id}
    #[Route('/{id}', name: 'tournoi_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $tournoi = $this->tournoiRepository->find($id);
        if (!$tournoi) {
            return $this->json(['message' => 'Tournoi non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($tournoi);
        $this->em->flush();

        return $this->json(['message' => 'Tournoi supprimé'], Response::HTTP_OK);
    }

    // GET /api/tournois/{id}/participants
    #[Route('/{id}/participants', name: 'tournoi_participants', methods: ['GET'])]
    public function participants(int $id): JsonResponse
    {
        $tournoi = $this->tournoiRepository->find($id);
        if (!$tournoi) {
            return $this->json(['message' => 'Tournoi non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($tournoi->getParticipants(), 'json', ['groups' => 'participant:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // GET /api/tournois/{id}/poules
    #[Route('/{id}/poules', name: 'tournoi_poules', methods: ['GET'])]
    public function poules(int $id): JsonResponse
    {
        $tournoi = $this->tournoiRepository->find($id);
        if (!$tournoi) {
            return $this->json(['message' => 'Tournoi non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($tournoi->getPoules(), 'json', ['groups' => 'poule:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // GET /api/tournois/{id}/equipes
    #[Route('/{id}/equipes', name: 'tournoi_equipes', methods: ['GET'])]
    public function equipes(int $id): JsonResponse
    {
        $tournoi = $this->tournoiRepository->find($id);
        if (!$tournoi) {
            return $this->json(['message' => 'Tournoi non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($tournoi->getEquipes(), 'json', ['groups' => 'equipe:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
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
}
