<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use App\Repository\AdherentRepository;
use App\Repository\TournoiRepository;
use App\Repository\EquipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/participants')]
class ParticipantController extends AbstractController
{
    public function __construct(
        private ParticipantRepository $participantRepository,
        private AdherentRepository $adherentRepository,
        private TournoiRepository $tournoiRepository,
        private EquipeRepository $equipeRepository,
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
    ) {}

    // GET /api/participants
    #[Route('', name: 'participant_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $participants = $this->participantRepository->findAll();
        $data = $this->serializer->serialize($participants, 'json', ['groups' => 'participant:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // GET /api/participants/{id}
    #[Route('/{id}', name: 'participant_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $participant = $this->participantRepository->find($id);
        if (!$participant) {
            return $this->json(['message' => 'Participant non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $data = $this->serializer->serialize($participant, 'json', ['groups' => 'participant:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    // POST /api/participants — Inscription d'un adhérent à un tournoi
    #[Route('', name: 'participant_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['adherent_id']) || !isset($data['tournoi_id'])) {
            return $this->json(['message' => 'adherent_id et tournoi_id sont obligatoires'], Response::HTTP_BAD_REQUEST);
        }

        $adherent = $this->adherentRepository->find($data['adherent_id']);
        if (!$adherent) {
            return $this->json(['message' => 'Adhérent non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $tournoi = $this->tournoiRepository->find($data['tournoi_id']);
        if (!$tournoi) {
            return $this->json(['message' => 'Tournoi non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si l'adhérent est déjà inscrit à ce tournoi
        foreach ($tournoi->getParticipants() as $p) {
            if ($p->getAdherent() && $p->getAdherent()->getId() === $adherent->getId()) {
                return $this->json(['message' => 'Cet adhérent est déjà inscrit à ce tournoi'], Response::HTTP_CONFLICT);
            }
        }

        $participant = new Participant();
        $participant->setAdherent($adherent);
        $participant->setPoule($data['poule'] ?? null);
        $participant->setRangPoule($data['rang_poule'] ?? null);
        $participant->setRangTournoi($data['rang_tournoi'] ?? null);
        $participant->setPointsTournoi($data['points_tournoi'] ?? null);

        if (isset($data['equipe_id'])) {
            $equipe = $this->equipeRepository->find($data['equipe_id']);
            if ($equipe) {
                $participant->setEquipe($equipe);
            }
        }

        $tournoi->addParticipant($participant);

        $this->em->persist($participant);
        $this->em->flush();

        $result = $this->serializer->serialize($participant, 'json', ['groups' => 'participant:read']);
        return new JsonResponse($result, Response::HTTP_CREATED, [], true);
    }

    // PUT /api/participants/{id}
    #[Route('/{id}', name: 'participant_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $participant = $this->participantRepository->find($id);
        if (!$participant) {
            return $this->json(['message' => 'Participant non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['rang_poule'])) $participant->setRangPoule($data['rang_poule']);
        if (isset($data['rang_tournoi'])) $participant->setRangTournoi($data['rang_tournoi']);
        if (isset($data['points_tournoi'])) $participant->setPointsTournoi($data['points_tournoi']);
        if (isset($data['poule'])) $participant->setPoule($data['poule']);

        if (isset($data['equipe_id'])) {
            $equipe = $this->equipeRepository->find($data['equipe_id']);
            if ($equipe) $participant->setEquipe($equipe);
        }

        $this->em->flush();

        $result = $this->serializer->serialize($participant, 'json', ['groups' => 'participant:read']);
        return new JsonResponse($result, Response::HTTP_OK, [], true);
    }

    // DELETE /api/participants/{id} — Désinscription
    #[Route('/{id}', name: 'participant_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $participant = $this->participantRepository->find($id);
        if (!$participant) {
            return $this->json(['message' => 'Participant non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($participant);
        $this->em->flush();

        return $this->json(['message' => 'Participant supprimé'], Response::HTTP_OK);
    }

    // GET /api/participants/{id}/scores
    #[Route('/{id}/scores', name: 'participant_scores', methods: ['GET'])]
    public function scores(int $id): JsonResponse
    {
        $participant = $this->participantRepository->find($id);
        if (!$participant) {
            return $this->json(['message' => 'Participant non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($participant->getScores(), 'json', ['groups' => 'score:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}