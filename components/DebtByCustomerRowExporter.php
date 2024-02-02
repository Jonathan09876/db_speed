<?php

namespace app\components;

class DebtByCustomerRowExporter extends \yii\base\Component
{
    public $model;
    public $searchModel;
    public $targetTerm;
    public $totals;
    public $span;

    public function export()
    {
        $model = $this->model;
        $customer = $model->customer;
        $targetTerm = $this->targetTerm;
        $rows = [];
        $spanTo = \app\models\Term::findOne(['term' => $this->span['to']->format('Y-m-01')]);
        foreach($model->contractDetails as $detail) {
            $term = \app\models\Term::findOne(['term' => $this->span['to']->format('Y-m-d')]);
            $monthlyCharges = $term->getMonthlyCharges($detail->contract_detail_id);
            $monthlyCharge = count($monthlyCharges) > 1 ? array_pop($monthlyCharges) : $monthlyCharges[0] ?? false;
            $chargeFinished = false;
            if (!$monthlyCharge) {
                $lastTerm = \app\models\Term::findOne(['term' => $detail->monthlyChargeSpan->last_term]);
                $monthlyCharge = $lastTerm->getMonthlyCharges($detail->contract_detail_id)[0];
                $chargeFinished = true;
            }
            $monthlyPayments = $term->getMonthlyPayments($detail->contract_detail_id);
            $monthlyPayment = count($monthlyPayments) > 1 ? array_pop($monthlyPayments) : $monthlyPayments[0] ?? false;
            $paymentFinished = false;
            if (!$monthlyPayment) {
                $lastTerm = \app\models\Term::findOne(['term' => $detail->monthlyPaymentSpan->last_term]);
                $monthlyPayment = $lastTerm->getMonthlyPayments($detail->contract_detail_id)[0];
                $paymentFinished = true;
            }
            $row = [
                $customer->customer_code,
                $customer->clientContract->repaymentPattern->name,
                $customer->getName(),
                $model->contractNumber,
                $detail->term_start_at,
                $detail->term_end_at,
                $model->leaseTarget->registration_number,
                $detail->term_months_count . 'ヶ月',
                $detail->lease_start_at,
                $detail->getErapsedMonths($spanTo),
                $monthlyCharge ? ($chargeFinished ? $detail->term_months_count : $monthlyCharge->orderCount) : '',
                Helper::calcTaxIncluded($detail->tax_application_id, $detail->monthly_charge, $detail->fraction_processing_pattern, $targetTerm->format('Y-m-d')),
                $detail->getReceivable($term, 0.0),
                $detail->getReceivable($term, 8.0),
                $detail->getReceivable($term, 10.0),
                $detail->getAdvances($term, 0.0),
                $detail->getAdvances($term, 8.0),
                $detail->getAdvances($term, 10.0),
                $detail->leaseServicer->shorten_name,
                $monthlyPayment ? ($detail->leaseServicer->for_internal ? '-' : $monthlyPayment->orderCount) : '',
                Helper::calcTaxIncluded($detail->tax_application_id, $detail->monthly_payment, $detail->fraction_processing_pattern, $targetTerm->format('Y-m-d')),
                $detail->getPayable($term, 0.0),
                $detail->getPayable($term, 8.0),
                $detail->getPayable($term, 10.0),
                $detail->getPayableAdvance($term, 0.0),
                $detail->getPayableAdvance($term, 8.0),
                $detail->getPayableAdvance($term, 10.0)
            ];
            $rows[] = $row;
            $last_cdid = explode(',', $this->totals[$customer->customer_id]['cdids'])[0];
            if ($detail->contract_detail_id == $last_cdid) {
                $debt_total_0 = 0;
                $debt_total_8 = 0;
                $debt_total_10 = 0;
                $advance_total_0 = 0;
                $advance_total_8 = 0;
                $advance_total_10 = 0;
                $client_debt_total_0 = 0;
                $client_debt_total_8 = 0;
                $client_debt_total_10 = 0;
                $client_prepaid_total_0 = 0;
                $client_prepaid_total_8 = 0;
                $client_prepaid_total_10 = 0;
                foreach(explode(',', $this->totals[$customer->customer_id]['cdids']) as $cdid) {
                    $cd = \app\models\ContractDetail::findOne($cdid);
                    $debt_total_0 += $cd->getReceivable($term, 0.0);
                    $debt_total_8 += $cd->getReceivable($term, 8.0);
                    $debt_total_10 += $cd->getReceivable($term, 10.0);
                    $advance_total_0 += $cd->getAdvances($term, 0.0);
                    $advance_total_8 += $cd->getAdvances($term, 8.0);
                    $advance_total_10 += $cd->getAdvances($term, 10.0);
                    $client_debt_total_0 += $cd->getPayable($term, 0.0);
                    $client_debt_total_8 += $cd->getPayable($term, 8.0);
                    $client_debt_total_10 += $cd->getPayable($term, 10.0);
                    $client_prepaid_total_0 += $cd->getPayableAdvance($term, 0.0);
                    $client_prepaid_total_8 += $cd->getPayableAdvance($term, 8.0);
                    $client_prepaid_total_10 += $cd->getPayableAdvance($term, 10.0);
                }
                $totalRow = [
                            '========',
                            '========',
                            $customer->getName(),
                            '========',
                            '========',
                            '========',
                            '========',
                            '========',
                            '========',
                            '========',
                            '========',
                            '========',
                            $debt_total_0,
                            $debt_total_8,
                            $debt_total_10,
                            $advance_total_0,
                            $advance_total_8,
                            $advance_total_10,
                            '========',
                            '========',
                            '========',
                            $client_debt_total_0,
                            $client_debt_total_8,
                            $client_debt_total_10,
                            $client_prepaid_total_0,
                            $client_prepaid_total_8,
                            $client_prepaid_total_10,
                ];
                $rows[] = $totalRow;
            }
        }
        return $rows;
    }
}