<?php

namespace app\models;

use yii\data\ActiveDataProvider;

class AccountTransferAgencySearch extends AccountTransferAgency
{
    public function rules()
    {
        return [
            [['code', 'name'], 'safe'],
        ];
    }

    public function search()
    {
        $query = AccountTransferAgency::find()->where(['removed' => null]);

        $query
            ->andFilterWhere(['code' => $this->code])
            ->andFilterWhere(['like', 'name', $this->name]);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }
}