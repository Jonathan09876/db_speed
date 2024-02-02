<?php

namespace app\models;

use yii\data\ActiveDataProvider;

class LeaseContractStatusTypeSearch extends LeaseContractStatusType
{
    public function rules()
    {
        return [
            [['type'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'type' => '契約ステータス',
        ];
    }

    public function search($params)
    {
        $query = LeaseContractStatusType::find()
            ->orderBy(['disp_order' => SORT_ASC])
            ->where(['removed' => null]);

        $dataProvider = new ActiveDataProvider(['query' => $query]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'type', $this->type]);

        return $dataProvider;
    }
}