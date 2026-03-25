<?php

namespace app\commands;

use app\models\Album;
use app\models\Photo;
use app\models\User;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Exception as DbException;

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
     * @param string[][] $rows
     */
    protected function mysqlInsertIgnoreBatch(string $table, array $columns, array $rows, int $chunkSize = 200): void
    {
        if ($rows === []) {
            return;
        }
        if (Yii::$app->db->driverName !== 'mysql') {
            throw new DbException('Batch seed expects MySQL (INSERT IGNORE).');
        }
        $db = Yii::$app->db;
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $sql = $db->createCommand()->batchInsert($table, $columns, $chunk)->getRawSql();
            $db->createCommand(str_replace('INSERT INTO', 'INSERT IGNORE INTO', $sql))->execute();
        }
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
        $security = Yii::$app->security;
        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                $prefix . $i,
                $security->generateRandomString(32),
                $security->generatePasswordHash($password),
                'User',
                (string) $i,
            ];
        }
        try {
            $this->mysqlInsertIgnoreBatch(User::tableName(), [
                'username',
                'auth_key',
                'password_hash',
                'first_name',
                'last_name',
            ], $rows);
        } catch (DbException $e) {
            $this->stderr($e->getMessage() . PHP_EOL);
            return ExitCode::UNSPECIFIED_ERROR;
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
        $rows = [];
        for ($i = 1; $i <= 10; $i++) {
            $u = User::findByUsername('user_' . $i);
            if ($u === null) {
                $this->stderr("Missing user_{$i}; run ./yii seed/users first.\n");
                return ExitCode::DATAERR;
            }
            for ($n = 1; $n <= $perUser; $n++) {
                $rows[] = [(int) $u->id, 'album_' . $u->id . '_' . $n];
            }
        }
        try {
            $this->mysqlInsertIgnoreBatch(Album::tableName(), ['user_id', 'title'], $rows);
        } catch (DbException $e) {
            $this->stderr($e->getMessage() . PHP_EOL);
            return ExitCode::UNSPECIFIED_ERROR;
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
        $rows = [];
        foreach (Album::find()->orderBy(['id' => SORT_ASC])->each(100) as $album) {
            for ($n = 1; $n <= $perAlbum; $n++) {
                $rows[] = [(int) $album->id, 'photo_' . $album->id . '_' . $n];
            }
        }
        try {
            $this->mysqlInsertIgnoreBatch(Photo::tableName(), ['album_id', 'title'], $rows);
        } catch (DbException $e) {
            $this->stderr($e->getMessage() . PHP_EOL);
            return ExitCode::UNSPECIFIED_ERROR;
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
