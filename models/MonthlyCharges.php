<?php

namespace app\models;

use yii\base\Model;
use yii\helpers\VarDumper;

class MonthlyCharges extends Model
{
    public $contract_detail_ids = [];
    public $term_from;
    public $term_to;
    public $repayment_type_id;
    public $charge_amount;

    public function rules()
    {
        return [
            [['charge_amount'], 'filter', 'filter' => [$this, 'zen2han']],
            [['charge_amount'], 'filter', 'filter' => [$this, 'digitOnly']],
            [['term_from', 'term_to'], 'required'],
            [['contract_detail_ids'], 'each', 'rule' => ['exist', 'targetClass' => ContractDetail::class, 'targetAttribute' => 'contract_detail_id']],
            [['term_from', 'term_to'], 'match', 'pattern' => '/\d+年\d+月/'],
            [['repayment_type_id'], 'exist', 'targetClass' => RepaymentType::class, 'targetAttribute' => 'repayment_type_id'],
            [['charge_amount'], 'match', 'pattern' => '/[0-9,]+/'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'contract_detail_ids' => '対象契約詳細情報',
            'term_from' => '開始月',
            'term_to' => '終了月',
            'repayment_type_id' => '回収区分',
            'charge_amount' => '回収予定額'
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
        $lastMonth = new \DateTime(date('Y-m-01'));
        $lastMonth->modify('-1 month');
        foreach($this->contract_detail_ids as $contract_detail_id) {
            $detail = ContractDetail::findOne($contract_detail_id);
            $term_from = preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $this->term_from);
            $term_to = preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $this->term_to);
            $terms = Term::find()->where(['and', ['>=', 'term', $term_from], ['<=', 'term', $term_to]]);
            foreach($terms->each() as $term) {
                if ($use_current) {
                    $query = MonthlyCharge::find()
                        ->where([
                            'contract_detail_id' => $contract_detail_id,
                            'term' => $term->termDateTime->format('Y-m-d')
                        ]);
                    $query->multiple = true;
                    $monthlyCharges = $query->all();
                }
                else {
                    $monthlyCharges = $term->getMonthlyCharges($contract_detail_id);
                }
                if ($monthlyCharges) {
                    foreach($monthlyCharges as $monthlyCharge) {
                        //締め済み、もしくは実績登録済みの場合は更新しない
                        //$is_closed = $term->termDateTime < $lastMonth || \app\models\TargetTermMonthlyChargeStored::isMonthClosed($term->term, $detail->leaseContract->customer->clientContract->client_corporation_id);
                        $is_closed = \app\models\TargetTermMonthlyChargeStored::isMonthClosed($term->term, $detail->leaseContract->customer->clientContract->client_corporation_id);
                        if ($is_closed || $monthlyCharge->getRepayments()->count() > 0) {
                            continue;
                        }
                        if (!empty($this->repayment_type_id)) {
                            $monthlyCharge->repayment_type_id = $this->repayment_type_id;
                        }
                        if (strlen($this->charge_amount) > 0) {
                            $monthlyCharge->charge_amount = $monthlyCharge->getAmountFromWithTax($this->charge_amount);
                            $monthlyCharge->temporary_charge_amount = null;
                        }
                        $monthlyCharge->save();
                    }
                }
            }
            $contractDetail = ContractDetail::findOne($contract_detail_id);
            $contractDetail->updateContent();
        }
        unset($session['ignore-update-content']);
    }
}