<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\User;
use App\Repository\MatchTourRepository;
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
        private MatchTourRepository $matchs,
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
    public function inscrire(int $id, Request $request): JsonResponse
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

        $equipe = null;
        if ($tournoi->isEquipe()) {
            $data = json_decode($request->getContent(), true) ?: [];
            $equipeId = $data['equipe_id'] ?? null;
            if (!$equipeId) {
                return $this->json(['error' => 'Ce tournoi est en équipe : veuillez choisir une équipe'], 400);
            }
            foreach ($tournoi->getEquipes() as $e) {
                if ($e->getId() === (int)$equipeId) { $equipe = $e; break; }
            }
            if (!$equipe) {
                return $this->json(['error' => 'Équipe invalide pour ce tournoi'], 400);
            }
        }

        $p = new Participant();
        $p->setAdherent($adh);
        $p->setPaye(false);
        $p->addTournoi($tournoi);
        if ($equipe) $p->setEquipe($equipe);
        $this->em->persist($p);
        $this->em->flush();

        return new JsonResponse($this->serializer->serialize($p, 'json', ['groups' => 'participant:read']), 201, [], true);
    }

    #[Route('/tournois/{id}/equipes', name: 'me_tournoi_equipes', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function tournoiEquipes(int $id): JsonResponse
    {
        $tournoi = $this->tournois->find($id);
        if (!$tournoi) return $this->json(['error' => 'Tournoi not found'], 404);

        $out = [];
        foreach ($tournoi->getEquipes() as $e) {
            $out[] = ['id' => $e->getId(), 'nom' => $e->getNom()];
        }
        return $this->json($out);
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
                // Détache la relation ManyToMany au lieu de supprimer le participant
                // (évite les erreurs FK avec matchs/scores liés)
                $tournoi->removeParticipant($p);
                $p->getTournois()->removeElement($tournoi);
                // Si plus aucun tournoi et aucun score, supprimer complètement
                if ($p->getTournois()->isEmpty() && $p->getScores()->isEmpty()) {
                    $this->em->remove($p);
                }
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

        $out = [];
        foreach ($this->participants->findBy(['adherent' => $adh]) as $p) {
            foreach ($p->getTournois() as $t) {
                $out[$t->getId()] = [
                    'id' => $t->getId(),
                    'date' => $t->getDate()?->format('Y-m-d'),
                    'etat' => $t->getEtat(),
                    'equipe' => $t->isEquipe(),
                    'prix_participation' => $t->getPrixParticipation(),
                    'mon_equipe' => $p->getEquipe() ? [
                        'id' => $p->getEquipe()->getId(),
                        'nom' => $p->getEquipe()->getNom(),
                    ] : null,
                    'paye' => $p->isPaye(),
                ];
            }
        }
        return $this->json(array_values($out));
    }

    #[Route('/scores', name: 'me_scores', methods: ['GET'])]
    public function mesScores(): JsonResponse
    {
        $adh = $this->currentAdherent();
        if (!$adh) return $this->json(['error' => 'No adherent linked'], 403);

        $out = [];
        foreach ($this->participants->findBy(['adherent' => $adh]) as $p) {
            foreach ($p->getScores() as $s) {
                // Find related match (via matchTours) and adversaire
                $match = $s->getMatchTours()->first() ?: null;
                $adversaire = null;
                $phase = null;
                $round = null;
                $tournoiDate = null;
                if ($match) {
                    $phase = $match->getPhase();
                    $round = $match->getRound();
                    $opp = $match->getParticipant1() === $p ? $match->getParticipant2() : $match->getParticipant1();
                    if ($opp && $opp->getAdherent()) {
                        $adversaire = $opp->getAdherent()->getPrenom() . ' ' . $opp->getAdherent()->getNom();
                    } elseif ($match->getEquipe1() || $match->getEquipe2()) {
                        $myEq = $p->getEquipe();
                        $oppEq = $match->getEquipe1() === $myEq ? $match->getEquipe2() : $match->getEquipe1();
                        $adversaire = $oppEq?->getNom();
                    }
                    if ($poule = $match->getPoule()) {
                        $tournoiDate = $poule->getTournoi()?->getDate()?->format('Y-m-d');
                    }
                }
                $out[] = [
                    'id' => $s->getId(),
                    'score' => $s->getScore(),
                    'gagnant' => $s->isGagnant(),
                    'disqualification' => $s->isDisqualification(),
                    'adversaire' => $adversaire,
                    'phase' => $phase,
                    'round' => $round,
                    'date' => $tournoiDate,
                    'equipe' => $p->getEquipe()?->getNom(),
                ];
            }
        }
        return $this->json($out);
    }

    #[Route('/tournois/{id}/matchs', name: 'me_tournoi_matchs', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function mesMatchs(int $id): JsonResponse
    {
        $adh = $this->currentAdherent();
        if (!$adh) return $this->json(['error' => 'No adherent linked'], 403);

        $tournoi = $this->tournois->find($id);
        if (!$tournoi) return $this->json(['error' => 'Tournoi not found'], 404);

        $myParticipants = [];
        $myEquipes = [];
        foreach ($this->participants->findBy(['adherent' => $adh]) as $p) {
            if ($p->getTournois()->contains($tournoi)) {
                $myParticipants[] = $p;
                if ($p->getEquipe()) $myEquipes[] = $p->getEquipe();
            }
        }

        $out = [];
        foreach ($this->matchs->findAll() as $m) {
            $involved = false;
            $isP1 = false;
            foreach ($myParticipants as $mp) {
                if ($m->getParticipant1() === $mp) { $involved = true; $isP1 = true; break; }
                if ($m->getParticipant2() === $mp) { $involved = true; break; }
            }
            $isE1 = false;
            if (!$involved) {
                foreach ($myEquipes as $me) {
                    if ($m->getEquipe1() === $me) { $involved = true; $isE1 = true; break; }
                    if ($m->getEquipe2() === $me) { $involved = true; break; }
                }
            }
            if (!$involved) continue;

            // Match must belong to this tournoi (via poule or via equipes)
            $belongs = false;
            if ($m->getPoule() && $m->getPoule()->getTournoi() === $tournoi) $belongs = true;
            if ($m->getEquipe1() && $m->getEquipe1()->getTournoi() === $tournoi) $belongs = true;
            if ($m->getEquipe2() && $m->getEquipe2()->getTournoi() === $tournoi) $belongs = true;
            if (!$belongs) continue;

            $adversaire = null;
            if ($m->getParticipant1() || $m->getParticipant2()) {
                $opp = $isP1 ? $m->getParticipant2() : $m->getParticipant1();
                if ($opp && $opp->getAdherent()) {
                    $adversaire = $opp->getAdherent()->getPrenom() . ' ' . $opp->getAdherent()->getNom();
                }
            } else {
                $opp = $isE1 ? $m->getEquipe2() : $m->getEquipe1();
                $adversaire = $opp?->getNom();
            }

            $out[] = [
                'id' => $m->getId(),
                'phase' => $m->getPhase(),
                'round' => $m->getRound(),
                'tatami' => $m->getTatami(),
                'heure_debut' => $m->getHeureDebut()?->format('H:i'),
                'heure_fin' => $m->getHeureFin()?->format('H:i'),
                'adversaire' => $adversaire ?? '—',
            ];
        }
        return $this->json($out);
    }
}
