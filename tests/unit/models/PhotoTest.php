<?php

namespace tests\unit\models;

use app\models\Photo;
use Codeception\Test\Unit;

class PhotoTest extends Unit
{
    public function testVirtualUrlPointsToStaticImages()
    {
        $photo = new Photo(['id' => 11, 'album_id' => 1, 'title' => 'x']);
        verify(str_starts_with($photo->url, '/static-images/'))->true();
        verify(str_ends_with($photo->url, '.png'))->true();
    }
}
