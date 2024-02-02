<?php

namespace app\models;

use yii\data\ActiveDataProvider;

class TermSearch extends Term
{
    public function search($when, $params)
    {
        switch($when) {
            default:
                $query = ContractDetail::find()->alias('cd')->distinct()
                    ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
                    ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
                    ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
                    ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id')
                    ->innerJoin('lease_target lt', 'lc.lease_target_id=lt.lease_target_id')
                    ->leftJoin('lease_contract_status lcs1', 'lcs1.lease_contract_id=lc.lease_contract_id')
                    ->leftJoin('lease_contract_status lcs2', 'lcs1.lease_contract_id=lcs2.lease_contract_id AND lcs1.registered < lcs2.registered')
                    ->leftJoin('monthly_charge_span mcs', 'mcs.contract_detail_id=cd.contract_detail_id')
                    ->leftJoin('monthly_payment_span mps', 'mps.contract_detail_id=cd.contract_detail_id')
                    ->orderBy(['cc.client_corporation_id' => SORT_ASC, 'c.customer_code' =>SORT_ASC, 'lc.disp_order' => SORT_ASC, 'cd.term_start_at' =>SORT_ASC]);
                $sort = [
                    'attributes' => [
                        'cf' => [
                            'asc' => ['c.customer_code' => SORT_ASC],
                            'desc' => ['c.customer_code' => SORT_DESC],
                            'default' => SORT_ASC,
                            'label' => 'CF'
                        ],
                        'rp' => [
                            'asc' => ['rp.name' => SORT_ASC],
                            'desc' => ['rp.name' => SORT_DESC],
                            'default' => SORT_ASC,
                            'label' => '支払方法'
                        ],
                    ]
                ];

                $dataProvider = new ActiveDataProvider([
                    'query' => $query,
                    'sort' => $sort,
                ]);

                if (!$this->validate()) {
                    return $dataProvider;
                }
                $span = $this->getTermsFromSpan();
                $query
                    ->andWhere(['not', ['or', ['<', 'IF(mcs.last_term > mps.last_term, mcs.last_term, mps.last_term)', $span['from']->format('Y-m-01')], ['>', 'IF(mcs.first_term < mps.first_term, mcs.first_term, mps.first_term)', $span['to']->format('Y-m-t')]]])
                    ->andFilterWhere(['cc.client_corporation_id' => $this->client_corporation_id])
                    ->andFilterWhere(['lc.contract_pattern_id' => $this->contract_pattern_id])
                    ->andFilterWhere(['lc.contract_number' => $this->contract_number])
                    ->andFilterWhere(['lc.contract_code' => $this->contract_code])
                    ->andFilterWhere(['lc.contract_sub_code' => $this->contract_sub_code])
                    ->andFilterWhere(['or',
                        ['like', 'lt.name', $this->target_word],
                        ['like', 'lt.registration_number', $this->target_word],
                        ['like', 'lt.attributes', $this->target_word],
                        ['like', 'lt.memo', $this->target_word],
                    ])
                    ->andFilterWhere(['not',
                        ['or', ['<', 'cd.term_end_at', $term_from], ['>', 'cd.term_start_at', $term_to]],
                    ])
                    ->andFilterWhere(['c.customer_id' => $this->customer_id])
                    ->andFilterWhere(['cc.repayment_pattern_id' => $this->repayment_pattern_id])
                    ->andFilterWhere(['lcs2.lease_contract_status_type_id' => $this->lease_contract_status_type_id])
                    ->andFilterWhere(['cd.tax_application_id' => $this->tax_application_id]);

                return $dataProvider;
        }
    }
}