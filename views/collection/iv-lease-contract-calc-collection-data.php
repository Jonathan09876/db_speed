<?php
/**
 * @var $this \yii\web\View;
 * @var $model \app\models\LeaseContract;
 * @var $index integer;
 * @var $widget \yii\widgets\ListView;
 * @var $targetTerm \DateTime;
 * @var $lastTerm \DateTime;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 * @var $totals array;
 * @var $isUpdateEnable boolean;
 */

use yii\bootstrap5\Html;
$customer = $model->customer;
$customerBgColor = $model->contractPattern->bg_color;

foreach($model->contractDetails as $detail) :
    if ($detail->monthly_charge == 0) continue;
    if ($index == 0) {
        $indexOrder = Yii::$app->db->createCommand("
            SELECT FIND_IN_SET(:id, :set) - 1
        ")->bindValues([
            ':id' => $detail->contract_detail_id,
            ':set' => $totals[$customer->customer_id]['cdids']
        ])->queryScalar();
    }
    $mcQuery = \app\models\MonthlyCharge::find()->alias('mc')
        ->innerJoin('contract_detail cd', 'mc.contract_detail_id=cd.contract_detail_id')
        ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
        ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
        ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
        ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id');
    $lastMonthlyCharges = (clone $mcQuery)
        ->where(['mc.contract_detail_id' => $detail->contract_detail_id, 'CASE `rp`.`target_month` WHEN "next" THEN `mc`.`term` + INTERVAL 1 MONTH ELSE `mc`.`term` END' => $lastTerm->format('Y-m-01')])
        ->all();

    $lastRepayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $lastMonthlyCharges), 'array_merge', []);
    $bg_color = ($lastMonthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $lastMonthlyCharges)) : 0) - ($lastRepayments ? array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $lastRepayments)) : 0) > 0 ? '#ffd4d4' : '#f2f2f2';
    $style = $lastMonthlyCharges ? implode('', array_map(function($mc){return isset($mc->repayments[0]) ? $mc->repayments[0]->repaymentType->style : $mc->repaymentType->style;}, $lastMonthlyCharges)) : '';

    $monthlyCharges = (clone $mcQuery)
        ->where(['mc.contract_detail_id' => $detail->contract_detail_id, 'CASE `rp`.`target_month` WHEN "next" THEN `mc`.`term` + INTERVAL 1 MONTH ELSE `mc`.`term` END' => $targetTerm->format('Y-m-01')])
        ->all();
    $hasRegisteredRepayments = false;
    foreach($monthlyCharges as $monthlyCharge) {
        if ($monthlyCharge->getRepayments()->count() > 0) {
            $hasRegisteredRepayments = true;
        }
    }
    ?>
    <tr<?= $hasRegisteredRepayments ? ' class="has-registered-repayments"' : '' ?>>
        <td class="sticky-cell sticky-cell1 border-bottom"><?= Html::a($customer->customer_code, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell sticky-cell2 border-bottom"><?= $customer->clientContract->repaymentPattern->name ?></td>
        <td class="sticky-cell sticky-cell3 border-bottom"<?= $customerBgColor ? " style=\"background-color:{$customerBgColor}!important;\"" : '' ?>><?= Html::a($customer->getName(), ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell sticky-cell4 border-bottom"><?= Html::a($model->contractNumber, ['/aas/lease-contract', 'id' => $model->lease_contract_id]) ?></td>
        <td class="border-bottom"><?= $detail->taxApplication->application_name ?></td>
        <td class="border-bottom"><?= $model->contract_date ?></td>
        <td class="border-bottom"><?= $model->leaseTarget->registration_number ?></td>
        <td class="border-bottom"><?= $detail->term_months_count ?>ヶ月</td>
        <td class="border-bottom">回収</td>

        <td class="text-end border-bottom" ><?= $lastMonthlyCharges ? join(',', array_map(function($monthlyCharge){return $monthlyCharge->orderCountText;}, $lastMonthlyCharges)) : '&nbsp;' ?></td>
        <td class="text-end border-bottom" style="background-color:<?= $bg_color ?>;<?= $style ?>"><?= $lastMonthlyCharges ? number_format(array_sum(array_map(function($monthlyCharge){return $monthlyCharge->temporaryAmountWithTax;}, $lastMonthlyCharges)),0) : '&nbsp;' ?></td>

        <td class="text-end border-bottom"><?= $monthlyCharges ? join(',', array_map(function($monthlyCharge){return $monthlyCharge->orderCountText;}, $monthlyCharges)) : '&nbsp;' ?></td>
        <?php if ($isUpdateEnable) : ?>
        <td class="text-end border-bottom updatable element-cell cell-dropdown"><?= $monthlyCharges ? join('<br />', array_map(function($monthlyCharge){return isset($monthlyCharge->repayments[0]) ? Html::textInput('registered-repayment', $monthlyCharge->repayments[0]->repaymentType->type, ['readonly' => true, 'class' => 'form-control form-select', 'style' => 'background-color:#e4e4e4;']) : Html::dropDownList("MonthlyCharge[{$monthlyCharge->monthly_charge_id}][repayment_type_id]", $monthlyCharge->repaymentType->repayment_type_id, \app\models\RepaymentType::getTypes(), ['class' => 'form-control form-select', 'data-id' => $monthlyCharge->monthly_charge_id]);}, $monthlyCharges)) : '&nbsp;' ?></td>
        <td class="text-end border-bottom updatable element-cell cell-text-input" data-id=""><?= $monthlyCharges ? join('<br />', array_map(function($monthlyCharge){return isset($monthlyCharge->repayments[0]) ? Html::textInput('registered-repayment-amount', $monthlyCharge->repayments[0]->repayment_amount, ['readonly' => true, 'class' => 'form-control text-end formatted', 'style' => 'background-color:#e4e4e4;']) : Html::textInput("MonthlyCharge[{$monthlyCharge->monthly_charge_id}][amount_with_tax]", $monthlyCharge->temporaryAmountWithTax, ['class' => 'form-control formatted', 'data-id' => $monthlyCharge->monthly_charge_id, 'style' => $monthlyCharge->repaymentType->style ?? '']);}, $monthlyCharges)) : '&nbsp;' ?></td>
        <?php else : ?>
        <td class="text-end border-bottom cell-monthly_charge-repayment_type_id"><?= ($monthlyCharges[0] ?? false) ? $monthlyCharges[0]->repaymentType->type : '&nbsp;' ?></td>
        <td class="text-end border-bottom cell-monthly_charge-temporary_charge_amount" data-id="<?= $monthlyCharges ? join(',',array_map(function($mc){return $mc->monthly_charge_id;}, $monthlyCharges)) : '' ?>"><?= $monthlyCharges ? number_format(array_sum(array_map(function($monthlyCharge){return $monthlyCharge->temporaryAmountWithTax;}, $monthlyCharges)),0) : '&nbsp;' ?></td>
        <?php endif; ?>
        <?php $diff = ($monthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->temporaryAmountWithTax;}, $monthlyCharges)) : 0) - ($lastMonthlyCharges ? array_sum(array_map(function($lastCharge){return $lastCharge->temporaryAmountWithTax;}, $lastMonthlyCharges)) : 0); ?>
        <td class="text-end border-bottom<?= $diff < 0 ? ' text-red' : '' ?>"><?= number_format($diff,0) ?></td>
        <?php $fcdid = explode(',',$totals[$customer->customer_id]['cdids'])[0]; ?>
        <?php if ($fcdid == $detail->contract_detail_id) : ?>
        <td style="vertical-align: middle" class="text-center border-bottom" rowspan="<?= $totals[$customer->customer_id]['rowspan'] ?>" class="text-end border-bottom"><?= number_format($totals[$customer->customer_id]['customer_total']) ?></td>
        <?php elseif ($index == 0) : ?>
        <td  style="vertical-align: middle" class="text-center border-bottom" indexOrder="<?= $indexOrder ?>" rowspan="<?= $totals[$customer->customer_id]['rowspan'] - $indexOrder ?>" class="text-end border-bottom"><?= number_format($totals[$customer->customer_id]['customer_total']) ?></td>
        <?php endif; ?>
        <td class="border-bottom"><?= $customer->name ?></td>
        <td class="border-bottom"><?= $customer->customer_code ?></td>
    </tr>
<?php endforeach; ?>