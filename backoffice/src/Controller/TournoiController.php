<?php

namespace App\Controller;

use App\Service\ApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournois')]
class TournoiController extends AbstractController
{
    const ROUNDS = ['16ème de finale', 'Quarts de finale', 'Demi-finales', '3ème place', 'Finale'];

    public function __construct(private ApiClient $api) {}

    #[Route('', name: 'app_tournois')]
    public function index(): Response
    {
        return $this->render('tournoi/index.html.twig', [
            'tournois' => $this->api->getTournois(),
        ]);
    }

    #[Route('/nouveau', name: 'app_tournoi_new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = [
                'equipe'             => $request->request->get('equipe') === '1',
                'date'               => $request->request->get('date'),
                'etat'               => 'ouvert',
                'prix_participation' => $request->request->get('prix_participation') ?: null,
                'iban'               => $request->request->get('iban') ?: null,
                'parametre' => [
                    'temps_combat'     => $request->request->get('temps_combat', '5.00'),
                    'min_poule'        => (int) $request->request->get('min_poule', 3),
                    'max_participants' => (int) $request->request->get('max_participants', 32),
                    'max_poule'        => (int) $request->request->get('max_poule', 6),
                    'nb_tatamis'       => (int) $request->request->get('nb_tatamis', 2),
                ],
            ];

            $result = $this->api->createTournoi($data);

            if ($result && isset($result['id'])) {
                $this->addFlash('success', 'Tournoi créé avec succès.');
                return $this->redirectToRoute('app_tournoi_show', ['id' => $result['id']]);
            }

            $this->addFlash('error', $this->api->getLastError() ?? 'Erreur lors de la création du tournoi.');
        }

        return $this->render('tournoi/form.html.twig', ['tournoi' => null, 'mode' => 'create']);
    }

    #[Route('/{id}', name: 'app_tournoi_show')]
    public function show(int $id): Response
    {
        $data = $this->api->getTournoiShow($id);
        if (!$data) {
            $this->addFlash('error', 'Tournoi introuvable.');
            return $this->redirectToRoute('app_tournois');
        }

        $tournoi      = $data['tournoi'];
        $participants = $data['participants'];
        $equipes      = $data['equipes'];
        $adherents    = $data['adherents'];
        $allMatchs    = $data['matchs'];
        $poules       = $tournoi['poules'] ?? [];

        // Grouper les matchs par poule et par phase (scores déjà embarqués)
        $matchsQualifParPoule = array_fill_keys(array_column($poules, 'id'), []);
        $matchsElimination    = [];

        foreach ($allMatchs as $match) {
            $pouleId = $match['poule']['id'] ?? null;
            if ($pouleId === null) continue;

            if (($match['phase'] ?? 'qualification') === 'elimination') {
                $matchsElimination[] = $match;
            } else {
                $matchsQualifParPoule[$pouleId][] = $match;
            }
        }

        $elimParRound = [];
        foreach (self::ROUNDS as $r) {
            $elimParRound[$r] = array_values(array_filter($matchsElimination, fn($m) => ($m['round'] ?? '') === $r));
        }
        $elimParRound['Autres'] = array_values(array_filter($matchsElimination, fn($m) => empty($m['round'])));

        $creneaux = [];
        $start = new \DateTime('08:00');
        $end   = new \DateTime('20:00');
        while ($start <= $end) {
            $creneaux[] = $start->format('H:i');
            $start->modify('+15 minutes');
        }

        return $this->render('tournoi/show.html.twig', [
            'tournoi'              => $tournoi,
            'participants'         => $participants,
            'poules'               => $poules,
            'adherents'            => $adherents,
            'equipes'              => $equipes,
            'matchsQualifParPoule' => $matchsQualifParPoule,
            'elimParRound'         => $elimParRound,
            'rounds'               => self::ROUNDS,
            'creneaux'             => $creneaux,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_tournoi_edit')]
    public function edit(int $id, Request $request): Response
    {
        $tournoi = $this->api->getTournoi($id);
        if (!$tournoi) return $this->redirectToRoute('app_tournois');

        if ($request->isMethod('POST')) {
            $data = [
                'date'               => $request->request->get('date'),
                'etat'               => $request->request->get('etat'),
                'prix_participation' => $request->request->get('prix_participation') ?: null,
                'iban'               => $request->request->get('iban') ?: null,
            ];

            $result = $this->api->updateTournoi($id, $data);
            if ($result !== null) {
                $this->addFlash('success', 'Tournoi mis à jour.');
                return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
            }
            $this->addFlash('error', $this->api->getLastError() ?? 'Erreur lors de la mise à jour.');
        }

        return $this->render('tournoi/form.html.twig', ['tournoi' => $tournoi, 'mode' => 'edit']);
    }

    #[Route('/{id}/etat', name: 'app_tournoi_etat', methods: ['POST'])]
    public function updateEtat(int $id, Request $request): Response
    {
        $this->api->updateEtatTournoi($id, $request->request->get('etat'));
        $this->addFlash('success', 'État mis à jour.');
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/{id}/supprimer', name: 'app_tournoi_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $this->api->deleteTournoi($id);
        $this->addFlash('success', 'Tournoi supprimé.');
        return $this->redirectToRoute('app_tournois');
    }

    // ── Inscriptions individuelles ────────────────────────────────────────────

    #[Route('/{id}/inscrire', name: 'app_tournoi_inscrire', methods: ['POST'])]
    public function inscrire(int $id, Request $request): Response
    {
        $result = $this->api->createParticipant([
            'adherent_id' => (int) $request->request->get('adherent_id'),
            'tournoi_id'  => $id,
        ]);

        if ($result && isset($result['id'])) {
            $this->addFlash('success', 'Participant inscrit.');
        } else {
            $this->addFlash('error', $this->api->getLastError() ?? 'Erreur inscription.');
        }
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/{id}/desinscrire/{participantId}', name: 'app_tournoi_desinscrire', methods: ['POST'])]
    public function desinscrire(int $id, int $participantId): Response
    {
        $this->api->deleteParticipant($participantId);
        $this->addFlash('success', 'Participant retiré.');
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    // ── Paiement ──────────────────────────────────────────────────────────────

    #[Route('/{id}/paiement/{participantId}', name: 'app_tournoi_paiement', methods: ['POST'])]
    public function togglePaiement(int $id, int $participantId): Response
    {
        $result = $this->api->togglePaiement($participantId);
        if ($result) {
            $this->addFlash('success', ($result['paye'] ?? false) ? 'Paiement confirmé.' : 'Paiement annulé.');
        } else {
            $this->addFlash('error', $this->api->getLastError() ?? 'Erreur paiement.');
        }
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    // ── Poules ────────────────────────────────────────────────────────────────

    #[Route('/{id}/poule/ajouter', name: 'app_tournoi_poule_add', methods: ['POST'])]
    public function addPoule(int $id, Request $request): Response
    {
        $this->api->createPoule(['tournoi_id' => $id, 'categorie' => $request->request->get('categorie', 'A')]);
        $this->addFlash('success', 'Poule ajoutée.');
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/{id}/poule/{pouleId}/supprimer', name: 'app_tournoi_poule_delete', methods: ['POST'])]
    public function deletePoule(int $id, int $pouleId): Response
    {
        $this->api->deletePoule($pouleId);
        $this->addFlash('success', 'Poule supprimée.');
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    // ── Équipes ───────────────────────────────────────────────────────────────

    #[Route('/{id}/equipe/ajouter', name: 'app_tournoi_equipe_add', methods: ['POST'])]
    public function addEquipe(int $id, Request $request): Response
    {
        $result = $this->api->createEquipe([
            'tournoi_id' => $id,
            'nom'        => $request->request->get('nom'),
        ]);

        if ($result && isset($result['id'])) {
            $this->addFlash('success', 'Équipe créée.');
        } else {
            $this->addFlash('error', $this->api->getLastError() ?? 'Erreur création équipe.');
        }
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/{id}/equipe/{equipeId}/supprimer', name: 'app_tournoi_equipe_delete', methods: ['POST'])]
    public function deleteEquipe(int $id, int $equipeId): Response
    {
        $this->api->deleteEquipe($equipeId);
        $this->addFlash('success', 'Équipe supprimée.');
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/{id}/equipe/{equipeId}/membre', name: 'app_tournoi_equipe_membre_add', methods: ['POST'])]
    public function addMembreEquipe(int $id, int $equipeId, Request $request): Response
    {
        $result = $this->api->addMembreEquipe($equipeId, (int) $request->request->get('adherent_id'));

        if ($result) {
            $this->addFlash('success', 'Membre ajouté à l\'équipe.');
        } else {
            $this->addFlash('error', $this->api->getLastError() ?? 'Erreur ajout membre.');
        }
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/{id}/equipe/{equipeId}/membre/{participantId}/retirer', name: 'app_tournoi_equipe_membre_remove', methods: ['POST'])]
    public function removeMembreEquipe(int $id, int $equipeId, int $participantId): Response
    {
        $this->api->removeMembreEquipe($equipeId, $participantId);
        $this->addFlash('success', 'Membre retiré de l\'équipe.');
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    // ── Matchs qualification ──────────────────────────────────────────────────

    #[Route('/{id}/match/ajouter', name: 'app_tournoi_match_add', methods: ['POST'])]
    public function addMatch(int $id, Request $request): Response
    {
        $pouleId    = (int) $request->request->get('poule_id');
        $payload    = ['poule_id' => $pouleId, 'phase' => 'qualification'];

        if ($p1 = $request->request->get('participant1_id')) $payload['participant1_id'] = (int)$p1;
        if ($p2 = $request->request->get('participant2_id')) $payload['participant2_id'] = (int)$p2;
        if ($e1 = $request->request->get('equipe1_id')) $payload['equipe1_id'] = (int)$e1;
        if ($e2 = $request->request->get('equipe2_id')) $payload['equipe2_id'] = (int)$e2;
        if ($t  = $request->request->get('tatami'))    $payload['tatami'] = (int)$t;
        if ($hd = $request->request->get('heure_debut')) $payload['heure_debut'] = $hd;
        if ($hf = $request->request->get('heure_fin'))   $payload['heure_fin']   = $hf;

        $result = $this->api->createMatch($payload);

        if ($result && isset($result['id'])) {
            $this->addFlash('success', 'Match de qualification créé.');
        } else {
            $this->addFlash('error', $this->api->getLastError() ?? 'Erreur création match.');
        }
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    // ── Matchs phase finale ───────────────────────────────────────────────────

    #[Route('/{id}/match/finale/ajouter', name: 'app_tournoi_match_finale_add', methods: ['POST'])]
    public function addMatchFinale(int $id, Request $request): Response
    {
        $pouleId = (int) $request->request->get('poule_id');
        $payload = [
            'poule_id' => $pouleId,
            'phase'    => 'elimination',
            'round'    => $request->request->get('round'),
        ];

        if ($p1 = $request->request->get('participant1_id')) $payload['participant1_id'] = (int)$p1;
        if ($p2 = $request->request->get('participant2_id')) $payload['participant2_id'] = (int)$p2;
        if ($e1 = $request->request->get('equipe1_id')) $payload['equipe1_id'] = (int)$e1;
        if ($e2 = $request->request->get('equipe2_id')) $payload['equipe2_id'] = (int)$e2;
        if ($t  = $request->request->get('tatami'))    $payload['tatami'] = (int)$t;
        if ($hd = $request->request->get('heure_debut')) $payload['heure_debut'] = $hd;
        if ($hf = $request->request->get('heure_fin'))   $payload['heure_fin']   = $hf;

        $result = $this->api->createMatch($payload);

        if ($result && isset($result['id'])) {
            $this->addFlash('success', 'Match de phase finale créé.');
        } else {
            $this->addFlash('error', $this->api->getLastError() ?? 'Erreur création match finale.');
        }
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    // ── Match commun : planifier, résultat équipe, supprimer ─────────────────

    #[Route('/{id}/match/{matchId}/planifier', name: 'app_tournoi_match_planifier', methods: ['POST'])]
    public function planifierMatch(int $id, int $matchId, Request $request): Response
    {
        $result = $this->api->updateMatch($matchId, [
            'tatami'      => (int) $request->request->get('tatami'),
            'heure_debut' => $request->request->get('heure_debut'),
            'heure_fin'   => $request->request->get('heure_fin'),
        ]);

        if ($result !== null) {
            $this->addFlash('success', 'Créneau attribué.');
        } else {
            $this->addFlash('error', $this->api->getLastError() ?? 'Erreur planification.');
        }
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/{id}/match/{matchId}/resultat-equipe', name: 'app_tournoi_match_resultat_equipe', methods: ['POST'])]
    public function resultatMatchEquipe(int $id, int $matchId, Request $request): Response
    {
        $se1 = $request->request->get('score_equipe1');
        $se2 = $request->request->get('score_equipe2');

        $result = $this->api->updateMatch($matchId, [
            'score_equipe1' => $se1 !== '' ? (int)$se1 : null,
            'score_equipe2' => $se2 !== '' ? (int)$se2 : null,
        ]);

        if ($result !== null) {
            $this->addFlash('success', 'Résultat enregistré.');
        } else {
            $this->addFlash('error', $this->api->getLastError() ?? 'Erreur résultat.');
        }
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/{id}/match/{matchId}/supprimer', name: 'app_tournoi_match_delete', methods: ['POST'])]
    public function deleteMatch(int $id, int $matchId): Response
    {
        $this->api->deleteMatch($matchId);
        $this->addFlash('success', 'Match supprimé.');
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    // ── Scores individuels ────────────────────────────────────────────────────

    #[Route('/{id}/match/{matchId}/score', name: 'app_tournoi_score_add', methods: ['POST'])]
    public function addScore(int $id, int $matchId, Request $request): Response
    {
        $participantId = (int) $request->request->get('participant_id');
        $score         = (int) $request->request->get('score', 0);
        $gagnant       = $request->request->get('gagnant') === '1';
        $disq          = $request->request->get('disqualification') === '1';

        $scores = $this->api->getMatchScores($matchId);
        $existingId = null;
        foreach ($scores as $s) {
            if (isset($s['participant']['id']) && $s['participant']['id'] === $participantId) {
                $existingId = $s['id'];
                break;
            }
        }

        if ($existingId) {
            $result = $this->api->updateScore($existingId, ['score' => $score, 'gagnant' => $gagnant, 'disqualification' => $disq]);
        } else {
            $result = $this->api->createScore(['match_id' => $matchId, 'participant_id' => $participantId, 'score' => $score, 'gagnant' => $gagnant, 'disqualification' => $disq]);
        }

        if ($result !== null) {
            $this->addFlash('success', 'Score enregistré.');
        } else {
            $this->addFlash('error', $this->api->getLastError() ?? 'Erreur score.');
        }
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/{id}/match/{matchId}/score/{scoreId}/supprimer', name: 'app_tournoi_score_delete', methods: ['POST'])]
    public function deleteScore(int $id, int $matchId, int $scoreId): Response
    {
        $this->api->deleteScore($scoreId);
        $this->addFlash('success', 'Score supprimé.');
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }
}
