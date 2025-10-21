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
        $lastParticipate = \DateTimeImmutable::createFromMutable(self::faker()->dateTime('today'));
        $finished = false;

        $user = UserFactory::random();
        $hunt = TreasureHuntFactory::random();
        $teamHunt = $hunt->getTeam();
        $members = $teamHunt->getMembers();
        $userTeam = false;

        foreach ($members as $member) {
            if ($user->getId() == $member->getId()) {
                $userTeam = true;
            }
        }
        $score = 0;
        $time = 0;

        if ($user->getId() != $hunt->getOwner()->getId()) {
            if (!$userTeam) {
                $riddles = $hunt->getRiddles();
                foreach ($riddles as $riddle) {
                    $participations = $riddle->getParticipateRiddles();
                    foreach ($participations as $participation) {
                        if ($participation->getHunter()->getId() == $user->getId()) {
                            $score += $participation->getScore();
                            $time += (int) abs($participation->getFinishTime()->getTimestamp() - $participation->getStartTime()->getTimestamp());
                        }
                        if ($participation->getFinishTime()) {
                            $lastParticipate = $participation->getFinishTime();
                        } else {
                            if ($lastParticipate < $participation->getLastParticipate()) {
                                $lastParticipate = $participation->getLastParticipate();
                            }
                        }
                        if ($riddle->getOrderNumber() == $hunt->getRiddleCount()) {
                            $finished = true;
                        }
                    }
                }

            }
        }

        return [
            'lastParticipate' => $lastParticipate,
            'score' => $score,
            'time' => $time,
            'finished' => $finished,
        ];
    }
}
