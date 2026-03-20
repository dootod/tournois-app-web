<?php

namespace App\Entity;

use App\Repository\ScoreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ScoreRepository::class)]
class Score
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['score:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['score:read'])]
    private ?bool $gagnant = null;

    #[ORM\Column]
    #[Groups(['score:read'])]
    private ?int $score = null;

    #[ORM\Column]
    #[Groups(['score:read'])]
    private ?bool $disqualification = null;

    #[ORM\ManyToOne(inversedBy: 'scores')]
    #[Groups(['score:read'])]
    private ?Participant $participant = null;

    /**
     * @var Collection<int, MatchTour>
     */
    #[ORM\ManyToMany(targetEntity: MatchTour::class, inversedBy: 'scores')]
    #[Groups(['score:read'])]
    private Collection $matchTours;

    public function __construct()
    {
        $this->matchTours = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function isGagnant(): ?bool { return $this->gagnant; }
    public function setGagnant(bool $gagnant): static { $this->gagnant = $gagnant; return $this; }

    public function getScore(): ?int { return $this->score; }
    public function setScore(int $score): static { $this->score = $score; return $this; }

    public function isDisqualification(): ?bool { return $this->disqualification; }
    public function setDisqualification(bool $disqualification): static { $this->disqualification = $disqualification; return $this; }

    public function getParticipant(): ?Participant { return $this->participant; }
    public function setParticipant(?Participant $participant): static { $this->participant = $participant; return $this; }

    public function getMatchTours(): Collection { return $this->matchTours; }

    public function addMatchTour(MatchTour $matchTour): static
    {
        if (!$this->matchTours->contains($matchTour)) {
            $this->matchTours->add($matchTour);
        }
        return $this;
    }

    public function removeMatchTour(MatchTour $matchTour): static
    {
        $this->matchTours->removeElement($matchTour);
        return $this;
    }
}