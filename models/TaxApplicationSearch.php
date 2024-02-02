<?php

namespace app\models;

use yii\data\ActiveDataProvider;

class TaxApplicationSearch extends TaxApplication
{
    public function rules()
    {
        return [
            [['application_name', 'tax_rate'], 'safe'],
        ];
    }

    public function search()
    {
        $query = TaxApplication::find()->where(['removed' => null])->orderBy(['disp_order' => SORT_ASC]);

        $query
            ->andFilterWhere(['tax_rate' => $this->tax_rate])
            ->andFilterWhere(['like', 'application_name', $this->application_name]);

        return new ActiveDataProvider([
            'query' => $query,
            'sort' => false,
        ]);
    }
}