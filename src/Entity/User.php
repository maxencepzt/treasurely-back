<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Controller\User\DeleteUserPictureController;
use App\Controller\User\GetUserPictureController;
use App\Controller\User\UploadUserPictureController;
use App\Enum\Gender;
use App\Repository\UserRepository;
use App\State\MeProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['nickname'], message: 'Ce pseudo est déjà utilisé.')]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà utilisée.')]
#[ORM\UniqueConstraint(name: 'UNIQ_NICKNAME', fields: ['nickname'])]
#[ORM\UniqueConstraint(name: 'UNIQ_EMAIL', fields: ['email'])]
#[ApiResource(
    operations: [
        // Get collection of users (non-sensitive data only)
        new GetCollection(
            openapi: new Operation(
                summary: 'List of users',
                description: 'Retrieve all users with non-sensitive data only. Each entry represents a "User" resource. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['user:read']],
            security: "is_granted('ROLE_USER')",
        ),
        // Get details of a specific user by ID (only if the user is activated or the requester is an admin)
        new Get(
            openapi: new Operation(
                summary: 'User details',
                description: 'Retrieve detailed information about a specific user by their ID. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['user:read']],
            security: "is_granted('ROLE_USER') and (is_granted('ROLE_ADMIN') or object.isActivated())",
        ),
        // Get the currently authenticated user's information
        new Get(
            uriTemplate: 'me',
            openapi: new Operation(
                summary: 'Get my account',
                description: 'Retrieve the currently authenticated user\'s information. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['user:me', 'user:id']],
            security: "is_granted('ROLE_USER') and object == user",
            provider: MeProvider::class,
        ),
        new Get(
            uriTemplate: '/users/{id}/picture',
            formats: [
                'png' => 'image/png',
            ],
            controller: GetUserPictureController::class,
            openapi: new Operation(
                summary: 'Retrieves the picture from the user',
                description: 'Retrieves the PNG image corresponding to the picture of the user',
            ),
            normalizationContext: ['groups' => ['user:picture']],
            security: "is_granted('ROLE_USER')",
        ),
        new Get(
            uriTemplate: '/users/{id}/teams',
            openapi: new Operation(
                summary: 'All teams where the user is present.',
                description: 'Retrieves all the teams where the user is present and/or is the owner.'
            ),
            normalizationContext: ['groups' => ['user:teams']],
            security: "is_granted('ROLE_USER')"
        ),
        new Post(
            uriTemplate: '/users/{id}/picture',
            controller: UploadUserPictureController::class,
            openapi: new Operation(
                summary: 'Upload a picture for the user',
                description: 'Upload an image to set as the picture for the user',
            ),
            normalizationContext: ['groups' => ['user:picture']],
            security: "is_granted('ROLE_USER') and object == user",
        ),
        // Register a new user
        new Post(
            uriTemplate: 'register',
            openapi: new Operation(
                summary: 'User registration',
                description: 'Register a new user by providing necessary details. This endpoint is publicly accessible.'
            ),
            normalizationContext: ['groups' => ['user:read', 'user:id']],
            denormalizationContext: ['groups' => ['user:write', 'user:password']],
            security: 'is_granted("PUBLIC_ACCESS")',
        ),
        // Update a specific user by ID (only the user themselves can update their information)
        new Patch(
            openapi: new Operation(
                summary: 'Update user',
                description: 'Update a specific user by their ID. Users can only update their own information. Requires ROLE_USER permission.'
            ),
            normalizationContext: ['groups' => ['user:read', 'user:id']],
            denormalizationContext: ['groups' => ['user:write', 'user:password']],
            security: "is_granted('ROLE_USER') and object == user"
        ),
        // Delete a specific user by ID (only the user themselves can delete their account)
        new Delete(
            openapi: new Operation(
                summary: 'Delete user',
                description: 'Delete a specific user by their ID. Users can only delete their own account. Requires ROLE_USER permission.'
            ),
            security: "is_granted('ROLE_USER') and object == user"
        ),
        new Delete(
            uriTemplate: '/users/{id}/picture',
            formats: [
                'png' => 'image/png',
            ],
            controller: DeleteUserPictureController::class,
            openapi: new Operation(
                summary: 'Remove the picture from the user',
                description: 'Remove the PNG image corresponding to the picture of the user',
            ),
            denormalizationContext: ['groups' => ['user:picture']],
            security: "is_granted('ROLE_USER')",
        ),
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:me', 'user:id', 'user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['user:me', 'user:write', 'team:members', 'user:read'])]
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
    #[Groups(['user:password'])]
    private string $password;

    #[ORM\Column(length: 100)]
    #[Groups(['user:me', 'user:write'])]
    private string $firstname;

    #[ORM\Column(length: 100)]
    #[Groups(['user:me', 'user:write'])]
    private string $lastname;

    #[ORM\Column(length: 50)]
    #[Groups(['user:me', 'user:write'])]
    #[Assert\Email(
        message: 'The email {{ value }} is not a valid email.',
    )]
    private string $email;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['user:me', 'user:write'])]
    #[Assert\Type(\DateTime::class)]
    #[Assert\LessThan('today', message: 'The birth date cannot be in the future.')]
    private \DateTime $birthDate;

    #[ORM\Column(length: 12)]
    #[Groups(['user:me', 'user:write'])]
    private string $phone;

    #[ORM\Column]
    private bool $activated;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    #[Groups(['user:me', 'user:read'])]
    private \DateTimeImmutable $creationDate;

    // TODO Gérer comment connaitre la dernière fois que quelqu'un s'est connecté (peut pas fonctionner avec Timestampable)
    #[ORM\Column]
    private \DateTime $lastLogin;

    #[ORM\Column]
    #[Groups(['user:me', 'user:write', 'user:read'])]
    private bool $public;

    #[ORM\Column(type: 'string', enumType: Gender::class)]
    #[Groups(['user:me', 'user:write', 'user:read'])]
    private Gender $gender;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['user:picture'])]
    private ?Picture $profilePicture = null;

    #[ORM\Column]
    #[Groups(['user:me', 'user:read'])]
    private int $totalTime;

    #[ORM\Column]
    #[Groups(['user:me', 'user:read'])]
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
    #[Groups(['user:teams'])]
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $symfonySessionId = null;

    #[ORM\Column(length: 150)]
    #[Groups(['user:me', 'user:write', 'user:read'])]
    private string $description = '';

    /**
     * @var Collection<int, ParticipateHunt>
     */
    #[ORM\OneToMany(targetEntity: ParticipateHunt::class, mappedBy: 'hunter')]
    private Collection $participateHunts;

    public function __construct()
    {
        $this->ownedTeams = new ArrayCollection();
        $this->teams = new ArrayCollection();
        $this->treasureHunts = new ArrayCollection();
        $this->participateRiddles = new ArrayCollection();
        $this->lastLogin = new \DateTime();
        $this->activated = true;
        $this->setTotalTime(0);
        $this->setTotalHunt(0);
        $this->participateHunts = new ArrayCollection();
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

    public function getProfilePicture(): ?Picture
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?Picture $profilePicture): static
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

    public function getSymfonySessionId(): ?string
    {
        return $this->symfonySessionId;
    }

    public function setSymfonySessionId(?string $symfonySessionId): static
    {
        $this->symfonySessionId = $symfonySessionId;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, ParticipateHunt>
     */
    public function getParticipateHunts(): Collection
    {
        return $this->participateHunts;
    }

    public function addParticipateHunt(ParticipateHunt $participateHunt): static
    {
        if (!$this->participateHunts->contains($participateHunt)) {
            $this->participateHunts->add($participateHunt);
            $participateHunt->setHunter($this);
        }

        return $this;
    }

    public function removeParticipateHunt(ParticipateHunt $participateHunt): static
    {
        if ($this->participateHunts->removeElement($participateHunt)) {
            // set the owning side to null (unless already changed)
            if ($participateHunt->getHunter() === $this) {
                $participateHunt->setHunter(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nickname;
    }
}
