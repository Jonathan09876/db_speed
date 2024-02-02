<?php
/**
 * @var $this \yii\web\View;
 * @var $searchModel \app\models\MonthlyChargeSearch;
 * @var $model \app\models\MonthlyCharge;
 * @var $index integer;
 * @var $widget \yii\widgets\ListView;
 * @var $targetTerm \DateTime;
 * @var $terms array;
 */

use yii\bootstrap5\Html;

$detail = $model->contractDetail;
$contract = $model->contractDetail->leaseContract;
$customer = $model->contractDetail->leaseContract->customer;
if ($searchModel->hide_collection || $detail->monthly_charge == 0) : ?>
<tr>
    <td class="sticky-cell1 border-bottom"><?= Html::a($customer->customer_code, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
    <td class="sticky-cell2 border-bottom"><?= $customer->clientContract->repaymentPattern->name ?></td>
    <td class="sticky-cell3 border-bottom"><?= Html::a($customer->name, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
    <td class="sticky-cell4 border-bottom"><?= Html::a($contract->contractNumber, ['/aas/lease-contract', 'id' => $contract->lease_contract_id]) ?></td>
    <td class="border-bottom"><?= $detail->taxApplication->application_name ?></td>
    <td class="border-bottom"><?= $detail->term_start_at ?><br /><?= $detail->term_end_at ?></td>
    <td class="border-bottom"><?= $contract->leaseTarget->registration_number ?></td>
    <td class="border-bottom"><?= $detail->term_months_count ?>ヶ月<br /><?= $detail->leaseServicer->shorten_name ?></td>

    <td class="payment-cell border-bottom">支払</td>
    <?php foreach($terms as $term) : if ($term['the_term']->format('Ym') == $targetTerm->format('Ym')) : ?>
        <?php $monthlyPayments = \app\models\MonthlyPayment::getSiblings($model->contract_detail_id, $targetTerm->format('Ym'), $term['relative_month']) ?>
        <?php if (count($monthlyPayments) > 1) : ?>
        <td class="text-end payment-cell border-bottom"><?= implode(',', array_map(function($monthlyPayment){return $monthlyPayment->orderCount;}, $monthlyPayments)) ?></td>
        <td class="text-end payment-cell border-bottom"><?= number_format(array_sum(array_map(function($monthlyPayment){return $monthlyPayment->amountWithTax;}, $monthlyPayments)),0) ?></td>
        <?php else : $monthlyPayment = $monthlyPayments[0] ?? null; ?>
        <td class="text-end payment-cell border-bottom"><?= $monthlyPayment ? $monthlyPayment->orderCount : '&nbsp;' ?></td>
        <td class="text-end payment-cell border-bottom"><?= $monthlyPayment ? number_format($monthlyPayment->amountWithTax,0) : '&nbsp;' ?></td>
        <?php endif; ?>
        <td class="payment-cell border-bottom">&nbsp;</td>
    <?php else : ?>
        <?php $monthlyPayment = \app\models\MonthlyPayment::getSibling($model->contract_detail_id, $targetTerm->format('Ym'), $term['relative_month']); ?>
        <?php if (count($monthlyPayments) > 1) : ?>
        <td class="text-end payment-cell border-bottom"><?= implode(',', array_map(function($monthlyPayment){return $monthlyPayment->orderCount;}, $monthlyPayments)) ?></td>
        <td class="text-end payment-cell border-bottom"><?= number_format(array_sum(array_map(function($monthlyPayment){return $monthlyPayment->amountWithTax;}, $monthlyPayments)),0) ?></td>
        <?php else : $monthlyPayment = $monthlyPayments[0] ?? null; ?>
        <td class="text-end payment-cell border-bottom"><?= $monthlyPayment ? $monthlyPayment->orderCount : '&nbsp;' ?></td>
        <td class="text-end payment-cell border-bottom"><?= $monthlyPayment ? number_format($monthlyPayment->amountWithTax,0) : '&nbsp;' ?></td>
        <?php endif; ?>
    <?php endif; endforeach; ?>

    <td class="text-end border-bottom">
        <?= number_format($model->contractDetail->totalPaymentAmountWithTax, 0) ?>
    </td>
    <?php $lease_start_at = (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $model->contractDetail->lease_start_at))); ?>
    <td class="border-bottom"><?= $lease_start_at->format('Y/m') ?><br/><?= \app\models\MonthlyPayment::getRemainsCount($model->contract_detail_id) ?></td>
    <td class="border-bottom"><?= \app\models\MonthlyPayment::getRemainsCount($model->contract_detail_id) ?>ヶ月<br /><?= number_format(\app\models\MonthlyPayment::getRemainsAmount($model->contract_detail_id), 0) ?></td>
    <td class="text-end border-bottom"><?= number_format($model->contractDetail->monthlyChargeWithTax, 0) ?></td>
