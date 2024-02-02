<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

class RepaymentSearch extends Repayment
{
    public $model;
    public $contract_pattern_id;

    public function rules()
    {
        return [
            [['contract_pattern_id'], 'safe'],
            [['repayment_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => RepaymentType::class, 'targetAttribute' => ['repayment_type_id' => 'repayment_type_id']],
        ];
    }

    public function search($params)
    {
        $this->load($params);
        $this->contract_pattern_id = $params['RepaymentSearch']['contract_pattern_id'] ?? null;
        $session = Yii::$app->session;
        $session['repayment-search-params'] = $params;
        $filter_code = $session['filter-code'];
        $filter_name = $session['filter-name'];
        $filter_repayment_type_id = $session['filter-repayment_type_id'];
        $filter_contract_code = $session['filter-contract_code'];
        $query = $this->model->getMonthlyCharges()->alias('mc')
            ->innerJoin('contract_detail cd', 'mc.contract_detail_id=cd.contract_detail_id')
            ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
            ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
            ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
            ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id')
            ->leftJoin('repayment r', 'r.monthly_charge_id=mc.monthly_charge_id')
            ->andFilterWhere(['lc.contract_pattern_id' => $this->contract_pattern_id])
            ->andFilterWhere(['IFNULL(r.repayment_type_id, IFNULL(mc.repayment_type_id, rp.repayment_type_id))' => $this->repayment_type_id])
            ->andFilterWhere(['like', 'c.name', $filter_name])
            ->andFilterWhere(['like', 'c.customer_code', $filter_code])
            ->andFilterWhere(['IFNULL(r.repayment_type_id, IFNULL(mc.repayment_type_id, rp.repayment_type_id))' => $filter_repayment_type_id])
            ->andFilterWhere(['like', 'lc.code_search', $filter_contract_code])
            ->orderBy([
                'cc.client_corporation_id' => SORT_ASC,
                'c.customer_code' =>SORT_ASC,
                'lc.disp_order' => SORT_ASC,
                'cd.contract_type' => SORT_ASC,
                'cd.term_start_at' =>SORT_ASC,
                'mc.monthly_charge_id' => SORT_ASC]);

        $dataProvider = new \yii\data\ActiveDataProvider(['query' => $query]);
        return $dataProvider;
    }
}