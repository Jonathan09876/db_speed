<?php

namespace app\components;

use yii\base\Component;

class DelinquencyRowExporter extends Component
{
    public $model;
    public $terms;
    public $searchModel;

    public function export()
    {
        $detail = $this->model;
        $contract = $this->model->leaseContract;
        $customer = $this->model->leaseContract->customer;
        $repaymentPattern = $customer->clientContract->repaymentPattern;
        $isNext = $repaymentPattern->target_month == 'next';
        $customerBgColor = $contract->contractPattern->bg_color;
        $repaymentAmounts = [];
        $delinquencies = 0;
        $targetTerm = preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $this->searchModel->target_term);
        $currentMonthTerm = \app\models\Term::findOne(['term' => $targetTerm]);

        $row1 = [
            $customer->customer_code,
            $customer->clientContract->repaymentPattern->name,
            $customer->getName(),
            $customer->salesPerson->name,
            $contract->contractNumber,
            $detail->taxApplication->application_name,
            $detail->term_start_at,
            $contract->leaseTarget->registration_number,
            $detail->totalChargeAmountWithTax,
        ];
        $prev = $this->terms[0]->termDateTime->modify('-1 month');
        $prevTerm = \app\models\Term::findOne(['term' => $prev->format('Y-m-d')]);
        $remains = $this->model->getChargeRemains($prevTerm);
        $row1 = array_merge($row1, [
            $remains,
            '回収予定',
        ]);
        $chargesTotal = 0;
        foreach($this->terms as $term) {
            $collectionCell = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
            $currentTerm = new \DateTime(date('Y-m-01'));
            $options = json_decode($collectionCell->options, true);
            $rtids = isset($options['mcrtid']) ? explode(',', $options['mcrtid']) : [];
            $render_order = $collectionCell->monthly_charge_amount_with_tax > 0 || count(array_intersect($rtids, [11, 12])) == 0;
            $chargesTotal += ($render_order && isset($options['mcid']) ? $collectionCell->monthly_charge_amount_with_tax : 0);
            $row1[] = $render_order ? ($options['mcOrderCount'] ?? '') : '';
            $row1[] = $render_order && isset($options['mcid']) ? $collectionCell->monthly_charge_amount_with_tax : '';
        }
        $collectionRemains = \app\models\MonthlyCharge::getRelativeShortage($detail->contract_detail_id, $this->searchModel->target_term);
        $row1 = array_merge($row1, [
            $chargesTotal,
            $collectionRemains,
            $this->model->leaseContract->memo,
        ]);

        $row2 = [
            $customer->customer_code,
            $customer->clientContract->repaymentPattern->name,
            $customer->getName(),
            $customer->salesPerson->name,
            $contract->contractNumber,
            $detail->taxApplication->application_name,
            $detail->term_end_at,
            '',
            $detail->monthlyChargeWithTax,
            '',
            '入金額'
        ];
        $collectionTotal = 0;
        foreach($this->terms as $term) {
            $collectionCell = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
            $options = json_decode($collectionCell->options, true);
            $delinquency = $collectionCell->monthly_charge_amount_with_tax - $collectionCell->repayment_amount_with_tax;
            if ($term->termDateTime <= $currentMonthTerm->termDateTime) {
                $delinquencies += $delinquency;
            }
            else {
                $delinquency = 0;
            }
            $repayments = $collectionCell->term->getCurrentRepayments($detail->contract_detail_id);
            if (count($repayments)) {
                $repayment_total = array_sum(array_map(function($rp){return $rp->repayment_amount;}, $repayments));
                $repaymentAmounts[$term->term] = $repayment_total;
                $collectionTotal += $repayment_total;
                $row2[] = '';
                $row2[] = isset($options['rpid']) && !empty($options['rpid']) ? $repayment_total : '';
            }
            else {
                $row2[] = '';
                $row2[] = '';
            }
        }
        $row2[] = $collectionTotal;
        $row2[] = '';
        $row2[] = '';

        $row3 = [
            $customer->customer_code,
            $customer->clientContract->repaymentPattern->name,
            $customer->getName(),
            $customer->salesPerson->name,
            $contract->contractNumber,
            $detail->taxApplication->application_name,
            $detail->lease_start_at,
            '',
            $detail->term_months_count,
            '',
            '残額'
        ];
        foreach($this->terms as $term) {
            if (isset($repaymentAmounts[$term->term])) {
                $remains -= $repaymentAmounts[$term->term];
            }
            $firstTerm = new \DateTime(min($this->model->monthlyChargeSpan->first_term, $this->model->monthlyPaymentSpan->first_term));
            $row3[] = '';
            $row3[] = $term->termDateTime >= $firstTerm ? $remains : '';
        }
        $row3[] = $remains;
        $row3[] = '';
        $row3[] = '';
        return [$row1, $row2, $row3];
    }
}