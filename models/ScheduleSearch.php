<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\VarDumper;

class ScheduleSearch extends Model
{
    public $target_term_year;
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
    public $store_collection_data;
    public $export_collection_data;
    public $credit_debt_collection_by_agency;
    public $credit_debt_collection_by_customer;
    public $register_repayments;
    public $hide_collection;
    public $hide_payment;
    public $repayment_types;

    public $span = 'year';

    public $group_by_customer = 0;
    public $show_without_deficient = 0;
    
    public $do_search = false;
    public $skip_search = false;

    static $selections = [
        'collection' => '回収',
        'payment' => '支払',
    ];

    public function init()
    {
        $this->target_term_year = empty($this->target_term_year) ? date('Y') : $this->target_term_year;
        $this->target_term = $this->target_term_year . date('年n月');
    }

    public function rules()
    {
        return [
            [['target_term_year'], 'required'],
            [['repayment_pattern_id'], 'required', 'when' => function($data){
                return !!$data->calc_collection_data;
            }, 'whenClient' => "function(attr,val){return false}", 'message' => '回収条件を選択してください。'],
            [['target_term'], 'match', 'pattern' => '/\d+年\d+月/'],
            [[
                'customer_id', 'contract_pattern_id', 'contract_number', 'contract_code', 'contract_sub_code', 'client_corporation_id',
                'target_word', 'term_from', 'term_to', 'repayment_pattern_id', 'lease_contract_status_type_id', 'tax_application_id',
                'calc_collection_data', 'export_collection_data', 'store_collection_data',
                'credit_debt_collection_by_agency', 'credit_debt_collection_by_customer',
                'register_repayments', 'hide_collection', 'hide_payment',
                'repayment_types', 'group_by_customer', 'show_without_deficient'
            ], 'safe'],
            [['skip_search'], 'boolean'],
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
            'register_repayments' => '実績登録済み',
            'group_by_customer' => '会社ごとにまとめる',
            'show_without_deficient' => '全件表示'
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

    public function getRepaymentPattern()
    {
        return RepaymentPattern::findOne($this->repayment_pattern_id);
    }

    public function getTermsFromSpan($year = null, $span = null)
    {
        $year = !isset($year) ? ($this->target_term_year ?? date('Y')) : $year;
        $current_month = date('n');
        $span = $span ?? $this->span;
        switch($span) {
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

    public function getTargetTerm()
    {
        return new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $this->target_term));
    }

    public function search($params, $when = null)
    {
        
        $this->load($params);
        if (!empty($params['ScheduleSearch'])) {
            $this->do_search = ((int)$this->skip_search != 1);
        }
        if ($this->customer_id) {
            $customer = $this->customer;
            $this->customer_code = $customer->customer_code;
            $this->customer_name = $customer->name;
        }
        $session = Yii::$app->session;
        $session['schedule_search_params'] = $params;
        if ($when == 'delinquencies') {
            $when = $this->group_by_customer ? 'delinquencies' : 'delinquencies-recent';
        }
        switch($when) {
            case 'store_collection_data':
            case 'calc_collection_data':
                if (empty($this->repayment_pattern_id)) {
                    $this->repayment_pattern_id = 1;
                }
                $term_from = !empty($this->term_from) ? (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->term_from)))->format('Y-m-01') : null;
                $term_to = !empty($this->term_to) ? (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->term_to)))->format('Y-m-t') : null;
                $targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->target_term));
                $lastTerm = (clone $targetTerm)->modify('-1 month');
                $stored_id = \app\models\TargetTermMonthlyChargeStored::isStored($targetTerm->format('Y-m-d'), $this->client_corporation_id, $this->repayment_pattern_id);
                //echo "stored_id:{$stored_id}</br/>";
                if ($stored_id) {
                    $query = LeaseContract::find()->alias('lc')->distinct()
                        ->innerJoin('contract_detail cd', 'cd.lease_contract_id=lc.lease_contract_id')
                        ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
                        ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
                        ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id')
                        ->innerJoin('lease_target lt', 'lc.lease_target_id=lt.lease_target_id')
                        ->innerJoin('tax_application ta', 'cd.tax_application_id=ta.tax_application_id')
                        ->innerJoin('monthly_charge_span mcs', 'cd.contract_detail_id=mcs.contract_detail_id')
                        ->leftJoin('lease_contract_status lcs1', 'lcs1.lease_contract_id=lc.lease_contract_id')
                        ->leftJoin('lease_contract_status lcs2', 'lcs1.lease_contract_id=lcs2.lease_contract_id AND lcs1.registered < lcs2.registered')
                        ->leftJoin('monthly_charge mc', 'cd.contract_detail_id=mc.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc.term + INTERVAL 1 MONTH ELSE mc.term END = :term')
                        ->leftJoin('repayment r', 'r.monthly_charge_id=mc.monthly_charge_id')
                        ->where(['and', ['lcs2.lease_contract_status_id' => null], ['not', ['cd.monthly_charge' => 0]]])
                        ->andWhere(['or',
                            ['not', ['lcs1.lease_contract_status_type_id' => 10]],
                            ['and', ['lcs1.lease_contract_status_type_id' => 10], '(SELECT COUNT(`mc1`.`monthly_charge_id`) FROM `monthly_charge` `mc1`
                            INNER JOIN `contract_detail` `cd1` ON `mc1`.`contract_detail_id`=`cd1`.`contract_detail_id`
                            INNER JOIN `lease_contract` `lc1` ON `cd1`.`lease_contract_id`=`lc1`.`lease_contract_id`
                            INNER JOIN `customer` `c1` ON `lc1`.`customer_id`=`c1`.`customer_id`
                            INNER JOIN `client_contract` `cc1` ON `c1`.`client_contract_id`=`cc1`.`client_contract_id`
                            INNER JOIN `repayment_pattern` `rp1` ON `cc1`.`repayment_pattern_id`=`rp1`.`repayment_pattern_id`
                            WHERE `cd1`.`contract_detail_id`=`cd`.`contract_detail_id` AND
                                IFNULL(`mc1`.`repayment_type_id`, `rp1`.`repayment_type_id`) NOT IN (12) AND
                                CASE `rp1`.`target_month` WHEN "next" THEN `mc1`.`term` + INTERVAL 1 MONTH ELSE `mc1`.`term` END IN (:term, :term2)) > 0'],
                                                        ['lcs1.lease_contract_status_id' => null]
                        ])
                        //今月のmonthlychargeがstoreされている、もしくは今月のmonthlyChargeはnullだが先月レコードあり
                        ->andWhere(['or', ['mc.monthly_charge_id' => null], 'mc.monthly_charge_id IN (SELECT `monthly_charge_id` FROM `stored_monthly_charge` WHERE `target_term_monthly_charge_stored_id`=:stored_id)'])
                        ->params([':term' => $targetTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01'), ':stored_id' => $stored_id])
                        ->andFilterWhere(['rp.repayment_type_id' => $this->repayment_types])
                        ->orderBy(['cc.client_corporation_id' => SORT_ASC, 'c.customer_code' =>SORT_ASC, 'lc.disp_order' => SORT_ASC, 'cd.term_start_at' =>SORT_ASC]);
                }
                else {
                    echo "afeafaefafeafaelkfjjlkj";
                    $query = LeaseContract::find()->alias('lc')->distinct()
                        ->innerJoin('contract_detail cd', 'cd.lease_contract_id=lc.lease_contract_id')
                        ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
                        ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
                        ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id')
                        ->innerJoin('lease_target lt', 'lc.lease_target_id=lt.lease_target_id')
                        ->innerJoin('tax_application ta', 'cd.tax_application_id=ta.tax_application_id')
                        ->innerJoin('monthly_charge_span mcs', 'cd.contract_detail_id=mcs.contract_detail_id')
                        ->leftJoin('lease_contract_status lcs1', 'lcs1.lease_contract_id=lc.lease_contract_id')
                        ->leftJoin('lease_contract_status lcs2', 'lcs1.lease_contract_id=lcs2.lease_contract_id AND lcs1.registered < lcs2.registered')
                        ->leftJoin('monthly_charge mc', 'cd.contract_detail_id=mc.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc.term + INTERVAL 1 MONTH ELSE mc.term END = :term')
                        ->leftJoin('repayment r', 'r.monthly_charge_id=mc.monthly_charge_id')
                        ->params([':term' => $targetTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->where(['and', ['lcs2.lease_contract_status_id' => null], ['not', ['cd.monthly_charge' => 0]]])
                        ->andWhere(['or',
                            ['not', ['lcs1.lease_contract_status_type_id' => 10]],
                            ['and', ['lcs1.lease_contract_status_type_id' => 10], '(SELECT COUNT(`mc1`.`monthly_charge_id`) FROM `monthly_charge` `mc1`
                            INNER JOIN `contract_detail` `cd1` ON `mc1`.`contract_detail_id`=`cd1`.`contract_detail_id`
                            INNER JOIN `lease_contract` `lc1` ON `cd1`.`lease_contract_id`=`lc1`.`lease_contract_id`
                            INNER JOIN `customer` `c1` ON `lc1`.`customer_id`=`c1`.`customer_id`
                            INNER JOIN `client_contract` `cc1` ON `c1`.`client_contract_id`=`cc1`.`client_contract_id`
                            INNER JOIN `repayment_pattern` `rp1` ON `cc1`.`repayment_pattern_id`=`rp1`.`repayment_pattern_id`
                            WHERE `cd1`.`contract_detail_id`=`cd`.`contract_detail_id` AND
                                IFNULL(`mc1`.`repayment_type_id`, `rp1`.`repayment_type_id`) NOT IN (12) AND
                                CASE `rp1`.`target_month` WHEN "next" THEN `mc1`.`term` + INTERVAL 1 MONTH ELSE `mc1`.`term` END IN (:term, :term2)) > 0'],
                                                        ['lcs1.lease_contract_status_id' => null]
                        ])
                        ->andFilterWhere(['rp.repayment_type_id' => $this->repayment_types])
                        ->orderBy(['cc.client_corporation_id' => SORT_ASC, 'c.customer_code' =>SORT_ASC, 'lc.disp_order' => SORT_ASC, 'cd.term_start_at' =>SORT_ASC]);
                }
                
                $dataProvider = new ActiveDataProvider([
                    'query' => $query,
                ]);

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
                            //['or', ['<', 'mcs.last_term', $lastTerm->format('Y-m-01')], ['>', 'mcs.first_term', $targetTerm->format('Y-m-t')]],
                            ['or', ['<', 'CASE `rp`.`target_month` WHEN "next" THEN `mcs`.`last_term` + INTERVAL 1 MONTH ELSE `mcs`.`last_term` END', $lastTerm->format('Y-m-01')], ['>', '`mcs`.`first_term`', $targetTerm->format('Y-m-t')]],
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
                    ->andFilterWhere(['lcs1.lease_contract_status_type_id' => $this->lease_contract_status_type_id])
                    ->andFilterWhere(['cd.tax_application_id' => $this->tax_application_id]);

                if (!$this->validate()) {
                    $query->andWhere(['lc.lease_contract_id' => 0]);
                }
                return $dataProvider;
                break;
            case 'credit_debt_collection_by_agency':
            case 'credit_debt_collection_by_customer':
                $query = LeaseContract::find()->alias('lc')->distinct()
                    ->innerJoin('contract_detail cd', 'cd.lease_contract_id=lc.lease_contract_id')
                    ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
                    ->innerJoin('lease_servicer ls', 'cd.lease_servicer_id=ls.lease_servicer_id')
                    ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
                    ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id')
                    ->innerJoin('lease_target lt', 'lc.lease_target_id=lt.lease_target_id')
                    ->innerJoin('tax_application ta', 'cd.tax_application_id=ta.tax_application_id')
                    ->leftJoin('monthly_charge_span mcs', 'mcs.contract_detail_id=cd.contract_detail_id')
                    ->leftJoin('monthly_payment_span mps', 'mps.contract_detail_id=cd.contract_detail_id')
                    ->leftJoin('lease_contract_status lcs1', 'lcs1.lease_contract_id=lc.lease_contract_id')
                    ->leftJoin('lease_contract_status lcs2', 'lcs1.lease_contract_id=lcs2.lease_contract_id AND lcs1.registered < lcs2.registered')
                    ->where('((SELECT lease_contract_status_type_id FROM lease_contract_status WHERE lease_contract_status.lease_contract_id=lc.lease_contract_id ORDER BY lease_contract_status.registered DESC LIMIT 1) NOT IN (5, 10)) OR ((SELECT lease_contract_status_type_id FROM lease_contract_status WHERE lease_contract_status.lease_contract_id=lc.lease_contract_id ORDER BY lease_contract_status.registered DESC LIMIT 1) IS NULL)')
                    ->andWhere(['not', ['cd.contract_type' => 'delinquency']]);
                if ($when == 'credit_debt_collection_by_agency') {
                    $query
                        ->orderBy(['ls.lease_servicer_id' => SORT_ASC,'cc.client_corporation_id' => SORT_ASC, 'c.customer_code' =>SORT_ASC, 'lc.disp_order' => SORT_ASC, 'cd.term_start_at' =>SORT_ASC]);
                }

                if ($when == 'credit_debt_collection_by_customer') {
                    $query
                        ->orderBy(['c.customer_code' => SORT_ASC, 'cc.client_corporation_id' => SORT_ASC, 'lc.disp_order' => SORT_ASC, 'cd.term_start_at' =>SORT_ASC]);
                }

                $dataProvider = new ActiveDataProvider([
                    'query' => $query,
                ]);

                if (!$this->validate()) {
                    return $dataProvider;
                }
                $span = $this->getTermsFromSpan($this->target_term_year);
                $query
                    ->andWhere(['not', ['or', ['<', 'IF(IFNULL(mcs.last_term, "2000-01-01") > IFNULL(mps.last_term, "2000-01-01"), mcs.last_term, mps.last_term)', $span['from']->format('Y-m-01')], ['>', 'IF(IFNULL(mcs.first_term, "3000-01-01") < IFNULL(mps.first_term, "3000-01-01"), mcs.first_term, mps.first_term)', $span['to']->format('Y-m-t')]]])
                    //->andWhere(['not', ['or', ['<', 'IF(mcs.last_term > mps.last_term, mcs.last_term, mps.last_term)', $span['from']->format('Y-m-01')], ['>', 'IF(mcs.first_term < mps.first_term, mcs.first_term, mps.first_term)', $span['to']->format('Y-m-t')]]])
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
                    ->andFilterWhere(['lcs1.lease_contract_status_type_id' => $this->lease_contract_status_type_id])
                    ->andFilterWhere(['cd.tax_application_id' => $this->tax_application_id]);
                return $dataProvider;
            case 'delinquencies-recent':
                $query = ContractDetail::find()->alias('cd')->distinct()
                    ->select(['cd.*',
                        'SUM(`clc`.`monthly_charge_amount_with_tax` - `clc`.`repayment_amount_with_tax`) as `delinquencies`'
                        ])
                    ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
                    ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
                    ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
                    ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id')
                    ->innerJoin('lease_target lt', 'lc.lease_target_id=lt.lease_target_id')
                    ->innerJoin('collection_cell clc', 'cd.contract_detail_id=clc.contract_detail_id')
                    ->innerJoin('term t', 'clc.term_id=t.term_id AND t.term <=:term')
                    ->leftJoin('lease_contract_status lcs1', 'lcs1.lease_contract_id=lc.lease_contract_id')
                    ->leftJoin('lease_contract_status lcs2', 'lcs1.lease_contract_id=lcs2.lease_contract_id AND lcs1.registered < lcs2.registered')
                    ->leftJoin('monthly_charge_span mcs', 'mcs.contract_detail_id=cd.contract_detail_id')
                    ->leftJoin('monthly_payment_span mps', 'mps.contract_detail_id=cd.contract_detail_id')
                    ->params([
                        ':term' => $this->targetTerm->format('Y-m-d')
                    ])
                    ->groupBy(['cd.contract_detail_id'])
                    //->having(['>', 'delinquencies', 0])
                    ->orderBy(['cc.client_corporation_id' => SORT_ASC, 'c.customer_code' =>SORT_ASC, 'lc.disp_order' => SORT_ASC, 'cd.term_start_at' =>SORT_ASC]);
                if (!$this->show_without_deficient) {
                    $query->having(['>', 'delinquencies', 0]);
                }
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
                $from = $this->term_from ? new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->term_from)) : $span['from'];
                $to = $this->term_to ? new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->term_to)) : $span['to'];
                $query
                    ->andWhere(['not', ['or', ['<', 'IF(mcs.last_term > mps.last_term, mcs.last_term, mps.last_term)', $from->format('Y-m-01')], ['>', 'IF(mcs.first_term < mps.first_term, mcs.first_term, mps.first_term)', $to->format('Y-m-t')]]])
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
                    ->andFilterWhere(['lcs1.lease_contract_status_type_id' => $this->lease_contract_status_type_id])
                    ->andFilterWhere(['cd.tax_application_id' => $this->tax_application_id]);

                return $dataProvider;
            case 'delinquencies':
                Yii::$app->db->createCommand('SET group_concat_max_len = 5120')->execute();
                $query = ContractDetail::find()->alias('cd')->distinct()
                    ->select(['cd.*',
                        'SUM(`clc`.`monthly_charge_amount_with_tax` - `clc`.`repayment_amount_with_tax`) as `delinquencies`',
                        //'(SELECT CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE t.term >= application_from AND t.term <= IFNULL(application_to, "2099-12-31")) END FROM tax_application ta WHERE ta.tax_application_id=cd.tax_application_id) as `tax_rate`',
                        'GROUP_CONCAT(DISTINCT cd.contract_detail_id ORDER BY lc.disp_order ASC) AS cdids',
                    ])
                    ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
                    ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
                    ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
                    ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id')
                    ->innerJoin('lease_target lt', 'lc.lease_target_id=lt.lease_target_id')
                    ->innerJoin('collection_cell clc', 'cd.contract_detail_id=clc.contract_detail_id')
                    ->innerJoin('term t', 'clc.term_id=t.term_id AND t.term <=:term')
                    ->leftJoin('lease_contract_status lcs1', 'lcs1.lease_contract_id=lc.lease_contract_id')
                    ->leftJoin('lease_contract_status lcs2', 'lcs1.lease_contract_id=lcs2.lease_contract_id AND lcs1.registered < lcs2.registered')
                    ->leftJoin('monthly_charge_span mcs', 'mcs.contract_detail_id=cd.contract_detail_id')
                    ->leftJoin('monthly_payment_span mps', 'mps.contract_detail_id=cd.contract_detail_id')
                    ->params([
                        ':term' => $this->targetTerm->format('Y-m-d')
                    ])
                    //->groupBy(['lc.customer_id', 'tax_rate'])
                    ->groupBy(['lc.customer_id'])
                    //->having(['>', 'delinquencies', 0])
                    ->orderBy(['c.customer_code' =>SORT_ASC, 'lc.disp_order' => SORT_ASC]);
                if (!$this->show_without_deficient) {
                    $query->having(['>', 'delinquencies', 0]);
                }
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
                $from = $this->term_from ? new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->term_from)) : $span['from'];
                $to = $this->term_to ? new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->term_to)) : $span['to'];
                $query
                    ->andWhere(['not', ['or', ['<', 'IF(mcs.last_term > mps.last_term, mcs.last_term, mps.last_term)', $from->format('Y-m-01')], ['>', 'IF(mcs.first_term < mps.first_term, mcs.first_term, mps.first_term)', $to->format('Y-m-t')]]])
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
                    ->andFilterWhere(['lcs1.lease_contract_status_type_id' => $this->lease_contract_status_type_id])
                    ->andFilterWhere(['cd.tax_application_id' => $this->tax_application_id]);
                return $dataProvider;
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
                    ->orderBy(['cc.client_corporation_id' => SORT_ASC, 'c.customer_code' =>SORT_ASC, 'lc.disp_order' => SORT_ASC, 'cd.contract_type' => SORT_ASC, 'cd.term_start_at' =>SORT_ASC]);
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
                $lastYearLastTerm = (clone $span['from'])->modify('-1 month');
                $sql = <<<EOS
                    SELECT COUNT(`mc`.`monthly_charge_id`) FROM `monthly_charge` `mc`
                    INNER JOIN `contract_detail` `cdx` ON `mc`.`contract_detail_id`=`cdx`.`contract_detail_id`
                    INNER JOIN `lease_contract` `lcx` ON `cdx`.`lease_contract_id`=`lcx`.`lease_contract_id`
                    INNER JOIN `customer` `cx` ON `lcx`.`customer_id`=`cx`.`customer_id`
                    INNER JOIN `client_contract` `ccx` ON `cx`.`client_contract_id`=`ccx`.`client_contract_id`
                    INNER JOIN `repayment_pattern` `rpx` ON `ccx`.`repayment_pattern_id`=`rpx`.`repayment_pattern_id` 
                    WHERE
                        `lcx`.`lease_contract_id`=`lc`.`lease_contract_id`
                        AND CASE `rpx`.`target_month` WHEN 'next' THEN `mc`.`term` + INTERVAL 1 MONTH ELSE `mc`.`term` END BETWEEN :mcfrom AND :mcto
                        AND IFNULL(`mc`.`repayment_type_id`, `rp`.`repayment_type_id`) NOT IN (12)
                EOS;
                
                $sql2 = <<<EOS
                    SELECT COUNT(`mc`.`monthly_charge_id`) FROM `monthly_charge` `mc`
                    INNER JOIN `contract_detail` `cdx` ON `mc`.`contract_detail_id`=`cdx`.`contract_detail_id`
                    INNER JOIN `lease_contract` `lcx` ON `cdx`.`lease_contract_id`=`lcx`.`lease_contract_id`
                    INNER JOIN `customer` `cx` ON `lcx`.`customer_id`=`cx`.`customer_id`
                    INNER JOIN `client_contract` `ccx` ON `cx`.`client_contract_id`=`ccx`.`client_contract_id`
                    INNER JOIN `repayment_pattern` `rpx` ON `ccx`.`repayment_pattern_id`=`rpx`.`repayment_pattern_id` 
                    WHERE
                        `lcx`.`lease_contract_id`=`lc`.`lease_contract_id`
                        AND CASE `rpx`.`target_month` WHEN 'next' THEN `mc`.`term` + INTERVAL 1 MONTH ELSE `mc`.`term` END BETWEEN :mcfrom2 AND :mcto2
                        AND IFNULL(`mc`.`repayment_type_id`, `rp`.`repayment_type_id`) NOT IN (12)
                EOS;
                $query
                    /*
                    ->andWhere(['or',
                        '(SELECT lease_contract_status_type_id FROM lease_contract_status WHERE lease_contract_status.lease_contract_id=lc.lease_contract_id ORDER BY lease_contract_status.registered DESC LIMIT 1) IS NULL',
                        '(SELECT lease_contract_status_type_id FROM lease_contract_status WHERE lease_contract_status.lease_contract_id=lc.lease_contract_id ORDER BY lease_contract_status.registered DESC LIMIT 1) NOT IN (10)',
                        ])
                    */
                    ->andWhere(['or',
                        ['not', ['or', ['<', 'IF(mcs.last_term > mps.last_term, mcs.last_term, mps.last_term)', $span['from']->format('Y-m-01')], ['>', 'IF(mcs.first_term < mps.first_term, mcs.first_term, mps.first_term)', $span['to']->format('Y-m-t')]]],
                        "IF((SELECT lease_contract_status_type_id FROM lease_contract_status WHERE lease_contract_status.lease_contract_id=lc.lease_contract_id ORDER BY lease_contract_status.registered DESC LIMIT 1) IN (10), ($sql) > 0, 0)",
                        "IF((SELECT lease_contract_status_type_id FROM lease_contract_status WHERE lease_contract_status.lease_contract_id=lc.lease_contract_id ORDER BY lease_contract_status.registered DESC LIMIT 1) IN (10), ($sql2) > 0, 0)",
                        "NOT (DATE_FORMAT(cd.term_end_at, '%Y%m') < :cdstart OR DATE_FORMAT(cd.term_start_at, '%Y%m') > :cdend)"
                        ])
                    ->params([
                        ':mcfrom' => $span['from']->format('Y-m-01'),
                        ':mcto' => $span['to']->format('Y-m-01'),
                        ':mcfrom2' => $lastYearLastTerm->format('Y-m-01'),
                        ':mcto2' => $lastYearLastTerm->format('Y-m-t'),
                        ':cdstart' => $lastYearLastTerm->format('Ym'),
                        ':cdend' => $span['to']->format('Ym')
                    ])

                    //->orWhere(['>=', 'DATE_FORMAT(cd.term_end_at, "%Y%m")', $lastYearLastTerm->format('Ym')])
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
                    ->andFilterWhere(['lcs1.lease_contract_status_type_id' => $this->lease_contract_status_type_id])
                    ->andFilterWhere(['cd.tax_application_id' => $this->tax_application_id]);
                //print_r($query->createCommand()->rawSql);die();
                return $dataProvider;
        }
    }
}