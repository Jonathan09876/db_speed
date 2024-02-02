<?php

namespace app\models;

use yii\data\ActiveDataProvider;

class LeaseServicerSearch extends LeaseServicer
{
    public function rules()
    {
        return [
            [['name', 'shorten_name'], 'safe'],
        ];
    }

    public function search()
    {
        $query = LeaseServicer::find()->where(['removed' => null]);

        $query
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'shorten_name', $this->shorten_name]);

        return new ActiveDataProvider(['query' => $query]);
    }
}