<?php

namespace app\controllers;

use app\models\User;
use Yii;
use yii\filters\ContentNegotiator;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class UsersController extends Controller
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
     * GET /users — paginated list (id, first_name, last_name).
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $page = max(1, (int) $request->get('page', 1));
        $perPage = (int) $request->get('per-page', 10);
        $perPage = min(50, max(1, $perPage));

        $query = User::find()->orderBy(['id' => SORT_ASC]);
        $totalCount = (int) $query->count();

        $items = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->all();

        $pageCount = $totalCount > 0 ? (int) ceil($totalCount / $perPage) : 0;

        return [
            'items' => array_map(static function (User $u) {
                return [
                    'id' => (int) $u->id,
                    'first_name' => $u->first_name,
                    'last_name' => $u->last_name,
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
     * GET /users/{id} — user detail with albums (id, title).
     */
    public function actionView($id)
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
}
