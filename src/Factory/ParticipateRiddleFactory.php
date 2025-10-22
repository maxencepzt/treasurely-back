<?php

namespace App\Factory;

use App\Entity\ParticipateRiddle;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<ParticipateRiddle>
 */
final class ParticipateRiddleFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return ParticipateRiddle::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        // Instanciation des données pour tous les utilisateurs

        $startTime = \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('today - 5 minutes', 'today'));
        $score = 0;
        $lastParticipate = $startTime;
        $finishTime = null;

        // Vérifier si un User n'est pas concepteur de la chasse d'où provient le Riddle

        $user = UserFactory::random();
        $riddleFactory = [GPSRiddleFactory::random(), MCQRiddleFactory::random(), QRRiddleFactory::random(), TextRiddleFactory::random()];
        $riddle = $riddleFactory[array_rand($riddleFactory)];
        $hunt = $riddle->getHunt();
        $team = $hunt->getTeam();
        $members = $team->getMembers();

        if ($user->getId() == $hunt->getOwner()->getId()) {
            UserFactory::random();
        } else {
            foreach ($members as $member) {
                if ($user->getId() == $member->getId()) {
                    UserFactory::random();
                } else {
                    // Instanciation des données pour un utilisateur qui n'est ni un concepteur, ni le créateur de la chasse

                    $lastParticipate = \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('today', 'today + 5 minutes'));
                    while ($startTime > $lastParticipate) {
                        $lastParticipate = \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('today', 'today + 5 minutes'));
                    }
                    $difficulty = $riddle->getDifficulty();
                    $finish = (bool) rand(0, 1);
                    if ($finish) {
                        // Si l'énigme est résolu, mettre fin à l'énigme et calculer son score.

                        $finishTime = $lastParticipate;
                        $time = $finishTime->getTimestamp() - $startTime->getTimestamp();
                        $score = (int) ($difficulty * (1000 * exp(-0.001 * (int) abs($time))));
                    }
                }
            }
        }

        return [
            'startTime' => $startTime,
            'finishTime' => $finishTime,
            'score' => $score,
            'lastParticipate' => $lastParticipate,
            'hunter' => $user,
            'riddle' => $riddle,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(ParticipateRiddle $participateRiddle): void {})
        ;
    }
}
