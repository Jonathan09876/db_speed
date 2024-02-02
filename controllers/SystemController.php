<?php

namespace app\controllers;

use Yii;
use app\models\User;
use app\models\UserSearch;
use yii\data\ActiveDataProvider;

class SystemController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionUsers()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->post());

        return $this->render('users', compact("searchModel", "dataProvider"));
    }

    public function actionUser($id = null)
    {
        if (isset($id)) {
            $model = User::findOne($id);
        }
        else {
            $model = new User([
                'role' => 'account',
                'status' => 1,
            ]);
        }
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->save();
            return $this->redirect(['/system/users']);
        }
        return $this->render('user', compact("model"));
    }
}