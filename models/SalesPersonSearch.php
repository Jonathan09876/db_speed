<?php

namespace app\models;

use yii\data\ActiveDataProvider;

class SalesPersonSearch extends SalesPerson
{
    public function rules()
    {
        return [
            [['name'], 'safe'],
        ];
    }

    public function search()
    {
        $query = SalesPerson::find()->where(['removed' => null]);

        $query
            ->andFilterWhere(['like', 'name', $this->name]);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }
}