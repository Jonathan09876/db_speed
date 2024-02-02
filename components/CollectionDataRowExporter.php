<?php

namespace app\components;

class CollectionDataRowExporter extends \yii\base\Component
{
    public $model;
    public $targetTerm;
    public $lastTerm;
    public $totals;

    public function export()
    {
        $customer = $this->model->customer;
        $fcdid = explode(',',$this->totals[$customer->customer_id]['cdids'])[0];
        $rows = [];
        foreach($this->model->contractDetails as $detail) {
            $mcQuery = \app\models\MonthlyCharge::find()->alias('mc')
                ->innerJoin('contract_detail cd', 'mc.contract_detail_id=cd.contract_detail_id')
                ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
                ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
                ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
                ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id');
            $lastMonthlyCharges = (clone $mcQuery)
                ->where(['mc.contract_detail_id' => $detail->contract_detail_id, 'CASE `rp`.`target_month` WHEN "next" THEN `mc`.`term` + INTERVAL 1 MONTH ELSE `mc`.`term` END' => $this->lastTerm->format('Y-m-01')])
                ->all();
            $monthlyCharges = (clone $mcQuery)
                ->where(['mc.contract_detail_id' => $detail->contract_detail_id, 'CASE `rp`.`target_month` WHEN "next" THEN `mc`.`term` + INTERVAL 1 MONTH ELSE `mc`.`term` END' => $this->targetTerm->format('Y-m-01')])
                ->all();
            $diff = ($monthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->temporaryAmountWithTax;}, $monthlyCharges)) : 0) - ($lastMonthlyCharges ? array_sum(array_map(function($lastCharge){return $lastCharge->temporaryAmountWithTax;}, $lastMonthlyCharges)) : 0);

            if (count($monthlyCharges)) {
                $i = 0;
                foreach($monthlyCharges as $monthlyCharge) {
                    $row = [
                        $customer->customer_code,
                        $customer->clientContract->repaymentPattern->name,
                        $customer->getName(),
                        $this->model->contractNumber,
                        $detail->taxApplication->application_name,
                        $this->model->contract_date,
                        $this->model->leaseTarget->registration_number,
                        $detail->term_months_count,
                        '回収',
                        $lastMonthlyCharges ? join(',', array_map(function($monthlyCharge){return $monthlyCharge->orderCountText;}, $lastMonthlyCharges)) : '',
                        $lastMonthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->temporaryAmountWithTax;}, $lastMonthlyCharges)) : '',
                        $monthlyCharge->orderCountText,
                        isset($monthlyCharge->repayments[0]) ? $monthlyCharge->repayments[0]->repaymentType->type : $monthlyCharge->repaymentType->type,
                        isset($monthlyCharge->repayments[0]) ? $monthlyCharge->repayments[0]->repayment_amount : $monthlyCharge->temporaryAmountWithTax,
                        $i == 0 ? $diff : '-----',
                        $i == 0 && $fcdid == $detail->contract_detail_id ? $this->totals[$customer->customer_id]['customer_total'] : '-----',
                    ];
                    $i++;
                    $rows[] = $row;
                }
            }
            else {
                $row = [
                    $customer->customer_code,
                    $customer->clientContract->repaymentPattern->name,
                    $customer->getName(),
                    $this->model->contractNumber,
                    $detail->taxApplication->application_name,
                    $this->model->contract_date,
                    $this->model->leaseTarget->registration_number,
                    $detail->term_months_count,
                    '回収',
                    $lastMonthlyCharges ? join(',', array_map(function($monthlyCharge){return $monthlyCharge->orderCountText;}, $lastMonthlyCharges)) : '',
                    $lastMonthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->temporaryAmountWithTax;}, $lastMonthlyCharges)) : '',
                    '',
                    '',
                    '',
                    $diff,
                    $fcdid == $detail->contract_detail_id ? $this->totals[$customer->customer_id]['customer_total'] : '-----',
                ];
                $rows[] = $row;
            }
        }
        return $rows;
    }
}