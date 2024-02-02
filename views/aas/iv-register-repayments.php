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
 */

use yii\bootstrap5\Html;
$customer = $model->customer;
$defaultType = $customer->clientContract->repaymentPattern->repayment_type_id ?? '1';
$repaymentTypes = \app\models\RepaymentType::getTypes();
foreach($model->contractDetails as $detail) : ?>
    <?php $monthlyCharge = \app\models\MonthlyCharge::getSibling($detail->contract_detail_id, $targetTerm->format('Ym'), 0); ?>
    <?php if ($monthlyCharge && count($monthlyCharge->repayments) == 0) : ?>
    <tr>
        <td class="sticky-cell1 border-bottom"><?= Html::a($customer->customer_code, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell2 border-bottom"><?= $customer->clientContract->repaymentPattern->name ?></td>
        <td class="sticky-cell3 border-bottom"><?= Html::a($customer->name, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell4 border-bottom"><?= Html::a($model->contractNumber, ['/aas/lease-contract', 'id' => $model->lease_contract_id]) ?></td>
        <td class="border-bottom"><?= $detail->taxApplication->application_name ?></td>
        <td class="border-bottom"><?= $model->contract_date ?></td>
        <td class="border-bottom"><?= $model->leaseTarget->registration_number ?></td>
        <td class="border-bottom"><?= $detail->term_months_count ?>ヶ月</td>
        <td class="border-bottom">回収</td>

        <td class="text-end border-bottom"><?= $monthlyCharge ? $monthlyCharge->orderCount : '&nbsp;' ?></td>
        <td class="text-end border-bottom<?= $monthlyCharge ? ' editable cell-monthly_charge-temporary_charge_amount' : '' ?>" data-id="<?= $monthlyCharge ? $monthlyCharge->monthly_charge_id : '' ?>"><?= $monthlyCharge ? number_format($monthlyCharge->temporaryAmountWithTax,0) : '&nbsp;' ?></td>

        <td class="text-center border-bottom element-cell cell-checkbox" data-amount="<?= $monthlyCharge->temporaryAmountWithTax ?>">
            <?= Html::hiddenInput("Repayment[{$index}][contract_detail_id]", $detail->contract_detail_id) ?>
            <?= Html::hiddenInput("Repayment[{$index}][processed]", $monthlyCharge->targetTransferdate) ?>
            <?= Html::hiddenInput("Repayment[{$index}][collected]", '0') ?>
            <?= Html::checkbox("Repayment[{$index}][collected]", true, ['value' => '1']) ?>
        </td>
        <td class="border-bottom element-cell cell-dropdown"><?= Html::dropDownList("Repayment[{$index}][repayment_type_id]", $defaultType, $repaymentTypes, ['class' => 'form-control form-select', 'prompt' => '--- 区分を選択 ---']) ?></td>
        <td class="border-bottom element-cell cell-text-input"><?= Html::textInput("Repayment[{$index}][repayment_amount]", $monthlyCharge->temporaryAmountWithTax, ['class' => 'form-control formatted text-end']) ?></td>

        <td class="border-bottom"><?= $customer->name ?></td>
        <td class="border-bottom"><?= $customer->customer_code ?></td>
    </tr>
    <?php endif; $index += 10000; ?>
<?php endforeach; ?>