<?php

namespace app\models;

use yii\base\Component;

class TermTicker extends Component
{
    public $contract_detail_id;
    public $monthly_charge_id;
    public $monthly_payment_id;
    public $term;

    public function getContractDetail()
    {
        return ContractDetail::findOne($this->contract_detail_id);
    }

    public function getMonthlyCharge()
    {
        return $this->monthly_charge_id ? MonthlyCharge::findOne($this->monthly_charge_id) : null;
    }

    public function getMonthlyPayment()
    {
        return $this->monthly_payment_id ? MonthlyPayment::findOne($this->monthly_payment_id) : null;
    }
}