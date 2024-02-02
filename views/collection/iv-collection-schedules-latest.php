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
$customerBgColor = $detail->contract_type == 'delinquency' ? '#888' : $contract->contractPattern->bg_color;
if ($searchModel->hide_collection || $detail->monthly_charge == 0) : ?>
<tr data-cdid="<?= $detail->contract_detail_id ?>" class="type-<?= $model->contract_type ?>">
    <td class="sticky-cell sticky-cell1 border-bottom"><?= Html::a($customer->customer_code, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
    <td class="sticky-cell sticky-cell2 border-bottom"><?= $customer->clientContract->repaymentPattern->name ?></td>
    <td class="sticky-cell sticky-cell3 border-bottom"<?= $customerBgColor ? " style=\"background-color:{$customerBgColor}!important;\"" : '' ?>><?= Html::a($customer->getName(), ['/aas/redirect-to-register-repayment-by-customer', 'id' => $customer->customer_id], ['target' => '_blank', 'style' => $detail->contract_type == 'delinquency' ? 'color:#fff;' : '']) ?></td>
    <td class="sticky-cell sticky-cell4 border-bottom"><?= Html::a($contract->contractNumber, ['/aas/lease-contract', 'id' => $contract->lease_contract_id], ['target' => '_blank']) ?></td>
    <td class="sticky-cell sticky-cell5 border-bottom"><?= $detail->taxApplication->application_name ?></td>
    <td class="sticky-cell sticky-cell6 border-bottom"><?= $detail->contract_type == 'delinquency' ? '' : $detail->term_start_at ?><br /><?= $detail->contract_type == 'delinquency' ? '' : $detail->term_end_at ?></td>
    <td class="sticky-cell sticky-cell7 border-bottom"><?= $contract->leaseTarget->registration_number ?></td>
    <td class="sticky-cell sticky-cell8 border-bottom"><?= $detail->contract_type == 'delinquency' ? '' : $detail->term_months_count . 'ヶ月' ?><br /><?= $detail->leaseServicer->shorten_name ?></td>

    <td class="sticky-cell sticky-cell9 payment-cell border-bottom">支払</td>
    <?php foreach($terms as $term) {
        $instance = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
        if (!$detail->registration_status) {
            echo $instance->render($term, 'paymentOnly', 'monthly_payment');
        }
        else {
            echo '<td class="border-bottom payment-cell" colspan="2">&nbsp;</td>';
        }
    } ?>
    <?php $lastTerm = $terms[11];
    $lastMonthlyPayments = $lastTerm->getMonthlyPayments($model->contract_detail_id);
    $lastMonthlyPayment = count($lastMonthlyPayments) > 1 ? array_pop($lastMonthlyPayments) : $lastMonthlyPayments[0] ?? false;
    ?>

    <td class="text-end border-bottom"><?= number_format($model->getTermsTotalpaymentAmountWithTax($terms), 0) ?></td>
    <td class="text-end border-bottom"><?php if ($detail->contract_type == 'delinquency') : echo (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $model->lease_start_at)))->format('Y/m') ?><br/><?= !$detail->leaseContract->isStoppedOrCancelled ? $detail->getErapsedMonths($lastTerm) : ''; endif;?></td>
    <td class="text-end border-bottom"><?= $model->getAdvanceRepayments()->count() ?></td>
    <td class="text-end border-bottom"><?= number_format($model->advanceRepaymentTotal, 0) ?></td>
    <td class="border-bottom edit-memo" data-id="<?= $model->lease_contract_id ?>"><?= $model->leaseContract->memo ?></td>
