<?php

namespace app\models;

use Yii;
use yii\helpers\VarDumper;

class RepaymentByCustomerForm extends \yii\base\Model
{
    public $repayment_amount;
    public $repayment_type_id;
    public $repayment_processed;
    public $pooled_repayment;

    public function rules()
    {
        return [
            [['repayment_amount', 'repayment_type_id'], 'required'],
            [['repayment_amount', 'pooled_repayment'], 'filter', 'filter' => [$this, 'digitOnly']],
            [['repayment_amount', 'pooled_repayment'], 'match', 'pattern' => '/[0-9,]+/'],
            [['repayment_amount'], 'isMatchApportioned'],
            [['repayment_processed'], 'match', 'pattern' => '/\d{4}[^\d]\d{1,2}[^\d]\d{1,2}/'],
            [['repayment_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => RepaymentType::class, 'targetAttribute' => ['repayment_type_id' => 'repayment_type_id']],
        ];
    }

    public function digitOnly($val)
    {
        return preg_replace('/[^0-9]+/', '', $val);
    }

    public function getRepaymentAmount()
    {
        return (int)strtr($this->repayment_amount, [',' => '']);
    }

    public function isMatchApportioned($attr, $params)
    {
        $repayments = Yii::$app->request->post('Repayment', []);
        if ($repayments) {
            $apportionedTotal = array_sum(array_map(function($rp){return (int)strtr(isset($rp['additional_repayment_amount']) ? $rp['additional_repayment_amount'] : $rp['repayment_amount'], [',' => '']);}, $repayments));
            if ($apportionedTotal + $this->pooled_repayment == $this->getRepaymentAmount()) {
                return;
            }
        }
        $this->addError($attr,'振分済合計額と回収額が一致しません。');
    }

    public function attributelabels()
    {
        return [
            'repayment_type_id' => '回収区分',
            'repayment_amount' => '回収額',
            'repayment_processed' => '回収日',
            'pooled_repayment' => '過入金額'
        ];
    }

    public function registerRepayment()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $repayments = Yii::$app->request->post('Repayment', []);
            if (count($repayments)) {
                foreach($repayments as $params) {
                    //回収済みだが金額不足の場合
                    if (isset($params['repayment_id'])) {
                        $additional_repayment_amount = strtr($params['additional_repayment_amount'], [','=>'']) * 1;
                        if ($additional_repayment_amount > 0) {
                            $repayment = Repayment::findOne($params['repayment_id']);
                            $debts = $repayment->monthlyCharge->debts;
                            //売掛金があったら相殺
                            if ($debts) {
                                $debtAmount = array_sum(array_map(function($debt){return $debt->debt_amount;}, $debts));
                                if ($debtAmount > 0) {
                                    $debt_sub = new Debt([
                                        'contract_detail_id' => $repayment->contract_detail_id,
                                        'monthly_charge_id' => $repayment->monthly_charge_id,
                                        'repayment_id' => $repayment->repayment_id,
                                        'term' => $repayment->monthlyCharge->termInstance->term,
                                        'debt_amount' => $additional_repayment_amount * -1,
                                        'registered' => date('Y-m-d H:i:s')
                                    ]);
                                    $debt_sub->save();
                                }
                            }
                            $repayment->repayment_type_id = $this->repayment_type_id;
                            $repayment->processed = $this->repayment_processed;
                            $repayment->repayment_amount += $additional_repayment_amount;
                            $repayment->save();
                        }
                    }
                    elseif ($params['monthly_charge_id']) {
                        $repayment_amount = strtr($params['repayment_amount'], [','=>'']) * 1;
                        if ($repayment_amount > 0) {
                            $monthlyCharge = MonthlyCharge::findOne($params['monthly_charge_id']);
                            $amountWithTax = $monthlyCharge->temporaryAmountWithTax;
                            $repayment = new Repayment([
                                'contract_detail_id' => $monthlyCharge->contract_detail_id,
                                'monthly_charge_id' => $monthlyCharge->monthly_charge_id,
                                'repayment_type_id' => $this->repayment_type_id,
                                'repayment_amount' => $repayment_amount,
                                'processed' => $this->repayment_processed ? $this->repayment_processed : date('Y-m-d'),
                                'registered' => date('Y-m-d H:i:s'),
                            ]);
                            $repayment->save();
                            if ($amountWithTax - $repayment_amount > 0) {
                                //金額不足分を売掛登録
                                $debt = new Debt([
                                    'contract_detail_id' => $monthlyCharge->contract_detail_id,
                                    'monthly_charge_id' => $monthlyCharge->monthly_charge_id,
                                    'repayment_id' => $repayment->repayment_id,
                                    'term' => $monthlyCharge->termInstance->term,
                                    'debt_amount' => $amountWithTax - $repayment_amount*1,
                                    'registered' => date('Y-m-d H:i:s')
                                ]);
                                $debt->save();
                            }
                        }
                    }
                }
            }
            $transaction->commit();
        } catch(\Throwable $e) {
            $transaction->rollBack();
            VarDumper::dump($e, 10, 1);
            die();
        }
    }
}