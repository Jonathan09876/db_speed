<?php

namespace app\models;

use yii\base\Model;
use yii\helpers\VarDumper;

class MonthlyPayments extends Model
{
    public $contract_detail_ids = [];
    public $term_from;
    public $term_to;
    public $payment_amount;

    public function rules()
    {
        return [
            [['payment_amount'], 'filter', 'filter' => [$this, 'zen2han']],
            [['payment_amount'], 'filter', 'filter' => [$this, 'digitOnly']],
            [['term_from', 'term_to'], 'required'],
            [['contract_detail_ids'], 'each', 'rule' => ['exist', 'targetClass' => ContractDetail::class, 'targetAttribute' => 'contract_detail_id']],
            [['term_from', 'term_to'], 'match', 'pattern' => '/\d+年\d+月/'],
            [['payment_amount'], 'match', 'pattern' => '/[0-9,]+/'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'contract_detail_ids' => '対象契約詳細情報',
            'term_from' => '開始月',
            'term_to' => '終了月',
            'payment_amount' => '支払額'
        ];
    }

    public function digitOnly($val)
    {
        return preg_replace('/[^0-9]+/', '', $val);
    }

    public function zen2han($value)
    {
        return mb_convert_kana($value, "as", 'UTF-8');
    }

    public function bulkUpdate($use_current = false)
    {
        $session = \Yii::$app->session;
        $session['ignore-update-content'] = 1;
        foreach($this->contract_detail_ids as $contract_detail_id) {
            $term_from = preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $this->term_from);
            $term_to = preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $this->term_to);
            $terms = Term::find()->where(['and', ['>=', 'term', $term_from], ['<=', 'term', $term_to]]);
            foreach($terms->each() as $term) {
                if ($use_current) {
                    $query = MonthlyPayment::find()
                        ->where([
                            'contract_detail_id' => $contract_detail_id,
                            'term' => $term->termDateTime->format('Y-m-d')
                        ]);
                    $query->multiple = true;
                    $monthlyPayments = $query->all();
                }
                else {
                    $monthlyPayments = $term->getMonthlyPayments($contract_detail_id);
                }
                if ($monthlyPayments) {
                    foreach($monthlyPayments as $monthlyPayment) {
                        if (strlen($this->payment_amount) > 0) {
                            $monthlyPayment->payment_amount = $monthlyPayment->getAmountFromWithTax($this->payment_amount);
                        }
                        $monthlyPayment->save();
                    }
                }
                $contractDetail = ContractDetail::findOne($contract_detail_id);
                $contractDetail->updateContent();
            }
        }
        unset($session['ignore-update-content']);
    }
}