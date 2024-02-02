<?php

namespace app\models;

use yii\data\ActiveDataProvider;

class ContractPatternSearch extends ContractPattern
{
    public function rules()
    {
        return [
            [['client_corporation_id', 'pattern_name', 'code', 'bg_color'], 'safe'],
        ];
    }

    public function search()
    {
        $query = ContractPattern::find()->where(['removed' => null]);

        $query
            ->andFilterWhere(['client_corporation_id' => $this->client_corporation_id])
            ->andFilterWhere(['like', 'pattern_name', $this->pattern_name])
            ->andFilterWhere(['like', 'code', $this->code]);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }
}