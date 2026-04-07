<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\User;
use App\Repository\ParticipantRepository;
use App\Repository\TournoiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/me')]
#[IsGranted('ROLE_USER')]
class MeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TournoiRepository $tournois,
        private ParticipantRepository $participants,
        private SerializerInterface $serializer,
    ) {}

    private function currentAdherent(): ?\App\Entity\Adherent
    {
        /** @var User $u */
        $u = $this->getUser();
        return $u?->getAdherent();
    }

    #[Route('/adherent', name: 'me_adherent_update', methods: ['PUT'])]
    public function updateAdherent(Request $request): JsonResponse
    {
        $adh = $this->currentAdherent();
        if (!$adh) return $this->json(['error' => 'No adherent linked to this account'], 403);

        $data = json_decode($request->getContent(), true) ?: [];
        if (isset($data['nom'])) $adh->setNom($data['nom']);
        if (isset($data['prenom'])) $adh->setPrenom($data['prenom']);
        if (isset($data['date_naissance'])) $adh->setDateNaissance(new \DateTime($data['date_naissance']));
        if (isset($data['ceinture'])) $adh->setCeinture($data['ceinture']);
        if (array_key_exists('poids', $data)) $adh->setPoids($data['poids'] !== null ? (string)$data['poids'] : null);
        if (array_key_exists('genre', $data)) $adh->setGenre($data['genre']);

        $this->em->flush();
        return new JsonResponse($this->serializer->serialize($adh, 'json', ['groups' => 'adherent:read']), 200, [], true);
    }

    #[Route('/tournois/{id}/inscription', name: 'me_tournoi_inscrire', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function inscrire(int $id): JsonResponse
    {
        $adh = $this->currentAdherent();
        if (!$adh) return $this->json(['error' => 'No adherent linked to this account'], 403);

        $tournoi = $this->tournois->find($id);
        if (!$tournoi) return $this->json(['error' => 'Tournoi not found'], 404);
        if ($tournoi->getEtat() !== 'ouvert') {
            return $this->json(['error' => 'Ce tournoi n\'accepte plus d\'inscriptions'], 400);
        }

        // Refuse double inscription
        foreach ($this->participants->findBy(['adherent' => $adh]) as $p) {
            if ($p->getTournois()->contains($tournoi)) {
                return $this->json(['error' => 'Déjà inscrit à ce tournoi'], 409);
            }
        }

        $p = new Participant();
        $p->setAdherent($adh);
        $p->setPaye(false);
        $p->addTournoi($tournoi);
        $this->em->persist($p);
        $this->em->flush();

        return new JsonResponse($this->serializer->serialize($p, 'json', ['groups' => 'participant:read']), 201, [], true);
    }

    #[Route('/tournois/{id}/inscription', name: 'me_tournoi_desinscrire', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function desinscrire(int $id): JsonResponse
    {
        $adh = $this->currentAdherent();
        if (!$adh) return $this->json(['error' => 'No adherent linked to this account'], 403);

        $tournoi = $this->tournois->find($id);
        if (!$tournoi) return $this->json(['error' => 'Tournoi not found'], 404);

        foreach ($this->participants->findBy(['adherent' => $adh]) as $p) {
            if ($p->getTournois()->contains($tournoi)) {
                if ($p->isPaye()) {
                    return $this->json(['error' => 'Inscription déjà payée, contactez un administrateur'], 400);
                }
                $this->em->remove($p);
                $this->em->flush();
                return new JsonResponse(null, 204);
            }
        }
        return $this->json(['error' => 'Non inscrit à ce tournoi'], 404);
    }

    #[Route('/tournois', name: 'me_tournois', methods: ['GET'])]
    public function mesTournois(): JsonResponse
    {
        $adh = $this->currentAdherent();
        if (!$adh) return $this->json(['error' => 'No adherent linked'], 403);

        $tournois = [];
        foreach ($this->participants->findBy(['adherent' => $adh]) as $p) {
            foreach ($p->getTournois() as $t) {
                $tournois[$t->getId()] = $t;
            }
        }
        return new JsonResponse($this->serializer->serialize(array_values($tournois), 'json', ['groups' => 'tournoi:read']), 200, [], true);
    }

    #[Route('/scores', name: 'me_scores', methods: ['GET'])]
    public function mesScores(): JsonResponse
    {
        $adh = $this->currentAdherent();
        if (!$adh) return $this->json(['error' => 'No adherent linked'], 403);

        $out = [];
        foreach ($this->participants->findBy(['adherent' => $adh]) as $p) {
            foreach ($p->getScores() as $s) {
                $out[] = [
                    'id' => $s->getId(),
                    'score' => $s->getScore(),
                    'gagnant' => $s->isGagnant(),
                    'disqualification' => $s->isDisqualification(),
                    'participant_id' => $p->getId(),
                ];
            }
        }
        return $this->json($out);
    }
}
