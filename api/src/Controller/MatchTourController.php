<?php

namespace App\Controller;

use App\Entity\MatchTour;
use App\Repository\MatchTourRepository;
use App\Repository\PouleRepository;
use App\Repository\ParticipantRepository;
use App\Repository\EquipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/matchs')]
class MatchTourController extends AbstractController
{
    public function __construct(
        private MatchTourRepository $matchTourRepository,
        private PouleRepository $pouleRepository,
        private ParticipantRepository $participantRepository,
        private EquipeRepository $equipeRepository,
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
    ) {}

    // GET /api/matchs
    #[Route('', name: 'matchtour_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $matchs = $this->matchTourRepository->findAll();
        $data = $this->serializer->serialize($matchs, 'json', ['groups' => 'matchtour:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // GET /api/matchs/{id}
    #[Route('/{id}', name: 'matchtour_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $match = $this->matchTourRepository->find($id);
        if (!$match) {
            return $this->json(['message' => 'Match non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $data = $this->serializer->serialize($match, 'json', ['groups' => 'matchtour:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // POST /api/matchs
    #[Route('', name: 'matchtour_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['poule_id'])) {
            return $this->json(['message' => 'poule_id est obligatoire'], Response::HTTP_BAD_REQUEST);
        }

        $poule = $this->pouleRepository->find($data['poule_id']);
        if (!$poule) {
            return $this->json(['message' => 'Poule non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $match = new MatchTour();
        $match->setPoule($poule);
        $match->setPhase($data['phase'] ?? MatchTour::PHASE_QUALIFICATION);
        $match->setRound($data['round'] ?? null);

        $this->applyOpponents($match, $data);

        // Vérifier que les deux combattants/équipes sont différents
        if ($error = $this->validateOpponents($match)) {
            return $this->json(['message' => $error], Response::HTTP_BAD_REQUEST);
        }

        $this->applyPlanning($match, $data);

        if ($error = $this->validateTatami($match, $poule)) {
            return $this->json(['message' => $error], Response::HTTP_CONFLICT);
        }

        $this->em->persist($match);
        $this->em->flush();

        $result = $this->serializer->serialize($match, 'json', ['groups' => 'matchtour:read']);
        return new JsonResponse($result, Response::HTTP_CREATED, [], true);
    }

    // PUT /api/matchs/{id}
    #[Route('/{id}', name: 'matchtour_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $match = $this->matchTourRepository->find($id);
        if (!$match) {
            return $this->json(['message' => 'Match non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['phase'])) $match->setPhase($data['phase']);
        if (array_key_exists('round', $data)) $match->setRound($data['round']);
        $this->applyOpponents($match, $data);

        // Vérifier que les deux combattants/équipes sont différents
        if ($error = $this->validateOpponents($match)) {
            return $this->json(['message' => $error], Response::HTTP_BAD_REQUEST);
        }

        $this->applyPlanning($match, $data);

        if ($error = $this->validateTatami($match, $match->getPoule(), $match->getId())) {
            return $this->json(['message' => $error], Response::HTTP_CONFLICT);
        }

        // Score équipes
        if (array_key_exists('score_equipe1', $data)) $match->setScoreEquipe1($data['score_equipe1'] !== null ? (int)$data['score_equipe1'] : null);
        if (array_key_exists('score_equipe2', $data)) $match->setScoreEquipe2($data['score_equipe2'] !== null ? (int)$data['score_equipe2'] : null);

        $this->em->flush();

        $result = $this->serializer->serialize($match, 'json', ['groups' => 'matchtour:read']);
        return new JsonResponse($result, Response::HTTP_OK, [], true);
    }

    // DELETE /api/matchs/{id}
    #[Route('/{id}', name: 'matchtour_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $match = $this->matchTourRepository->find($id);
        if (!$match) {
            return $this->json(['message' => 'Match non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($match);
        $this->em->flush();

        return $this->json(['message' => 'Match supprimé'], Response::HTTP_OK);
    }

    // GET /api/matchs/{id}/scores
    #[Route('/{id}/scores', name: 'matchtour_scores', methods: ['GET'])]
    public function scores(int $id): JsonResponse
    {
        $match = $this->matchTourRepository->find($id);
        if (!$match) {
            return $this->json(['message' => 'Match non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($match->getScores(), 'json', ['groups' => 'score:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function applyOpponents(MatchTour $match, array $data): void
    {
        if (isset($data['participant1_id'])) {
            $p = $this->participantRepository->find($data['participant1_id']);
            $match->setParticipant1($p ?: null);
        }
        if (isset($data['participant2_id'])) {
            $p = $this->participantRepository->find($data['participant2_id']);
            $match->setParticipant2($p ?: null);
        }
        if (isset($data['equipe1_id'])) {
            $e = $this->equipeRepository->find($data['equipe1_id']);
            $match->setEquipe1($e ?: null);
        }
        if (isset($data['equipe2_id'])) {
            $e = $this->equipeRepository->find($data['equipe2_id']);
            $match->setEquipe2($e ?: null);
        }
    }

    private function applyPlanning(MatchTour $match, array $data): void
    {
        if (isset($data['tatami'])) $match->setTatami((int) $data['tatami']);
        if (array_key_exists('heure_debut', $data)) {
            $match->setHeureDebut($data['heure_debut'] ? new \DateTime($data['heure_debut']) : null);
        }
        if (array_key_exists('heure_fin', $data)) {
            $match->setHeureFin($data['heure_fin'] ? new \DateTime($data['heure_fin']) : null);
        }
    }

    private function validateOpponents(MatchTour $match): ?string
    {
        $p1 = $match->getParticipant1();
        $p2 = $match->getParticipant2();
        $e1 = $match->getEquipe1();
        $e2 = $match->getEquipe2();

        // Vérifier que les deux combattants sont différents
        if ($p1 && $p2 && $p1->getId() === $p2->getId()) {
            return 'Les deux combattants doivent être différents.';
        }

        // Vérifier que les deux équipes sont différentes
        if ($e1 && $e2 && $e1->getId() === $e2->getId()) {
            return 'Les deux équipes doivent être différentes.';
        }

        return null;
    }

    private function validateTatami(MatchTour $match, ?object $poule, ?int $excludeMatchId = null): ?string
    {
        if ($match->getTatami() === null || $match->getHeureDebut() === null) {
            return null;
        }

        $tournoi = $poule?->getTournoi();
        if (!$tournoi) return null;

        $nbTatamisMax = $tournoi->getParametre()?->getNbTatamis() ?? 99;
        $creneau = $match->getHeureDebut()->format('H:i');

        if ($match->getTatami() > $nbTatamisMax) {
            return sprintf('Tatami %d inexistant. Ce tournoi n\'a que %d tatami(s).', $match->getTatami(), $nbTatamisMax);
        }

        $count = 0;
        foreach ($tournoi->getPoules() as $p) {
            foreach ($p->getMatchTours() as $m) {
                if ($excludeMatchId !== null && $m->getId() === $excludeMatchId) continue;
                if ($m->getHeureDebut() && $m->getHeureDebut()->format('H:i') === $creneau) {
                    $count++;
                }
            }
        }

        if ($count >= $nbTatamisMax) {
            return sprintf('Créneau %s saturé : maximum %d tatami(s) disponible(s) pour ce tournoi.', $creneau, $nbTatamisMax);
        }

        return null;
    }
}
