<?php

namespace App\Controller;

use App\Entity\MatchTour;
use App\Repository\MatchTourRepository;
use App\Repository\PouleRepository;
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
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
    ) {}

    // GET /api/matchs
    #[Route('', name: 'matchtour_list', methods: ['GET'])]
    public function list(): JsonResponse
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

        $match = new MatchTour();

        if (isset($data['poule_id'])) {
            $poule = $this->pouleRepository->find($data['poule_id']);
            if (!$poule) {
                return $this->json(['message' => 'Poule non trouvée'], Response::HTTP_NOT_FOUND);
            }
            $match->setPoule($poule);
        }

        $this->em->persist($match);
        $this->em->flush();

        $result = $this->serializer->serialize($match, 'json', ['groups' => 'matchtour:read']);
        return new JsonResponse($result, Response::HTTP_CREATED, [], true);
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

    // GET /api/matchs/{id}/planning
    #[Route('/{id}/planning', name: 'matchtour_planning', methods: ['GET'])]
    public function planning(int $id): JsonResponse
    {
        $match = $this->matchTourRepository->find($id);
        if (!$match) {
            return $this->json(['message' => 'Match non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($match->getPlannings(), 'json', ['groups' => 'planning:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}