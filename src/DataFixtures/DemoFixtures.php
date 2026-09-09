<?php

namespace App\DataFixtures;

use App\Dto\Designer\HuntInput;
use App\Dto\Designer\RiddleInput;
use App\Entity\DesignerTeam;
use App\Entity\HuntType;
use App\Entity\PlayerTeam;
use App\Entity\User;
use App\Enum\Gender;
use App\Service\Designer\HuntEditor;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Le jeu de démonstration : un administrateur, quatre joueurs, une équipe de chaque sorte et
 * deux chasses ouvertes dans Paris, écrites à la main. Il ne fait pas partie du groupe par
 * défaut : `doctrine:fixtures:load --group=demo --append` le charge sur une base vide, en
 * local comme en production, sans toucher aux migrations. Les chasses passent par
 * `HuntEditor`, comme depuis la façade, pour que les codes QR, le nombre d'énigmes et le
 * statut « ouverte » soient posés par les mêmes règles.
 */
class DemoFixtures extends Fixture implements FixtureGroupInterface
{
    public const string GROUP = 'demo';
    /** Le mot de passe temporaire de tous les comptes de démonstration, à changer après le chargement. */
    public const string PASSWORD = 'Treasurely-2026!';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly HuntEditor $huntEditor,
    ) {
    }

    public static function getGroups(): array
    {
        return [self::GROUP];
    }

    public function load(ObjectManager $manager): void
    {
        $admin = $this->user($manager, 'maxence', 'Maxence', 'Poizat', Gender::MAN, ['ROLE_ADMIN']);
        $camille = $this->user($manager, 'camille', 'Camille', 'Roussel', Gender::WOMAN);
        $theo = $this->user($manager, 'theo', 'Théo', 'Marchand', Gender::MAN);
        $ines = $this->user($manager, 'ines', 'Inès', 'Benali', Gender::WOMAN);
        $sacha = $this->user($manager, 'sacha', 'Sacha', 'Lefort', Gender::OTHER);

        $atelier = (new DesignerTeam())
            ->setName('Atelier Paris')
            ->setDescription('Les concepteurs des chasses parisiennes de Treasurely.')
            ->setOwner($admin)
            ->addMember($admin);
        $manager->persist($atelier);

        $flaneurs = (new PlayerTeam())
            ->setCode(PlayerTeam::generateCode())
            ->setName('Les Flâneurs')
            ->setDescription('On marche, on cherche, on trouve. Rarement dans cet ordre.')
            ->setOwner($camille)
            ->addMember($camille)
            ->addMember($theo)
            ->addMember($ines);
        $manager->persist($flaneurs);
        $manager->persist((new PlayerTeam())
            ->setCode(PlayerTeam::generateCode())
            ->setName('Solo Sacha')
            ->setDescription('Une équipe d\'une personne, en attendant les autres.')
            ->setOwner($sacha)
            ->addMember($sacha));

        $patrimoine = (new HuntType())->setTitle('Patrimoine');
        $balade = (new HuntType())->setTitle('Balade urbaine');
        $manager->persist($patrimoine);
        $manager->persist($balade);
        // Les identifiants des équipes et des types sont nécessaires aux entrées de HuntEditor
        $manager->flush();

        $this->huntEditor->create(new HuntInput(
            title: 'Secrets de la Cité',
            description: 'Le berceau de Paris en cinq énigmes, du plus vieux pont de la ville au marché aux fleurs. Comptez une heure et demie à pied, tout se joue entre les deux rives.',
            designerTeamId: $atelier->getId(),
            huntTypeIds: [$patrimoine->getId(), $balade->getId()],
            difficulty: 2,
            estimatedTime: 90,
            location: 'Paris',
            action: HuntInput::ACTION_PUBLISH,
            riddles: [
                $this->text('Le doyen des ponts', "Malgré son nom, c'est le plus vieux pont de Paris encore debout. Rendez-vous à son extrémité ouest, face à la statue équestre, et donnez son nom.", 1, 'Pont Neuf'),
                $this->gps('Le point zéro', "Toutes les routes de France partent d'ici. Trouvez la dalle de bronze scellée sur le parvis de la cathédrale et tenez-vous dessus.", 1, 48.85339, 2.34877),
                $this->mcq('Chapelle de lumière', 'Quel roi a fait bâtir la Sainte-Chapelle pour abriter la couronne d\'épines ?', 2, ['Philippe Auguste', 'Saint Louis', 'François Ier', 'Louis XIV'], ['Saint Louis']),
                $this->qr('La Conciergerie', "Le code est affiché à l'accueil de l'ancienne prison de Marie-Antoinette, sur le quai de l'Horloge.", 2),
                $this->text('Le marché aux fleurs', "Le marché aux fleurs occupe une place qui porte le nom d'un préfet de police. Donnez son nom de famille.", 3, 'Lépine'),
            ],
            image: null,
        ), $admin);

        $this->huntEditor->create(new HuntInput(
            title: 'La Butte Montmartre',
            description: 'Six énigmes dans les rues de la Butte : la place des peintres, le mur des amoureux, un moulin, un lapin et une basilique. Prévoyez deux heures et de bonnes chaussures, ça grimpe.',
            designerTeamId: $atelier->getId(),
            huntTypeIds: [$balade->getId()],
            difficulty: 3,
            estimatedTime: 120,
            location: 'Paris',
            action: HuntInput::ACTION_PUBLISH,
            riddles: [
                $this->gps('Place des peintres', 'Rejoignez la place où les portraitistes installent leurs chevalets depuis plus d\'un siècle.', 1, 48.88650, 2.34080),
                $this->text('Le mur des amoureux', "Un mur de lave émaillée y répète « je t'aime » dans plus de deux cents langues. Dans quel square se trouve-t-il ? (le nom du poète)", 2, 'Jehan Rictus'),
                $this->mcq('La basilique', 'En quelle année la basilique du Sacré-Cœur a-t-elle été consacrée ?', 3, ['1875', '1914', '1919', '1923'], ['1919']),
                $this->qr('Le dernier moulin', 'Le code est affiché devant le moulin de la Galette, rue Lepic, celui que Renoir a peint.', 2),
                $this->text('Rue des Saules', 'Quel animal donne son nom au cabaret historique de la rue des Saules ?', 1, 'Lapin'),
                $this->gps('Le parvis', 'Terminez au pied de la basilique, sur le parvis qui domine tout Paris.', 1, 48.88670, 2.34310),
            ],
            image: null,
        ), $admin);
    }

    /**
     * @param string[] $roles
     */
    private function user(ObjectManager $manager, string $nickname, string $firstname, string $lastname, Gender $gender, array $roles = []): User
    {
        $user = (new User())
            ->setNickname($nickname)
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setEmail($nickname.'@treasurely.app')
            ->setBirthDate(new \DateTime('1995-05-12'))
            ->setPhone('')
            ->setRoles($roles)
            ->setActivated(true)
            ->setPublic(true);
        $user->setGender($gender);
        $user->setPassword($this->hasher->hashPassword($user, self::PASSWORD));
        $manager->persist($user);

        return $user;
    }

    private function text(string $title, string $description, int $difficulty, string $answer): RiddleInput
    {
        return new RiddleInput(null, 'text', $title, $description, $difficulty, 3, $answer, null, null, [], [], false);
    }

    private function gps(string $title, string $description, int $difficulty, float $latitude, float $longitude): RiddleInput
    {
        return new RiddleInput(null, 'gps', $title, $description, $difficulty, 3, null, $latitude, $longitude, [], [], false);
    }

    /**
     * @param string[] $choices
     * @param string[] $answers
     */
    private function mcq(string $title, string $description, int $difficulty, array $choices, array $answers): RiddleInput
    {
        return new RiddleInput(null, 'mcq', $title, $description, $difficulty, 3, null, null, null, $choices, $answers, true);
    }

    private function qr(string $title, string $description, int $difficulty): RiddleInput
    {
        return new RiddleInput(null, 'qr', $title, $description, $difficulty, 3, null, null, null, [], [], false);
    }
}
