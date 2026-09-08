<?php

declare(strict_types=1);

namespace App\Tests\Api\Admin;

use App\Entity\ParticipateRiddle;
use App\Entity\User;
use App\Factory\DesignerTeamFactory;
use App\Factory\GPSRiddleFactory;
use App\Factory\HuntTypeFactory;
use App\Factory\MCQRiddleFactory;
use App\Factory\ParticipateHuntFactory;
use App\Factory\PictureFactory;
use App\Factory\PlayerTeamFactory;
use App\Factory\QRRiddleFactory;
use App\Factory\TextRiddleFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Le petit monde que le back office doit savoir afficher : une chasse avec une énigme de chaque
 * type, une équipe de joueurs, une participation en cours et une image.
 */
trait AdminWorld
{
    /**
     * @return array<string, object>
     */
    private function world(ApiTester $I): array
    {
        HuntTypeFactory::createOne();
        $owner = UserFactory::createOne()->_real();
        $designerTeam = DesignerTeamFactory::createOne(['owner' => $owner])->_real();
        $hunt = TreasureHuntFactory::createOne(['owner' => $owner, 'designerTeam' => $designerTeam, 'riddleCount' => 4])->_real();
        $text = TextRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 1])->_real();
        $gps = GPSRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 2])->_real();
        $mcq = MCQRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 3, 'choices' => ['a', 'b'], 'answers' => ['a']])->_real();
        $qr = QRRiddleFactory::createOne(['hunt' => $hunt, 'orderNumber' => 4])->_real();
        $player = UserFactory::createOne()->_real();
        $playerTeam = PlayerTeamFactory::createOne(['owner' => $player, 'code' => 'treasurely_000000000042'])->_real();
        $participation = ParticipateHuntFactory::createOne(['hunter' => $player, 'hunt' => $hunt, 'playerTeam' => $playerTeam, 'currentRiddle' => $text])->_real();

        $participateRiddle = (new ParticipateRiddle())
            ->setHunter($player)
            ->setRiddle($text)
            ->setStartTime(new \DateTimeImmutable('2026-09-08 02:24:46'))
            ->setLastParticipate(new \DateTime('2026-09-08 02:26:24'));
        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->persist($participateRiddle);
        $entityManager->flush();

        $picture = PictureFactory::createOne(['image' => 'not really a png'])->_real();

        return [
            'admin' => $this->admin(),
            'hunt' => $hunt,
            'huntType' => HuntTypeFactory::random()->_real(),
            'participation' => $participation,
            'text' => $text,
            'gps' => $gps,
            'mcq' => $mcq,
            'qr' => $qr,
            'participateRiddle' => $participateRiddle,
            'player' => $player,
            'playerTeam' => $playerTeam,
            'picture' => $picture,
        ];
    }

    private function admin(): User
    {
        return UserFactory::createOne(['roles' => ['ROLE_ADMIN']])->_real();
    }
}
