<?php

namespace App\Entity;

use App\Repository\ParametreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ParametreRepository::class)]
class Parametre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['tournoi:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['tournoi:read'])]
    private ?string $temps_combat = null;

    #[ORM\Column]
    #[Groups(['tournoi:read'])]
    private ?int $min_poule = null;

    #[ORM\Column]
    #[Groups(['tournoi:read'])]
    private ?int $max_participants = null;

    #[ORM\Column]
    #[Groups(['tournoi:read'])]
    private ?int $max_poule = null;

    #[ORM\Column]
    #[Groups(['tournoi:read'])]
    private ?int $nb_tatamis = null;

    #[ORM\OneToOne(inversedBy: 'parametre')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tournoi $tournoi = null;

    public function getId(): ?int { return $this->id; }

    public function getTempsCombat(): ?string { return $this->temps_combat; }
    public function setTempsCombat(string $temps_combat): static { $this->temps_combat = $temps_combat; return $this; }

    public function getMinPoule(): ?int { return $this->min_poule; }
    public function setMinPoule(int $min_poule): static { $this->min_poule = $min_poule; return $this; }

    public function getMaxParticipants(): ?int { return $this->max_participants; }
    public function setMaxParticipants(int $max_participants): static { $this->max_participants = $max_participants; return $this; }

    public function getMaxPoule(): ?int { return $this->max_poule; }
    public function setMaxPoule(int $max_poule): static { $this->max_poule = $max_poule; return $this; }

    public function getNbTatamis(): ?int { return $this->nb_tatamis; }
    public function setNbTatamis(int $nb_tatamis): static { $this->nb_tatamis = $nb_tatamis; return $this; }

    public function getTournoi(): ?Tournoi { return $this->tournoi; }
    public function setTournoi(?Tournoi $tournoi): static { $this->tournoi = $tournoi; return $this; }
}
