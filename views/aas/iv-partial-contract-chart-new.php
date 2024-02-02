<?php
/**
 * @var $this \yii\web\View;
 * @var $model \app\models\TermTicker;
 * @var $index integer;
 * @var $widget \yii\widgets\ListView;
 */

use yii\bootstrap5\Html;
use app\models\MonthlyCharge;
use app\models\MonthlyPayment;
use app\models\Repayment;

$lastMonthlyCharges = MonthlyCharge::getSiblings($model->contract_detail_id, $model->term->format('Ym'), -1);
$monthlyCharges = MonthlyCharge::getSiblings($model->contract_detail_id, $model->term->format('Ym'), 0);
$monthlyPayments = MonthlyPayment::getSiblings($model->contract_detail_id, $model->term->format('Ym'), 0);
$length = max(count($monthlyCharges), count($monthlyPayments));
for($i = 0; $i < $length; $i++) :
$monthlyCharge = $monthlyCharges[$i] ?? null;
$monthlyPayment = $monthlyPayments[$i] ?? null;
$advanceRepayments = $monthlyCharge ? $monthlyCharge->getAdvanceRepayments()->all() : [];
$repayment = $monthlyCharge ? ($advanceRepayments[0] ?? ($monthlyCharge->repayments[0] ?? false)) : null;
?>
<tr>
    <?= Html::tag('td', $monthlyCharge ? $monthlyCharge->orderCount : '&nbsp;') ?>
    <?php
    if ($monthlyCharge) {
        $options = ['data-id' => $monthlyCharge->monthly_charge_id, 'class' => ($lastMonthlyCharges ? '' : 'unskippable ') . ($repayment ? '' : 'skippable ') .'editable cell-monthly_charge-transfer_date'];
        $content = ($repayment ? '' : '<span class="skip-this"></span>') . $monthlyCharge->transfer_date;
    }
    else {
        $options = [];
        $transfer_date = $model->contractDetail->leaseContract->customer->clientContract->repayementPattern->transfer_date;
        $format = $transfer_date == 31 ? 'Y-m-t' : "Y-m-{$transfer_date}";
        $content = $model->term->format($format);
    }
    ?>
    <?= Html::tag('td', $content, $options) ?>
    <?php if ($monthlyCharge) {
        $options = [
            'data-id' => $monthlyCharge->monthly_charge_id,
            'class' => 'text-end editable cell-monthly_charge-temporary_charge_amount',
        ];
        $repayment = $monthlyCharge->repayments[0] ?? false;
        if ($repayment && $repayment->repaymentType->bg_color) {
            $options['style'] = "background-color:{$repayment->repaymentType->bg_color};";
        }
    } else {
        $options = [];
    } ?>
    <?= Html::tag('td', $monthlyCharge ? number_format($monthlyCharge->getAmountWithTax('temporary_charge_amount'), 0) : '&nbsp;', $options) ?>
    <?= Html::tag('td', $monthlyCharge ? (isset($monthlyCharge->advanceRepayments[0]) ? '◯' : '') : 'nbsp;') ?>
    <?php
    $options = $repayment ? ($repayment instanceof Repayment ? ['data-id' => $repayment->repayment_id, 'class' => 'deletable editable cell-repayment-repayment_type_id'] : ['data-id' => $repayment->repayment_id]) :
        ['data-mcid' => $monthlyCharge->monthly_charge_id, 'class' => 'registerable cell-repayment'];
    $content = $repayment ? ($repayment instanceof Repayment ? '<span class="delete-this"></span>' . $repayment->repaymentType->type : '前払リース料'): '';
    ?>
    <?= Html::tag('td', $monthlyCharge ? $content : '&nbsp;', $monthlyCharge ? $options : []) ?>
    <?php
    $options = $repayment ? ($repayment instanceof Repayment ? ['data-id' => $repayment->repayment_id, 'class' => 'editable cell-repayment-processed bg-gray'] : ['data-id' => $repayment->repayment_id]) :
        ['data-mcid' => $monthlyCharge->monthly_charge_id, 'class' => 'registerable cell-repayment'];
    $content = $repayment ? $repayment->processed : '';
    ?>
    <?= Html::tag('td', $monthlyCharge ? $content : '&nbsp;', $monthlyCharge ? $options : []) ?>
    <?php
    $options = $repayment ? ($repayment instanceof Repayment ? ['data-id' => $repayment->repayment_id, 'class' => 'editable cell-repayment-repayment_amount bg-gray'] : ['data-id' => $repayment->repayment_id]) :
        ['data-mcid' => $monthlyCharge->monthly_charge_id, 'class' => 'registerable cell-repayment'];
    $content = $repayment ? number_format((int)$repayment->repayment_amount,0) : ''; ?>
    <?= Html::tag('td', $monthlyCharge ? $content : '&nbsp;', $monthlyCharge ? $options : []) ?>
    <?php
    $options = $monthlyCharge ? ($monthlyCharge->repayments[0] ?? false ? ['data-id' => $monthlyCharge->repayments[0]->repayment_id, 'class' => 'text-end editable cell-repayment-chargeback_amount'] : []) : [];
    $content = $monthlyCharge ? ($monthlyCharge->repayments[0] ?? false ? number_format((int)$monthlyCharge->repayments[0]->chargeback_amount,0) : '') : '&nbsp;';
    ?>
    <?= Html::tag('td', $monthlyCharge ? $content : '&nbsp;', $monthlyCharge ? $options : []) ?>
    <?php
    if ($monthlyCharge) {
        $total = 0;
        foreach($model->contractDetail->monthlyCharges as $mc) {
            if ((new \DateTime($mc->term)) <= (new \DateTime($monthlyCharge->term))) {
                $rp = $mc->repayments[0] ?? false;
                $total += $rp ? ($rp->repayment_amount - $rp->chargeback_amount) : 0;
            }
        }
        $remains = MonthlyCharge::getTotal($model->contractDetail->monthlyCharges, 'amountWithTax') - \app\models\AdvanceRepayment::getTotal($model->contractDetail->monthlyCharges, 'repayment_amount') - $total;
        $options = ['class' => 'text-end'];
        $content = number_format($remains, 0);
    }
    else {
        $options = [];
        $content = '&nbsp';
    }
    ?>
    <?= Html::tag('td', $content, $options) ?>
    <?php if ($model->contractDetail->monthly_payment > 0) : ?>
    <?= Html::tag('id', $monthlyPayment ? $monthlyPayment->orderCount : 'Nbsp;') ?>
    <?php
        $options = $monthlyPayment ? [
        'data-id' => $monthlyPayment->monthly_payment_id,
        'class' => 'editable cell-monthly_payment-payment_date'
        ] : [];
        $content = $monthlyPayment ? $monthlyPayment->payment_date : '';
    ?>
    <?= Html::tag('td', $content, $options) ?>
    <?php
    $options = $monthlyPayment ? [
        'data-id' => $monthlyPayment->monthly_payment_id,
        'class' => 'text-end editable cell-monthly_payment-payment_amount'
    ] : [];
    $content = $monthlyPayment ? number_format($monthlyPayment->amountWithTax,0) : '';
    ?>
    <?= Html::tag('td', $content, $options) ?>
    <?php
    if ($monthlyPayment) {
        $sql = "SELECT CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE :term >= application_from AND :term <= IFNULL(application_to, '2099-12-31')) END FROM tax_application ta WHERE ta.tax_application_id=:id";
        $value = Yii::$app->db->createCommand($sql)->bindValues([
            ':term' => $monthlyPayment->term,
            ':id' => (int)$model->contractDetail->tax_application_id,
        ])->queryScalar();
        $content = (string)($value * 100).'%';
    }
    else {
        $content = 'nbsp;';
    }
    ?>
    <?= Html::tag('td', $content) ?>
    <?php endif; ?>
    <?php
    if ($monthlyCharge) {
        $options = [
            'style' => 'min-width: 300px;',
            'class' => 'editable cell-monthly_charge-memo',
            'data-id' => $monthlyCharge->monthly_charge_id,
        ];
        $content = $monthlyCharge->memo;
    }
    else {
        $options = [];
        $content = '&nbsp;';
    }
    ?>
    <?= Html::tag('td', $content, $options) ?>
</tr>
<?php endfor; ?>
