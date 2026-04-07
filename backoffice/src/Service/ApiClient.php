<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiClient
{
    private string $baseUrl;
    private ?string $lastError = null;

    public function __construct(
        private HttpClientInterface $httpClient,
        string $apiBaseUrl = 'http://localhost/tournois-app-web-main/api/public/api'
    ) {
        $this->baseUrl = rtrim($apiBaseUrl, '/');
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function request(string $method, string $endpoint, array $data = []): mixed
    {
        $this->lastError = null;
        $options = [
            'headers' => ['Content-Type' => 'application/json'],
        ];

        if (!empty($data)) {
            $options['json'] = $data;
        }

        try {
            $response = $this->httpClient->request($method, $this->baseUrl . $endpoint, $options);
            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

            if (empty($content)) return null;

            $decoded = json_decode($content, true);

            if ($statusCode >= 400) {
                if (isset($decoded['errors']) && is_array($decoded['errors'])) {
                    $this->lastError = implode(', ', $decoded['errors']);
                } elseif (isset($decoded['message'])) {
                    $this->lastError = $decoded['message'];
                } else {
                    $this->lastError = 'Erreur HTTP ' . $statusCode;
                }
                return null;
            }

            return $decoded;
        } catch (\Exception $e) {
            $this->lastError = 'Impossible de contacter l\'API : ' . $e->getMessage();
            return null;
        }
    }

    // ─── ADHÉRENTS ──────────────────────────────────────────────────────────

    public function getAdherents(): array { return $this->request('GET', '/adherents') ?? []; }
    public function getAdherent(int $id): ?array { return $this->request('GET', '/adherents/' . $id); }
    public function createAdherent(array $data): ?array { return $this->request('POST', '/adherents', $data); }
    public function updateAdherent(int $id, array $data): ?array { return $this->request('PUT', '/adherents/' . $id, $data); }
    public function deleteAdherent(int $id): ?array { return $this->request('DELETE', '/adherents/' . $id); }
    public function renouvelerAdherent(int $id): ?array { return $this->request('PATCH', '/adherents/' . $id . '/renouvellement'); }

    // ─── TOURNOIS ───────────────────────────────────────────────────────────

    public function getTournois(): array { return $this->request('GET', '/tournois') ?? []; }
    public function getTournoi(int $id): ?array { return $this->request('GET', '/tournois/' . $id); }
    public function createTournoi(array $data): ?array { return $this->request('POST', '/tournois', $data); }
    public function updateTournoi(int $id, array $data): ?array { return $this->request('PUT', '/tournois/' . $id, $data); }
    public function updateEtatTournoi(int $id, string $etat): ?array { return $this->request('PATCH', '/tournois/' . $id . '/etat', ['etat' => $etat]); }
    public function deleteTournoi(int $id): ?array { return $this->request('DELETE', '/tournois/' . $id); }
    public function getTournoiParticipants(int $id): array { return $this->request('GET', '/tournois/' . $id . '/participants') ?? []; }
    public function getTournoiPoules(int $id): array { return $this->request('GET', '/tournois/' . $id . '/poules') ?? []; }
    public function getTournoiEquipes(int $id): array { return $this->request('GET', '/tournois/' . $id . '/equipes') ?? []; }
    public function getTournoiMatchs(int $id): array { return $this->request('GET', '/tournois/' . $id . '/matchs') ?? []; }
    public function getTournoiShow(int $id): ?array { return $this->request('GET', '/tournois/' . $id . '/show'); }

    // ─── PARTICIPANTS ────────────────────────────────────────────────────────

    public function getParticipants(): array { return $this->request('GET', '/participants') ?? []; }
    public function createParticipant(array $data): ?array { return $this->request('POST', '/participants', $data); }
    public function updateParticipant(int $id, array $data): ?array { return $this->request('PUT', '/participants/' . $id, $data); }
    public function togglePaiement(int $id): ?array { return $this->request('PATCH', '/participants/' . $id . '/paiement'); }
    public function deleteParticipant(int $id): ?array { return $this->request('DELETE', '/participants/' . $id); }

    // ─── ÉQUIPES ────────────────────────────────────────────────────────────

    public function getEquipe(int $id): ?array { return $this->request('GET', '/equipes/' . $id); }
    public function createEquipe(array $data): ?array { return $this->request('POST', '/equipes', $data); }
    public function updateEquipe(int $id, array $data): ?array { return $this->request('PUT', '/equipes/' . $id, $data); }
    public function deleteEquipe(int $id): ?array { return $this->request('DELETE', '/equipes/' . $id); }
    public function addMembreEquipe(int $equipeId, int $adherentId): ?array { return $this->request('POST', '/equipes/' . $equipeId . '/membres', ['adherent_id' => $adherentId]); }
    public function removeMembreEquipe(int $equipeId, int $participantId): ?array { return $this->request('DELETE', '/equipes/' . $equipeId . '/membres/' . $participantId); }

    // ─── POULES ─────────────────────────────────────────────────────────────

    public function getPoules(): array { return $this->request('GET', '/poules') ?? []; }
    public function createPoule(array $data): ?array { return $this->request('POST', '/poules', $data); }
    public function deletePoule(int $id): ?array { return $this->request('DELETE', '/poules/' . $id); }
    public function getPouleMatchs(int $id): array { return $this->request('GET', '/poules/' . $id . '/matchs') ?? []; }

    // ─── MATCHS ─────────────────────────────────────────────────────────────

    public function getMatch(int $id): ?array { return $this->request('GET', '/matchs/' . $id); }
    public function createMatch(array $data): ?array { return $this->request('POST', '/matchs', $data); }
    public function updateMatch(int $id, array $data): ?array { return $this->request('PUT', '/matchs/' . $id, $data); }
    public function deleteMatch(int $id): ?array { return $this->request('DELETE', '/matchs/' . $id); }
    public function getMatchScores(int $id): array { return $this->request('GET', '/matchs/' . $id . '/scores') ?? []; }

    // ─── SCORES ─────────────────────────────────────────────────────────────

    public function createScore(array $data): ?array { return $this->request('POST', '/scores', $data); }
    public function updateScore(int $id, array $data): ?array { return $this->request('PUT', '/scores/' . $id, $data); }
    public function deleteScore(int $id): ?array { return $this->request('DELETE', '/scores/' . $id); }
}
