<?php

namespace App\Controller;

use App\Entity\Equipe;
use App\Repository\EquipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/equipes')]
class EquipeController extends AbstractController
{
    public function __construct(
        private EquipeRepository $equipeRepository,
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
    ) {}

    // GET /api/equipes
    #[Route('', name: 'equipe_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $equipes = $this->equipeRepository->findAll();
        $data = $this->serializer->serialize($equipes, 'json', ['groups' => 'equipe:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // GET /api/equipes/{id}
    #[Route('/{id}', name: 'equipe_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $equipe = $this->equipeRepository->find($id);
        if (!$equipe) {
            return $this->json(['message' => 'Équipe non trouvée'], Response::HTTP_NOT_FOUND);
        }
        $data = $this->serializer->serialize($equipe, 'json', ['groups' => 'equipe:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // POST /api/equipes
    #[Route('', name: 'equipe_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        $equipe = new Equipe();
        $equipe->setRangEquipe($data['rang_equipe'] ?? 0);

        $this->em->persist($equipe);
        $this->em->flush();

        $result = $this->serializer->serialize($equipe, 'json', ['groups' => 'equipe:read']);
        return new JsonResponse($result, Response::HTTP_CREATED, [], true);
    }

    // PUT /api/equipes/{id}
    #[Route('/{id}', name: 'equipe_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $equipe = $this->equipeRepository->find($id);
        if (!$equipe) {
            return $this->json(['message' => 'Équipe non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['rang_equipe'])) $equipe->setRangEquipe($data['rang_equipe']);

        $this->em->flush();

        $result = $this->serializer->serialize($equipe, 'json', ['groups' => 'equipe:read']);
        return new JsonResponse($result, Response::HTTP_OK, [], true);
    }

    // DELETE /api/equipes/{id}
    #[Route('/{id}', name: 'equipe_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $equipe = $this->equipeRepository->find($id);
        if (!$equipe) {
            return $this->json(['message' => 'Équipe non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($equipe);
        $this->em->flush();

        return $this->json(['message' => 'Équipe supprimée'], Response::HTTP_OK);
    }

    // GET /api/equipes/{id}/participants
    #[Route('/{id}/participants', name: 'equipe_participants', methods: ['GET'])]
    public function participants(int $id): JsonResponse
    {
        $equipe = $this->equipeRepository->find($id);
        if (!$equipe) {
            return $this->json(['message' => 'Équipe non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($equipe->getParticipants(), 'json', ['groups' => 'participant:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}