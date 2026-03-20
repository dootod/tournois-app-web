<?php

namespace App\Entity;

use App\Repository\PlanningRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PlanningRepository::class)]
class Planning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['planning:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups(['planning:read'])]
    private ?\DateTime $heure_debut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups(['planning:read'])]
    private ?\DateTime $heure_fin = null;

    #[ORM\ManyToOne(inversedBy: 'plannings')]
    #[Groups(['planning:read'])]
    private ?Tatami $tatami = null;

    #[ORM\ManyToOne(inversedBy: 'plannings')]
    #[Groups(['planning:read'])]
    private ?MatchTour $matchTour = null;

    public function getId(): ?int { return $this->id; }

    public function getHeureDebut(): ?\DateTime { return $this->heure_debut; }
    public function setHeureDebut(\DateTime $heure_debut): static { $this->heure_debut = $heure_debut; return $this; }

    public function getHeureFin(): ?\DateTime { return $this->heure_fin; }
    public function setHeureFin(\DateTime $heure_fin): static { $this->heure_fin = $heure_fin; return $this; }

    public function getTatami(): ?Tatami { return $this->tatami; }
    public function setTatami(?Tatami $tatami): static { $this->tatami = $tatami; return $this; }

    public function getMatchTour(): ?MatchTour { return $this->matchTour; }
    public function setMatchTour(?MatchTour $matchTour): static { $this->matchTour = $matchTour; return $this; }
}