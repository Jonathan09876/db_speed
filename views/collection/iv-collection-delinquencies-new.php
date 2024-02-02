<?php
/**
 * @var $this \yii\web\View;
 * @var $searchModel \app\models\ScheduleSearch;
 * @var $model \app\models\ContractDetail;
 * @var $index integer;
 * @var $widget \yii\widgets\ListView;
 * @var $terms array;
 */

use yii\bootstrap5\Html;

$detail = $model;
$contract = $model->leaseContract;
$customer = $model->leaseContract->customer;
$repaymentPattern = $customer->clientContract->repaymentPattern;
$isNext = $repaymentPattern->target_month == 'next';
$customerBgColor = $contract->contractPattern->bg_color;
$repaymentAmounts = [];
$delinquencies = 0;
$targetTerm = preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $searchModel->target_term);
$currentMonthTerm = \app\models\Term::findOne(['term' => $targetTerm]);
?>
    <tr>
        <td class="sticky-cell1 border-bottom" rowspan="2"><?= Html::a($customer->customer_code, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell2 border-bottom" rowspan="2"><?= $customer->clientContract->repaymentPattern->name ?></td>
        <td class="sticky-cell3 border-bottom" rowspan="2"<?= $customerBgColor ? " style=\"background-color:{$customerBgColor}!important;\"" : '' ?>><?= Html::a($customer->name, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell4 border-bottom" rowspan="2"><?= Html::a($contract->contractNumber, ['/aas/lease-contract', 'id' => $contract->lease_contract_id]) ?></td>
        <td class="border-bottom" rowspan="2"><?= $detail->taxApplication->application_name ?></td>
        <td><?= $detail->term_start_at ?></td>
        <td class="border-bottom" rowspan="2"><?= $contract->leaseTarget->registration_number ?></td>
        <td class="text-end border-bottom" rowspan="2"><?= number_format($detail->monthlyChargeWithTax, 0) ?></td>
        <?php $prev = $terms[0]->termDateTime->modify('-1 month');
        $prevTerm = \app\models\Term::findOne(['term' => $prev->format('Y-m-d')]);
        $remains = $model->getChargeRemains($prevTerm);
        ?>
        <td rowspan="2" class="border-bottom text-end"><?= number_format($remains, 0) ?></td>
        <td>回収額</td>
        <?php foreach($terms as $term) :
            $monthlyCharges = $term->getMonthlyCharges($model->contract_detail_id);
            $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []);
            $delinquency = ($monthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)) : 0) - ($repayments ? array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments)) : 0);
            if ($term->termDateTime <= $currentMonthTerm->termDateTime) {
                $delinquencies += $delinquency;
            }
            else {
                $delinquency = 0;
            }
            $bgColor = $delinquency > 0 ? '#ffd4d4' : ($monthlyCharges ? ($term->termDateTime > $currentMonthTerm->termDateTime ? '#ffffff' : '#f2f2f2') : '#ffffff');
        if (count($repayments) > 0) :
            $styles = array_unique(array_map(function($repayment){return $repayment->repaymentType->style;}, $repayments));
            $repaymentAmount = array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments));
            $repaymentAmounts[$term->term] = $repaymentAmount; ?>
        <td class="border-bottom text-end" style="background-color:<?= $bgColor ?>;<?= $styles ? join('', $styles) : '' ?>"><?= number_format($repaymentAmount,0) ?></td>
        <?php else : $repayment = $repayments[0] ?? null;
            $repaymentAmount = $repayment ? $repayment->repayment_amount : 0;
            $repaymentAmounts[$term->term] = $repaymentAmount; ?>
        <td class="border-bottom text-end" style="background-color:<?= $bgColor ?>;<?= $repayment && $repayment->repaymentType->style ? $repayment->repaymentType->style : '' ?>"><?= $repayment ? number_format($repaymentAmount,0) : '&nbsp;' ?></td>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php $collectionRemains = $model->getChargeRemains($terms[11]); ?>
        <td rowspan="2" class="border-bottom text-end"><?= number_format($collectionRemains, 0) ?></td>
        <td rowspan="2" class="border-bottom text-end"><?= number_format($model->getDelinquencies($currentMonthTerm), 0) ?></td>
        <td rowspan="2" class="text-end border-bottom"><?= $model->leaseContract->memo ?></td>
    </tr>
    <tr>
        <td class="border-bottom"><?= $detail->term_end_at ?></td>
        <td class="border-bottom">残額</td>
        <?php foreach($terms as $term) :
        if (isset($repaymentAmounts[$term->term])) {
            $remains -= $repaymentAmounts[$term->term];
        }
        $firstTerm = new \DateTime(min($model->monthlyChargeSpan->first_term, $model->monthlyPaymentSpan->first_term));
        ?>
        <td class="border-bottom text-end"><?= $term->termDateTime >= $firstTerm ? number_format($remains, 0) : '&nbsp;' ?></td>
        <?php endforeach; ?>
    </tr>
