<?php
/**
 * @var $this \yii\web\View;
 * @var $searchModel \app\models\MonthlyChargeSearch2;
 * @var $model \app\models\LeaseContract;
 * @var $index integer;
 * @var $widget \yii\widgets\ListView;
 * @var $targetTerm \DateTime;
 * @var $lastTerm \DateTime;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 * @var $totals array;
 * @var $span array
 */

use yii\bootstrap5\Html;
use app\models\Debt;
use app\models\AdvanceRepayment;
use app\models\ClientDebt;
use app\components\Helper;

$customer = $model->customer;
$isNext = $customer->clientContract->repaymentPattern->target_month == 'next';
foreach($model->contractDetails as $detail) :
    $servicer = $detail->leaseServicer;
    ?>
    <tr>
        <td class="sticky-cell1 border-bottom"><?= Html::a($customer->customer_code, ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell2 border-bottom"><?= $customer->clientContract->repaymentPattern->name ?></td>
        <td class="sticky-cell3 border-bottom"><?= Html::a($customer->getName(), ['/aas/customer', 'id' => $customer->customer_id]) ?></td>
        <td class="sticky-cell4 border-bottom"><?= Html::a($model->contractNumber, ['/aas/lease-contract', 'id' => $model->lease_contract_id]) ?></td>
        <td class="sticky-cell5 border-bottom"><?= $detail->term_start_at ?></td>
        <td class="sticky-cell6 border-bottom"><?= $detail->term_end_at ?></td>
        <td class="sticky-cell7 border-bottom"><?= $model->leaseTarget->registration_number ?></td>
        <td class="sticky-cell8 border-bottom"><?= $detail->term_months_count ?>ヶ月</td>
        <td class="sticky-cell9 border-bottom"><?= $detail->lease_start_at ?></td>
        <td class="sticky-cell10 border-bottom text-end"><?php $spanTo = \app\models\Term::findOne(['term' => $span['to']->format('Y-m-01')]); echo $detail->getErapsedMonths($spanTo) ?></td>
        <?php
        $term = \app\models\Term::findOne(['term' => $span['to']->format('Y-m-d')]);
        $monthlyCharges = $term->getMonthlyCharges($detail->contract_detail_id);
        $monthlyCharge = count($monthlyCharges) > 1 ? array_pop($monthlyCharges) : $monthlyCharges[0] ?? false;
        $chargeFinished = false;
        if (!$monthlyCharge) {
            $lastTerm = \app\models\Term::findOne(['term' => $detail->monthlyChargeSpan->last_term]);
            $monthlyCharge = $lastTerm->getMonthlyCharges($detail->contract_detail_id)[0];
            $chargeFinished = true;
        }
        ?>
        <td class="border-bottom charge-side text-end"><?= $monthlyCharge ? ($chargeFinished ? $detail->term_months_count : $monthlyCharge->orderCount) : '' ?></td>
        <td class="border-bottom text-end charge-side"><?= number_format(Helper::calcTaxIncluded($detail->tax_application_id, $detail->monthly_charge, $detail->fraction_processing_pattern, $targetTerm->format('Y-m-d')), 0) ?></td>
        <td class="border-bottom text-end charge-side"><?= number_format($detail->getReceivable($term, 0.0), 0) ?></td>
        <td class="border-bottom text-end charge-side"><?= number_format($detail->getReceivable($term, 8.0), 0) ?></td>
        <td class="border-bottom text-end charge-side"><?= number_format($detail->getReceivable($term, 10.0), 0) ?></td>
        <td class="border-bottom text-end charge-side"><?= number_format($detail->getAdvances($term, 0.0), 0) ?></td>
        <td class="border-bottom text-end charge-side"><?= number_format($detail->getAdvances($term, 8.0), 0) ?></td>
        <td class="border-bottom text-end charge-side"><?= number_format($detail->getAdvances($term, 10.0), 0) ?></td>
        <td class="border-bottom debt-side"><?= $detail->leaseServicer->shorten_name ?></td>
        <?php
        $monthlyPayments = $term->getMonthlyPayments($detail->contract_detail_id);
        $monthlyPayment = count($monthlyPayments) > 1 ? array_pop($monthlyPayments) : $monthlyPayments[0] ?? false;
        $paymentFinished = false;
        if (!$monthlyPayment) {
            $lastTerm = \app\models\Term::findOne(['term' => $detail->monthlyPaymentSpan->last_term]);
            if ($lastTerm) {
                $monthlyPayment = $lastTerm->getMonthlyPayments($detail->contract_detail_id)[0];
                $paymentFinished = true;
            }
        }
        ?>
        <td class="border-bottom debt-side"><?= $monthlyPayment ? ($detail->leaseServicer->for_internal ? '-' : $monthlyPayment->orderCount) : '' ?></td>
        <td class="border-bottom text-end debt-side"><?= number_format(Helper::calcTaxIncluded($detail->tax_application_id, $detail->monthly_payment, $detail->fraction_processing_pattern, $targetTerm->format('Y-m-d')), 0) ?></td>
        <td class="border-bottom text-end debt-side"><?= number_format($detail->getPayable($term, 0.0), 0) ?></td>
        <td class="border-bottom text-end debt-side"><?= number_format($detail->getPayable($term, 8.0), 0) ?></td>
        <td class="border-bottom text-end debt-side"><?= number_format($detail->getPayable($term, 10.0), 0) ?></td>
        <td class="border-bottom text-end debt-side"><?= number_format($detail->getPayableAdvance($term, 0.0), 0) ?></td>
        <td class="border-bottom text-end debt-side"><?= number_format($detail->getPayableAdvance($term, 8.0), 0) ?></td>
        <td class="border-bottom text-end debt-side"><?= number_format($detail->getPayableAdvance($term, 10.0), 0) ?></td>
    </tr>
    <?php
        $last_cdid = explode(',', $totals[$servicer->lease_servicer_id]['cdids'])[0];
        if ($detail->contract_detail_id == $last_cdid) :
            $debt_total_0 = 0;
            $debt_total_8 = 0;
            $debt_total_10 = 0;
            $advance_total_0 = 0;
            $advance_total_8 = 0;
            $advance_total_10 = 0;
            $client_debt_total_0 = 0;
            $client_debt_total_8 = 0;
            $client_debt_total_10 = 0;
            $client_prepaid_total_0 = 0;
            $client_prepaid_total_8 = 0;
            $client_prepaid_total_10 = 0;
            foreach(explode(',', $totals[$servicer->lease_servicer_id]['cdids']) as $cdid) {
                $cd = \app\models\ContractDetail::findOne($cdid);
                $debt_total_0 += $cd->getReceivable($term, 0.0);
                $debt_total_8 += $cd->getReceivable($term, 8.0);
                $debt_total_10 += $cd->getReceivable($term, 10.0);
                $advance_total_0 += $cd->getAdvances($term, 0.0);
                $advance_total_8 += $cd->getAdvances($term, 8.0);
                $advance_total_10 += $cd->getAdvances($term, 10.0);
                $client_debt_total_0 += $cd->getPayable($term, 0.0);
                $client_debt_total_8 += $cd->getPayable($term, 8.0);
                $client_debt_total_10 += $cd->getPayable($term, 10.0);
                $client_prepaid_total_0 += $cd->getPayableAdvance($term, 0.0);
                $client_prepaid_total_8 += $cd->getPayableAdvance($term, 8.0);
                $client_prepaid_total_10 += $cd->getPayableAdvance($term, 10.0);
            } ?>
    <tr class="total-row">
        <td colspan="10" class="border-bottom text-center sticky-cell1" style="background-color:#ffffdd"><?= $servicer->name ?></td>
        <td class="border-bottom"></td>
        <td class="border-bottom"></td>
        <td class="text-end border-bottom"><?= number_format($debt_total_0, 0) ?></td>
        <td class="text-end border-bottom"><?= number_format($debt_total_8, 0) ?></td>
        <td class="text-end border-bottom"><?= number_format($debt_total_10, 0) ?></td>
        <td class="text-end border-bottom"><?= number_format($advance_total_0, 0) ?></td>
        <td class="text-end border-bottom"><?= number_format($advance_total_8, 0) ?></td>
        <td class="text-end border-bottom"><?= number_format($advance_total_10, 0) ?></td>
        <td class="border-bottom"></td>
        <td class="border-bottom"></td>
        <td class="border-bottom"></td>
        <td class="text-end border-bottom"><?= number_format($client_debt_total_0, 0) ?></td>
        <td class="text-end border-bottom"><?= number_format($client_debt_total_8, 0) ?></td>
        <td class="text-end border-bottom"><?= number_format($client_debt_total_10, 0) ?></td>
        <td class="text-end border-bottom"><?= number_format($client_prepaid_total_0, 0) ?></td>
        <td class="text-end border-bottom"><?= number_format($client_prepaid_total_8, 0) ?></td>
        <td class="text-end border-bottom"><?= number_format($client_prepaid_total_10, 0) ?></td>
    </tr>
    <?php endif ?>
<?php endforeach; ?>