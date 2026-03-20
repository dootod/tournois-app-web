<?php

namespace App\Controller;

use App\Entity\Poule;
use App\Repository\PouleRepository;
use App\Repository\TournoiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/poules')]
class PouleController extends AbstractController
{
    public function __construct(
        private PouleRepository $pouleRepository,
        private TournoiRepository $tournoiRepository,
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
    ) {}

    // GET /api/poules
    #[Route('', name: 'poule_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $poules = $this->pouleRepository->findAll();
        $data = $this->serializer->serialize($poules, 'json', ['groups' => 'poule:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // GET /api/poules/{id}
    #[Route('/{id}', name: 'poule_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $poule = $this->pouleRepository->find($id);
        if (!$poule) {
            return $this->json(['message' => 'Poule non trouvée'], Response::HTTP_NOT_FOUND);
        }
        $data = $this->serializer->serialize($poule, 'json', ['groups' => 'poule:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // POST /api/poules
    #[Route('', name: 'poule_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['tournoi_id'])) {
            return $this->json(['message' => 'tournoi_id est obligatoire'], Response::HTTP_BAD_REQUEST);
        }

        $tournoi = $this->tournoiRepository->find($data['tournoi_id']);
        if (!$tournoi) {
            return $this->json(['message' => 'Tournoi non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $poule = new Poule();
        $poule->setCategorie($data['categorie'] ?? 'A');
        $poule->setTournoi($tournoi);

        $this->em->persist($poule);
        $this->em->flush();

        $result = $this->serializer->serialize($poule, 'json', ['groups' => 'poule:read']);
        return new JsonResponse($result, Response::HTTP_CREATED, [], true);
    }

    // PUT /api/poules/{id}
    #[Route('/{id}', name: 'poule_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $poule = $this->pouleRepository->find($id);
        if (!$poule) {
            return $this->json(['message' => 'Poule non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['categorie'])) $poule->setCategorie($data['categorie']);

        $this->em->flush();

        $result = $this->serializer->serialize($poule, 'json', ['groups' => 'poule:read']);
        return new JsonResponse($result, Response::HTTP_OK, [], true);
    }

    // DELETE /api/poules/{id}
    #[Route('/{id}', name: 'poule_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $poule = $this->pouleRepository->find($id);
        if (!$poule) {
            return $this->json(['message' => 'Poule non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($poule);
        $this->em->flush();

        return $this->json(['message' => 'Poule supprimée'], Response::HTTP_OK);
    }

    // GET /api/poules/{id}/matchs
    #[Route('/{id}/matchs', name: 'poule_matchs', methods: ['GET'])]
    public function matchs(int $id): JsonResponse
    {
        $poule = $this->pouleRepository->find($id);
        if (!$poule) {
            return $this->json(['message' => 'Poule non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($poule->getMatchTours(), 'json', ['groups' => 'matchtour:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}