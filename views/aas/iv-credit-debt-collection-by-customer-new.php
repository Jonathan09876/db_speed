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
use app\models\Debt;
use app\models\AdvanceRepayment;
use app\models\ClientDebt;
use app\components\Helper;

$customer = $model->customer;
foreach($model->contractDetails as $detail) :
    if ($index == 0) {
        $indexQuery = clone $dataProvider->query;
        $indexOrder = $indexQuery
            ->select(['COUNT(`cd`.`contract_detail_id`)'])
            ->andWhere(['and',
                ['<', '`cd`.`contract_detail_id`', $detail->contract_detail_id],
                ['c.customer_id' => $customer->customer_id]
            ])
            ->createCommand()->queryScalar();
    }

    ?>
    <tr>
        <td class="sticky-cell1 border-bottom"><?= Html::a($customer->customer_code, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell2 border-bottom"><?= $customer->clientContract->repaymentPattern->name ?></td>
        <td class="sticky-cell3 border-bottom"><?= Html::a($customer->name, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell4 border-bottom"><?= Html::a($model->contractNumber, ['/aas/lease-contract', 'id' => $model->lease_contract_id]) ?></td>
        <td class="border-bottom"><?= $detail->term_start_at ?></td>
        <td class="border-bottom"><?= $detail->term_end_at ?></td>
        <td class="border-bottom"><?= $model->leaseTarget->registration_number ?></td>
        <td class="border-bottom"><?= $detail->term_months_count ?>ヶ月</td>
        <td class="border-bottom"><?= $detail->lease_start_at ?></td>
        <?php $monthlyCharge = \app\models\MonthlyCharge::getSibling($detail->contract_detail_id, $targetTerm->format('Ym'), 0); ?>
        <td class="border-bottom charge-side"><?= $monthlyCharge ? $monthlyCharge->orderCount : '' ?></td>
        <td class="border-bottom text-end charge-side"><?= number_format(Helper::calcTaxIncluded($detail->tax_application_id, $detail->monthly_charge, $detail->fraction_processing_pattern, $targetTerm->format('Y-m-d')), 0) ?></td>
        <td class="border-bottom text-end charge-side"><?= number_format(Debt::getTotalDebtBySpan($detail->contract_detail_id, $searchModel->getTermsFromSpan(), 8.0), 0) ?></td>
        <td class="border-bottom text-end charge-side"><?= number_format(Debt::getTotalDebtBySpan($detail->contract_detail_id, $searchModel->getTermsFromSpan(), 10.0), 0) ?></td>
        <td class="border-bottom text-end charge-side"><?= number_format(AdvanceRepayment::getTotalAmountBySpan($detail->contract_detail_id, $searchModel->getTermsFromSpan(), 8.0), 0) ?></td>
        <td class="border-bottom text-end charge-side"><?= number_format(AdvanceRepayment::getTotalAmountBySpan($detail->contract_detail_id, $searchModel->getTermsFromSpan(), 10.0), 0) ?></td>
        <td class="border-bottom debt-side"><?= $detail->leaseServicer->shorten_name ?></td>
        <?php $monthlyPayment = \app\models\MonthlyPayment::getSibling($detail->contract_detail_id, $targetTerm->format('Ym'), 0); ?>
        <td class="border-bottom debt-side"><?= $monthlyPayment ? $monthlyPayment->orderCount : '' ?></td>
        <td class="border-bottom text-end debt-side"><?= number_format(Helper::calcTaxIncluded($detail->tax_application_id, $detail->monthly_payment, $detail->fraction_processing_pattern, $targetTerm->format('Y-m-d')), 0) ?></td>
        <td class="border-bottom text-end debt-side"><?= number_format(ClientDebt::getTotalDebtBySpan($detail->contract_detail_id, $searchModel->getTermsFromSpan(), 8.0), 0) ?></td>
        <td class="border-bottom text-end debt-side"><?= number_format(ClientDebt::getTotalDebtBySpan($detail->contract_detail_id, $searchModel->getTermsFromSpan(), 10.0), 0) ?></td>
    </tr><?php
    $last_cdid = explode(',', $totals[$customer->customer_id]['cdids'])[0];
    if ($detail->contract_detail_id == $last_cdid) : ?>
    <tr class="total-row">
        <td colspan="9" class="border-bottom text-center" style="background-color:#ffffdd"><?= $customer->name ?></td>
        <td class="border-bottom"></td>
        <td class="border-bottom"></td>
        <?php $debt_total_8 = 0; foreach(explode(',', $totals[$customer->customer_id]['cdids']) as $cdid) {
            $debt_total_8 += Debt::getTotalDebtBySpan($cdid, $span, 8.0);
        } ?>
        <td class="text-end border-bottom"><?= number_format($debt_total_8, 0) ?></td>
        <?php $debt_total_10 = 0; foreach(explode(',', $totals[$customer->customer_id]['cdids']) as $cdid) {
            $debt_total_10 += Debt::getTotalDebtBySpan($cdid, $span, 10.0);
        } ?>
        <td class="text-end border-bottom"><?= number_format($debt_total_10, 0) ?></td>
        <?php $advance_total_8 = 0; foreach(explode(',', $totals[$customer->customer_id]['cdids']) as $cdid) {
            $advance_total_8 += AdvanceRepayment::getTotalAmountBySpan($cdid, $span, 8.0);
        } ?>
        <td class="text-end border-bottom"><?= number_format($advance_total_8, 0) ?></td>
        <?php $advance_total_10 = 0; foreach(explode(',', $totals[$customer->customer_id]['cdids']) as $cdid) {
            $advance_total_10 += AdvanceRepayment::getTotalAmountBySpan($cdid, $span, 10.0);
        } ?>
        <td class="text-end border-bottom"><?= number_format($advance_total_10, 0) ?></td>
        <td class="border-bottom"></td>
        <td class="border-bottom"></td>
        <td class="border-bottom"></td>
        <?php $client_debt_total_8 = 0; foreach(explode(',', $totals[$customer->customer_id]['cdids']) as $cdid) {
            $client_debt_total_8 += ClientDebt::getTotalDebtBySpan($cdid, $span, 8.0);
        } ?>
        <td class="text-end border-bottom"><?= number_format($client_debt_total_8, 0) ?></td>
        <?php $client_debt_total_10 = 0; foreach(explode(',', $totals[$customer->customer_id]['cdids']) as $cdid) {
            $client_debt_total_10 += ClientDebt::getTotalDebtBySpan($cdid, $span, 10.0);
        } ?>
        <td class="text-end border-bottom"><?= number_format($client_debt_total_10, 0) ?></td>
    </tr>
    <?php endif ?>
<?php endforeach; ?>