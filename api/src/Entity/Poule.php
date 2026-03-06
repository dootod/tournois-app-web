<?php

namespace App\Entity;

use App\Repository\PouleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PouleRepository::class)]
class Poule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $categorie = null;

    #[ORM\ManyToOne(inversedBy: 'poules')]
    private ?Tournoi $tournoi = null;

    /**
     * @var Collection<int, MatchTour>
     */
    #[ORM\OneToMany(targetEntity: MatchTour::class, mappedBy: 'poule')]
    private Collection $matchTours;

    public function __construct()
    {
        $this->matchTours = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getTournoi(): ?Tournoi
    {
        return $this->tournoi;
    }

    public function setTournoi(?Tournoi $tournoi): static
    {
        $this->tournoi = $tournoi;

        return $this;
    }

    /**
     * @return Collection<int, MatchTour>
     */
    public function getMatchTours(): Collection
    {
        return $this->matchTours;
    }

    public function addMatchTour(MatchTour $matchTour): static
    {
        if (!$this->matchTours->contains($matchTour)) {
            $this->matchTours->add($matchTour);
            $matchTour->setPoule($this);
        }

        return $this;
    }

    public function removeMatchTour(MatchTour $matchTour): static
    {
        if ($this->matchTours->removeElement($matchTour)) {
            // set the owning side to null (unless already changed)
            if ($matchTour->getPoule() === $this) {
                $matchTour->setPoule(null);
            }
        }

        return $this;
    }
}
