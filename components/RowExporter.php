<?php
/**
 * @var $this \yii\web\View;
 * @var $searchModel \app\models\MonthlyChargeSearch;
 * @var $this->model \app\models\ContractDetail;
 * @var $index integer;
 * @var $widget \yii\widgets\ListView;
 * @var $targetTerm \DateTime;
 * @var $this->terms array;
 * @var $lastMonth \DateTime;
 * @var $fp resource|false;
 */

namespace app\components;

class RowExporter extends \yii\base\Component
{
    public $model;
    public $searchModel;
    public $targetTerm;
    public $terms;
    public $lastMonth;
    
    public function export()
    {
        $detail = $this->model;
        $contract = $this->model->leaseContract;
        $customer = $this->model->leaseContract->customer;
        $repaymentPattern = $customer->clientContract->repaymentPattern;
        $isNext = $repaymentPattern->target_month == 'next';
        $customerBgColor = $contract->contractPattern->bg_color;

        if ($this->searchModel->hide_collection || $detail->monthly_charge == 0) :
            $row = [
                $customer->customer_code,
                $customer->clientContract->repaymentPattern->name,
                $customer->getName(),
                $contract->contractNumber,
                $detail->taxApplication->application_name,
                $detail->term_start_at,
                $detail->term_end_at,
                $contract->leaseTarget->registration_number,
                $detail->term_months_count . "ヶ月\n" . $detail->leaseServicer->shorten_name,
                '支払',
            ];
            foreach($this->terms as $term) {
                $instance = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
                $instance->renderExports($row, 'monthly_payment');
            }
            $lastTerm = $this->terms[11];
            $row = array_merge($row, [
                $this->model->getTermsTotalpaymentAmountWithTax($this->terms),
                (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->model->lease_start_at)))->format('Y/m') . "\n" . $detail->getErapsedMonths($lastTerm),
                $this->model->getAdvanceRepayments()->count(),
                $this->model->advanceRepaymentTotal,
                $this->model->leaseContract->memo
            ]);
            return [$row];

        elseif ($this->searchModel->hide_payment || $detail->monthly_payment == 0) :
            $row = [
                $customer->customer_code,
                $customer->clientContract->repaymentPattern->name,
                $customer->getName(),
                $contract->contractNumber,
                $detail->taxApplication->application_name,
                $detail->term_start_at,
                $detail->term_end_at,
                $contract->leaseTarget->registration_number,
                $detail->term_months_count . 'ヶ月',
                '回収',
            ];
            foreach($this->terms as $term) {
                $instance = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
                $instance->renderExports($row, 'monthly_charge');
            }
            $row = array_merge($row, [
                $this->model->getTermsTotalChargeAmountWithTax($this->terms),
                (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->model->lease_start_at)))->format('Y/m'),
                $this->model->getAdvanceRepayments()->count(),
                $this->model->advanceRepaymentTotal,
                $this->model->leaseContract->memo
            ]);

            $row2 = [
                $customer->customer_code,
                $customer->clientContract->repaymentPattern->name,
                $customer->getName(),
                $contract->contractNumber,
                $detail->taxApplication->application_name,
                $detail->term_start_at,
                $detail->term_end_at,
                $contract->leaseTarget->registration_number,
                '',
                '実績'
            ];
            foreach($this->terms as $term) {
                $instance = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
                $instance->renderExports($row2, 'repayment');
            }
            $lastTerm = $this->terms[11];
            $lastMonthlyCharges = $lastTerm->getMonthlyCharges($this->model->contract_detail_id);
            $lastMonthlyCharge = count($lastMonthlyCharges) > 1 ? array_pop($lastMonthlyCharges) : $lastMonthlyCharges[0] ?? false;
            $monthlyCharges = array_reduce(array_map(function($term){return $term->getMonthlyCharges($this->model->contract_detail_id);}, $this->terms), 'array_merge', []);
            $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []);
            if ($repayments) {
                usort($repayments, function($a, $b){
                    return (new \DateTime($a->processed)) >= (new \DateTime($b->processed));
                });
                $lastRepayment = array_pop($repayments);
            }
            else {
                $lastRepayment = false;
            }

            $row2 = array_merge($row2, [
                $this->model->getTermsRepaymentTotal($this->terms),
                $detail->getErapsedMonths($lastTerm),//$lastMonthlyCharge ? ($lastMonthlyCharge->isLast ? '終' : $lastMonthlyCharge->orderCount + 1) : '終',
                '',
                '',
                ''
            ]);
            return [$row, $row2];

        else :
            $row = [
                $customer->customer_code,
                $customer->clientContract->repaymentPattern->name,
                $customer->getName(),
                $contract->contractNumber,
                $detail->taxApplication->application_name,
                $detail->term_start_at,
                $detail->term_end_at,
                $contract->leaseTarget->registration_number,
                $detail->term_months_count . 'ヶ月',
                '回収',
            ];
            foreach($this->terms as $term) {
                $instance = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
                $instance->renderExports($row, 'monthly_charge');
            }
            $row = array_merge($row, [
                $this->model->getTermsTotalChargeAmountWithTax($this->terms),
                (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->model->lease_start_at)))->format('Y/m'),
                $this->model->getAdvanceRepayments()->count(),
                $this->model->advanceRepaymentTotal,
                $this->model->leaseContract->memo
            ]);

            $row2 = [
                $customer->customer_code,
                $customer->clientContract->repaymentPattern->name,
                $customer->name,
                $contract->contractNumber,
                $detail->taxApplication->application_name,
                $detail->term_start_at,
                $detail->term_end_at,
                $contract->leaseTarget->registration_number,
                '',
                '実績',

            ];
            foreach($this->terms as $term) {
                $instance = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
                $instance->renderExports($row2, 'repayment');
            }
            $lastTerm = $this->terms[11];
            $lastMonthlyCharges = $lastTerm->getMonthlyCharges($this->model->contract_detail_id);
            $lastMonthlyCharge = count($lastMonthlyCharges) > 1 ? array_pop($lastMonthlyCharges) : $lastMonthlyCharges[0] ?? false;
            $monthlyCharges = array_reduce(array_map(function($term){return $term->getMonthlyCharges($this->model->contract_detail_id);}, $this->terms), 'array_merge', []);
            $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []);
            if ($repayments) {
                usort($repayments, function($a, $b){
                    return (new \DateTime($a->processed)) >= (new \DateTime($b->processed));
                });
                $lastRepayment = array_pop($repayments);
            }
            else {
                $lastRepayment = false;
            }
            $row2 = array_merge($row2, [
                $this->model->getTermsRepaymentTotal($this->terms),
                $detail->getErapsedMonths($lastTerm),//$lastMonthlyCharge ? ($lastMonthlyCharge->isLast ? '終' : $lastMonthlyCharge->orderCount + 1) : '終',
                '',
                '',
                ''
            ]);

            $row3 = [
                $customer->customer_code,
                $customer->clientContract->repaymentPattern->name,
                $customer->getName(),
                $contract->contractNumber,
                $detail->taxApplication->application_name,
                $detail->term_start_at,
                $detail->term_end_at,
                $contract->leaseTarget->registration_number,
                $detail->leaseServicer->shorten_name,
                '支払'
            ];
            foreach($this->terms as $term) {
                $instance = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
                $instance->renderExports($row3, 'monthly_payment');
            }
            $row3 = array_merge($row3, [
                $this->model->getTermsTotalpaymentAmountWithTax($this->terms),
                '',
                '',
                '',
                ''
            ]);
            return [$row, $row2, $row3];
        endif;    }
}