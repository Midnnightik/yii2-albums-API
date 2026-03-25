<?php

use yii\db\Migration;

class m250325_100001_create_album_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%album}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-album-user_id', '{{%album}}', 'user_id');
        $this->createIndex('uq-album-user_id-title', '{{%album}}', ['user_id', 'title'], true);

        $this->addForeignKey(
            'fk-album-user_id',
            '{{%album}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-album-user_id', '{{%album}}');
        $this->dropTable('{{%album}}');
    }
}
