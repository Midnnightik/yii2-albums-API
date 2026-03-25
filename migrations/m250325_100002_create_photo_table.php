<?php

use yii\db\Migration;

class m250325_100002_create_photo_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%photo}}', [
            'id' => $this->primaryKey(),
            'album_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-photo-album_id', '{{%photo}}', 'album_id');
        $this->createIndex('uq-photo-album_id-title', '{{%photo}}', ['album_id', 'title'], true);

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

    public function safeDown()
    {
        $this->dropForeignKey('fk-photo-album_id', '{{%photo}}');
        $this->dropTable('{{%photo}}');
    }
}
