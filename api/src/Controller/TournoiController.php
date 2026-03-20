<?php

namespace App\Controller;

use App\Entity\Tournoi;
use App\Entity\Parametre;
use App\Repository\TournoiRepository;
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
        $tournoi->setEquipe($data['equipe'] ?? false);
        $tournoi->setEtat($data['etat'] ?? 'ouvert');
        if (isset($data['date'])) {
            $tournoi->setDate(new \DateTime($data['date']));
        } else {
            $tournoi->setDate(new \DateTime());
        }

        // Création des paramètres liés au tournoi si fournis
        if (isset($data['parametre'])) {
            $p = $data['parametre'];
            $parametre = new Parametre();
            $parametre->setTempsCombat($p['temps_combat'] ?? '5.00');
            $parametre->setMaxEquipes($p['max_equipes'] ?? 0);
            $parametre->setMinPoule($p['min_poule'] ?? 3);
            $parametre->setMaxParticipants($p['max_participants'] ?? 32);
            $parametre->setMaxPoule($p['max_poule'] ?? 6);
            $parametre->setNbSurfaces($p['nb_surfaces'] ?? 1);
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

        if (isset($data['equipe'])) $tournoi->setEquipe($data['equipe']);
        if (isset($data['etat'])) $tournoi->setEtat($data['etat']);
        if (isset($data['date'])) $tournoi->setDate(new \DateTime($data['date']));

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
}