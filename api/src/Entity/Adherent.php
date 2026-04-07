<?php

namespace App\Entity;

use App\Repository\AdherentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AdherentRepository::class)]
class Adherent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['adherent:read', 'participant:read', 'matchtour:read', 'equipe:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['adherent:read', 'participant:read', 'matchtour:read', 'equipe:read'])]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    #[Groups(['adherent:read', 'participant:read', 'matchtour:read', 'equipe:read'])]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['adherent:read'])]
    private ?\DateTime $date_naissance = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['adherent:read'])]
    private ?\DateTime $date_adhesion = null;

    #[ORM\Column(length: 50)]
    #[Groups(['adherent:read', 'participant:read', 'matchtour:read', 'equipe:read'])]
    private ?string $ceinture = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    #[Groups(['adherent:read', 'participant:read', 'matchtour:read', 'equipe:read'])]
    private ?string $poids = null;

    /**
     * @var Collection<int, Participant>
     */
    #[ORM\OneToMany(targetEntity: Participant::class, mappedBy: 'adherent')]
    private Collection $participants;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getDateNaissance(): ?\DateTime { return $this->date_naissance; }
    public function setDateNaissance(\DateTime $date_naissance): static { $this->date_naissance = $date_naissance; return $this; }

    public function getDateAdhesion(): ?\DateTime { return $this->date_adhesion; }
    public function setDateAdhesion(\DateTime $date_adhesion): static { $this->date_adhesion = $date_adhesion; return $this; }

    public function getCeinture(): ?string { return $this->ceinture; }
    public function setCeinture(string $ceinture): static { $this->ceinture = $ceinture; return $this; }

    public function getPoids(): ?string { return $this->poids; }
    public function setPoids(?string $poids): static { $this->poids = $poids; return $this; }

    public function getParticipants(): Collection { return $this->participants; }

    public function addParticipant(Participant $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setAdherent($this);
        }
        return $this;
    }

    public function removeParticipant(Participant $participant): static
    {
        if ($this->participants->removeElement($participant)) {
            if ($participant->getAdherent() === $this) {
                $participant->setAdherent(null);
            }
        }
        return $this;
    }
}