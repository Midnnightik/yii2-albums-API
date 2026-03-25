<?php

/**
 * API JSON shape tests (users / albums).
 */
class ApiCest
{
    private function createUser(\FunctionalTester $I, string $username, string $firstName, string $lastName): int
    {
        return $I->haveRecord(\app\models\User::class, [
            'username' => $username,
            'auth_key' => \Yii::$app->security->generateRandomString(32),
            'password_hash' => \Yii::$app->security->generatePasswordHash('test-password'),
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    public function usersListJsonShape(\FunctionalTester $I)
    {
        $I->amOnRoute('api/users', ['page' => 1, 'per-page' => 5]);
        $I->seeResponseCodeIs(200);
        $data = json_decode($I->grabPageSource(), true);
        verify($data)->arrayHasKey('items');
        verify($data)->arrayHasKey('pagination');
        foreach (['page', 'perPage', 'totalCount', 'pageCount'] as $k) {
            verify($data['pagination'])->arrayHasKey($k);
        }
        if (!empty($data['items'])) {
            foreach (['id', 'first_name', 'last_name'] as $k) {
                verify($data['items'][0])->arrayHasKey($k);
            }
        }
    }

    public function userDetailIncludesAlbums(\FunctionalTester $I)
    {
        $userId = $this->createUser($I, 'api_ctest_user_1', 'API', 'User1');

        $I->haveRecord(\app\models\Album::class, [
            'user_id' => $userId,
            'title' => 'api_ctest_album',
        ]);

        $I->amOnRoute('api/user', ['id' => $userId]);
        $I->seeResponseCodeIs(200);
        $data = json_decode($I->grabPageSource(), true);
        foreach (['id', 'first_name', 'last_name', 'albums'] as $k) {
            verify($data)->arrayHasKey($k);
        }
        $titles = array_column($data['albums'], 'title');
        \PHPUnit\Framework\Assert::assertContains('api_ctest_album', $titles);
        foreach ($data['albums'] as $row) {
            foreach (['id', 'title'] as $k) {
                verify($row)->arrayHasKey($k);
            }
        }
    }

    public function albumsListJsonShape(\FunctionalTester $I)
    {
        $I->amOnRoute('api/albums', ['page' => 1, 'per-page' => 10]);
        $I->seeResponseCodeIs(200);
        $data = json_decode($I->grabPageSource(), true);
        verify($data)->arrayHasKey('items');
        verify($data)->arrayHasKey('pagination');
        if (!empty($data['items'])) {
            foreach (['id', 'title'] as $k) {
                verify($data['items'][0])->arrayHasKey($k);
            }
        }
    }

    public function albumDetailIncludesPhotosWithUrl(\FunctionalTester $I)
    {
        $userId = $this->createUser($I, 'api_ctest_user_2', 'API', 'User2');

        $albumId = $I->haveRecord(\app\models\Album::class, [
            'user_id' => $userId,
            'title' => 'api_ctest_album_photos',
        ]);

        $I->haveRecord(\app\models\Photo::class, [
            'album_id' => $albumId,
            'title' => 'api_ctest_photo',
        ]);

        $I->amOnRoute('api/album', ['id' => $albumId]);
        $I->seeResponseCodeIs(200);
        $data = json_decode($I->grabPageSource(), true);
        foreach (['id', 'first_name', 'last_name', 'photos'] as $k) {
            verify($data)->arrayHasKey($k);
        }
        verify($data['photos'])->notEmpty();
        $p = $data['photos'][0];
        foreach (['id', 'title', 'url'] as $k) {
            verify($p)->arrayHasKey($k);
        }
        verify(str_contains($p['url'], '/static-images/'))->true();
    }

    public function userNotFound(\FunctionalTester $I)
    {
        $I->amOnRoute('api/user', ['id' => 999999999]);
        $I->seeResponseCodeIs(404);
    }
}
