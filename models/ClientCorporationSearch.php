<?php

namespace app\models;

use yii\data\ActiveDataProvider;

class ClientCorporationSearch extends ClientCorporation
{
    public function rules()
    {
        return [
            [['code', 'name', 'shorten_name', 'account_closing_month'], 'safe'],
        ];
    }

    public function search()
    {
        $query = ClientCorporation::find()->where(['removed' => null]);

        $query
            ->andFilterWhere(['like', 'code', $this->code])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'shorten_name', $this->shorten_name])
            ->andFilterWhere(['account_closing_month' => $this->account_closing_month]);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }
}