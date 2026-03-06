<?php

namespace App\Entity;

use App\Repository\ParametreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParametreRepository::class)]
class Parametre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $temps_combat = null;

    #[ORM\Column]
    private ?int $max_equipes = null;

    #[ORM\Column]
    private ?int $min_poule = null;

    #[ORM\Column]
    private ?int $max_participants = null;

    #[ORM\Column]
    private ?int $max_poule = null;

    #[ORM\Column]
    private ?int $nb_surfaces = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTempsCombat(): ?string
    {
        return $this->temps_combat;
    }

    public function setTempsCombat(string $temps_combat): static
    {
        $this->temps_combat = $temps_combat;

        return $this;
    }

    public function getMaxEquipes(): ?int
    {
        return $this->max_equipes;
    }

    public function setMaxEquipes(int $max_equipes): static
    {
        $this->max_equipes = $max_equipes;

        return $this;
    }

    public function getMinPoule(): ?int
    {
        return $this->min_poule;
    }

    public function setMinPoule(int $min_poule): static
    {
        $this->min_poule = $min_poule;

        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->max_participants;
    }

    public function setMaxParticipants(int $max_participants): static
    {
        $this->max_participants = $max_participants;

        return $this;
    }

    public function getMaxPoule(): ?int
    {
        return $this->max_poule;
    }

    public function setMaxPoule(int $max_poule): static
    {
        $this->max_poule = $max_poule;

        return $this;
    }

    public function getNbSurfaces(): ?int
    {
        return $this->nb_surfaces;
    }

    public function setNbSurfaces(int $nb_surfaces): static
    {
        $this->nb_surfaces = $nb_surfaces;

        return $this;
    }
}
