<?php
/**
 * @var $this \yii\web\View;
 * @var $model \app\models\MonthlyCharge;
 * @var $index integer;
 * @var $widget \yii\widgets\ListView;
 * @var $targetTerm \DateTime;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

use yii\bootstrap5\Html;
use app\models\Repayment;
use app\models\Debt;

$customer = $model->contractDetail->leaseContract->customer;
$detail = $model->contractDetail;
$contract = $detail->leaseContract;
$customerBgColor = $contract->contractPattern->bg_color;

$defaultType = $customer->clientContract->repaymentPattern->repayment_type_id ?? '1';
$repaymentTypes = \app\models\RepaymentType::getTypes();
$query = \app\models\MonthlyCharge::find()->alias('mc')
    ->leftJoin('repayment r', 'r.monthly_charge_id=mc.monthly_charge_id')
    ->leftJoin('debt d', 'd.monthly_charge_id=mc.monthly_charge_id')
    ->where(['and',
        ['mc.contract_detail_id' => $model->contract_detail_id],
        ['<', 'mc.term', $model->term],
        ['or', ['not', ['r.repayment_id' => null]], ['not', ['d.debt_id' => null]]]
    ]);
$lastMonthlyChargeHavingRepayement = $query
    ->orderBy(['mc.term' => SORT_DESC, 'mc.monthly_charge_id' => SORT_DESC])
    ->limit(1)
    ->one();
$repayment = $lastMonthlyChargeHavingRepayement->repayments[0] ?? $lastMonthlyChargeHavingRepayement->debts[0] ?? false;
$repaymentCount = $query->count();
//$last_repayment_type_id = $repayment instanceof Repayment ? $repayment->repayment_type_id : $repayment->debt_type_id;
?>
    <?php if ($registered = $model->getRepayments()->count() > 0 || $model->getDebts()->count() > 0) : ?>
    <tr class="bg-gray">
    <?php else : ?>
    <tr>
    <?php endif; ?>
        <td class="sticky-cell1 border-bottom"><?= Html::a($customer->customer_code, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell2 border-bottom"><?= $customer->clientContract->repaymentPattern->name ?></td>
        <td class="sticky-cell3 border-bottom"<?= $customerBgColor ? " style=\"background-color:{$customerBgColor}!important;\"" : '' ?>><?= Html::a($customer->name, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell4 border-bottom"><?= Html::a($contract->contractNumber, ['/aas/lease-contract', 'id' => $contract->lease_contract_id]) ?></td>
        <td class="border-bottom"><?= $detail->taxApplication->application_name ?></td>
        <td class="border-bottom"><?= $contract->contract_date ?></td>
        <td class="border-bottom"><?= $contract->leaseTarget->registration_number ?></td>
        <td class="border-bottom"><?= $detail->term_months_count ?>ヶ月</td>
        <td class="border-bottom">回収</td>
        <td class="border-bottom text-end"><?= $repaymentCount ? $repaymentCount : '&nbsp;' ?></td>
        <td class="border-bottom"><?= $repayment instanceof Repayment ? $repayment->repaymentType->type : ($repayment ? $repayment->debtType->type : '&nbsp;') ?></td>
        <td class="border-bottom text-end"><?= $repayment ? number_format(($repayment instanceof Repayment ? ($repayment->repayment_amount - $repayment->chargeback_amount) : $repayment->debt_amount), 0) : '&nbsp;' ?></td>
        <td class="text-end border-bottom"><?= $repaymentCount+1 ?></td>
        <td class="text-end border-bottom editable cell-monthly_charge-temporary_charge_amount" data-id="<?= $model->monthly_charge_id ?>"><?= number_format($model->temporaryAmountWithTax,0)  ?></td>
    <?php if ($registered) : ?>
        <td class="text-center border-bottom"><?php if ($repayment = $model->repayments[0] ?? null) : echo Html::button('<i class="ri-close-line"></i>', ['class' => 'btn btn-sm btn-danger btn-cancel-repayment', 'data-id' => $repayment->repayment_id]); endif; ?></td>
        <td class="border-bottom element-cell cell-dropdown">
            <input type="text" class="text-start form-control form-select bg-gray" readonly value="<?php if ($repayment = $model->repayments[0] ?? null) : ?><?= $repaymentTypes[$repayment['repayment_type_id']] ?><?php else : ?>未回収<?php endif; ?>" />
        </td>
        <td class="border-bottom element-cell formatted text-end">
            <input type="text" class="form-control formatted text-end bg-gray" readonly value="<?= $repayment ? $repayment->repayment_amount : '' ?>"/></td>
    <?php else : ?>
        <td class="text-center border-bottom element-cell cell-checkbox" data-amount="<?= $model->temporaryAmountWithTax ?>">
            <?= Html::hiddenInput("Repayment[{$index}][contract_detail_id]", $detail->contract_detail_id) ?>
            <?= Html::hiddenInput("Repayment[{$index}][monthly_charge_id]", $model->monthly_charge_id) ?>
            <?= Html::hiddenInput("Repayment[{$index}][processed]", $model->targetTransferdate) ?>
            <?= Html::hiddenInput("Repayment[{$index}][collected]", '0') ?>
            <?= Html::checkbox("Repayment[{$index}][collected]", true, ['value' => '1']) ?>
        </td>
        <td class="border-bottom element-cell cell-dropdown"><?= Html::dropDownList("Repayment[{$index}][repayment_type_id]", $model->repayment_type_id ?? $defaultType, $repaymentTypes, ['class' => 'form-control form-select', 'prompt' => '--- 区分を選択 ---']) ?></td>
        <td class="border-bottom element-cell cell-text-input"><?= Html::textInput("Repayment[{$index}][repayment_amount]", $model->temporaryAmountWithTax, ['class' => 'form-control formatted text-end']) ?></td>
    <?php endif; ?>
        <td class="border-bottom"><?= $customer->name ?></td>
        <td class="border-bottom"><?= $customer->customer_code ?></td>
    </tr>
