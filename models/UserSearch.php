<?php

namespace app\models;

use yii\data\ActiveDataProvider;

class UserSearch extends User
{
    public function rules()
    {
        return [
            [['username', 'name', 'client_corporation_id'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = in_array(\Yii::$app->user->identity->role, ['system_administrator', 'administrator']) ? User::find()->where(['role' => ['administrator','account'], 'status' => 1]) : User::find()->where(['role' => 'account', 'status' => 1]);

        $dataProvider = new ActiveDataProvider(['query' => $query]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['client_corporation_id' => $this->client_corporation_id]);

        return $dataProvider;
    }
}