<?php

namespace app\controllers;

use app\models\Album;
use app\models\User;
use Closure;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class ApiController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * GET /users — paginated list (id, first_name, last_name).
     */
    public function actionUsers()
    {
        $request = Yii::$app->request;
        $page = max(1, (int) $request->get('page', 1));
        $perPage = min(50, max(1, (int) $request->get('per-page', 10)));

        $provider = new ActiveDataProvider([
            'query' => User::find()->orderBy(['id' => SORT_ASC]),
            'pagination' => [
                'pageSize' => $perPage,
                'page' => $page - 1,
                'validatePage' => false,
            ],
        ]);

        return $this->paginatedList($provider, static function (User $u) {
            return [
                'id' => (int) $u->id,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
            ];
        });
    }

    /**
     * GET /users/{id} — user detail with albums (id, title).
     */
    public function actionUser($id)
    {
        $user = User::findOne((int) $id);
        if ($user === null) {
            throw new NotFoundHttpException('User not found.');
        }

        $albums = [];
        foreach ($user->getAlbums()->orderBy(['id' => SORT_ASC])->all() as $album) {
            $albums[] = [
                'id' => (int) $album->id,
                'title' => $album->title,
            ];
        }

        return [
            'id' => (int) $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'albums' => $albums,
        ];
    }

    /**
     * GET /albums — paginated list (id, title).
     */
    public function actionAlbums()
    {
        $request = Yii::$app->request;
        $page = max(1, (int) $request->get('page', 1));
        $perPage = min(50, max(1, (int) $request->get('per-page', 10)));

        $provider = new ActiveDataProvider([
            'query' => Album::find()->orderBy(['id' => SORT_ASC]),
            'pagination' => [
                'pageSize' => $perPage,
                'page' => $page - 1,
                'validatePage' => false,
            ],
        ]);

        return $this->paginatedList($provider, static function (Album $a) {
            return [
                'id' => (int) $a->id,
                'title' => $a->title,
            ];
        });
    }

    /**
     * GET /albums/{id} — album detail with owner names and photos (id, title, url).
     */
    public function actionAlbum($id)
    {
        $album = Album::find()->with('user')->where(['id' => (int) $id])->one();
        if ($album === null) {
            throw new NotFoundHttpException('Album not found.');
        }

        $owner = $album->user;

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

    /**
     * @param ActiveDataProvider $provider
     * @param Closure $mapItem function(ActiveRecord): array
     * @return array<string, mixed>
     */
    protected function paginatedList(ActiveDataProvider $provider, Closure $mapItem): array
    {
        $models = $provider->getModels();
        $pagination = $provider->getPagination();

        return [
            'items' => array_map($mapItem, $models),
            'pagination' => [
                'page' => $pagination->getPage() + 1,
                'perPage' => $pagination->pageSize,
                'totalCount' => (int) $provider->getTotalCount(),
                'pageCount' => $pagination->getPageCount(),
            ],
        ];
    }
}
