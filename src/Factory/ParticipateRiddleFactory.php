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

        $startTime = \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('today -1 day', 'today'));
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

        if ($user->getId() != $hunt->getOwner()->getId()) {
            foreach ($members as $member) {
                if ($user->getId() != $member->getId()) {
                    // Instanciation des données pour un utilisateur qui n'est ni un concepteur, ni le créateur de la chasse

                    $lastParticipate = \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('today', 'today + 1 day'));
                    while ($startTime > $lastParticipate) {
                        $lastParticipate = \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('today', 'today + 1 day'));
                    }

                    $finish = (bool) rand(0, 1);
                    if ($finish) {
                        // Si l'énigme est résolu, mettre fin à l'énigme et calculer son score.

                        while ($finishTime != $lastParticipate) {
                            $finishTime = $lastParticipate;
                        }
                        $time = $finishTime->getTimestamp() - $startTime->getTimestamp();
                        $difficulty = $riddle->getDifficulty();
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
