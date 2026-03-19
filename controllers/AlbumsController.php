<?php

namespace app\controllers;

use app\models\Album;
use Yii;
use yii\filters\ContentNegotiator;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AlbumsController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public $enableCsrfValidation = false;

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * GET /albums — paginated list (id, title).
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $page = max(1, (int) $request->get('page', 1));
        $perPage = (int) $request->get('per-page', 10);
        $perPage = min(50, max(1, $perPage));

        $query = Album::find()->orderBy(['id' => SORT_ASC]);
        $totalCount = (int) $query->count();

        $items = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->all();

        $pageCount = $totalCount > 0 ? (int) ceil($totalCount / $perPage) : 0;

        return [
            'items' => array_map(static function (Album $a) {
                return [
                    'id' => (int) $a->id,
                    'title' => $a->title,
                ];
            }, $items),
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'totalCount' => $totalCount,
                'pageCount' => $pageCount,
            ],
        ];
    }

    /**
     * GET /albums/{id} — album detail with owner names and photos (id, title, url).
     */
    public function actionView($id)
    {
        $album = Album::findOne((int) $id);
        if ($album === null) {
            throw new NotFoundHttpException('Album not found.');
        }

        $owner = $album->user;
        if ($owner === null) {
            throw new NotFoundHttpException('Album owner not found.');
        }

        $photos = [];
        foreach ($album->getPhotos()->orderBy(['id' => SORT_ASC])->all() as $photo) {
            $photos[] = [
                'id' => (int) $photo->id,
                'title' => $photo->title,
                'url' => $photo->url,
            ];
        }

        return [
            'id' => (int) $album->id,
            'first_name' => $owner->first_name,
            'last_name' => $owner->last_name,
            'photos' => $photos,
        ];
    }
}
