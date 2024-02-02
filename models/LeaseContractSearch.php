<?php

namespace app\models;

use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

class LeaseContractSearch extends LeaseContract
{
    public $target_word;
    public $term_from;
    public $term_to;

    public $target_term;
    public $client_corporation_id;
    public $customer_code;
    public $customer_name;
    public $lease_servicer_id;
    public $repayment_pattern_id;
    public $contract_type;
    public $lease_contract_status_id;
    public $target_yeas_month;

    public function scenarios()
    {
        return [
            self::SCENARIO_DEFAULT => [
                'lease_contract_id', 'customer_id', 'lease_target_id',
                'contract_pattern_id', 'contract_number', 'contract_code', 'contract_sub_code',
                'contract_date',
                'registration_incomplete',
                'collection_application_complete',
                'target_term',
                'term_from',
                'term_to',
                'client_corporation_id',
                'customer_id',
                'target_word',
                'customer_id',
                'customer_code',
                'customer_name',
                'lease_servicer_id',
                'repayment_pattern_id',
                'current_status',
                'contract_type',
            ],
        ];
    }

    public function rules()
    {
        return [
            [[
                'target_term',
                'term_from',
                'term_to',
                'client_corporation_id',
                'customer_id',
                'target_word',
                'contract_pattern_id',
                'contract_number',
                'contract_code',
                'contract_sub_code',
                'contract_date',
                'customer_id',
                'customer_code',
                'customer_name',
                'lease_servicer_id',
                'repayment_pattern_id',
                'registration_incomplete',
                'current_status',
                'contract_type'
            ], 'safe'],
            /*
            [['term_from'], 'required', 'when' => function($data){
                return !empty($this->term_to);
            }],
            [['term_to'], 'required', 'when' => function($data){
                return !empty($this->term_from);
            }],
            */
        ];
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'client_corporation_id' => '会社',
            'target_term' => '対象月',
            'term_from' => '開始月',
            'term_to' => '終了月',
            'target_word' => '物件情報',
            'lease_servicer_id' => 'リース会社',
            'repayment_pattern_id' => '支払条件',
            'contract_type' => 'リース区分',
            'lease_contract_status_id' => '契約状況',
            'target_yeas_month' => '調査対象年月',
            'current_status' => 'ステータス',
            'currentStatus' => 'ステータス'
        ]);
    }

    public function search($params)
    {
        $childrens = \Yii::$app->user->identity->clientCorporation->clientCorporationChildren;
        $query = self::find()->alias('lc')
            ->innerJoin('lease_target lt', 'lc.lease_target_id=lt.lease_target_id')
            ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
            ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
            ->leftJoin('contract_detail cd', 'lc.lease_contract_id=cd.lease_contract_id')
            ->leftJoin('lease_contract_status lcs1', 'lcs1.lease_contract_id=lc.lease_contract_id')
            ->leftJoin('lease_contract_status lcs2', 'lcs1.lease_contract_id=lcs2.lease_contract_id AND lcs1.registered < lcs2.registered')
            ->where(['lcs2.lease_contract_status_id' => null, 'cc.client_corporation_id' => ArrayHelper::getColumn($childrens, 'client_corporation_id')])
            ->orderBy(['cc.client_corporation_id' => SORT_ASC, 'c.customer_code' =>SORT_ASC, 'lc.disp_order' => SORT_ASC, 'cd.term_start_at' =>SORT_ASC]);


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }
        $term_from = $this->term_from ? (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->term_from)))->format('Y-m-01') : ($this->term_to ? '1900-01-01' : null);
        $term_to = $this->term_to ? (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->term_from)))->format('Y-m-t') : ($this->term_from ? '2999-12-31' : null);
        //TODO:絞り込み対象条件実装
        $query
            ->andFilterWhere(['cc.client_corporation_id' => $this->client_corporation_id])
            ->andFilterWhere(['lc.customer_id' => $this->customer_id])
            ->andFilterWhere(['lc.contract_pattern_id' => $this->contract_pattern_id])
            ->andFilterWhere(['like', 'lc.contract_number', $this->contract_number])
            ->andFilterWhere(['like', 'lc.contract_code', $this->contract_code])
            ->andFilterWhere(['like', 'lc.contract_sub_code', $this->contract_sub_code])
            ->andFilterWhere(['cd.lease_servicer_id' => $this->lease_servicer_id])
            ->andFilterWhere(['cc.repayment_pattern_id' => $this->repayment_pattern_id])
            ->andFilterWhere(['registration_incomplete' => $this->registration_incomplete])
            ->andFilterWhere(['collection_application_complete' => $this->collection_application_complete])
            ->andFilterWhere(['lcs1.lease_contract_status_type_id' => $this->current_status])
            ->andFilterWhere(['cd.contract_type' => $this->contract_type])
            ->andFilterWhere(['or',
                ['like', 'lt.name', $this->target_word],
                ['like', 'lt.registration_number', $this->target_word],
                ['like', 'lt.attributes', $this->target_word],
                ['like', 'lt.memo', $this->target_word],
            ])
            ->andFilterWhere(['not',
                ['or', ['<', 'cd.term_end_at', $term_from], ['>', 'cd.term_start_at', $term_to]],
            ]);

        return $dataProvider;
    }

    public function operationSearch($params)
    {
        $query = self::find()->alias('lc')
            ->innerJoin('lease_target lt', 'lc.lease_target_id=lt.lease_target_id')
            ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
            ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
            ->leftJoin('contract_detail cd', 'lc.lease_contract_id=cd.lease_contract_id');


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        //TODO:絞り込み対象条件実装
        $query
            ->andFilterWhere(['and', ['<=','cd.term_start_at', $this->target_term], ['>=','cd.term_end_at', $this->target_term]])
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
            ->andFilterWhere(['or',
                ['and', ['<=', 'cd.term_start_at', $this->term_from], ['>=', 'cd.term_end_at', $this->term_from], ['<=', 'cd.term_start_at', $this->term_to], ['>=', 'cd.term_end_at', $this->term_to]],
                ['and', ['<=', 'cd.term_start_at', $this->term_from], ['>=', 'cd.term_end_at', $this->term_from], ['<=', 'cd.term_start_at', $this->term_to], ['<=', 'cd.term_end_at', $this->term_to]],
                ['and', ['>=', 'cd.term_start_at', $this->term_from], ['>=', 'cd.term_end_at', $this->term_from], ['<=', 'cd.term_start_at', $this->term_to], ['>=', 'cd.term_end_at', $this->term_to]],
                ['and', ['>=', 'cd.term_start_at', $this->term_from], ['>=', 'cd.term_end_at', $this->term_from], ['<=', 'cd.term_start_at', $this->term_to], ['<=', 'cd.term_end_at', $this->term_to]],
            ]);

        return $dataProvider;
    }
}