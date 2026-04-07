<?php

namespace App\Controller;

use App\Entity\Score;
use App\Repository\ScoreRepository;
use App\Repository\ParticipantRepository;
use App\Repository\MatchTourRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/scores')]
class ScoreController extends AbstractController
{
    public function __construct(
        private ScoreRepository $scoreRepository,
        private ParticipantRepository $participantRepository,
        private MatchTourRepository $matchTourRepository,
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
    ) {}

    // GET /api/scores
    #[Route('', name: 'score_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $scores = $this->scoreRepository->findAll();
        $data = $this->serializer->serialize($scores, 'json', ['groups' => 'score:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // GET /api/scores/{id}
    #[Route('/{id}', name: 'score_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $score = $this->scoreRepository->find($id);
        if (!$score) {
            return $this->json(['message' => 'Score non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $data = $this->serializer->serialize($score, 'json', ['groups' => 'score:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // POST /api/scores
    #[Route('', name: 'score_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['participant_id']) || !isset($data['match_id'])) {
            return $this->json(['message' => 'participant_id et match_id sont obligatoires'], Response::HTTP_BAD_REQUEST);
        }

        $participant = $this->participantRepository->find($data['participant_id']);
        if (!$participant) {
            return $this->json(['message' => 'Participant non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $match = $this->matchTourRepository->find($data['match_id']);
        if (!$match) {
            return $this->json(['message' => 'Match non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $score = new Score();
        $score->setScore($data['score'] ?? 0);
        $score->setGagnant($data['gagnant'] ?? false);
        $score->setDisqualification($data['disqualification'] ?? false);
        $score->setParticipant($participant);
        $score->addMatchTour($match);

        $this->em->persist($score);
        $this->em->flush();

        $result = $this->serializer->serialize($score, 'json', ['groups' => 'score:read']);
        return new JsonResponse($result, Response::HTTP_CREATED, [], true);
    }

    // PUT /api/scores/{id}
    #[Route('/{id}', name: 'score_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $score = $this->scoreRepository->find($id);
        if (!$score) {
            return $this->json(['message' => 'Score non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['score'])) $score->setScore($data['score']);
        if (isset($data['gagnant'])) $score->setGagnant($data['gagnant']);
        if (isset($data['disqualification'])) $score->setDisqualification($data['disqualification']);

        $this->em->flush();

        $result = $this->serializer->serialize($score, 'json', ['groups' => 'score:read']);
        return new JsonResponse($result, Response::HTTP_OK, [], true);
    }

    // DELETE /api/scores/{id}
    #[Route('/{id}', name: 'score_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $score = $this->scoreRepository->find($id);
        if (!$score) {
            return $this->json(['message' => 'Score non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($score);
        $this->em->flush();

        return $this->json(['message' => 'Score supprimé'], Response::HTTP_OK);
    }
}