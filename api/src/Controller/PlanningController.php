<?php

namespace App\Controller;

use App\Entity\Planning;
use App\Entity\Tatami;
use App\Repository\PlanningRepository;
use App\Repository\TatamiRepository;
use App\Repository\MatchTourRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class PlanningController extends AbstractController
{
    public function __construct(
        private PlanningRepository $planningRepository,
        private TatamiRepository $tatamiRepository,
        private MatchTourRepository $matchTourRepository,
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
    ) {}

    // ─── PLANNING ───────────────────────────────────────────────────────────────

    // GET /api/plannings
    #[Route('/plannings', name: 'planning_list', methods: ['GET'])]
    public function listPlannings(): JsonResponse
    {
        $plannings = $this->planningRepository->findAll();
        $data = $this->serializer->serialize($plannings, 'json', ['groups' => 'planning:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // GET /api/plannings/{id}
    #[Route('/plannings/{id}', name: 'planning_show', methods: ['GET'])]
    public function showPlanning(int $id): JsonResponse
    {
        $planning = $this->planningRepository->find($id);
        if (!$planning) {
            return $this->json(['message' => 'Planning non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $data = $this->serializer->serialize($planning, 'json', ['groups' => 'planning:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // POST /api/plannings
    #[Route('/plannings', name: 'planning_create', methods: ['POST'])]
    public function createPlanning(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['heure_debut']) || !isset($data['heure_fin'])) {
            return $this->json(['message' => 'heure_debut et heure_fin sont obligatoires'], Response::HTTP_BAD_REQUEST);
        }

        $planning = new Planning();
        $planning->setHeureDebut(new \DateTime($data['heure_debut']));
        $planning->setHeureFin(new \DateTime($data['heure_fin']));

        if (isset($data['tatami_id'])) {
            $tatami = $this->tatamiRepository->find($data['tatami_id']);
            if (!$tatami) {
                return $this->json(['message' => 'Tatami non trouvé'], Response::HTTP_NOT_FOUND);
            }
            $planning->setTatami($tatami);
        }

        if (isset($data['match_id'])) {
            $match = $this->matchTourRepository->find($data['match_id']);
            if (!$match) {
                return $this->json(['message' => 'Match non trouvé'], Response::HTTP_NOT_FOUND);
            }
            $planning->setMatchTour($match);
        }

        $this->em->persist($planning);
        $this->em->flush();

        $result = $this->serializer->serialize($planning, 'json', ['groups' => 'planning:read']);
        return new JsonResponse($result, Response::HTTP_CREATED, [], true);
    }

    // PUT /api/plannings/{id}
    #[Route('/plannings/{id}', name: 'planning_update', methods: ['PUT'])]
    public function updatePlanning(int $id, Request $request): JsonResponse
    {
        $planning = $this->planningRepository->find($id);
        if (!$planning) {
            return $this->json(['message' => 'Planning non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['heure_debut'])) $planning->setHeureDebut(new \DateTime($data['heure_debut']));
        if (isset($data['heure_fin'])) $planning->setHeureFin(new \DateTime($data['heure_fin']));

        if (isset($data['tatami_id'])) {
            $tatami = $this->tatamiRepository->find($data['tatami_id']);
            if ($tatami) $planning->setTatami($tatami);
        }

        if (isset($data['match_id'])) {
            $match = $this->matchTourRepository->find($data['match_id']);
            if ($match) $planning->setMatchTour($match);
        }

        $this->em->flush();

        $result = $this->serializer->serialize($planning, 'json', ['groups' => 'planning:read']);
        return new JsonResponse($result, Response::HTTP_OK, [], true);
    }

    // DELETE /api/plannings/{id}
    #[Route('/plannings/{id}', name: 'planning_delete', methods: ['DELETE'])]
    public function deletePlanning(int $id): JsonResponse
    {
        $planning = $this->planningRepository->find($id);
        if (!$planning) {
            return $this->json(['message' => 'Planning non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($planning);
        $this->em->flush();

        return $this->json(['message' => 'Planning supprimé'], Response::HTTP_OK);
    }

    // ─── TATAMIS ────────────────────────────────────────────────────────────────

    // GET /api/tatamis
    #[Route('/tatamis', name: 'tatami_list', methods: ['GET'])]
    public function listTatamis(): JsonResponse
    {
        $tatamis = $this->tatamiRepository->findAll();
        $data = $this->serializer->serialize($tatamis, 'json', ['groups' => 'tatami:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // GET /api/tatamis/{id}
    #[Route('/tatamis/{id}', name: 'tatami_show', methods: ['GET'])]
    public function showTatami(int $id): JsonResponse
    {
        $tatami = $this->tatamiRepository->find($id);
        if (!$tatami) {
            return $this->json(['message' => 'Tatami non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $data = $this->serializer->serialize($tatami, 'json', ['groups' => 'tatami:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // POST /api/tatamis
    #[Route('/tatamis', name: 'tatami_create', methods: ['POST'])]
    public function createTatami(): JsonResponse
    {
        $tatami = new Tatami();
        $this->em->persist($tatami);
        $this->em->flush();

        $result = $this->serializer->serialize($tatami, 'json', ['groups' => 'tatami:read']);
        return new JsonResponse($result, Response::HTTP_CREATED, [], true);
    }

    // DELETE /api/tatamis/{id}
    #[Route('/tatamis/{id}', name: 'tatami_delete', methods: ['DELETE'])]
    public function deleteTatami(int $id): JsonResponse
    {
        $tatami = $this->tatamiRepository->find($id);
        if (!$tatami) {
            return $this->json(['message' => 'Tatami non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($tatami);
        $this->em->flush();

        return $this->json(['message' => 'Tatami supprimé'], Response::HTTP_OK);
    }
}