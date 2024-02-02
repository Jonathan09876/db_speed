<?php

namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\VarDumper;

class MonthlyChargeSearch2 extends MonthlyCharge
{
    public $target_term;
    public $target_term_year;
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
    public $store_collection_data;
    public $export_collection_data;
    public $credit_debt_collection_by_agency;
    public $credit_debt_collection_by_customer;
    public $register_repayments;
    public $hide_collection;
    public $hide_payment;
    public $repayment_types;

    public $span = 'year';

    static $selections = [
        'collection' => '回収',
        'payment' => '支払',
    ];

    public function rules()
    {
        return [
            [['target_term', 'target_term_year'], 'required'],
            [['repayment_pattern_id'], 'required', 'when' => function($data){
                return !!$data->calc_collection_data;
            }, 'whenClient' => "function(attr,val){return false}", 'message' => '回収条件を選択してください。'],
            [[
                'customer_id', 'contract_pattern_id', 'contract_number', 'contract_code', 'contract_sub_code', 'client_corporation_id',
                'target_word', 'term_from', 'term_to', 'repayment_pattern_id', 'lease_contract_status_type_id', 'tax_application_id',
                'calc_collection_data', 'export_collection_data', 'store_collection_data',
                'credit_debt_collection_by_agency', 'credit_debt_collection_by_customer',
                'register_repayments', 'hide_collection', 'hide_payment',
                'repayment_types',
            ], 'safe'],
            [['span'], 'in', 'range' => ['year', 'quarter', 'quarter1', 'quarter2', 'quarter3', 'quarter4']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'target_term_year' => '対象年',
            'target_term' => '対象年月',
            'client_corporation_id' => '対象会社',
            'customer_code' => '得意先コード',
            'customer_name' => '得意先名',
            'target_word' => '物件キーワード',
            'repayment_pattern_id' => '支払条件',
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

    public function getTermsFromSpan($year = null)
    {
        $targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->target_term));
        $year = !isset($year) ? ($this->target_term_year ?? date('Y')) : $year;
        $current_month = !isset($year) ? $targetTerm->format('n') : date('n');
        switch($this->span) {
            case 'year':
                $clientCorporation = $this->clientCorporation;
                $last_month = $clientCorporation->account_closing_month;
                $to = (new \DateTime())->setDate((int)$year + ($current_month > $last_month ? 1 : 0), $last_month, 1);
                $from = (clone $to)->modify('-11 month');
                return compact("from", "to");
            case 'quarter':
                $clientCorporation = $this->clientCorporation;
                $last_month = $clientCorporation->account_closing_month;
                $to = (new \DateTime())->setDate((int)$year + ($current_month > $last_month ? 1 : 0), $last_month, 1);
                $from = (clone $to)->modify('-11 month');
                $nth = intdiv(($current_month - $last_month + 11) % 12, 3);
                $from = $nth > 0 ? ($from->modify((string)($nth * 3) . ' month')) : $from;
                $to = (clone $from)->modify('2 month');
                return compact("from", "to");
            case 'quarter1':
                $clientCorporation = $this->clientCorporation;
                $last_month = $clientCorporation->account_closing_month;
                $to = (new \DateTime())->setDate((int)$year + ($current_month > $last_month ? 1 : 0), $last_month, 1);
                $from = (clone $to)->modify('-11 month');
                $to = (clone $from)->modify('2 month');
                return compact("from", "to");
            case 'quarter2':
                $clientCorporation = $this->clientCorporation;
                $last_month = $clientCorporation->account_closing_month;
                $to = (new \DateTime())->setDate((int)$year + ($current_month > $last_month ? 1 : 0), $last_month, 1);
                $from = (clone $to)->modify('-11 month');
                $nth = 1;
                $from = $nth > 0 ? ($from->modify((string)($nth * 3) . ' month')) : $from;
                $to = (clone $from)->modify('2 month');
                return compact("from", "to");
            case 'quarter3':
                $clientCorporation = $this->clientCorporation;
                $last_month = $clientCorporation->account_closing_month;
                $to = (new \DateTime())->setDate((int)$year + ($current_month > $last_month ? 1 : 0), $last_month, 1);
                $from = (clone $to)->modify('-11 month');
                $nth = 2;
                $from = $nth > 0 ? ($from->modify((string)($nth * 3) . ' month')) : $from;
                $to = (clone $from)->modify('2 month');
                return compact("from", "to");
            case 'quarter4':
                $clientCorporation = $this->clientCorporation;
                $last_month = $clientCorporation->account_closing_month;
                $to = (new \DateTime())->setDate((int)$year + ($current_month > $last_month ? 1 : 0), $last_month, 1);
                $from = (clone $to)->modify('-11 month');
                $nth = 3;
                $from = $nth > 0 ? ($from->modify((string)($nth * 3) . ' month')) : $from;
                $to = (clone $from)->modify('2 month');
                return compact("from", "to");
        }
    }

    public function search($params)
    {
        $this->load($params);
        $session = Yii::$app->session;
        $session['monthly_charge_search_params'] = $params;

        if (isset($params['MonthlyChargeSearch2']['target_term_year'])) {
            $this->target_term = $this->target_term_year . date('年n月');
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

        if (!!$this->credit_debt_collection_by_agency || !!$this->credit_debt_collection_by_customer){
            $query = LeaseContract::find()->alias('lc')->distinct()
                ->innerJoin('contract_detail cd', 'cd.lease_contract_id=lc.lease_contract_id')
                ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
                ->innerJoin('lease_servicer ls', 'cd.lease_servicer_id=ls.lease_servicer_id')
                ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
                ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id')
                ->innerJoin('lease_target lt', 'lc.lease_target_id=lt.lease_target_id')
                ->innerJoin('tax_application ta', 'cd.tax_application_id=ta.tax_application_id')
                ->leftJoin('lease_contract_status lcs1', 'lcs1.lease_contract_id=lc.lease_contract_id')
                ->leftJoin('lease_contract_status lcs2', 'lcs1.lease_contract_id=lcs2.lease_contract_id AND lcs1.registered < lcs2.registered');
            if (!!$this->credit_debt_collection_by_agency) {
                $query
                    ->orderBy(['ls.lease_servicer_id' => SORT_ASC,'cc.client_corporation_id' => SORT_ASC, 'c.customer_code' =>SORT_ASC, 'lc.disp_order' => SORT_ASC, 'cd.term_start_at' =>SORT_ASC]);
            }

            if (!!$this->credit_debt_collection_by_customer) {
                $query
                    ->orderBy(['c.customer_code' => SORT_ASC, 'cc.client_corporation_id' => SORT_ASC, 'lc.disp_order' => SORT_ASC, 'cd.term_start_at' =>SORT_ASC]);
            }

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);

            if (!$this->validate()) {
                return $dataProvider;
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
                ->andFilterWhere(['c.customer_id' => $this->customer_id])
                ->andFilterWhere(['cc.repayment_pattern_id' => $this->repayment_pattern_id])
                ->andFilterWhere(['lcs2.lease_contract_status_type_id' => $this->lease_contract_status_type_id])
                ->andFilterWhere(['cd.tax_application_id' => $this->tax_application_id]);
/*
            $span = $this->getTermsFromSpan();
            $query
                ->andWhere(['not', ['or', ['<', 'cd.term_end_at', $span['from']->format('Y-m-01')], ['>', 'cd.term_start_at', $span['to']->format('Y-m-t')]]])
                ->orderBy(['cd.lease_servicer_id' => SORT_ASC, 'c.customer_id' => SORT_ASC, 'lc.lease_contract_id' => SORT_ASC]);
*/
            return $dataProvider;
        }
        else if (!!$this->calc_collection_data){
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
                ->where(['and', ['lcs2.lease_contract_status_id' => null], ['not', ['cd.monthly_charge' => 0]]])
                ->andFilterWhere(['rp.repayment_type_id' => $this->repayment_types])
                ->orderBy(['cc.client_corporation_id' => SORT_ASC, 'c.customer_code' =>SORT_ASC, 'lc.disp_order' => SORT_ASC, 'cd.term_start_at' =>SORT_ASC]);
                //->orderBy(['c.customer_id' => SORT_ASC, 'cd.contract_detail_id' => SORT_ASC]);

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);

            if (!$this->validate()) {
                //return $dataProvider;
            }
            if (!!$this->register_repayments) {
                $query
                    ->andWhere(['not',
                        ['or', ['<', 'cd.term_end_at', $targetTerm->format('Y-m-01')], ['>', 'cd.term_start_at', $targetTerm->format('Y-m-t')]],
                    ])
                    ->andWhere(['and', ['r.repayment_id' => null], ['d.debt_id' => null]]);
            }
            else {
                $query
                    ->andWhere(['not',
                        ['or', ['<', 'cd.term_end_at', $lastTerm->format('Y-m-01')], ['>', 'cd.term_start_at', $targetTerm->format('Y-m-t')]],
                    ]);
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
        /*
        else {
            $query = self::find()->alias('mc')->distinct()
                ->leftJoin('monthly_charge mc2', 'mc.contract_detail_id=mc2.contract_detail_id AND mc.term=mc2.term AND mc.monthly_charge_id > mc2.monthly_charge_id')
                ->innerJoin('contract_detail cd', 'mc.contract_detail_id=cd.contract_detail_id')
                ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
                ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
                ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
                ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id')
                ->innerJoin('lease_target lt', 'lc.lease_target_id=lt.lease_target_id')
                ->leftJoin('lease_contract_status lcs1', 'lcs1.lease_contract_id=lc.lease_contract_id')
                ->leftJoin('lease_contract_status lcs2', 'lcs1.lease_contract_id=lcs2.lease_contract_id AND lcs1.registered < lcs2.registered')
                ->where(['mc2.monthly_charge_id' => null])
                //->orderBy(['c.customer_id' => SORT_ASC, 'cd.contract_detail_id' => SORT_ASC]);
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
                ->orWhere(['not', ['or', ['<', 'cd.term_end_at', $span['from']->format('Y-m-01')], ['>', 'cd.term_start_at', $span['to']->format('Y-m-t')]]])
                ->andFilterWhere(['DATE_FORMAT(CASE rp.target_month WHEN \'current\' THEN mc.term ELSE mc.term + INTERVAL 1 MONTH END, "%Y年%c月")' => $this->target_term])
                //->andFilterWhere(['DATE_FORMAT(mc.term, "%Y年%c月")' => $this->target_term])
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
        */
        else {
            $query = ContractDetail::find()->alias('cd')->distinct()
                ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
                ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
                ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
                ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id')
                ->innerJoin('lease_target lt', 'lc.lease_target_id=lt.lease_target_id')
                ->leftJoin('lease_contract_status lcs1', 'lcs1.lease_contract_id=lc.lease_contract_id')
                ->leftJoin('lease_contract_status lcs2', 'lcs1.lease_contract_id=lcs2.lease_contract_id AND lcs1.registered < lcs2.registered')
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
                ->andWhere(['not', ['or', ['<', 'cd.term_end_at', $span['from']->format('Y-m-01')], ['>', 'cd.term_start_at', $span['to']->format('Y-m-t')]]])
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