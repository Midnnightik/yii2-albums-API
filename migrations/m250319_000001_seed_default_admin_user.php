<?php

use yii\db\Migration;

/**
 * Seeds a default admin user for local dev and functional tests (username/password: admin).
 */
class m250319_000001_seed_default_admin_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $exists = (new \yii\db\Query())
            ->from('{{%user}}')
            ->where(['username' => 'admin'])
            ->exists();
        if ($exists) {
            return;
        }

        $security = \Yii::$app->security;
        $this->insert('{{%user}}', [
            'username' => 'admin',
            'auth_key' => $security->generateRandomString(),
            'password_hash' => $security->generatePasswordHash('admin'),
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%user}}', ['username' => 'admin']);
    }
}
