<?php

namespace App\Entity;

use App\Repository\MatchTourRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MatchTourRepository::class)]
class MatchTour
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['matchtour:read', 'planning:read', 'score:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'matchTours')]
    #[Groups(['matchtour:read'])]
    private ?Poule $poule = null;

    /**
     * @var Collection<int, Score>
     */
    #[ORM\ManyToMany(targetEntity: Score::class, mappedBy: 'matchTours')]
    private Collection $scores;

    /**
     * @var Collection<int, Planning>
     */
    #[ORM\OneToMany(targetEntity: Planning::class, mappedBy: 'matchTour')]
    private Collection $plannings;

    public function __construct()
    {
        $this->scores = new ArrayCollection();
        $this->plannings = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getPoule(): ?Poule { return $this->poule; }
    public function setPoule(?Poule $poule): static { $this->poule = $poule; return $this; }

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

    public function getPlannings(): Collection { return $this->plannings; }

    public function addPlanning(Planning $planning): static
    {
        if (!$this->plannings->contains($planning)) {
            $this->plannings->add($planning);
            $planning->setMatchTour($this);
        }
        return $this;
    }

    public function removePlanning(Planning $planning): static
    {
        if ($this->plannings->removeElement($planning)) {
            if ($planning->getMatchTour() === $this) {
                $planning->setMatchTour(null);
            }
        }
        return $this;
    }
}