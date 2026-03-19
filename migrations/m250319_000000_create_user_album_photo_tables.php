<?php

use yii\db\Migration;

/**
 * Creates user, album, photo tables for the albums API.
 */
class m250319_000000_create_user_album_photo_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(64)->notNull()->unique(),
            'auth_key' => $this->string(32)->notNull(),
            'password_hash' => $this->string()->notNull(),
            'first_name' => $this->string(128)->notNull(),
            'last_name' => $this->string(128)->notNull(),
        ], $tableOptions);

        $this->createTable('{{%album}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-album-user_id', '{{%album}}', 'user_id');

        $this->createTable('{{%photo}}', [
            'id' => $this->primaryKey(),
            'album_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-photo-album_id', '{{%photo}}', 'album_id');

        $this->addForeignKey(
            'fk-album-user_id',
            '{{%album}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-photo-album_id',
            '{{%photo}}',
            'album_id',
            '{{%album}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-photo-album_id', '{{%photo}}');
        $this->dropForeignKey('fk-album-user_id', '{{%album}}');
        $this->dropTable('{{%photo}}');
        $this->dropTable('{{%album}}');
        $this->dropTable('{{%user}}');
    }
}
