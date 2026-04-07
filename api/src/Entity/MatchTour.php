<?php

namespace App\Entity;

use App\Repository\MatchTourRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MatchTourRepository::class)]
class MatchTour
{
    // Phases possibles
    const PHASE_QUALIFICATION = 'qualification';
    const PHASE_ELIMINATION   = 'elimination';

    // Tours de phase finale
    const ROUNDS = ['16ème de finale', 'Quarts de finale', 'Demi-finales', '3ème place', 'Finale'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['matchtour:read', 'score:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Groups(['matchtour:read'])]
    private string $phase = self::PHASE_QUALIFICATION;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['matchtour:read'])]
    private ?string $round = null;

    #[ORM\ManyToOne(inversedBy: 'matchTours')]
    #[Groups(['matchtour:read'])]
    private ?Poule $poule = null;

    // ── Combattants individuels ──────────────────────────────────────────────

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['matchtour:read'])]
    private ?Participant $participant1 = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['matchtour:read'])]
    private ?Participant $participant2 = null;

    // ── Équipes (tournoi par équipes) ────────────────────────────────────────

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['matchtour:read'])]
    private ?Equipe $equipe1 = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['matchtour:read'])]
    private ?Equipe $equipe2 = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['matchtour:read'])]
    private ?int $score_equipe1 = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['matchtour:read'])]
    private ?int $score_equipe2 = null;

    // ── Planification ────────────────────────────────────────────────────────

    #[ORM\Column(nullable: true)]
    #[Groups(['matchtour:read'])]
    private ?int $tatami = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Groups(['matchtour:read'])]
    private ?\DateTime $heure_debut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Groups(['matchtour:read'])]
    private ?\DateTime $heure_fin = null;

    /**
     * @var Collection<int, Score>
     */
    #[ORM\ManyToMany(targetEntity: Score::class, mappedBy: 'matchTours')]
    #[Groups(['matchtour:read'])]
    private Collection $scores;

    public function __construct()
    {
        $this->scores = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getPhase(): string { return $this->phase; }
    public function setPhase(string $phase): static { $this->phase = $phase; return $this; }

    public function getRound(): ?string { return $this->round; }
    public function setRound(?string $round): static { $this->round = $round; return $this; }

    public function getPoule(): ?Poule { return $this->poule; }
    public function setPoule(?Poule $poule): static { $this->poule = $poule; return $this; }

    public function getParticipant1(): ?Participant { return $this->participant1; }
    public function setParticipant1(?Participant $p): static { $this->participant1 = $p; return $this; }

    public function getParticipant2(): ?Participant { return $this->participant2; }
    public function setParticipant2(?Participant $p): static { $this->participant2 = $p; return $this; }

    public function getEquipe1(): ?Equipe { return $this->equipe1; }
    public function setEquipe1(?Equipe $e): static { $this->equipe1 = $e; return $this; }

    public function getEquipe2(): ?Equipe { return $this->equipe2; }
    public function setEquipe2(?Equipe $e): static { $this->equipe2 = $e; return $this; }

    public function getScoreEquipe1(): ?int { return $this->score_equipe1; }
    public function setScoreEquipe1(?int $s): static { $this->score_equipe1 = $s; return $this; }

    public function getScoreEquipe2(): ?int { return $this->score_equipe2; }
    public function setScoreEquipe2(?int $s): static { $this->score_equipe2 = $s; return $this; }

    public function getTatami(): ?int { return $this->tatami; }
    public function setTatami(?int $tatami): static { $this->tatami = $tatami; return $this; }

    public function getHeureDebut(): ?\DateTime { return $this->heure_debut; }
    public function setHeureDebut(?\DateTime $h): static { $this->heure_debut = $h; return $this; }

    public function getHeureFin(): ?\DateTime { return $this->heure_fin; }
    public function setHeureFin(?\DateTime $h): static { $this->heure_fin = $h; return $this; }

    public function getScores(): Collection { return $this->scores; }

    public function addScore(Score $score): static
    {
        if (!$this->scores->contains($score)) {
            $this->scores->add($score);
            $score->addMatchTour($this);
        }
        return $this;
    }

    public function removeScore(Score $score): static
    {
        if ($this->scores->removeElement($score)) {
            $score->removeMatchTour($this);
        }
        return $this;
    }
}
