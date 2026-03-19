<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $album_id
 * @property string $title
 * @property-read string $url virtual URL to a static demo image
 */
class Photo extends ActiveRecord
{
    /**
     * Basenames of files under web/static-images/ (5–10 images).
     * @return string[]
     */
    public static function staticImageBasenames(): array
    {
        return [
            'demo-01.png',
            'demo-02.png',
            'demo-03.png',
            'demo-04.png',
            'demo-05.png',
            'demo-06.png',
            'demo-07.png',
            'demo-08.png',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%photo}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['album_id', 'title'], 'required'],
            [['album_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [
                ['album_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Album::class,
                'targetAttribute' => ['album_id' => 'id'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fields()
    {
        $fields = parent::fields();
        $fields['url'] = 'url';
        return $fields;
    }

    /**
     * Virtual attribute: deterministic pick from static images by photo id.
     * @return string
     */
    public function getUrl(): string
    {
        $files = static::staticImageBasenames();
        $n = count($files);
        if ($n === 0) {
            return '/static-images/placeholder.png';
        }
        $index = $this->id !== null ? ((int) $this->id % $n) : 0;
        return '/static-images/' . $files[$index];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAlbum()
    {
        return $this->hasOne(Album::class, ['id' => 'album_id']);
    }
}
