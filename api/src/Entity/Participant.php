<?php

namespace App\Entity;

use App\Repository\ParticipantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
class Participant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $rang_poule = null;

    #[ORM\Column(nullable: true)]
    private ?int $rang_tournoi = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $points_tournoi = null;

    #[ORM\Column(nullable: true)]
    private ?int $poule = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    private ?Adherent $adherent = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    private ?Equipe $equipe = null;

    /**
     * @var Collection<int, Score>
     */
    #[ORM\OneToMany(targetEntity: Score::class, mappedBy: 'participant')]
    private Collection $scores;

    /**
     * @var Collection<int, Tournoi>
     */
    #[ORM\ManyToMany(targetEntity: Tournoi::class, mappedBy: 'participants')]
    private Collection $tournois;

    public function __construct()
    {
        $this->scores = new ArrayCollection();
        $this->tournois = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRangPoule(): ?int
    {
        return $this->rang_poule;
    }

    public function setRangPoule(?int $rang_poule): static
    {
        $this->rang_poule = $rang_poule;

        return $this;
    }

    public function getRangTournoi(): ?int
    {
        return $this->rang_tournoi;
    }

    public function setRangTournoi(?int $rang_tournoi): static
    {
        $this->rang_tournoi = $rang_tournoi;

        return $this;
    }

    public function getPointsTournoi(): ?string
    {
        return $this->points_tournoi;
    }

    public function setPointsTournoi(?string $points_tournoi): static
    {
        $this->points_tournoi = $points_tournoi;

        return $this;
    }

    public function getPoule(): ?int
    {
        return $this->poule;
    }

    public function setPoule(?int $poule): static
    {
        $this->poule = $poule;

        return $this;
    }

    public function getAdherent(): ?Adherent
    {
        return $this->adherent;
    }

    public function setAdherent(?Adherent $adherent): static
    {
        $this->adherent = $adherent;

        return $this;
    }

    public function getEquipe(): ?Equipe
    {
        return $this->equipe;
    }

    public function setEquipe(?Equipe $equipe): static
    {
        $this->equipe = $equipe;

        return $this;
    }

    /**
     * @return Collection<int, Score>
     */
    public function getScores(): Collection
    {
        return $this->scores;
    }

    public function addScore(Score $score): static
    {
        if (!$this->scores->contains($score)) {
            $this->scores->add($score);
            $score->setParticipant($this);
        }

        return $this;
    }

    public function removeScore(Score $score): static
    {
        if ($this->scores->removeElement($score)) {
            // set the owning side to null (unless already changed)
            if ($score->getParticipant() === $this) {
                $score->setParticipant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tournoi>
     */
    public function getTournois(): Collection
    {
        return $this->tournois;
    }

    public function addTournoi(Tournoi $tournoi): static
    {
        if (!$this->tournois->contains($tournoi)) {
            $this->tournois->add($tournoi);
            $tournoi->addParticipant($this);
        }

        return $this;
    }

    public function removeTournoi(Tournoi $tournoi): static
    {
        if ($this->tournois->removeElement($tournoi)) {
            $tournoi->removeParticipant($this);
        }

        return $this;
    }
}
