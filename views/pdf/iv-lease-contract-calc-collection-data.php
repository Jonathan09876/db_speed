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

    $pageSize = 50;
    $cdCount = \app\models\ContractDetail::find()->alias('cd')
        ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
        ->leftJoin('monthly_charge_span mcs', 'cd.contract_detail_id=mcs.contract_detail_id')
        ->leftJoin('monthly_payment_span mps', 'cd.contract_detail_id=mps.contract_detail_id')
        ->where(['and',
            ['lc.customer_id' => $customer->customer_id],
            ['>=', 'mcs.first_term', $targetTerm->format('Y-m-01')],
            ['<=', 'mcs.last_term', $targetTerm->format('Y-m-01')],
        ])
        ->count();
    if ($index == 0) {
        $indexOrder = Yii::$app->db->createCommand("
            SELECT FIND_IN_SET(:id, :set) - 1
        ")->bindValues([
            ':id' => $detail->contract_detail_id,
            ':set' => $totals[$customer->customer_id]['cdids']
        ])->queryScalar();
    }
    $cdids = explode(',',$totals[$customer->customer_id]['cdids']);
    $fcdid = $cdids[0];
    $lcdid = $cdids[count($cdids) - 1];
    $isLast = false;
    if ($fcdid == $detail->contract_detail_id) {
        $rowSpan = $pageSize - $index < $totals[$customer->customer_id]['rowspan'] ?
            $pageSize - $index :
            $totals[$customer->customer_id]['rowspan'];
    }
    elseif ($index == 0) {
        $rowSpan = $pageSize < $totals[$customer->customer_id]['rowspan'] - $indexOrder ?
            $pageSize :
            $totals[$customer->customer_id]['rowspan'] - $indexOrder;
        $isLast = !($pageSize < $totals[$customer->customer_id]['rowspan'] - $indexOrder);
    }

    ?>
    <tr<?= $hasRegisteredRepayments ? ' class="has-registered-repayments' . ($detail->contract_detail_id == $lcdid ? ' customer-last-contract' : '') . '"' : ($detail->contract_detail_id == $lcdid ? ' class="customer-last-contract"' : '')?>>
        <td class="border-bottom"><?= $customer->customer_code ?></td>
        <td class="border-bottom"><?= $customer->clientContract->repaymentPattern->name ?></td>
        <td class="border-bottom"<?= $customerBgColor ? " style=\"background-color:{$customerBgColor}!important;\"" : '' ?>><?= $customer->getName() ?></td>
        <td class="border-bottom"><?= $model->contractNumber ?></td>
        <td class="border-bottom"><?= $detail->taxApplication->application_name ?></td>
        <td class="border-bottom"><?= $model->contract_date ?></td>
        <td class="border-bottom"><?= $model->leaseTarget->registration_number ?></td>
        <td class="border-bottom"><?= $detail->term_months_count ?>ヶ月</td>
        <td class="border-bottom">回収</td>

        <td class="text-end border-bottom" ><?= $lastMonthlyCharges ? join(',', array_map(function($monthlyCharge){return $monthlyCharge->orderCountText;}, $lastMonthlyCharges)) : '&nbsp;' ?></td>
        <td class="text-end border-bottom" style="background-color:<?= $bg_color ?>;<?= $style ?>"><?= $lastMonthlyCharges ? number_format(array_sum(array_map(function($monthlyCharge){return $monthlyCharge->temporaryAmountWithTax;}, $lastMonthlyCharges)),0) : '&nbsp;' ?></td>

        <td class="text-end border-bottom"><?= $monthlyCharges ? join(',', array_map(function($monthlyCharge){return $monthlyCharge->orderCountText;}, $monthlyCharges)) : '&nbsp;' ?></td>
        <?php if ($isUpdateEnable) : ?>
        <td class="text-end border-bottom element-cell"><?= $monthlyCharges ? join('<br />', array_map(function($monthlyCharge){return isset($monthlyCharge->repayments[0]) ? Html::tag('div', $monthlyCharge->repayments[0]->repaymentType->type, ['style' => 'background-color:#e4e4e4;']) : Html::tag('div', $monthlyCharge->repaymentType->type);}, $monthlyCharges)) : '&nbsp;' ?></td>
        <td class="text-end border-bottom element-cell"><?= $monthlyCharges ? join('<br />', array_map(function($monthlyCharge){return isset($monthlyCharge->repayments[0]) ? Html::tag('div', number_format($monthlyCharge->repayments[0]->repayment_amount, 0), ['style' => 'background-color:#e4e4e4;']) : Html::tag('div', number_format($monthlyCharge->temporaryAmountWithTax, 0), ['style' => $monthlyCharge->repaymentType->style ?? '']);}, $monthlyCharges)) : '&nbsp;' ?></td>
        <?php else : ?>
        <td class="text-end border-bottom"><?= ($monthlyCharges[0] ?? false) ? Html::tag('div', $monthlyCharges[0]->repaymentType->type) : '&nbsp;' ?></td>
        <td class="text-end border-bottom"><?= $monthlyCharges ? number_format(array_sum(array_map(function($monthlyCharge){return $monthlyCharge->temporaryAmountWithTax;}, $monthlyCharges)),0) : '&nbsp;' ?></td>
        <?php endif; ?>
        <?php $diff = ($monthlyCharges ? array_sum(array_map(function($monthlyCharge){return $monthlyCharge->temporaryAmountWithTax;}, $monthlyCharges)) : 0) - ($lastMonthlyCharges ? array_sum(array_map(function($lastCharge){return $lastCharge->temporaryAmountWithTax;}, $lastMonthlyCharges)) : 0); ?>
        <td class="text-end border-bottom<?= $diff < 0 ? ' text-red' : '' ?>"><?= number_format($diff,0) ?></td>
        <?php if ($fcdid == $detail->contract_detail_id) : ?>
        <td style="vertical-align: middle" class="text-center border-bottom<?= $totals[$customer->customer_id]['rowspan'] == $rowSpan ? ' border-bottom-bold' : '' ?>" cdcount="<?= $cdCount ?>" spans="<?= $totals[$customer->customer_id]['rowspan'] ?>" rowspan="<?= $rowSpan ?>" class="text-end border-bottom"><?= number_format($totals[$customer->customer_id]['customer_total']) ?></td>
        <?php elseif ($index == 0) : ?>
    <td  style="vertical-align: middle" class="text-center border-bottom<?= $isLast ? ' border-bottom-bold' : '' ?>" indexOrder="<?= $indexOrder ?>" rowspan="<?= $rowSpan ?>" class="text-end border-bottom"><?= number_format($totals[$customer->customer_id]['customer_total']) ?></td>
        <?php endif; ?>
        <td class="border-bottom"><?= $customer->name ?></td>
        <td class="border-bottom"><?= $customer->customer_code ?></td>
    </tr>
<?php endforeach; ?>