</tr>
<?php /* elseif ($searchModel->hide_payment || $detail->monthly_payment == 0) : ?>
    <tr data-cdid="<?= $detail->contract_detail_id ?>">
        <td class="sticky-cell sticky-cell1 border-bottom" rowspan="2"><?= Html::a($customer->customer_code, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell sticky-cell2 border-bottom" rowspan="2"><?= $customer->clientContract->repaymentPattern->name ?></td>
        <td class="sticky-cell sticky-cell3 border-bottom" rowspan="2"<?= $customerBgColor ? " style=\"background-color:{$customerBgColor}!important;\"" : '' ?>><?= Html::a($customer->getName(), ['/aas/customer', 'id' => $customer->customer_id], ['target' => '_blank', 'style' => $detail->contract_type == 'delinquency' ? 'color:#fff;' : '']) ?></td>
        <td class="sticky-cell sticky-cell4 border-bottom" rowspan="2"><?= Html::a($contract->contractNumber, ['/aas/lease-contract', 'id' => $contract->lease_contract_id], ['target' => '_blank']) ?></td>
        <td class="sticky-cell sticky-cell5 border-bottom" rowspan="2"><?= $detail->taxApplication->application_name ?></td>
        <td class="sticky-cell sticky-cell6"><?= $detail->contract_type == 'delinquency' ? '' : $detail->term_start_at ?></td>
        <td class="sticky-cell sticky-cell7 border-bottom" rowspan="2"><?= $contract->leaseTarget->registration_number ?></td>
        <td class="sticky-cell sticky-cell8"><?= $detail->contract_type == 'delinquency' ? '' : $detail->term_months_count . 'ヶ月' ?></td>

        <td class="sticky-cell sticky-cell9">回収</td>
        <?php foreach($terms as $term) {
            $instance = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
            echo $instance->render($term, 'chargeOnly', 'monthly_charge');
        } ?>

        <td class="text-end"><?= number_format($model->getTermsTotalChargeAmountWithTax($terms), 0) ?></td>
        <?php $lastTerm = $terms[11];
        $lastMonthlyCharges = $lastTerm->getMonthlyCharges($model->contract_detail_id);
        $lastMonthlyCharge = count($lastMonthlyCharges) > 1 ? array_pop($lastMonthlyCharges) : $lastMonthlyCharges[0] ?? false;
        ?>
        <td class="text-end"><?= $detail->contract_type == 'delinquency' ? '' : (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $model->lease_start_at)))->format('Y/m') ?></td>
        <td class="text-end"><?= $model->getAdvanceRepayments()->count() ?></td>
        <td class="text-end"><?= number_format($model->advanceRepaymentTotal, 0) ?></td>
        <td class=""><?= $model->leaseContract->memo ?></td>
    </tr>
    <tr>
        <td class="sticky-cell sticky-cell6 border-bottom"><?= $detail->contract_type == 'delinquency' ? '' : $detail->term_end_at ?></td>
        <td class="sticky-cell sticky-cell8 border-bottom"></td>
        <td class="sticky-cell sticky-cell9 border-bottom">実績</td>
        <?php foreach($terms as $term) {
            $instance = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
            echo $instance->render($term, 'chargeOnly', 'repayment');
        } ?>
        <td class="border-bottom text-end"><?= number_format($model->getTermsRepaymentTotal($terms), 0) ?></td>
        
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
            
        <td class="border-bottom text-end"><?= $detail->contract_type == 'delinquency' ? '' : (!$detail->leaseContract->isStoppedOrCancelled ? $detail->getErapsedMonths($lastTerm) : '') ?></td>
        <td class="border-bottom">&nbsp;</td>
        <td class="border-bottom">&nbsp;</td>
        <td class="border-bottom">&nbsp;</td>

    </tr>
<?php */ else : ?>
    <tr data-cdid="<?= $detail->contract_detail_id ?>">
        <td class="sticky-cell sticky-cell1 border-bottom" rowspan="3"><?= Html::a($customer->customer_code, ['/aas/customer', 'id' => $customer->customer_id], ['target' => '_blank']) ?></td>
        <td class="sticky-cell sticky-cell2 border-bottom" rowspan="3"><?= $customer->clientContract->repaymentPattern->name ?></td>
        <td class="sticky-cell sticky-cell3 border-bottom" rowspan="3"<?= $customerBgColor ? " style=\"background-color:{$customerBgColor}!important;\"" : '' ?>><?= Html::a($customer->getName(), ['/aas/redirect-to-register-repayment-by-customer', 'id' => $customer->customer_id], ['target' => '_blank', 'style' => $detail->contract_type == 'delinquency' ? 'color:#fff;' : '']) ?></td>
        <td class="sticky-cell sticky-cell4 border-bottom" rowspan="3"><?= Html::a($contract->contractNumber, ['/aas/lease-contract', 'id' => $contract->lease_contract_id], ['target' => '_blank']) ?></td>
        <td class="sticky-cell sticky-cell5 border-bottom" rowspan="3"><?= $detail->taxApplication->application_name ?></td>
        <td class="sticky-cell sticky-cell6" rowspan="2"><?= $detail->contract_type == 'delinquency' ? '' : $detail->term_start_at ?></td>
        <td class="sticky-cell sticky-cell7 border-bottom" rowspan="3"><?= $contract->leaseTarget->registration_number ?></td>
        <td class="sticky-cell sticky-cell8" rowspan="2"><?= $detail->contract_type == 'delinquency' ? '' : $detail->term_months_count . 'ヶ月' ?></td>
        <td class="sticky-cell sticky-cell9">回収</td>
        <?php foreach($terms as $term) {
            $instance = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
            echo $instance->render($term, 'both', 'monthly_charge');
        } ?>
        <td class="text-end"><?= number_format($model->getTermsTotalChargeAmountWithTax($terms), 0) ?></td>
        
        <?php $lastTerm = $terms[11];
        $lastMonthlyCharges = $lastTerm->getMonthlyCharges($model->contract_detail_id);
        $lastMonthlyCharge = count($lastMonthlyCharges) > 1 ? array_pop($lastMonthlyCharges) : $lastMonthlyCharges[0] ?? false;
        ?>
       
        <td class="text-end"><?= $detail->contract_type == 'delinquency' ? '' : (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $model->lease_start_at)))->format('Y/m') ?></td>
        <td class="text-end"><?= $model->getAdvanceRepayments()->count() ?></td>
        <td class="text-end"><?= number_format($model->advanceRepaymentTotal, 0) ?></td>
        <td rowspan="3" class="border-bottom edit-memo" data-id="<?= $model->lease_contract_id ?>"><?= $model->leaseContract->memo ?></td>
    </tr>
    <tr class="row-repayments">
        <td class="sticky-cell sticky-cell9">実績</td>
        <?php foreach($terms as $term) {
            $instance = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
            echo $instance->render($term, 'both', 'repayment');
        } ?>
        <td class="text-end"><?= number_format($model->getTermsRepaymentTotal($terms), 0) ?></td>
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
        
        <td class="text-end"><?= $detail->contract_type == 'delinquency' ? '' : (!$detail->leaseContract->isStoppedOrCancelled ? $detail->getErapsedMonths($lastTerm) : '') ?></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
    <tr class="type-<?= $model->contract_type ?>">
        <td class="sticky-cell sticky-cell6 border-bottom"><?= $detail->contract_type == 'delinquency' ? '' : $detail->term_end_at ?></td>
        <td class="sticky-cell sticky-cell8 border-bottom"><?= $detail->leaseServicer->shorten_name ?></td>
        <td class="sticky-cell sticky-cell9 payment-cell border-bottom">支払</td>
        <?php foreach($terms as $term) {
            $instance = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
            if (!$detail->registration_status) {
                echo $instance->render($term, 'paymentOnly', 'monthly_payment');
            }
            else {
                echo '<td class="border-bottom payment-cell" colspan="2">&nbsp;</td>';
            }
        } ?>

        <td class="border-bottom payment-cell text-end"><?= number_format($model->getTermsTotalpaymentAmountWithTax($terms), 0) ?></td>
           <?php $lastTerm = $terms[11];
        $lastMonthlyPayments = $lastTerm->getMonthlyPayments($model->contract_detail_id);
        $lastMonthlyPayment = count($lastMonthlyPayments) > 1 ? array_pop($lastMonthlyPayments) : $lastMonthlyPayments[0] ?? false;
        ?>
        <td class="border-bottom">&nbsp;</td>
        <td class="border-bottom">&nbsp;</td>
        <td class="border-bottom">&nbsp;</td>
    </tr>
<?php endif; ?>