<?php

namespace app\models;

use yii\data\ActiveDataProvider;

class RepaymentPatternSearch extends RepaymentPattern
{
    public function rules()
    {
        return [
            [['name', 'target_month', 'transfer_date'], 'safe'],
        ];
    }

    public function search()
    {
        $query = RepaymentPattern::find();

        $query
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['target_month' => $this->target_month])
            ->andFilterWhere(['transfer_date' => $this->transfer_date]);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }
}