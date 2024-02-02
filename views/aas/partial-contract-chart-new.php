<?php
/**
 * @var $this \yii\web\View;
 * @var $model \app\models\ContractDetail;
 */

use yii\grid\GridView;
use app\models\MonthlyCharge;
use app\models\MonthlyPayment;
use app\models\Repayment;
use yii\widgets\Pjax;

use yii\data\ArrayDataProvider;
use yii\widgets\ListView;
use app\models\TermTicker;

?>
<?php
$query = MonthlyCharge::find()->alias('mc')
    ->where(['mc.contract_detail_id' => $model->contract_detail_id]);
$firstTerm = new \DateTime((clone $query)->orderBy(['monthly_charge_id' => SORT_ASC])->limit(1)->one()->term);
$lastTerm = new \DateTime((clone $query)->orderBy(['monthly_charge_id' => SORT_DESC])->limit(1)->one()->term);
$term = clone $firstTerm;
$terms = [];
do {
    $terms[] = new TermTicker([
        'contract_detail_id' => $model->contract_detail_id,
        'term' => clone $term,
    ]);
    $term->modify('+1 month');
} while($term <= $lastTerm);
$dataProvider = new ArrayDataProvider([
    'allModels' => $terms,
    'pagination' => false,
]);
?>
<?php Pjax::begin([
    'id' => "pjax-grid-{$model->contract_detail_id}-wrapper",
    'options' => [
        'class' => 'contract-grid-wrapper',
    ]
]) ?>
<?php
$type = $model->contract_type == 'ordinary' ? '物件リース' : 'メンテナンスリース';
$footerContent = [
    'totalCharges' => number_format(array_sum(array_map(function($monthlyCharge){
        return $monthlyCharge->temporaryAmountWithTax;
    }, $model->monthlyCharges)), 0),
    'totalRepayments' => number_format(array_sum(array_map(function($monthlyCharge){
        $advanceRepayment = $monthlyCharge->advanceRepayments ?? false;
        $total = $advanceRepayment ? $advanceRepayment->repayment_amount : 0;
        $repayment = $monthlyCharge->repayments[0] ?? false;
        $total += $repayment ? ($repayment->repayment_amount - $repayment->chargeback_amount) : 0;
        return $total;
    }, $model->monthlyCharges)), 0),
    'totalChargeRemains' => number_format(array_sum(array_map(function($monthlyCharge){
        $advanceRepayment = $monthlyCharge->advanceRepayments ?? false;
        $total = $advanceRepayment ? $advanceRepayment->repayment_amount : 0;
        $repayment = $monthlyCharge->repayments[0] ?? false;
        $total += $repayment ? ($repayment->repayment_amount - $repayment->chargeback_amount) : 0;
        return $monthlyCharge->amountWithTax - $total;
    }, $model->monthlyCharges)), 0),
    'totalPayments' => number_format(array_sum(array_map(function($monthlyPayment){
        return $monthlyPayment->amountWithTax;
    }, $model->monthlyPayments)), 0),
    'totalPaymentRemains' => number_format(array_sum(array_map(function($monthlyPayment){
        $payment = $monthlyPayment->leasePayments[0] ?? false;
        return $monthlyPayment->amountWithTax - ($payment ? $payment->payment_amount : 0);
    }, $model->monthlyPayments)), 0),
];
if ($model->monthly_payment > 0) {
    $header = <<<EOH
<thead>
    <tr>
        <th>回数</th>
        <th>回収予定日</th>
        <th>回収予定額</th>
        <th>前払</th>
        <th>回収方法</th>
        <th>回収日</th>
        <th>回収額<sub>（税込）</sub>
        </th><th>返金額</th>
        <th>回収残額</th>
        <th>回数</th>
        <th>支払月</th>
        <th>支払額<sub>(税込)</sub></th>
        <th>税率</th>
        <th>コメント</th>
    </tr>
</thead>
EOH;
    $footer = <<<EOF
<tfoot>
    <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td class="text-end">総額:{$footerContent['totalCharges']}</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td class="text-end">総額:{$footerContent['totalRepayments']}</td>
        <td>&nbsp;</td>
        <td class="text-end">残額:{$footerContent['totalChargeRemains']}</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td class="text-end">総額:{$footerContent['totalPayments']}<br/>支払残額:{$footerContent['totalPaymentRemains']}</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
</tfoot>
EOF;
}
else {
    $header = <<<EOH
<thead>
    <tr>
        <th>回数</th>
        <th>回収予定日</th>
        <th>回収予定額</th>
        <th>前払</th>
        <th>回収方法</th>
        <th>回収日</th>
        <th>回収額<sub>（税込）</sub>
        </th><th>返金額</th>
        <th>回収残額</th>
        <th>コメント</th>
    </tr>
</thead>
EOH;

    $footer = <<<EOF
<tfoot>
    <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td class="text-end">総額:{$footerContent['totalCharges']}</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td class="text-end">総額:{$footerContent['totalRepayments']}</td>
        <td>&nbsp;</td>
        <td class="text-end">残額:{$footerContent['totalChargeRemains']}</td>
        <td>&nbsp;</td>
    </tr>
</tfoot>
EOF;
}

$layout =<<<EOL
【{$type}】
<div class="table-wrapper">
<table class="table table-striped table-bordered">
{$header}
</tbody>
{items}
{$footer}
</table>
</div>
{pager}
EOL; ?>
<?= ListView::widget([
    'dataProvider' => $dataProvider,
    'layout' => $layout,
    'itemView' => 'iv-partial-contract-chart-new'
]) ?>
<?php Pjax::end(); ?>