</tr>
<?php elseif ($searchModel->hide_payment || $detail->monthly_payment == 0) : ?>
    <tr>
        <td class="sticky-cell1 border-bottom" rowspan="2"><?= Html::a($customer->customer_code, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell2 border-bottom" rowspan="2"><?= $customer->clientContract->repaymentPattern->name ?></td>
        <td class="sticky-cell3 border-bottom" rowspan="2"><?= Html::a($customer->name, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell4 border-bottom" rowspan="2"><?= Html::a($contract->contractNumber, ['/aas/lease-contract', 'id' => $contract->lease_contract_id]) ?></td>
        <td class="border-bottom" rowspan="2"><?= $detail->taxApplication->application_name ?></td>
        <td><?= $detail->term_start_at ?></td>
        <td class="border-bottom" rowspan="2"><?= $contract->leaseTarget->registration_number ?></td>
        <td><?= $detail->term_months_count ?>ヶ月</td>

        <td>回収</td>
        <?php foreach($terms as $term) : if ($term['the_term']->format('Ym') == $targetTerm->format('Ym')) : ?>
            <td class="text-end"><?= $model->orderCount ?></td>
            <td class="text-end"><?= number_format($model->amountWithTax,0) ?></td>
            <?php
            if (empty($model->temporary_charge_amount)) {
                $model->temporary_charge_amount = $model->charge_amount;
            }
            ?>
            <td class="text-end editable formatted cell-monthly_charge-temporary_charge_amount" data-id="<?= $model->monthly_charge_id ?>"><?= number_format($model->amountWithTax,0) ?></td>
        <?php else : ?>
            <?php $monthlyCharges = \app\models\MonthlyCharge::getSiblings($model->contract_detail_id, $targetTerm->format('Ym'), $term['relative_month']); ?>
            <?php $repayments = \app\models\Repayment::getSiblings($model->contract_detail_id, $targetTerm->format('Ym'), $term['relative_month']); ?>
            <?php $bgColor = ($monthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)) : 0) - ($repayments ? array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments)) : 0) > 0 ? ($term['relative_month'] > 0 ? '#ffffff' : '#ffd4d4') : ($monthlyCharges ? '#f2f2f2' : '#ffffff') ?>
            <?php if (count($monthlyCharges) > 1) : ?>
            <td class="text-end" style="background-color:<?= $bgColor ?>"><?= join(',',array_map(function($monthlyCharge){return $monthlyCharge->orderCount;}, $monthlyCharges)) ?></td>
            <td class="text-end" style="background-color:<?= $bgColor ?>"><?= number_format(array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)),0) ?></td>
            <?php else : $monthlyCharge = $monthlyCharges[0] ?? null; ?>
            <td class="text-end" style="background-color:<?= $bgColor ?>"><?= $monthlyCharge ? $monthlyCharge->orderCount : '&nbsp;' ?></td>
            <td class="text-end" style="background-color:<?= $bgColor ?>"><?= $monthlyCharge ? number_format($monthlyCharge->amountWithTax,0) : '&nbsp;' ?></td>
            <?php endif; ?>
        <?php endif; endforeach; ?>

        <td rowspan="1" class="text-end"><?= number_format($model->contractDetail->totalChargeAmountWithTax, 0) ?></td>
        <?php $lease_start_at = (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $model->contractDetail->lease_start_at))); ?>
        <td rowspan="1"><?= $lease_start_at->format('Y/m') ?></td>
        <td rowspan="1"><?= \app\models\MonthlyPayment::getRemainsCount($model->contract_detail_id) ?>ヶ月</td>
        <td rowspan="1" class="text-end"><?= number_format($model->contractDetail->monthlyChargeWithTax, 0) ?></td>
    </tr>
    <tr>
        <td class="border-bottom"><?= $detail->term_end_at ?></td>
        <td class="border-bottom"></td>
        <td class="border-bottom">実績</td>
        <?php foreach($terms as $term) : if ($term['the_term']->format('Ym') == $targetTerm->format('Ym')) : ?>
            <td class="border-bottom text-end">&nbsp;</td>
            <td class="border-bottom text-end">&nbsp;</td>
            <td class="border-bottom text-end"><?= number_format(\app\models\MonthlyCharge::getRelativeShortage($model->contract_detail_id, $targetTerm->format('Ym')), 0) ?></td>
        <?php else : ?>
            <?php $monthlyCharges = \app\models\MonthlyCharge::getSiblings($model->contract_detail_id, $targetTerm->format('Ym'), $term['relative_month']); ?>
            <?php $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []); ?>
            <?php $bgColor = ($monthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)) : 0) - ($repayments ? array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments)) : 0) > 0 ? ($term['relative_month'] > 0 ? '#ffffff' : '#ffd4d4') : ($monthlyCharges ? '#f2f2f2' : '#ffffff') ?>
            <?php if (count($repayments) > 0) : ?>
            <td class="border-bottom text-end" style="background-color:<?= $bgColor ?>"><?= join(',', array_map(function($repayment){return $repayment->orderCount;}, $repayments)) ?></td>
            <td class="border-bottom text-end" style="background-color:<?= $bgColor ?>"><?= number_format(array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments)),0) ?></td>
            <?php else : $repayment = $repayments[0] ?? null; ?>
            <td class="border-bottom text-end" style="background-color:<?= $bgColor ?>"><?= $repayment ? $repayment->orderCount : '&nbsp;' ?></td>
            <td class="border-bottom text-end" style="background-color:<?= $bgColor ?>"><?= $repayment ? number_format($repayment->repayment_amount,0) : '&nbsp;' ?></td>
            <?php endif; ?>
        <?php endif; endforeach; ?>
        <td class="border-bottom text-end">&nbsp;</td>
        <td class="border-bottom"><?= \app\models\MonthlyPayment::getRemainsCount($model->contract_detail_id) ?></td>
        <td class="border-bottom text-end"><?= number_format(\app\models\MonthlyPayment::getRemainsAmount($model->contract_detail_id), 0) ?></td>
        <td class="border-bottom">&nbsp;</td>
    </tr>
