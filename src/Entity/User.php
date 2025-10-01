<?php

namespace App\Entity;

use App\Enum\Gender;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email', 'nickname'])]
#[ORM\UniqueConstraint(name: 'UNIQUE_IDENTIFIERS', fields: ['nickname', 'email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $nickname;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private string $password;

    #[ORM\Column(length: 100)]
    private string $firstname;

    #[ORM\Column(length: 100)]
    private string $lastname;

    #[ORM\Column(length: 50)]
    private string $email;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTime $birthDate;

    #[ORM\Column(length: 12)]
    private string $phone;

    #[ORM\Column]
    private bool $activated;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private \DateTimeImmutable $creationDate;

    // TODO Gérer comment connaitre la dernière fois que quelqu'un s'est connecté (peut pas fonctionner avec Timestampable)
    #[ORM\Column]
    private \DateTime $lastLogin;

    #[ORM\Column]
    private bool $public;

    #[ORM\Column(type: 'string', enumType: Gender::class)]
    private Gender $gender;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private Picture $profilePicture;

    #[ORM\Column]
    private int $totalTime;

    #[ORM\Column]
    private int $totalHunt;

    /**
     * @var Collection<int, Team>
     */
    #[ORM\OneToMany(targetEntity: Team::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $ownedTeams;

    /**
     * @var Collection<int, Team>
     */
    #[ORM\ManyToMany(targetEntity: Team::class, mappedBy: 'members')]
    private Collection $teams;

    /**
     * @var Collection<int, TreasureHunt>
     */
    #[ORM\OneToMany(targetEntity: TreasureHunt::class, mappedBy: 'owner')]
    private Collection $treasureHunts;

    /**
     * @var Collection<int, ParticipateRiddle>
     */
    #[ORM\OneToMany(targetEntity: ParticipateRiddle::class, mappedBy: 'hunter', orphanRemoval: true)]
    private Collection $participateRiddles;

    public function __construct()
    {
        $this->ownedTeams = new ArrayCollection();
        $this->teams = new ArrayCollection();
        $this->treasureHunts = new ArrayCollection();
        $this->participateRiddles = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): static
    {
        $this->nickname = $nickname;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->nickname;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getBirthDate(): \DateTime
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTime $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function isActivated(): bool
    {
        return $this->activated;
    }

    public function setActivated(bool $activated): static
    {
        $this->activated = $activated;

        return $this;
    }

    public function getCreationDate(): \DateTimeImmutable
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeImmutable $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getLastLogin(): \DateTime
    {
        return $this->lastLogin;
    }

    public function setLastLogin(\DateTime $lastLogin): static
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function getGender(): Gender
    {
        return $this->gender;
    }

    public function setGender(Gender $gender): void
    {
        $this->gender = $gender;
    }

    public function getProfilePicture(): Picture
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(Picture $profilePicture): static
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    public function getTotalTime(): int
    {
        return $this->totalTime;
    }

    public function setTotalTime(int $totalTime): static
    {
        $this->totalTime = $totalTime;

        return $this;
    }

    public function getTotalHunt(): int
    {
        return $this->totalHunt;
    }

    public function setTotalHunt(int $totalHunt): static
    {
        $this->totalHunt = $totalHunt;

        return $this;
    }

    /**
     * @return Collection<int, Team>
     */
    public function getOwnedTeams(): Collection
    {
        return $this->ownedTeams;
    }

    public function addOwnedTeam(Team $ownedTeam): static
    {
        if (!$this->ownedTeams->contains($ownedTeam)) {
            $this->ownedTeams->add($ownedTeam);
            $ownedTeam->setOwner($this);
        }

        return $this;
    }

    public function removeOwnedTeam(Team $ownedTeam): static
    {
        if ($this->ownedTeams->removeElement($ownedTeam)) {
            // set the owning side to null (unless already changed)
            if ($ownedTeam->getOwner() === $this) {
                $ownedTeam->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): static
    {
        if (!$this->teams->contains($team)) {
            $this->teams->add($team);
            $team->addMember($this);
        }

        return $this;
    }

    public function removeTeam(Team $team): static
    {
        if ($this->teams->removeElement($team)) {
            $team->removeMember($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, TreasureHunt>
     */
    public function getTreasureHunts(): Collection
    {
        return $this->treasureHunts;
    }

    public function addTreasureHunt(TreasureHunt $treasureHunt): static
    {
        if (!$this->treasureHunts->contains($treasureHunt)) {
            $this->treasureHunts->add($treasureHunt);
            $treasureHunt->setOwner($this);
        }

        return $this;
    }

    public function removeTreasureHunt(TreasureHunt $treasureHunt): static
    {
        if ($this->treasureHunts->removeElement($treasureHunt)) {
            // set the owning side to null (unless already changed)
            if ($treasureHunt->getOwner() === $this) {
                $treasureHunt->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ParticipateRiddle>
     */
    public function getParticipateRiddles(): Collection
    {
        return $this->participateRiddles;
    }

    public function addParticipateRiddle(ParticipateRiddle $participateRiddle): static
    {
        if (!$this->participateRiddles->contains($participateRiddle)) {
            $this->participateRiddles->add($participateRiddle);
            $participateRiddle->setHunter($this);
        }

        return $this;
    }

    public function removeParticipateRiddle(ParticipateRiddle $participateRiddle): static
    {
        if ($this->participateRiddles->removeElement($participateRiddle)) {
            // set the owning side to null (unless already changed)
            if ($participateRiddle->getHunter() === $this) {
                $participateRiddle->setHunter(null);
            }
        }

        return $this;
    }
}
