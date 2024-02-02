<?php

namespace app\models;

use yii\data\ActiveDataProvider;

class RepaymentTypeSearch extends RepaymentType
{
    public function rules()
    {
        return [
            [['type'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = RepaymentType::find()
            ->where(['removed' => null])
            ->orderBy(['disp_order' => SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'type', $this->type]);

        return $dataProvider;
    }
}