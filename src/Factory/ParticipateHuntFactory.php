<?php

namespace App\Factory;

use App\Entity\ParticipateHunt;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<ParticipateHunt>
 */
final class ParticipateHuntFactory extends PersistentProxyObjectFactory
{
    public static Generator $faker;

    public function __construct()
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return ParticipateHunt::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        $user = UserFactory::random();
        $hunt = TreasureHuntFactory::random();
        $riddles = $hunt->getRiddles();

        $score = 0;
        $time = 0;
        $finished = false;
        $lastParticipate = \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('today - 6 hours', 'today'));

        $count = 0;

        foreach ($riddles as $riddle) {
            $participations = $riddle->getParticipateRiddles();
            foreach ($participations as $participation) {
                if ($user->getId() == $participation->getHunter()->getId() && $hunt->getId() == $riddle->getHunt()->getId()) {
                    if ($lastParticipate <= $participation->getLastParticipate()) {
                        $lastParticipate = $participation->getLastParticipate();
                    }
                    $score += $participation->getScore();
                    $time += $participation->getFinishTime()->getTimestamp() - $participation->getStartTime()->getTimestamp();
                    if ($lastParticipate <= $participation->getFinishTime()) {
                        $lastParticipate = $participation->getFinishTime();
                    }
                    ++$count;
                }
            }
        }

        if ($hunt->getRiddleCount() == $count) {
            $finished = true;
        }

        return [
            'lastParticipate' => $lastParticipate,
            'score' => $score,
            'time' => $time,
            'finished' => $finished,
            'hunter' => $user,
            'hunt' => $hunt,
        ];
    }
}
