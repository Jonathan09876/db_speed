<?php
/**
 * @var $this \yii\web\View;
 * @var $searchModel \app\models\ScheduleSearch;
 * @var $model \app\models\ContractDetail;
 * @var $index integer;
 * @var $widget \yii\widgets\ListView;
 * @var $terms array;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

use yii\bootstrap5\Html;

$query = clone($dataProvider->query);
$query
    ->select([
        '(SELECT CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE t.term >= application_from AND t.term <= IFNULL(application_to, "2099-12-31")) END FROM tax_application ta WHERE ta.tax_application_id=cd.tax_application_id) as `tax_rate`',
        'SUM(`clc`.`monthly_charge_amount_with_tax` - `clc`.`repayment_amount_with_tax`) as `delinquencies`',
    ])
    ->groupBy(['tax_rate'])
    ->andWhere(['lc.customer_id' => $model->leaseContract->customer_id]);
$rates = $query->createCommand()->queryColumn();
$rowspan = count($rates) + 2;
$detailQuery = clone($dataProvider->query);
$detailQuery
    ->select([
        'cd.contract_detail_id',
        'SUM(`clc`.`monthly_charge_amount_with_tax` - `clc`.`repayment_amount_with_tax`) as `delinquencies`',
    ])
    ->groupBy(['cd.contract_detail_id'])
    ->andWhere(['lc.customer_id' => $model->leaseContract->customer_id]);
$sql = $detailQuery->createCommand()->rawSql;
$contract_detail_ids = $detailQuery->createCommand()->queryColumn();
$detail = $model;
$customer = $model->leaseContract->customer;
$repaymentPattern = $customer->clientContract->repaymentPattern;
$repaymentAmounts = [];
$wholeRepaymentAmounts = [];
$bgColorClasses = [];
$delinquencies = 0;
$targetTerm = preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $searchModel->target_term);
$currentMonthTerm = \app\models\Term::findOne(['term' => $targetTerm]);
?>
    <tr>
        <td class="sticky-cell sticky-cell1 border-bottom" rowspan="<?= $rowspan ?>"><?= $customer->customer_code ?></td>
        <td class="sticky-cell sticky-cell2 border-bottom" rowspan="<?= $rowspan ?>"><?= $customer->clientContract->repaymentPattern->name ?></td>
        <td class="sticky-cell sticky-cell3 border-bottom" rowspan="<?= $rowspan ?>"><?= $customer->getName() ?></td>
        <td class="sticky-cell sticky-cell4 border-bottom" rowspan="<?= $rowspan ?>"><?= $customer->salesPerson->name ?></td>
        <?php $prev = $terms[0]->termDateTime->modify('-1 month');
        $prevTerm = \app\models\Term::findOne(['term' => $prev->format('Y-m-d')]);
        $remains = 0;
        $details = \app\models\ContractDetail::find()->where(['contract_detail_id' => $contract_detail_ids])->all();
        foreach($details as $dtl) {
            $remains += $dtl->getChargeRemains($prevTerm);
        }
        ?>
        <td rowspan="<?= $rowspan ?>" class="sticky-cell sticky-cell5 border-bottom text-end"><?= number_format($remains, 0) ?></td>
        <td class="sticky-cell sticky-cell6">回収予定</td>
        <?php
            $chargesTotal = 0;
            foreach($terms as $term) {
                $cellsQuery = \app\models\CollectionCell::find()->where([
                    'contract_detail_id' => $contract_detail_ids,
                    'term_id' => $term->term_id
                ])
                    ->select(['SUM(IFNULL(`monthly_charge_amount_with_tax`, 0)) as `charges`', 'SUM(IFNULL(`repayment_amount_with_tax`, 0)) as `repayments`']);
                $cells = $cellsQuery->createCommand()->queryOne();
                $currentTerm = new \DateTime(date('Y-m-01'));
                if ($cells['charges'] > 0) {
                    $diff = $cells['charges'] - $cells['repayments'];
                    $bgColorClass = $diff > 0 ? ($term->termDateTime > $currentTerm ? '' : ' deficient') : ($cells['charges'] != 0 ? ' paid' : '');
                    if ($diff < 0) {
                        $bgColorClass = ' paid';
                    }
                } else {
                    $bgColorClass = '';
                }
                $bgColorClasses[$term->term] = $bgColorClass;
                $chargesTotal += $cells['charges'];
                echo Html::tag('td', number_format($cells['charges'], 0), ['class' => 'text-end' . $bgColorClass]);
            }
            foreach($contract_detail_ids as $detail_id) {
                $delinquencies += \app\models\MonthlyCharge::getRelativeShortage($detail_id, $searchModel->target_term);
            } ?>
        <td class="text-end"><?= number_format($chargesTotal, 0) ?></td>
        <td rowspan="<?= $rowspan ?>" class="text-end border-bottom"><?= number_format($delinquencies, 0) ?></td>
    </tr>
    <?php foreach($rates as $rate) : $rateText = sprintf('%d%%', $rate * 100) ?>
    <tr>
        <td class="sticky-cell sticky-cell6"><?= $rateText ?>回収額</td>
        <?php $collectionTotal = 0;
        foreach($terms as $term) {
            $cellsQuery = \app\models\CollectionCell::find()->alias('clc')
                ->innerJoin('contract_detail cd', 'clc.contract_detail_id=cd.contract_detail_id')
                ->innerJoin('term t', 'clc.term_id=t.term_id')
                ->where([
                    '(SELECT CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE t.term >= application_from AND t.term <= IFNULL(application_to, "2099-12-31")) END FROM tax_application ta WHERE ta.tax_application_id=cd.tax_application_id)' => $rate,
                    'clc.contract_detail_id' => $contract_detail_ids,
                    'clc.term_id' => $term->term_id
                ]);
            $repaymentAmounts[$term->term] = 0;
            foreach($cellsQuery->each() as $collectionCell) {
                $repayments = $collectionCell->term->getCurrentRepayments($collectionCell->contract_detail_id);
                $repayment_total = array_sum(array_map(function($rp){return $rp->repayment_amount;}, $repayments));
                $repaymentAmounts[$term->term] += $repayment_total;
                if (!isset($wholeRepaymentAmounts[$term->term])) {
                    $wholeRepaymentAmounts[$term->term] = 0;
                }
                $wholeRepaymentAmounts[$term->term] += $repayment_total;
                $collectionTotal += $repayment_total;
            }
            $bgColorClass = $bgColorClasses[$term->term];
            if ($repaymentAmounts[$term->term] > 0) : ?>
                <td class="text-end<?= $bgColorClass ?>"><?= number_format($repaymentAmounts[$term->term],0) ?></td>
            <?php else: ?>
                <td class="<?=$bgColorClass ?>">&nbsp;</td>
            <?php endif;
        } ?>
        <td class="text-end"><?= number_format($collectionTotal, 0) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr>
        <td class="sticky-cell sticky-cell6 border-bottom">残額</td>
        <?php foreach($terms as $term) :
        if (isset($wholeRepaymentAmounts[$term->term])) {
            $remains -= $wholeRepaymentAmounts[$term->term];
        }
        $firstTerm = new \DateTime(min($model->monthlyChargeSpan->first_term, $model->monthlyPaymentSpan->first_term));
        ?>
        <td class="border-bottom text-end"><?= $term->termDateTime >= $firstTerm ? number_format($remains, 0) : '&nbsp;' ?></td>
        <?php endforeach; ?>
        <td class="text-end border-bottom"><?= number_format($remains, 0) ?></td>
    </tr>
