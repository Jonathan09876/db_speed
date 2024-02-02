<?php

namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;

class TargetTermMonthlyChargeStoredSearch extends TargetTermMonthlyChargeStored
{
    public function rules()
    {
        return [
            [['target_term', 'client_corporation_id', 'repayment_pattern_id'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = TargetTermMonthlyChargeStored::find()->alias('t')
            ->innerJoin('client_corporation cc', 't.client_corporation_id=cc.client_corporation_id')
            ->innerJoin('repayment_pattern rp', 't.repayment_pattern_id=rp.repayment_pattern_id');
            //->orderBy(['t.target_term' => SORT_DESC]);//->where(['is_closed' => 0]);
        $sort = [
            'attributes' => [
                'target_term' => [
                    'asc' => ['t.target_term' => SORT_ASC],
                    'desc' => ['t.target_term' => SORT_DESC],
                    'default' => SORT_DESC
                ],
                'clientCorporation.name' => [
                    'asc' => ['cc.name' => SORT_ASC],
                    'desc' => ['cc.name' => SORT_DESC]
                ],
                'repaymentPattern.name' => [
                    'asc' => ['rp.name' => SORT_ASC],
                    'desc' => ['rp.name' => SORT_DESC]
                ]
            ],
            'defaultOrder' => ['target_term' => SORT_DESC]
        ];

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => $sort
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $target_term = !!$this->target_term ? (new \DateTime(
            preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->target_term)
        ))->format('Y-m-d') : null;
        $query
            //->andFilterWhere(['target_term' => $target_term])
            ->andFilterWhere(['t.client_corporation_id' => $this->client_corporation_id])
            ->andFilterWhere(['t.repayment_pattern_id' => $this->repayment_pattern_id]);

        return $dataProvider;
    }
}