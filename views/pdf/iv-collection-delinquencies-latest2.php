<?php
/**
 * @var $this \yii\web\View;
 * @var $searchModel \app\models\ScheduleSearch;
 * @var $model \app\models\ContractDetail;
 * @var $index integer;
 * @var $widget \yii\widgets\ListView;
 * @var $terms array;
 */

use yii\bootstrap5\Html;

$detail = $model;
$contract = $model->leaseContract;
$customer = $model->leaseContract->customer;
$repaymentPattern = $customer->clientContract->repaymentPattern;
$isNext = $repaymentPattern->target_month == 'next';
$customerBgColor = $contract->contractPattern->bg_color;
$repaymentAmounts = [];
$delinquencies = 0;
$targetTerm = preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $searchModel->target_term);
$currentMonthTerm = \app\models\Term::findOne(['term' => $targetTerm]);
?>
    <tr>
        <td class="sticky-cell sticky-cell1 border-bottom" rowspan="3"><?= $customer->customer_code ?></td>
        <td class="sticky-cell sticky-cell2 border-bottom" rowspan="3"><?= $customer->clientContract->repaymentPattern->name ?></td>
        <td class="sticky-cell sticky-cell3 border-bottom" rowspan="3"<?= $customerBgColor ? " style=\"background-color:{$customerBgColor}!important;\"" : '' ?>><?= $customer->getName() ?></td>
        <td class="sticky-cell sticky-cell4 border-bottom" rowspan="3"><?= $customer->salesPerson->name ?></td>
        <td class="sticky-cell sticky-cell5 border-bottom" rowspan="3"><?= $contract->contractNumber ?></td>
        <td class="sticky-cell sticky-cell6 border-bottom" rowspan="3"><?= $detail->taxApplication->application_name ?></td>
        <td class="sticky-cell sticky-cell7 "><?= $detail->term_start_at ?></td>
        <td class="sticky-cell sticky-cell8 border-bottom" rowspan="3"><?= $contract->leaseTarget->registration_number ?></td>
        <td class="sticky-cell sticky-cell9 text-end"><?= number_format($detail->totalChargeAmountWithTax, 0) ?></td>
        <?php $prev = $terms[0]->termDateTime->modify('-1 month');
        $prevTerm = \app\models\Term::findOne(['term' => $prev->format('Y-m-d')]);
        $remains = $model->getChargeRemains($prevTerm);
        ?>
        <td rowspan="3" class="sticky-cell sticky-cell10 border-bottom text-end"><?= number_format($remains, 0) ?></td>
        <td class="sticky-cell sticky-cell11">回収予定</td>
        <?php $chargesTotal = 0; foreach($terms as $term) :
            $collectionCell = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
            $currentTerm = new \DateTime(date('Y-m-01'));
            $options = json_decode($collectionCell->options, true);
            if (isset($options['mcid'])) {
                $diff = $collectionCell->monthly_charge_amount_with_tax - ($collectionCell->repayment_amount_with_tax ?? 0);
                $bgColorClass = $diff > 0 ? ($term->termDateTime > $currentTerm ? '' : ' deficient') : ($collectionCell->monthly_charge_amount_with_tax != 0 ? ' paid' : '');
                if ($diff < 0) {
                    $bgColorClass = ' paid';
                }
            }
            else {
                $bgColorClass = '';
            }
            $rtids = isset($options['mcrtid']) ? explode(',', $options['mcrtid']) : [];
            $render_order = $collectionCell->monthly_charge_amount_with_tax > 0 || count(array_intersect($rtids, [11,12])) == 0;
            $chargesTotal += ($render_order && isset($options['mcid']) ? $collectionCell->monthly_charge_amount_with_tax : 0);
            echo Html::tag('td', $render_order ? ($options['mcOrderCount'] ?? '') : '', ['class' => 'text-end' . $bgColorClass]) .
            Html::tag('td', $render_order && isset($options['mcid']) ? number_format($collectionCell->monthly_charge_amount_with_tax,0) : '', ['class' => 'text-end' . $bgColorClass ]);
        endforeach; ?>
        <?php $collectionRemains = \app\models\MonthlyCharge::getRelativeShortage($detail->contract_detail_id, $searchModel->target_term) //$model->getChargeRemains($terms[11]); ?>
        <td class="text-end"><?= number_format($chargesTotal, 0) ?></td>
        <td rowspan="3" class="text-end border-bottom"><?= number_format($collectionRemains, 0) ?></td>
        <td rowspan="3" class="text-end border-bottom"><?= $model->leaseContract->memo ?></td>
    </tr>
    <tr>
        <td class="sticky-cell sticky-cell7"><?= $detail->term_end_at ?></td>
        <td class="sticky-cell sticky-cell9 text-end"><?= number_format($detail->monthlyChargeWithTax, 0) ?></td>
        <td class="sticky-cell sticky-cell11">入金額</td>
        <?php $collectionTotal = 0; foreach($terms as $term) :
            $collectionCell = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
            $options = json_decode($collectionCell->options, true);
            $delinquency = $collectionCell->monthly_charge_amount_with_tax - $collectionCell->repayment_amount_with_tax;
            if ($term->termDateTime <= $currentMonthTerm->termDateTime) {
                $delinquencies += $delinquency;
            }
            else {
                $delinquency = 0;
            }
            if ($delinquency == 0 && isset($options['mcid'])) {
                $mcid = explode(',', $options['mcid']);
                $monthlyCharge = \app\models\MonthlyCharge::findOne(array_pop($mcid));
                $repayment = $monthlyCharge->repayments[0] ?? false;
                $paid = $repayment && $monthlyCharge->repaymentType->repayment_type_id != $repayment->repayment_type_id ? ' deficient' : ' paid';
            }
            else {
                $paid = ' paid';
            }
            $bgColorClass = $delinquency > 0 ? ' deficient' : (isset($options['mcid']) ? ($term->termDateTime > $currentMonthTerm->termDateTime ? '' : $paid) : '');
            $repayments = $collectionCell->term->getCurrentRepayments($detail->contract_detail_id);
            if (count($repayments)) :
                $repayment_total = array_sum(array_map(function($rp){return $rp->repayment_amount;}, $repayments));
                $repaymentAmounts[$term->term] = $repayment_total;
                $collectionTotal += $repayment_total; ?>
                <td colspan="2" class="text-end<?= $bgColorClass ?>" style="<?= $options['rpStyle'] ?? '' ?>"><?= isset($options['rpid']) && !empty($options['rpid']) ? number_format($repayment_total,0) : '' ?></td>
            <?php else: ?>
                <td colspan="2" class="<?=$bgColorClass ?>">&nbsp;</td>
            <?php endif; ?>
        <?php endforeach; ?>
        <td class="text-end"><?= number_format($collectionTotal, 0) ?></td>
    </tr>
    <tr>
        <td class="sticky-cell sticky-cell7 border-bottom"><?= $detail->lease_start_at ?></td>
        <td class="sticky-cell sticky-cell9 text-end border-bottom"><?= number_format($detail->term_months_count, 0) ?></td>
        <td class="sticky-cell sticky-cell11 border-bottom">残額</td>
        <?php foreach($terms as $term) :
        if (isset($repaymentAmounts[$term->term])) {
            $remains -= $repaymentAmounts[$term->term];
        }
        $firstTerm = new \DateTime(min($model->monthlyChargeSpan->first_term, $model->monthlyPaymentSpan->first_term));
        ?>
        <td colspan="2" class="border-bottom text-end"><?= $term->termDateTime >= $firstTerm ? number_format($remains, 0) : '&nbsp;' ?></td>
        <?php endforeach; ?>
        <td class="text-end border-bottom"><?= number_format($remains, 0) ?></td>
    </tr>
