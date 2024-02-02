<?php
/**
 * @var $this \yii\web\View;
 * @var $searchModel \app\models\MonthlyChargeSearch;
 * @var $model \app\models\ContractDetail;
 * @var $index integer;
 * @var $widget \yii\widgets\ListView;
 * @var $targetTerm \DateTime;
 * @var $terms array;
 * @var $lastMonth \DateTime;
 */

use yii\bootstrap5\Html;

$detail = $model;
$contract = $model->leaseContract;
$customer = $model->leaseContract->customer;
$repaymentPattern = $customer->clientContract->repaymentPattern;
$isNext = $repaymentPattern->target_month == 'next';
$customerBgColor = $contract->contractPattern->bg_color;
if ($searchModel->hide_collection || $detail->monthly_charge == 0) : ?>
<tr>
    <td class="sticky-cell1 border-bottom"><?= Html::a($customer->customer_code, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
    <td class="sticky-cell2 border-bottom"><?= $customer->clientContract->repaymentPattern->name ?></td>
    <td class="sticky-cell3 border-bottom"<?= $customerBgColor ? " style=\"background-color:{$customerBgColor}!important;\"" : '' ?>><?= Html::a($customer->name, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
    <td class="sticky-cell4 border-bottom"><?= Html::a($contract->contractNumber, ['/aas/lease-contract', 'id' => $contract->lease_contract_id]) ?></td>
    <td class="border-bottom"><?= $detail->taxApplication->application_name ?></td>
    <td class="border-bottom"><?= $detail->term_start_at ?><br /><?= $detail->term_end_at ?></td>
    <td class="border-bottom"><?= $contract->leaseTarget->registration_number ?></td>
    <td class="border-bottom"><?= $detail->term_months_count ?>ヶ月<br /><?= $detail->leaseServicer->shorten_name ?></td>

    <td class="payment-cell border-bottom">支払</td>
    <?php foreach($terms as $term) :
    $monthlyPayments = $term->getMonthlyPayments($model->contract_detail_id);
    if (count($monthlyPayments) > 1) : ?>
    <td class="text-end payment-cell border-bottom"><?= implode(',', array_map(function($monthlyPayment){return $monthlyPayment->orderCount;}, $monthlyPayments)) ?></td>
    <td class="text-end payment-cell border-bottom"><?= number_format(array_sum(array_map(function($monthlyPayment){return $monthlyPayment->amountWithTax;}, $monthlyPayments)),0) ?></td>
    <?php else : $monthlyPayment = $monthlyPayments[0] ?? null; ?>
    <td class="text-end payment-cell border-bottom"><?= $monthlyPayment ? $monthlyPayment->orderCount : '&nbsp;' ?></td>
    <td class="text-end payment-cell border-bottom"><?= $monthlyPayment ? number_format($monthlyPayment->amountWithTax,0) : '&nbsp;' ?></td>
    <?php endif; endforeach; ?>

    <td class="text-end border-bottom"><?= number_format($model->getTermsTotalpaymentAmountWithTax($terms), 0) ?></td>
    <td class="text-end border-bottom"><?= number_format($model->totalPaymentAmountWithTax, 0) ?></td>
    <?php $lastTerm = $terms[11];
    $lastMonthlyPayments = $lastTerm->getMonthlyPayments($model->contract_detail_id);
    $lastMonthlyPayment = count($lastMonthlyPayments) > 1 ? array_pop($lastMonthlyPayments) : $lastMonthlyPayments[0] ?? false;
    ?>
    <td class="border-bottom text-end"><?= $lastMonthlyPayment ? ($lastMonthlyPayment->isLast ? '終' : $lastMonthlyPayment->orderCount + 1) : '終' ?></td>
    <td class="border-bottom text-end"><?= $lastMonthlyPayment ? number_format(($model->getMonthlyPayments()->count() - $lastMonthlyPayment->orderCount) * $model->monthlyPaymentWithTax, 0) : 0 ?></td>
    <td class="border-bottom text-end"><?= number_format($model->monthlyPaymentWithTax, 0) ?></td>
    <td class="text-end border-bottom"><?= $model->term_months_count ?></td>
    <td class="text-end border-bottom"><?= number_format($model->advanceRepaymentTotal, 0) ?></td>
    <td class="text-end border-bottom"><?= $model->leaseContract->memo ?></td>
</tr>
<?php elseif ($searchModel->hide_payment || $detail->monthly_payment == 0) : ?>
    <tr>
        <td class="sticky-cell1 border-bottom" rowspan="2"><?= Html::a($customer->customer_code, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell2 border-bottom" rowspan="2"><?= $customer->clientContract->repaymentPattern->name ?></td>
        <td class="sticky-cell3 border-bottom" rowspan="2"<?= $customerBgColor ? " style=\"background-color:{$customerBgColor}!important;\"" : '' ?>><?= Html::a($customer->name, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell4 border-bottom" rowspan="2"><?= Html::a($contract->contractNumber, ['/aas/lease-contract', 'id' => $contract->lease_contract_id]) ?></td>
        <td class="border-bottom" rowspan="2"><?= $detail->taxApplication->application_name ?></td>
        <td><?= $detail->term_start_at ?></td>
        <td class="border-bottom" rowspan="2"><?= $contract->leaseTarget->registration_number ?></td>
        <td><?= $detail->term_months_count ?>ヶ月</td>

        <td>回収</td>
        <?php foreach($terms as $term) : $monthlyCharges = $term->getMonthlyCharges($model->contract_detail_id); ?>
            <?php $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []); ?>
            <?php $diff = ($monthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)) : 0) - ($repayments ? array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments)) : 0) ; ?>
            <?php $bgColorClass = $diff > 0 ? ($term['relative_month'] > 0 ? '' : ' deficient') : ($monthlyCharges ? ' paid' : '') ?>
            <?php if (count($monthlyCharges) > 1) :
                $styles = array_unique(array_map(function($monthlyCharge){return $monthlyCharge->repaymentType->style;}, $monthlyCharges)); ?>
            <td class="text-end<?= $bgColorClass ?>"><?= join(',',array_map(function($monthlyCharge){return $monthlyCharge->orderCountText;}, $monthlyCharges)) ?></td>
            <td class="text-end<?= $bgColorClass ?>" style="<?= $styles ? join('', $styles) : '' ?>"><?= number_format(array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)),0) ?></td>
            <?php else : $monthlyCharge = $monthlyCharges[0] ?? null; ?>
            <td class="text-end<?= $bgColorClass ?>"><?= $monthlyCharge ? $monthlyCharge->orderCountText : '&nbsp;' ?></td>
            <?php $is_closed = $term->termDateTime < $lastMonth || \app\models\TargetTermMonthlyChargeStored::isMonthClosed($term->term, $searchModel->client_corporation_id) ?>
            <td class="text-end<?= $bgColorClass ?><?= $monthlyCharge && !$is_closed ? ' editable formatted cell-monthly_charge-temporary_charge_amount_and_type' : '' ?>" style="<?= $monthlyCharge && $monthlyCharge->repaymentType->style ? $monthlyCharge->repaymentType->style : '' ?>" data-id="<?= $monthlyCharge ? $monthlyCharge->monthly_charge_id : '' ?>"><?= $monthlyCharge ? number_format($monthlyCharge->temporaryAmountWithTax,0) : '&nbsp;' ?></td>
         <?php endif; endforeach; ?>

        <td class="text-end"><?= number_format($model->getTermsTotalChargeAmountWithTax($terms), 0) ?></td>
        <td class="text-end"><?= number_format($model->totalChargeAmountWithTax, 0) ?></td>
        <?php $lastTerm = $terms[11];
        $lastMonthlyCharges = $lastTerm->getMonthlyCharges($model->contract_detail_id);
        $lastMonthlyCharge = count($lastMonthlyCharges) > 1 ? array_pop($lastMonthlyCharges) : $lastMonthlyCharges[0] ?? false;
        ?>
        <td class="text-end"><?= $lastMonthlyCharge ? ($lastMonthlyCharge->isLast ? '終' : $lastMonthlyCharge->orderCount + 1) : '終' ?></td>
        <td class="text-end"><?= $lastMonthlyCharge ? number_format(($model->getMonthlyCharges()->count() - $lastMonthlyCharge->orderCount) * $model->monthlyChargeWithTax, 0) : 0 ?></td>
        <td class="text-end"><?= number_format($model->monthlyChargeWithTax, 0) ?></td>
        <td class="text-end"><?= $model->term_months_count ?></td>
        <td class="text-end"><?= number_format($model->advanceRepaymentTotal, 0) ?></td>
        <td class="text-end"><?= $model->leaseContract->memo ?></td>
    </tr>
    <tr>
        <td class="border-bottom"><?= $detail->term_end_at ?></td>
        <td class="border-bottom"></td>
        <td class="border-bottom">実績</td>
        <?php foreach($terms as $term) :
        $monthlyCharges = $term->getMonthlyCharges($model->contract_detail_id);
        $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []);
        $diff = ($monthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)) : 0) - ($repayments ? array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments)) : 0);
        $bgColorClass = $diff > 0 ? ($term['relative_month'] > 0 ? ' ' : ' deficient') : ($monthlyCharges ? ' paid' : '');
        $is_closed = $term->termDateTime < $lastMonth || \app\models\TargetTermMonthlyChargeStored::isMonthClosed($term->term, $searchModel->client_corporation_id);
        $deficientClass = $repayments ? ($diff > 0 && $is_closed ? ' editable cell-repayment-repayment_amount' : '') : (count($monthlyCharges) > 0 && $is_closed ? ' registerable cell-repayment-repayment_amount' : '');
        $ids = $deficientClass ? ($repayments ? implode(',', array_map(function($rp){return $rp->repayment_id;}, $repayments)) : implode(',', array_map(function($mc){return $mc->monthly_charge_id;}, $monthlyCharges))) : '';
        $identifier = $ids ? " data-id=\"{$ids}\"" : ''; ?>
        <?php if (count($repayments) > 1) :
            $styles = array_unique(array_map(function($repayment){return $repayment->repaymentType->style;}, $repayments)); ?>
        <td class="border-bottom text-end<?= $bgColorClass ?>"><?= join(',', array_map(function($repayment){return $repayment->orderCountText;}, $repayments)) ?></td>
        <td class="border-bottom text-end<?= $bgColorClass . $deficientClass ?>"<?= $identifier ?> style="<?= $styles ? join('', $styles) : '' ?>"><?= number_format(array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments)),0) ?></td>
        <?php else : $repayment = $repayments[0] ?? null; ?>
        <td class="border-bottom text-end<?= $bgColorClass ?>"><?= $repayment ? $repayment->orderCountText : '&nbsp;' ?></td>
        <td class="border-bottom text-end<?= $bgColorClass . $deficientClass ?>"<?= $identifier ?> style="<?= $repayment && $repayment->repaymentType->style ? $repayment->repaymentType->style : '' ?>"><?= $repayment ? number_format($repayment->repayment_amount,0) : '&nbsp;' ?></td>
        <?php endif; endforeach; ?>
        <td class="border-bottom text-end"><?= number_format($model->getTermsRepaymentTotal($terms), 0) ?></td>
        <td class="border-bottom text-end"><?= number_format(\app\models\Repayment::getCurrentTotal($detail->contract_detail_id, $targetTerm->format('Ym')), 0) ?></td>
        <?php
        $monthlyCharges = array_reduce(array_map(function($term)use($model){return $term->getMonthlyCharges($model->contract_detail_id);}, $terms), 'array_merge', []);
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
        ?>
        <td class="border-bottom text-end"><?= $lastRepayment ? $lastRepayment->orderCount : '' ?></td>
        <td class="border-bottom text-end"><?= number_format($model->totalChargeAmountWithTax - \app\models\Repayment::getCurrentTotal($detail->contract_detail_id)) ?></td>
        <td class="border-bottom text-end"><?= (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $model->lease_start_at)))->format('Y/m') ?></td>
        <td class="border-bottom">&nbsp;</td>
        <td class="border-bottom">&nbsp;</td>
        <td class="border-bottom">&nbsp;</td>

    </tr>
