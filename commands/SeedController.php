<?php

namespace app\commands;

use app\models\Album;
use app\models\Photo;
use app\models\User;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Console seeders for demo users, albums, and photos.
 *
 * Password is read from `config/seed_password_local.php` (not committed).
 */
class SeedController extends Controller
{
    /**
     * @throws \RuntimeException
     */
    protected function loadSeedPassword(): string
    {
        $path = Yii::getAlias('@app/config/seed_password_local.php');
        if (!is_file($path)) {
            throw new \RuntimeException(
                'Missing seed password file. Copy config/seed_password_local.php.example to config/seed_password_local.php'
            );
        }
        $p = require $path;
        if (!is_string($p) || $p === '') {
            throw new \RuntimeException('Seed password must be a non-empty string.');
        }
        return $p;
    }

    /**
     * Creates demo users `user_1` … `user_{count}` (default 10).
     *
     * @param string $prefix username prefix (default user_)
     * @param int $count number of users
     */
    public function actionUsers(string $prefix = 'user_', int $count = 10): int
    {
        $password = $this->loadSeedPassword();
        for ($i = 1; $i <= $count; $i++) {
            $username = $prefix . $i;
            if (User::findByUsername($username) !== null) {
                continue;
            }
            $u = new User();
            $u->username = $username;
            $u->first_name = 'User';
            $u->last_name = (string) $i;
            $u->setPassword($password);
            $u->generateAuthKey();
            if (!$u->save()) {
                $this->stderr('User save failed: ' . json_encode($u->errors) . PHP_EOL);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }
        $this->stdout("Seeded users ({$prefix}1..{$prefix}{$count}).\n");
        return ExitCode::OK;
    }

    /**
     * Creates albums for `user_1`..`user_10`: `perUser` albums each (default 10 => 100 albums).
     *
     * @param int $perUser albums per seeded demo user
     */
    public function actionAlbums(int $perUser = 10): int
    {
        for ($i = 1; $i <= 10; $i++) {
            $u = User::findByUsername('user_' . $i);
            if ($u === null) {
                $this->stderr("Missing user_{$i}; run ./yii seed/users first.\n");
                return ExitCode::DATAERR;
            }
            for ($n = 1; $n <= $perUser; $n++) {
                $title = 'album_' . $u->id . '_' . $n;
                $exists = Album::find()->where(['user_id' => $u->id, 'title' => $title])->exists();
                if ($exists) {
                    continue;
                }
                $a = new Album(['user_id' => $u->id, 'title' => $title]);
                if (!$a->save()) {
                    $this->stderr('Album save failed: ' . json_encode($a->errors) . PHP_EOL);
                    return ExitCode::UNSPECIFIED_ERROR;
                }
            }
        }
        $this->stdout("Seeded albums (10 users × {$perUser}).\n");
        return ExitCode::OK;
    }

    /**
     * Creates photos: `perAlbum` photos for each album (default 10 => 1000 photos for 100 albums).
     *
     * @param int $perAlbum photos per album
     */
    public function actionPhotos(int $perAlbum = 10): int
    {
        foreach (Album::find()->orderBy(['id' => SORT_ASC])->each(100) as $album) {
            for ($n = 1; $n <= $perAlbum; $n++) {
                $title = 'photo_' . $album->id . '_' . $n;
                $exists = Photo::find()->where(['album_id' => $album->id, 'title' => $title])->exists();
                if ($exists) {
                    continue;
                }
                $p = new Photo(['album_id' => $album->id, 'title' => $title]);
                if (!$p->save()) {
                    $this->stderr('Photo save failed: ' . json_encode($p->errors) . PHP_EOL);
                    return ExitCode::UNSPECIFIED_ERROR;
                }
            }
        }
        $this->stdout("Seeded photos (per album: {$perAlbum}).\n");
        return ExitCode::OK;
    }

    /**
     * Runs users, then albums, then photos.
     */
    public function actionAll(): int
    {
        $code = $this->actionUsers();
        if ($code !== ExitCode::OK) {
            return $code;
        }
        $code = $this->actionAlbums();
        if ($code !== ExitCode::OK) {
            return $code;
        }
        return $this->actionPhotos();
    }
}
