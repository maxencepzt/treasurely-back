<?php

declare(strict_types=1);

namespace App\Tests\Api\User;

use App\Entity\Picture;
use App\Factory\UserFactory;
use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

final class UserPictureCest
{
    /** Régression : chaque envoi laissait l'ancienne photo en base, sans limite. */
    public function aNewPictureReplacesTheOldOneInTheDatabase(ApiTester $I): void
    {
        // 1. 'Arrange' : un joueur avec sa photo, et un PNG d'un pixel
        $user = UserFactory::createOne()->_real();
        $old = $user->getProfilePicture()?->getId();
        $I->assertNotNull($old);
        $png = tempnam(sys_get_temp_dir(), 'treasurely-');
        file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));

        // 2. 'Act'
        $I->amLoggedInAs($user);
        $I->sendFormPost('/api/users/'.$user->getId().'/picture', [], ['image' => $png]);
        unlink($png);

        // 3. 'Assert'
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->dontSeeInRepository(Picture::class, ['id' => $old]);
    }
}