<?php else : ?>
    <tr>
        <td class="sticky-cell1 border-bottom" rowspan="3"><?= Html::a($customer->customer_code, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell2 border-bottom" rowspan="3"><?= $customer->clientContract->repaymentPattern->name ?></td>
        <td class="sticky-cell3 border-bottom" rowspan="3"><?= Html::a($customer->name, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell4 border-bottom" rowspan="3"><?= Html::a($contract->contractNumber, ['/aas/lease-contract', 'id' => $contract->lease_contract_id]) ?></td>
        <td class="border-bottom" rowspan="3"><?= $detail->taxApplication->application_name ?></td>
        <td rowspan="2"><?= $detail->term_start_at ?></td>
        <td class="border-bottom" rowspan="3"><?= $contract->leaseTarget->registration_number ?></td>
        <td rowspan="2"><?= $detail->term_months_count ?>ヶ月</td>
        <td>回収</td>
        <?php foreach($terms as $term) : if ($term['the_term']->format('Ym') == $targetTerm->format('Ym')) : ?>

        <?php $monthlyCharges = \app\models\MonthlyCharge::getSiblings($model->contract_detail_id, $targetTerm->format('Ym'), $term['relative_month']); ?>
        <?php $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []); ?>
        <?php $bgColor = ($monthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)) : 0) - ($repayments ? array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments)) : 0) > 0 ? ($term['relative_month'] > 0 ? '#ffffff' : '#ffd4d4') : ($monthlyCharges ? '#f2f2f2' : '#ffffff') ?>
        <?php if (count($monthlyCharges) > 1) : ?>
        <td class="text-end" style="background-color:<?= $bgColor ?>"><?= join(',', array_map(function($monthlyCharge){return $monthlyCharge->orderCount;}, $monthlyCharges)) ?></td>
        <td class="text-end" style="background-color:<?= $bgColor ?>"><?= number_format(array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)),0) ?></td>
        <?php else : ?>
        <td class="text-end" style="background-color:<?= $bgColor ?>"><?= $model->orderCount ?></td>
        <td class="text-end" style="background-color:<?= $bgColor ?>"><?= number_format($model->amountWithTax,0) ?></td>
        <?php endif; ?>
        <?php
        if (empty($model->temporary_charge_amount)) {
            $model->temporary_charge_amount = $model->charge_amount;
        }
        ?>
        <td class="text-end editable formatted cell-monthly_charge-temporary_charge_amount" data-id="<?= $model->monthly_charge_id ?>"><?= number_format($model->amountWithTax,0) ?></td>
        <?php else : ?>
        <?php $monthlyCharges = \app\models\MonthlyCharge::getSiblings($model->contract_detail_id, $targetTerm->format('Ym'), $term['relative_month']); ?>
        <?php $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []); ?>
        <?php $bgColor = ($monthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)) : 0) - ($repayments ? array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments)) : 0) > 0 ? ($term['relative_month'] > 0 ? '#ffffff' : '#ffd4d4') : ($monthlyCharges ? '#f2f2f2' : '#ffffff') ?>
        <?php if (count($monthlyCharges) > 1) : ?>
        <td class="text-end" style="background-color:<?= $bgColor ?>"><?= join(',', array_map(function($monthlyCharge){return $monthlyCharge->orderCount;}, $monthlyCharges)) ?></td>
        <td class="text-end" style="background-color:<?= $bgColor ?>"><?= number_format(array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)),0) ?></td>
        <?php else: $monthlyCharge = $monthlyCharges[0] ?? null; ?>
        <td class="text-end" style="background-color:<?= $bgColor ?>"><?= $monthlyCharge ? $monthlyCharge->orderCount : '&nbsp;' ?></td>
        <td class="text-end" style="background-color:<?= $bgColor ?>"><?= $monthlyCharge ? number_format($monthlyCharge->amountWithTax,0) : '&nbsp;' ?></td>
        <?php endif; ?>
        <?php endif; endforeach; ?>
        <td rowspan="2" class="text-end"><?= number_format($model->contractDetail->totalChargeAmountWithTax, 0) ?></td>
        <?php $lease_start_at = (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $model->contractDetail->lease_start_at))); ?>
        <td rowspan="2"><?= $lease_start_at->format('Y/m') ?></td>
        <td rowspan="2"><?= \app\models\MonthlyPayment::getRemainsCount($model->contract_detail_id) ?>ヶ月</td>
        <td rowspan="2" class="text-end"><?= number_format($model->contractDetail->monthlyChargeWithTax, 0) ?></td>
    </tr>
    <tr>
        <td>実績</td>
        <?php foreach($terms as $term) : if ($term['the_term']->format('Ym') == $targetTerm->format('Ym')) : ?>
        <?php $monthlyCharges = \app\models\MonthlyCharge::getSiblings($model->contract_detail_id, $targetTerm->format('Ym'), $term['relative_month']); ?>
        <?php $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []); ?>
        <?php $bgColor = ($monthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)) : 0) - ($repayments ? array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments)) : 0) > 0 ? ($term['relative_month'] > 0 ? '#ffffff' : '#ffd4d4') : ($monthlyCharges ? '#f2f2f2' : '#ffffff') ?>
        <?php if (count($repayments) > 1) : ?>
        <td class="text-end" style="background-color:<?= $bgColor ?>"><?= join(',', array_map(function($repayment){return $repayment->orderCount;}, $repayments)) ?></td>
        <td class="text-end" style="background-color:<?= $bgColor ?>"><?= number_format(array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments)),0) ?></td>
        <?php else : $repayment = $repayments[0] ?? null ?>
        <td class="text-end" style="background-color:<?= $bgColor ?>"><?= $repayment ? $repayment->orderCount : '&nbsp;' ?></td>
        <td class="text-end" style="background-color:<?= $bgColor ?>"><?= $repayment ? number_format($repayment->repayment_amount,0) : '&nbsp;' ?></td>
        <?php endif; ?>
        <td class="text-end"><?= number_format(\app\models\MonthlyCharge::getRelativeShortage($model->contract_detail_id, $targetTerm->format('Ym')), 0) ?></td>
        <?php else : ?>
        <?php $monthlyCharges = \app\models\MonthlyCharge::getSiblings($model->contract_detail_id, $targetTerm->format('Ym'), $term['relative_month']); ?>
        <?php $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []); ?>
        <?php $bgColor = ($monthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)) : 0) - ($repayments ? array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments)) : 0) > 0 ? ($term['relative_month'] > 0 ? '#ffffff' : '#ffd4d4') : ($monthlyCharges ? '#f2f2f2' : '#ffffff') ?>
        <?php if (count($repayments) > 0) : ?>
        <td class="text-end" style="background-color:<?= $bgColor ?>"><?= join(',', array_map(function($repayment){return $repayment->orderCount;}, $repayments)) ?></td>
        <td class="text-end" style="background-color:<?= $bgColor ?>"><?= number_format(array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments)),0) ?></td>
        <?php else : $repayment = $repayments[0] ?? null; ?>
        <td class="text-end" style="background-color:<?= $bgColor ?>"><?= $repayment ? $repayment->orderCount : '&nbsp;' ?></td>
        <td class="text-end" style="background-color:<?= $bgColor ?>"><?= $repayment ? number_format($repayment->repayment_amount,0) : '&nbsp;' ?></td>
        <?php endif; ?>
        <?php endif; endforeach; ?>
    </tr>
    <tr>
        <td class="border-bottom"><?= $detail->term_end_at ?></td>
        <td class="border-bottom"><?= $detail->leaseServicer->shorten_name ?></td>
        <td class="payment-cell border-bottom">支払</td>
        <?php foreach($terms as $term) : if ($term['the_term']->format('Ym') == $targetTerm->format('Ym')) : ?>
        <?php $monthlyPayments = \app\models\MonthlyPayment::getSiblings($model->contract_detail_id, $targetTerm->format('Ym'), $term['relative_month']) ?>
        <?php if (count($monthlyPayments) > 1) : ?>
        <td class="text-end payment-cell border-bottom"><?= join(',', array_map(function($monthlyPayment){return $monthlyPayment->orderCount;}, $monthlyPayments)) ?></td>
        <td class="text-end payment-cell border-bottom"><?= number_format(array_sum(array_map(function($monthlyPayment){return $monthlyPayment->amountWithTax;}, $monthlyPayments)),0) ?></td>
        <?php else : $monthlyPayment = $monthlyPayments[0] ?? null; ?>
        <td class="text-end payment-cell border-bottom"><?= $monthlyPayment ? $monthlyPayment->orderCount : '&nbsp;' ?></td>
        <td class="text-end payment-cell border-bottom"><?= $monthlyPayment ? number_format($monthlyPayment->amountWithTax,0) : '&nbsp;' ?></td>
        <?php endif; ?>
        <td class="payment-cell border-bottom">&nbsp;</td>
        <?php else : ?>
        <?php $monthlyPayments = \app\models\MonthlyPayment::getSiblings($model->contract_detail_id, $targetTerm->format('Ym'), $term['relative_month']); ?>
        <?php if (count($monthlyPayments) > 1) : ?>
        <td class="text-end payment-cell border-bottom"><?= join(',', array_map(function($monthlyPayment){return $monthlyPayment->orderCount;}, $monthlyPayments)) ?></td>
        <td class="text-end payment-cell border-bottom"><?= number_format(array_sum(array_map(function($monthlyPayment){return $monthlyPayment->amountWithTax;}, $monthlyPayments)),0) ?></td>
        <?php else : $monthlyPayment = $monthlyPayments[0] ?? null; ?>
        <td class="text-end payment-cell border-bottom"><?= $monthlyPayment ? $monthlyPayment->orderCount : '&nbsp;' ?></td>
        <td class="text-end payment-cell border-bottom"><?= $monthlyPayment ? number_format($monthlyPayment->amountWithTax,0) : '&nbsp;' ?></td>
        <?php endif; ?>
        <?php endif; endforeach; ?>

        <td class="border-bottom" class="text-end"><?= number_format($model->contractDetail->totalPaymentAmountWithTax, 0) ?></td>
        <td class="border-bottom"><?= \app\models\MonthlyPayment::getRemainsCount($model->contract_detail_id) ?></td>
        <td class="border-bottom" class="text-end"><?= number_format(\app\models\MonthlyPayment::getRemainsAmount($model->contract_detail_id), 0) ?></td>
        <td class="border-bottom">&nbsp;</td>
    </tr>
    <?php /*
    <tr>
        <td class="payment-cell border-bottom">実績</td>
        <?php foreach($terms as $term) : if ($term['the_term']->format('Ym') == $targetTerm->format('Ym')) : ?>
        <?php $leasePayment = \app\models\LeasePayment::getSibling($model->contract_detail_id, $targetTerm->format('Ym'), $term['relative_month']) ?>
        <td class="text-end payment-cell border-bottom"><?= $leasePayment ? $leasePayment->orderCount : '&nbsp;' ?></td>
        <td class="payment-cell border-bottom">&nbsp;</td>
        <td class="text-end payment-cell border-bottom"><?= $leasePayment ? number_format($leasePayment->payment_amount,0) : '&nbsp;' ?></td>
        <td class="payment-cell border-bottom">&nbsp;</td>
        <td class="payment-cell border-bottom">&nbsp;</td>
        <?php else : ?>
        <?php $leasePayment = \app\models\LeasePayment::getSibling($model->contract_detail_id, $targetTerm->format('Ym'), $term['relative_month']); ?>
        <td class="text-end payment-cell border-bottom"><?= $leasePayment ? $leasePayment->orderCount : '&nbsp;' ?></td>
        <td class="text-end payment-cell border-bottom"><?= $leasePayment ? number_format($leasePayment->payment_amount,0) : '&nbsp;' ?></td>
        <?php endif; endforeach; ?>
    </tr>
    */ ?>
<?php endif; ?>