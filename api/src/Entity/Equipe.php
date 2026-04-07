<?php

namespace App\Entity;

use App\Repository\EquipeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EquipeRepository::class)]
class Equipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['equipe:read', 'participant:read', 'matchtour:read', 'tournoi:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['equipe:read', 'participant:read', 'matchtour:read', 'tournoi:read'])]
    private ?string $nom = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['equipe:read'])]
    private ?int $rang_equipe = null;

    #[ORM\ManyToOne(inversedBy: 'equipes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tournoi $tournoi = null;

    /**
     * @var Collection<int, Participant>
     */
    #[ORM\OneToMany(targetEntity: Participant::class, mappedBy: 'equipe')]
    #[Groups(['equipe:read'])]
    private Collection $participants;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getRangEquipe(): ?int { return $this->rang_equipe; }
    public function setRangEquipe(?int $rang_equipe): static { $this->rang_equipe = $rang_equipe; return $this; }

    public function getTournoi(): ?Tournoi { return $this->tournoi; }
    public function setTournoi(?Tournoi $tournoi): static { $this->tournoi = $tournoi; return $this; }

    public function getParticipants(): Collection { return $this->participants; }

    public function addParticipant(Participant $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setEquipe($this);
        }
        return $this;
    }

    public function removeParticipant(Participant $participant): static
    {
        if ($this->participants->removeElement($participant)) {
            if ($participant->getEquipe() === $this) {
                $participant->setEquipe(null);
            }
        }
        return $this;
    }
}
