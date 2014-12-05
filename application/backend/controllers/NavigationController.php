<?php

namespace app\backend\controllers;

use app\widgets\navigation\models\Navigation;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;

class NavigationController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['navigation manage'],
                    ],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'getTree' => [
                'class' => 'app\backend\actions\JSTreeGetTree',
                'modelName' => Navigation::className(),
                'label_attribute' => 'name',
                'vary_by_type_attribute' => null,
                'expand_in_admin_attribute' => null,
            ],
        ];
    }

    public function actionIndex($parent_id = 1)
    {
        $searchModel = new Navigation(['scenario' => 'search']);
        $searchModel->parent_id = $parent_id;
        $dataProvider = $searchModel->search($_GET);

        $model = null;
        if ($parent_id > 0) {
            $model = Navigation::findOne($parent_id);
        }

        return $this->render(
            'index',
            [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'model' => $model,
            ]
        );
    }

    public function actionEdit($parent_id, $id = null)
    {
        if ($id === null) {
            $model = new Navigation(['parent_id' => $parent_id]);
        } else {
            $model = Navigation::findOne($id);
        }

        $post = Yii::$app->request->post();
        if ($model->load($post) && $model->validate()) {
            if ($model->save()) {
                Yii::$app->session->setFlash('info', Yii::t('app', 'Object saved'));
                return $this->redirect(
                    [
                        '/backend/navigation/edit',
                        'id' => $model->id,
                        'parent_id' => $model->parent_id,
                    ]
                );
            } else {
                \Yii::$app->session->setFlash('error', Yii::t('app', 'Cannot update data'));
            }
        }

        return $this->render(
            'navigation-form',
            [
                'model' => $model,
            ]
        );
    }

    public function actionDelete($id)
    {
        $model = Navigation::findOne($id);
        $model->delete();
        Yii::$app->session->setFlash('info', Yii::t('app', 'Object removed'));
        return $this->redirect(
            Url::to(
                [
                    '/backend/navigation/index',
                    'parent_id' => $model->parent_id,
                ]
            )
        );
    }

    public function actionRemoveAll($parent_id)
    {
        $items = Yii::$app->request->post('items', []);
        if (!empty($items)) {
            $items = Navigation::find()->where(['in', 'id', $items])->all();
            foreach ($items as $item) {
                $item->delete();
            }
        }

        return $this->redirect(['index', 'parent_id' => $parent_id]);
    }
}
