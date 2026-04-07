<?php

namespace App\Controller;

use App\Entity\Equipe;
use App\Entity\Participant;
use App\Repository\EquipeRepository;
use App\Repository\TournoiRepository;
use App\Repository\AdherentRepository;
use App\Repository\ParticipantRepository;
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
        private TournoiRepository $tournoiRepository,
        private AdherentRepository $adherentRepository,
        private ParticipantRepository $participantRepository,
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
    ) {}

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

    // POST /api/equipes — Créer une équipe dans un tournoi
    #[Route('', name: 'equipe_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['tournoi_id']) || !isset($data['nom'])) {
            return $this->json(['message' => 'tournoi_id et nom sont obligatoires'], Response::HTTP_BAD_REQUEST);
        }

        $tournoi = $this->tournoiRepository->find($data['tournoi_id']);
        if (!$tournoi) {
            return $this->json(['message' => 'Tournoi non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $equipe = new Equipe();
        $equipe->setNom($data['nom']);
        $equipe->setTournoi($tournoi);
        $equipe->setRangEquipe($data['rang_equipe'] ?? null);

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

        if (isset($data['nom'])) $equipe->setNom($data['nom']);
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

    // POST /api/equipes/{id}/membres — Ajouter un adhérent à l'équipe
    #[Route('/{id}/membres', name: 'equipe_add_membre', methods: ['POST'])]
    public function addMembre(int $id, Request $request): JsonResponse
    {
        $equipe = $this->equipeRepository->find($id);
        if (!$equipe) {
            return $this->json(['message' => 'Équipe non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['adherent_id'])) {
            return $this->json(['message' => 'adherent_id est obligatoire'], Response::HTTP_BAD_REQUEST);
        }

        $adherent = $this->adherentRepository->find($data['adherent_id']);
        if (!$adherent) {
            return $this->json(['message' => 'Adhérent non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $tournoi = $equipe->getTournoi();

        // Vérifier si l'adhérent est déjà dans une équipe de ce tournoi
        foreach ($tournoi->getEquipes() as $e) {
            foreach ($e->getParticipants() as $p) {
                if ($p->getAdherent() && $p->getAdherent()->getId() === $adherent->getId()) {
                    return $this->json(['message' => 'Cet adhérent est déjà dans une équipe de ce tournoi'], Response::HTTP_CONFLICT);
                }
            }
        }

        // Créer ou récupérer le participant dans ce tournoi
        $participant = null;
        foreach ($tournoi->getParticipants() as $p) {
            if ($p->getAdherent() && $p->getAdherent()->getId() === $adherent->getId()) {
                $participant = $p;
                break;
            }
        }

        if (!$participant) {
            $participant = new Participant();
            $participant->setAdherent($adherent);
            $participant->setPaye(false);
            $tournoi->addParticipant($participant);
            $this->em->persist($participant);
        }

        $participant->setEquipe($equipe);
        $this->em->flush();

        $result = $this->serializer->serialize($equipe, 'json', ['groups' => 'equipe:read']);
        return new JsonResponse($result, Response::HTTP_OK, [], true);
    }

    // DELETE /api/equipes/{id}/membres/{participantId}
    #[Route('/{id}/membres/{participantId}', name: 'equipe_remove_membre', methods: ['DELETE'])]
    public function removeMembre(int $id, int $participantId): JsonResponse
    {
        $equipe = $this->equipeRepository->find($id);
        if (!$equipe) {
            return $this->json(['message' => 'Équipe non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $participant = $this->participantRepository->find($participantId);
        if (!$participant || $participant->getEquipe()?->getId() !== $id) {
            return $this->json(['message' => 'Membre non trouvé dans cette équipe'], Response::HTTP_NOT_FOUND);
        }

        $participant->setEquipe(null);
        $this->em->flush();

        return $this->json(['message' => 'Membre retiré de l\'équipe'], Response::HTTP_OK);
    }
}
