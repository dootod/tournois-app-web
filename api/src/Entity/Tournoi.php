<?php

namespace App\Entity;

use App\Repository\TournoiRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TournoiRepository::class)]
class Tournoi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['tournoi:read', 'participant:read', 'poule:read', 'equipe:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['tournoi:read'])]
    private bool $equipe = false;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['tournoi:read'])]
    private ?\DateTime $date = null;

    #[ORM\Column(length: 50)]
    #[Groups(['tournoi:read'])]
    private ?string $etat = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['tournoi:read'])]
    private ?string $prix_participation = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['tournoi:read'])]
    private ?string $iban = null;

    /**
     * @var Collection<int, Poule>
     */
    #[ORM\OneToMany(targetEntity: Poule::class, mappedBy: 'tournoi')]
    #[Groups(['tournoi:read'])]
    private Collection $poules;

    /**
     * @var Collection<int, Participant>
     */
    #[ORM\ManyToMany(targetEntity: Participant::class, inversedBy: 'tournois')]
    private Collection $participants;

    /**
     * @var Collection<int, Equipe>
     */
    #[ORM\OneToMany(targetEntity: Equipe::class, mappedBy: 'tournoi', cascade: ['remove'])]
    #[Groups(['tournoi:read'])]
    private Collection $equipes;

    #[ORM\OneToOne(mappedBy: 'tournoi', cascade: ['persist', 'remove'])]
    #[Groups(['tournoi:read'])]
    private ?Parametre $parametre = null;

    public function __construct()
    {
        $this->poules = new ArrayCollection();
        $this->participants = new ArrayCollection();
        $this->equipes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function isEquipe(): bool { return $this->equipe; }
    public function setEquipe(bool $equipe): static { $this->equipe = $equipe; return $this; }

    public function getDate(): ?\DateTime { return $this->date; }
    public function setDate(\DateTime $date): static { $this->date = $date; return $this; }

    public function getEtat(): ?string { return $this->etat; }
    public function setEtat(string $etat): static { $this->etat = $etat; return $this; }

    public function getPrixParticipation(): ?string { return $this->prix_participation; }
    public function setPrixParticipation(?string $prix_participation): static { $this->prix_participation = $prix_participation; return $this; }

    public function getIban(): ?string { return $this->iban; }
    public function setIban(?string $iban): static { $this->iban = $iban; return $this; }

    public function getPoules(): Collection { return $this->poules; }

    public function addPoule(Poule $poule): static
    {
        if (!$this->poules->contains($poule)) {
            $this->poules->add($poule);
            $poule->setTournoi($this);
        }
        return $this;
    }

    public function removePoule(Poule $poule): static
    {
        if ($this->poules->removeElement($poule)) {
            if ($poule->getTournoi() === $this) {
                $poule->setTournoi(null);
            }
        }
        return $this;
    }

    public function getParticipants(): Collection { return $this->participants; }

    public function addParticipant(Participant $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
        }
        return $this;
    }

    public function removeParticipant(Participant $participant): static
    {
        $this->participants->removeElement($participant);
        return $this;
    }

    public function getEquipes(): Collection { return $this->equipes; }

    public function addEquipe(Equipe $equipe): static
    {
        if (!$this->equipes->contains($equipe)) {
            $this->equipes->add($equipe);
            $equipe->setTournoi($this);
        }
        return $this;
    }

    public function removeEquipe(Equipe $equipe): static
    {
        $this->equipes->removeElement($equipe);
        return $this;
    }

    public function getParametre(): ?Parametre { return $this->parametre; }

    public function setParametre(?Parametre $parametre): static
    {
        if ($parametre !== null && $parametre->getTournoi() !== $this) {
            $parametre->setTournoi($this);
        }
        $this->parametre = $parametre;
        return $this;
    }
}
