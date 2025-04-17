<?php

namespace App\Entity;

use App\Repository\SortieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SortieRepository::class)]
class Sortie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateHeureDebut = null;

    #[ORM\Column]
    private ?int $duree = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateLimiteInscription = null;

    #[ORM\Column]
    private ?int $nbInscriptionsMax = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $infosSortie = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lieu $lieu = null;

    #[ORM\ManyToOne(inversedBy: 'sortiesOrganisees')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Participant $organisateur = null;

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: false)]
    private ?Campus $campus = null;

    #[ORM\ManyToMany(targetEntity: Participant::class, inversedBy: 'sortiesInscrit')]
    private Collection $participants;

    #[ORM\Column(length: 20)]
    private ?string $etat = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motifAnnulation = null;

    #[ORM\Column]
    private bool $isArchived = false;

    const ETAT_CREEE = 'Créée';
    const ETAT_OUVERTE = 'Ouverte';
    const ETAT_CLOTUREE = 'Clôturée';
    const ETAT_ACTIVITE_EN_COURS = 'Activité en cours';
    const ETAT_PASSEE = 'Passée';
    const ETAT_ANNULEE = 'Annulée';

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->etat = self::ETAT_CREEE;
        $this->isArchived = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDateHeureDebut(): ?\DateTimeInterface
    {
        return $this->dateHeureDebut;
    }

    public function setDateHeureDebut(\DateTimeInterface $dateHeureDebut): static
    {
        $this->dateHeureDebut = $dateHeureDebut;
        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): static
    {
        $this->duree = $duree;
        return $this;
    }

    public function getDateLimiteInscription(): ?\DateTimeInterface
    {
        return $this->dateLimiteInscription;
    }

    public function setDateLimiteInscription(\DateTimeInterface $dateLimiteInscription): static
    {
        $this->dateLimiteInscription = $dateLimiteInscription;
        return $this;
    }

    public function getNbInscriptionsMax(): ?int
    {
        return $this->nbInscriptionsMax;
    }

    public function setNbInscriptionsMax(int $nbInscriptionsMax): static
    {
        $this->nbInscriptionsMax = $nbInscriptionsMax;
        return $this;
    }

    public function getInfosSortie(): ?string
    {
        return $this->infosSortie;
    }

    public function setInfosSortie(?string $infosSortie): static
    {
        $this->infosSortie = $infosSortie;
        return $this;
    }

    public function getLieu(): ?Lieu
    {
        return $this->lieu;
    }

    public function setLieu(?Lieu $lieu): static
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getOrganisateur(): ?Participant
    {
        return $this->organisateur;
    }

    public function setOrganisateur(?Participant $organisateur): static
    {
        $this->organisateur = $organisateur;
        return $this;
    }

    public function getCampus(): ?Campus
    {
        return $this->campus;
    }

    public function setCampus(?Campus $campus): static
    {
        $this->campus = $campus;
        return $this;
    }

    public function getParticipants(): Collection
    {
        return $this->participants;
    }

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

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        $this->etat = $etat;
        return $this;
    }

    public function getMotifAnnulation(): ?string
    {
        return $this->motifAnnulation;
    }

    public function setMotifAnnulation(?string $motifAnnulation): static
    {
        $this->motifAnnulation = $motifAnnulation;
        return $this;
    }

    public function isArchived(): bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): static
    {
        $this->isArchived = $isArchived;
        return $this;
    }

    public function canRegister(Participant $participant): bool
    {
        if ($this->etat !== self::ETAT_OUVERTE) {
            return false;
        }

        if ($this->participants->contains($participant)) {
            return false;
        }

        if ($this->participants->count() >= $this->nbInscriptionsMax) {
            return false;
        }

        if ($this->dateLimiteInscription < new \DateTime()) {
            return false;
        }

        return true;
    }

    public function canUnregister(Participant $participant): bool
    {
        if (!$this->participants->contains($participant)) {
            return false;
        }

        if ($this->dateHeureDebut < new \DateTime()) {
            return false;
        }

        return true;
    }

    public function canCancel(): bool
    {
        return $this->etat !== self::ETAT_ANNULEE 
            && $this->dateHeureDebut > new \DateTime();
    }

    public function cancel(string $motif): void
    {
        if (!$this->canCancel()) {
            throw new \RuntimeException('Cette sortie ne peut pas être annulée.');
        }

        $this->etat = self::ETAT_ANNULEE;
        $this->motifAnnulation = $motif;
    }

    public function updateStatus(): void
    {
        $now = new \DateTime();

        // If already cancelled or archived, don't update
        if ($this->etat === self::ETAT_ANNULEE || $this->isArchived) {
            return;
        }

        // Check if should be archived (more than one month old)
        $oneMonthAgo = (new \DateTime())->modify('-1 month');
        if ($this->dateHeureDebut < $oneMonthAgo) {
            $this->isArchived = true;
            return;
        }

        // Update status based on current time
        if ($this->dateHeureDebut > $now) {
            if ($this->dateLimiteInscription < $now) {
                $this->etat = self::ETAT_CLOTUREE;
            } else {
                // If not past deadline and not already in a final state, set to Ouverte
                if ($this->etat !== self::ETAT_ACTIVITE_EN_COURS && 
                    $this->etat !== self::ETAT_PASSEE && 
                    $this->etat !== self::ETAT_ANNULEE) {
                    $this->etat = self::ETAT_OUVERTE;
                }
            }
        } elseif ($this->dateHeureDebut <= $now && $this->dateHeureDebut->modify("+{$this->duree} minutes") > $now) {
            $this->etat = self::ETAT_ACTIVITE_EN_COURS;
        } else {
            $this->etat = self::ETAT_PASSEE;
        }
    }
} 