<?php else : ?>
    <tr>
        <td class="sticky-cell1 border-bottom" rowspan="3"><?= Html::a($customer->customer_code, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell2 border-bottom" rowspan="3"><?= $customer->clientContract->repaymentPattern->name ?></td>
        <td class="sticky-cell3 border-bottom" rowspan="3"<?= $customerBgColor ? " style=\"background-color:{$customerBgColor}!important;\"" : '' ?>><?= Html::a($customer->name, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell4 border-bottom" rowspan="3"><?= Html::a($contract->contractNumber, ['/aas/lease-contract', 'id' => $contract->lease_contract_id]) ?></td>
        <td class="border-bottom" rowspan="3"><?= $detail->taxApplication->application_name ?></td>
        <td rowspan="2"><?= $detail->term_start_at ?></td>
        <td class="border-bottom" rowspan="3"><?= $contract->leaseTarget->registration_number ?></td>
        <td rowspan="2"><?= $detail->term_months_count ?>ヶ月</td>
        <td>回収</td>
        <?php foreach($terms as $term) :
            $monthlyCharges = $term->getMonthlyCharges($model->contract_detail_id); ?>
            <?php $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []); ?>
            <?php $diff = ($monthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)) : 0) - ($repayments ? array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments)) : 0); ?>
            <?php $bgColorClass = $diff > 0 ? ($term['relative_month'] > 0 ? '' : ' deficient') : ($monthlyCharges ? ' paid' : '') ?>
            <?php if (count($monthlyCharges) > 1) :
                $styles = array_unique(array_map(function($monthlyCharge){return $monthlyCharge->repaymentType->style;}, $monthlyCharges)); ?>
            <td class="text-end<?= $bgColorClass ?>"><?= join(',',array_map(function($monthlyCharge){return $monthlyCharge->orderCountText;}, $monthlyCharges)) ?></td>
            <td class="text-end<?= $bgColorClass ?>" style="<?= $styles ? join('', $styles) : '' ?>"><?= number_format(array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)),0) ?></td>
            <?php else : $monthlyCharge = $monthlyCharges[0] ?? null; ?>
            <td class="text-end<?= $bgColorClass ?>"><?= $monthlyCharge ? $monthlyCharge->orderCountText : '&nbsp;' ?></td>
                <?php $is_closed = $term->termDateTime < $lastMonth || \app\models\TargetTermMonthlyChargeStored::isMonthClosed($term->term, $searchModel->client_corporation_id) ?>
            <td class="text-end<?= $bgColorClass ?><?= $monthlyCharge && !$is_closed ? ' editable formatted cell-monthly_charge-temporary_charge_amount_and_type' : '' ?>" style="<?= $monthlyCharge && $monthlyCharge->repaymentType->style ? $monthlyCharge->repaymentType->style : '' ?>" data-id="<?= $monthlyCharge ? $monthlyCharge->monthly_charge_id : '' ?>"><?= $monthlyCharge ? number_format($monthlyCharge->temporaryAmountWithTax,0) : '&nbsp;' ?></td>
        <?php endif; endforeach; ?>
        <td class="text-end"><?= number_format($model->getTermsTotalChargeAmountWithTax($terms), 0) ?></td>
        <td class="text-end"><?= number_format($model->totalChargeAmountWithTax, 0) ?></td>
        <?php $lastTerm = $terms[11];
        $lastMonthlyCharges = $lastTerm->getMonthlyCharges($model->contract_detail_id);
        $lastMonthlyCharge = count($lastMonthlyCharges) > 1 ? array_pop($lastMonthlyCharges) : $lastMonthlyCharges[0] ?? false;
        ?>
        <td class="text-end"><?= $lastMonthlyCharge ? ($lastMonthlyCharge->isLast ? '終' : $lastMonthlyCharge->orderCount + 1) : '終' ?></td>
        <td class="text-end"><?= $lastMonthlyCharge ? number_format(($model->getMonthlyCharges()->count() - $lastMonthlyCharge->orderCount) * $model->monthlyChargeWithTax, 0) : 0 ?></td>
        <td class="text-end"><?= number_format($model->monthlyChargeWithTax, 0) ?></td>
        <td class="text-end"><?= $model->term_months_count ?></td>
        <td class="text-end"><?= number_format($model->advanceRepaymentTotal, 0) ?></td>
        <td class="text-end"><?= $model->leaseContract->memo ?></td>
    </tr>
    <tr>
        <td>実績</td>
        <?php foreach($terms as $term) :
        $monthlyCharges = $term->getMonthlyCharges($model->contract_detail_id);
        $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []);
        $diff = ($monthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)) : 0) - ($repayments ? array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments)) : 0);
        $bgColorClass = $diff > 0 ? ($term['relative_month'] > 0 ? ' ' : ' deficient') : ($monthlyCharges ? ' paid' : '');
        $is_closed = $term->termDateTime < $lastMonth || \app\models\TargetTermMonthlyChargeStored::isMonthClosed($term->term, $searchModel->client_corporation_id);
        $deficientClass = $repayments ? ($diff > 0 && $is_closed ? ' editable cell-repayment-repayment_amount' : '') : (count($monthlyCharges) > 0 && $is_closed ? ' registerable cell-repayment-repayment_amount' : '');
        $ids = $deficientClass ? ($repayments ? implode(',', array_map(function($rp){return $rp->repayment_id;}, $repayments)) : implode(',', array_map(function($mc){return $mc->monthly_charge_id;}, $monthlyCharges))) : '';
        $identifier = $ids ? " data-id=\"{$ids}\"" : ''; ?>
        <?php if (count($repayments) > 0) :
            $styles = array_unique(array_map(function($repayment){return $repayment->repaymentType->style;}, $repayments)); ?>
        <td class="text-end<?= $bgColorClass ?>"><?= join(',', array_map(function($repayment){return $repayment->orderCountText;}, $repayments)) ?></td>
        <td class="text-end<?= $bgColorClass . $deficientClass ?>"<?= $identifier ?> style="<?= $styles ? join('', $styles) : '' ?>"><?= number_format(array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments)),0) ?></td>
        <?php else : $repayment = $repayments[0] ?? null; ?>
        <td class="text-end<?= $bgColorClass ?>"><?= $repayment ? $repayment->orderCountText : '&nbsp;' ?></td>
        <td class="text-end<?= $bgColorClass . $deficientClass ?>"<?= $identifier ?> style="<?= $repayment && $repayment->repaymentType->style ? $repayment->repaymentType->style : '' ?>"><?= $repayment ? number_format($repayment->repayment_amount,0) : '&nbsp;' ?></td>
        <?php endif; endforeach; ?>
        <td class="text-end"><?= number_format($model->getTermsRepaymentTotal($terms), 0) ?></td>
        <td class="text-end"><?= number_format(\app\models\Repayment::getCurrentTotal($detail->contract_detail_id, $targetTerm->format('Ym'))) ?></td>
        <?php
            $monthlyCharges = array_reduce(array_map(function($term)use($model){return $term->getMonthlyCharges($model->contract_detail_id);}, $terms), 'array_merge', []);
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
        ?>
        <td class="text-end"><?= $lastRepayment ? $lastRepayment->orderCount : '' ?></td>
        <td class="text-end"><?= number_format($model->totalChargeAmountWithTax - \app\models\Repayment::getCurrentTotal($detail->contract_detail_id)) ?></td>
        <td class="text-end"><?= (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $model->lease_start_at)))->format('Y/m') ?></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td class="border-bottom"><?= $detail->term_end_at ?></td>
        <td class="border-bottom"><?= $detail->leaseServicer->shorten_name ?></td>
        <td class="payment-cell border-bottom">支払</td>
        <?php foreach($terms as $term) : if ($term->termDateTime->format('Ym') == $targetTerm->format('Ym')) : ?>
        <?php $monthlyPayments = $term->getMonthlyPayments($model->contract_detail_id) ?>
        <?php if (count($monthlyPayments) > 1) : ?>
        <td class="text-end payment-cell border-bottom"><?= join(',', array_map(function($monthlyPayment){return $monthlyPayment->orderCount;}, $monthlyPayments)) ?></td>
        <td class="text-end payment-cell border-bottom"><?= number_format(array_sum(array_map(function($monthlyPayment){return $monthlyPayment->amountWithTax;}, $monthlyPayments)),0) ?></td>
        <?php else : $monthlyPayment = $monthlyPayments[0] ?? null; ?>
        <td class="text-end payment-cell border-bottom"><?= $monthlyPayment ? $monthlyPayment->orderCount : '&nbsp;' ?></td>
        <td class="text-end payment-cell border-bottom"><?= $monthlyPayment ? number_format($monthlyPayment->amountWithTax,0) : '&nbsp;' ?></td>
        <?php endif; ?>
        <?php else : ?>
        <?php $monthlyPayments = $term->getMonthlyPayments($model->contract_detail_id); ?>
        <?php if (count($monthlyPayments) > 1) : ?>
        <td class="text-end payment-cell border-bottom"><?= join(',', array_map(function($monthlyPayment){return $monthlyPayment->orderCount;}, $monthlyPayments)) ?></td>
        <td class="text-end payment-cell border-bottom"><?= number_format(array_sum(array_map(function($monthlyPayment){return $monthlyPayment->amountWithTax;}, $monthlyPayments)),0) ?></td>
        <?php else : $monthlyPayment = $monthlyPayments[0] ?? null; ?>
        <td class="text-end payment-cell border-bottom"><?= $monthlyPayment ? $monthlyPayment->orderCount : '&nbsp;' ?></td>
        <td class="text-end payment-cell border-bottom"><?= $monthlyPayment ? number_format($monthlyPayment->amountWithTax,0) : '&nbsp;' ?></td>
        <?php endif; ?>
        <?php endif; endforeach; ?>

        <td class="border-bottom text-end"><?= number_format($model->getTermsTotalpaymentAmountWithTax($terms), 0) ?></td>
        <td class="border-bottom text-end"><?= number_format($model->totalPaymentAmountWithTax, 0) ?></td>
        <?php $lastTerm = $terms[11];
        $lastMonthlyPayments = $lastTerm->getMonthlyPayments($model->contract_detail_id);
        $lastMonthlyPayment = count($lastMonthlyPayments) > 1 ? array_pop($lastMonthlyPayments) : $lastMonthlyPayments[0] ?? false;
        ?>
        <td class="border-bottom text-end"><?= $lastMonthlyPayment ? ($lastMonthlyPayment->isLast ? '終' : $lastMonthlyPayment->orderCount + 1) : '終' ?></td>
        <td class="border-bottom text-end"><?= $lastMonthlyPayment ? number_format(($model->getMonthlyPayments()->count() - $lastMonthlyPayment->orderCount) * $model->monthlyPaymentWithTax, 0) : 0 ?></td>
        <td class="border-bottom text-end"><?= number_format($model->monthlyPaymentWithTax, 0) ?></td>
        <td class="border-bottom">&nbsp;</td>
        <td class="border-bottom">&nbsp;</td>
        <td class="border-bottom">&nbsp;</td>
    </tr>
<?php endif; ?>