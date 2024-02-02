<?php

namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;

class CdMonthlyChargeSearch extends MonthlyCharge
{
    public $target_term;
    public $target_word;
    public $term_from;
    public $term_to;

    public $customer_id;
    public $customer_code;
    public $customer_name;

    public $contract_pattern_id;
    public $contract_number;
    public $contract_code;
    public $contract_sub_code;
    public $client_corporation_id;

    public $lease_servicer_id;
    public $repayment_pattern_id;
    public $contract_type;
    public $lease_contract_status_type_id;
    public $tax_application_id;

    public $calc_collection_data;
    public $export_collection_data;
    public $credit_debt_collection_by_agency;
    public $credit_debt_collection_by_customer;
    public $register_repayments;
    public $hide_collection;
    public $hide_payment;
    public $repayment_types;

    static $selections = [
        'collection' => '回収',
        'payment' => '支払',
    ];

    public function rules()
    {
        return [
            [['target_term'], 'required'],
            [['repayment_pattern_id'], 'required', 'when' => function($data){
                return !!$data->calc_collection_data;
            }, 'whenClient' => "function(attr,val){return false}", 'message' => '回収条件を選択してください。'],
            [[
                'customer_id', 'contract_pattern_id', 'contract_number', 'contract_code', 'contract_sub_code', 'client_corporation_id',
                'target_word', 'term_from', 'term_to', 'repayment_pattern_id', 'lease_contract_status_type_id', 'tax_application_id',
                'calc_collection_data', 'export_collection_data',
                'credit_debt_collection_by_agency', 'credit_debt_collection_by_customer',
                'register_repayments', 'hide_collection', 'hide_payment',
                'repayment_types',
            ], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'target_term' => '対象年月',
            'client_corporation_id' => '対象会社',
            'customer_code' => '得意先コード',
            'customer_name' => '得意先名',
            'target_word' => '物件キーワード',
            'repayment_pattern_id' => '回収条件',
            'lease_contract_status_type_id' => '契約ステータス',
            'contract_pattern_id' => '契約情報',
            'tax_application_id' => '税区分',
            'balance_selection' => '収支',
            'hide_collection' => '回収を非表示',
            'hide_payment' => '支払を非表示',
            'repayment_types' => '回収区分',
            'register_repayments' => '実績登録済み'
        ];
    }

    public function updateSession()
    {
        $session = Yii::$app->session;
        $session['customer-client-corporations'] = $this->client_corporation_id;
    }

    public function getClientCorporation()
    {
        $client_corporation_id = empty($this->client_corporation_id) ? Yii::$app->user->identity->client_corporation_id : $this->client_corporation_id;
        return ClientCorporation::findOne($client_corporation_id);
    }

    public function getCustomer()
    {
        return Customer::findOne($this->customer_id);
    }

    public function search($params)
    {
        $this->load($params);
        $session = Yii::$app->session;
        $session['monthly_charge_search_params'] = $params;

        if (empty($this->target_term)) {
            $this->target_term = (new \DateTime())->format('Y年n月');
        }
        if ($this->customer_id) {
            $customer = $this->customer;
            $this->customer_code = $customer->customer_code;
            $this->customer_name = $customer->name;
        }

        $term_from = !empty($this->term_from) ? (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->term_from)))->format('Y-m-01') : null;
        $term_to = !empty($this->term_to) ? (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->term_to)))->format('Y-m-t') : null;
        $targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->target_term));
        $lastTerm = (clone $targetTerm)->modify('-1 month');

        $query = LeaseContract::find()->alias('lc')->distinct()
            ->innerJoin('contract_detail cd', 'cd.lease_contract_id=lc.lease_contract_id')
            ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
            ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
            ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id')
            ->innerJoin('lease_target lt', 'lc.lease_target_id=lt.lease_target_id')
            ->innerJoin('tax_application ta', 'cd.tax_application_id=ta.tax_application_id')
            ->leftJoin('lease_contract_status lcs1', 'lcs1.lease_contract_id=lc.lease_contract_id')
            ->leftJoin('lease_contract_status lcs2', 'lcs1.lease_contract_id=lcs2.lease_contract_id AND lcs1.registered < lcs2.registered')
            ->leftJoin('monthly_charge mc', 'cd.contract_detail_id=mc.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc.term + INTERVAL 1 MONTH ELSE mc.term END = :term')
            ->leftJoin('repayment r', 'r.monthly_charge_id=mc.monthly_charge_id')
            ->leftJoin('debt d', 'd.monthly_charge_id=mc.monthly_charge_id')
            ->params([':term' => $targetTerm->format('Y-m-01')])
            ->where(['and',['not', ['mc.monthly_charge_id' => null]],['not', ['cd.monthly_charge' => 0]]])
            ->andFilterWhere(['rp.repayment_type_id' => $this->repayment_types])
            ->orderBy(['c.customer_id' => SORT_ASC, 'cd.contract_detail_id' => SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!$this->validate()) {
            //return $dataProvider;
        }
        if (!!$this->register_repayments) {
            $query
                ->andWhere(['and', ['r.repayment_id' => null], ['d.debt_id' => null]]);
        }
        $query
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