<?php
/**
 * @var $this \yii\web\View;
 * @var $model \app\models\MonthlyCharge;
 * @var $index integer;
 * @var $widget \yii\widgets\ListView;
 * @var $selectedModel \app\models\TargetTermMonthlyChargeStored
 * @var $targetTerm \DateTime;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 * @var $totals array
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
$lastTerm = \app\models\Term::findOne(['term' => (clone $targetTerm)->modify('-1 month')->format('Y-m-d')]);
$lastTermCollectionCell = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $lastTerm->term_id);
$options = $lastTermCollectionCell ? json_decode($lastTermCollectionCell->options, true) : [];
if ($index == 0) {
    $indexOrder = Yii::$app->db->createCommand("
            SELECT FIND_IN_SET(:id, :set) - 1
        ")->bindValues([
        ':id' => $detail->contract_detail_id,
        ':set' => $totals[$customer->customer_id]['cdids']
    ])->queryScalar();
}

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
        <td class="border-bottom text-end"><?= $options ? ($options['mcOrderCount'] ?? '&nbsp;') : '&nbsp;' ?></td>
        <?php
        $bgColorClass = '';
        if ($lastTermCollectionCell) {
            if (isset($options['mcid'])) {
                $mcid = explode(',', $options['mcid']);
                $lastMonthlyCharge = \app\models\MonthlyCharge::findOne(array_pop($mcid));
            }
            else {
                $lastMonthlyCharge = false;
            }
            if (isset($options['rpid'])) {
                $rpid = explode(',', $options['rpid']);
                $repayment = Repayment::findOne(array_pop($rpid));
            }
            else {
                $repayment = false;
            }
            $bgColorClass = $lastTermCollectionCell->monthly_charge_amount_with_tax - $lastTermCollectionCell->repayment_amount_with_tax > 0 ? ' deficient' : ' paid';
        }
        ?>
        <td class="border-bottom<?= $bgColorClass ?>"><?= $repayment ? $repayment->repaymentType->type : ($lastMonthlyCharge ? $lastMonthlyCharge->repaymentType->type : '&nbsp;') ?></td>
        <td class="border-bottom text-end<?= $bgColorClass ?>" style="<?= $options ? ($options['rpStyle'] ?? '') : '' ?>"><?= $lastTermCollectionCell ? number_format($lastTermCollectionCell->repayment_amount_with_tax, 0) : '&nbsp;' ?></td>
        <td class="text-end border-bottom"><?= $model->orderCount ?></td>
        <td class="text-end border-bottom editable cell-monthly_charge-temporary_charge_amount" data-id="<?= $model->monthly_charge_id ?>"><?= number_format($model->temporaryAmountWithTax,0)  ?></td>

    <?php $cdids = explode(',',$totals[$customer->customer_id]['cdids']); $fcdid = $cdids[0]; ?>
    <?php if ($fcdid == $detail->contract_detail_id && $model->isFirstIntermCharges) : ?>
        <td style="vertical-align: middle" class="text-center border-bottom" rowspan="<?= $totals[$customer->customer_id]['rowspan'] ?>" class="border-bottom">
            <?= count($cdids) > 1 ? Html::checkbox("check_customer_all[{$customer->customer_id}]", false, ['value' => '1']) : '' ?>
        </td>
    <?php elseif ($index == 0) : ?>
        <td style="vertical-align: middle" class="text-center border-bottom" indexOrder="<?= $indexOrder ?>" rowspan="<?= $totals[$customer->customer_id]['rowspan'] - $indexOrder ?>" class="border-bottom">
            <?= count($cdids) > 1 ? Html::checkbox("check_customer_all[{$customer->customer_id}]", false, ['value' => '1']) : '' ?>
        </td>
    <?php endif; ?>

    <?php if ($registered) : ?>
        <td class="text-center border-bottom cell-checkbox"><?php if ($repayment = $model->repayments[0] ?? null) : ?>
            <?php if ($selectedModel->hasRegisteredRepayment($model)) : ?>
                <label>確定済</label>
            <?php else: ?>
            <?= Html::hiddenInput("CancelRepayment[{$repayment->repayment_id}][cancelled]", 0) ?>
            <label><?= Html::checkbox("CancelRepayment[{$repayment->repayment_id}][cancelled]", false, ['value' => 1, 'data-cid' => $customer->customer_id]) ?>取消</label>
            <?php endif; ?>
        <?php endif; ?></td>
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
            <?= Html::checkbox("Repayment[{$index}][collected]", false, ['value' => '1', 'data-cid' => $customer->customer_id]) ?>
        </td>
        <td class="border-bottom element-cell cell-dropdown"><?= Html::dropDownList("Repayment[{$index}][repayment_type_id]", $model->repayment_type_id ?? $defaultType, $repaymentTypes, ['class' => 'form-control form-select', 'prompt' => '--- 区分を選択 ---']) ?></td>
        <td class="border-bottom element-cell cell-text-input"><?= Html::textInput("Repayment[{$index}][repayment_amount]", $model->temporaryAmountWithTax, ['class' => 'form-control formatted text-end']) ?></td>
    <?php endif; ?>

    <?php if ($fcdid == $detail->contract_detail_id && $model->isFirstIntermCharges) : ?>
        <td style="vertical-align: middle" class="text-center border-bottom" rowspan="<?= $totals[$customer->customer_id]['rowspan'] ?>" class="text-end border-bottom">
            <?= Html::a(number_format($totals[$customer->customer_id]['customer_total']), ['/aas/register-repayment-by-customer', 'id' => $selectedModel->target_term_monthly_charge_stored_id, 'cid' => $customer->customer_id], ['target' => '_blank']) ?>
        </td>
    <?php elseif ($index == 0) : ?>
        <td style="vertical-align: middle" class="text-center border-bottom" indexOrder="<?= $indexOrder ?>" rowspan="<?= $totals[$customer->customer_id]['rowspan'] - $indexOrder ?>" class="text-end border-bottom">
            <?= Html::a(number_format($totals[$customer->customer_id]['customer_total']), ['/aas/register-repayment-by-customer', 'id' => $selectedModel->target_term_monthly_charge_stored_id, 'cid' => $customer->customer_id], ['target' => '_blank']) ?>
        </td>
    <?php endif; ?>

    <td class="border-bottom"><?= $customer->name ?></td>
        <td class="border-bottom"><?= $customer->customer_code ?></td>
    </tr>
