<?php
/**
 * @var $this \yii\web\View
 * @var $self \app\components\FormPublisher;
 * @var $searchModel \app\models\ScheduleSearch;
 */

use app\models\ScheduleSearch;
use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\widgets\ListView;
use yii\bootstrap5\Html;
use app\widgets\datetimepicker\Datetimepicker;
use app\models\RepaymentPattern;
use app\models\ContractPattern;
use kartik\typeahead\Typeahead;
use yii\helpers\Url;

$searchModel = $self->searchModel;
$dataProvider = $searchModel->search(Yii::$app->session['schedule_search_params'], 'delinquencies');
$this->title = '実績集計';

$style = <<<CSS
.list-group.position-absolute {
    top:calc(100%);
    max-height: 200px;
    -webkit-box-shadow: 0 5px 10px rgba(30,32,37,.12);
    box-shadow: 0 5px 10px rgba(30,32,37,.12);
    -webkit-animation-name: DropDownSlide;
    animation-name: DropDownSlide;
    -webkit-animation-duration: .3s;
    animation-duration: .3s;
    -webkit-animation-fill-mode: both;
    animation-fill-mode: both;
    z-index: 1000;
}
.position-absolute .list-group-item {
    cursor:pointer
}
.table-wrapper table.table {
	border-collapse:separate;
	border-spacing:0;
}
.table-wrapper table.table th,
.table-wrapper table.table td {
    border-left-width: 0;
}
.table-wrapper table.table thead th {
    position: sticky;
    top:0;
    z-index:2;
}
.sticky-header1 {
    position:sticky;
    top:0;
    left: 0;
    border-left-width: 1px;
    z-index:3 !important;
}
.sticky-cell {
    position:sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
}
.sticky-cell1 {
    left: 0;
    border-left-width: 1px;
}
.sticky-header2 {
    position:sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell2 {
}
.sticky-header3 {
    position:sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell3 {
}
.sticky-header4 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell4 {
}
.sticky-header5 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell5 {
}
.sticky-header6 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell6 {
}
.sticky-header7 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell7 {
}
.sticky-header8 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell8 {
}
.sticky-header9 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell9 {
}
.sticky-header10 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell10 {
}
.sticky-header11 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell11 {
}
.col-first {
    -webkit-box-flex: 0;
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    width: 110px;
}
.table-wrapper table.table th.current-term {
    background-color: var(--vz-indigo);
}
.paid {
    background-color: #e8e8e8!important;
}
.deficient {
    background-color: #ffd4d4!important;
}
CSS;
$this->registerCss($style);

$span = $searchModel->getTermsFromSpan();
$from = $searchModel->term_from ? new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $searchModel->term_from)) : $span['from'];
$to = $searchModel->term_to ? new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $searchModel->term_to)) : $span['to'];
$targetTerm = new \DateTime($searchModel->target_term_year . date('-m-01'));
$current = $from;
$terms = [];
while($current <= $to){
    $term = \app\models\Term::findOne(['term' => $current->format('Y-m-d')]);
    $terms[] = $term;
    $current = $current->modify('+1 month');
}
$dataProvider->pagination = [
    'totalCount' => $dataProvider->totalCount,
    'pageSize' => 17
];//ここは暫定
$prev = (clone $terms[0])->termDateTime->modify('-1 month');
$prevMonthText = $prev->format('Y/m');
$targetYearMonth = preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $searchModel->target_term);
$currentMonthTerm = \app\models\Term::findOne(['term' => $targetYearMonth]);
$pageCount = $dataProvider->pagination->pageCount;
for($page = 0; $page < $pageCount; $page++) :
$dataProvider->pagination->page = $page;
$dataProvider->pagination->pageSize = $page == $pageCount - 1 ? 28 : 30;
$dataProvider->prepare(true); ?>
<div style="padding: 5mm 5mm; font-size: 8.5px;<?php if ($page != $pageCount) : ?>page-break-after: always;<?php endif; ?>">
    <h4 class="text-center"><?= $this->title ?></h4>
    <p class="pull-right">Page: <?= $page + 1 ?> / <?= $pageCount ?></p>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th class="sticky-header1">CF</th>
                <th class="sticky-header2">支払方法</th>
                <th class="sticky-header3">顧客名
                <th class="sticky-header4">担当</th>
                <th class="sticky-header5">契約No.</th>
                <th class="sticky-header6">税率</th>
                <th class="sticky-header7">リース開始<br>リース終了<br>初年度登録月</th>
                <th class="sticky-header8">登録No.</th>
                <th class="sticky-header9">回収予定合計<br/>毎月回収額<br/>リース回数</th>
                <th class="sticky-header10"><?= $prevMonthText ?><br/>回収残高</th>
                <th class="sticky-header11">回収額<br/>残額</th>
<?php
        $delinquencyTotal = 0;
        $delinquencySubtotals = [];
        $chargeTotal = 0;
        $chargeSubtotals = [];
        $collectionTotal = 0;
        $collectionSubtotals = [];
        $remainsSubtotals = [];
        foreach($terms as $term) {
            $termText = $term->termDateTime->format('Y/m');
?>
                <th colspan="2"><?= $termText ?></th>
<?php
            $delinquencySubtotal = 0;
            $chargeSubtotal = 0;
            $collectionSubtotal = 0;
            $remainsSubtotal = 0;
            $models = $dataProvider->models;
            while($detail = array_shift($models)) {
                $collectionCell = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
                $delinquency = $collectionCell->monthly_charge_amount_with_tax - $collectionCell->repayment_amount_with_tax;
                $options = json_decode($collectionCell->options, true);
                $chargeSubtotal += (isset($options['mcid']) && !empty($options['mcid']) ? $collectionCell->monthly_charge_amount_with_tax : 0);
                $repayments = $collectionCell->term->getCurrentRepayments($detail->contract_detail_id);
                $repayment_total = array_sum(array_map(function($rp){return $rp->repayment_amount;}, $repayments));
                $collectionSubtotal += (count($repayments) ? $repayment_total : 0);
                if ($term->termDateTime <= $currentMonthTerm->termDateTime) {
                    $delinquencySubtotal += $delinquency;
                }
                $remainsSubtotal += $detail->getChargeRemains($term);
            }
            $delinquencySubtotals[$termText] = $delinquencySubtotal;
            $chargeTotal += $chargeSubtotal;
            $chargeSubtotals[$termText] = $chargeSubtotal;
            $collectionTotal += $collectionSubtotal;
            $collectionSubtotals[$termText] = $collectionSubtotal;
            $remainsSubtotals[$termText] = $remainsSubtotal;
        }
?>

                <th >合計</th>
                <th >遅延額合計</th>
                <th>特記事項</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($dataProvider->models as $model) {
            echo $this->render('iv-collection-delinquencies-latest2', compact("model", "terms", "searchModel"));
        } ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="11" class="sticky-header1 text-end">未回収額合計</th>
<?php
        $terms1 = $terms;
        while($term = array_shift($terms1)) {
            $termText = $term->termDateTime->format('Y/m');
            $subTotalText = number_format($delinquencySubtotals[$termText], 0);
?>
                <th colspan="2" class="text-end"><?= $subTotalText ?></th>
<?php
        }
        $collectionRemainsTotal = 0;
        $models = $dataProvider->models;
        while($detail = array_shift($models)) {
            $delinquencyTotal += \app\models\MonthlyCharge::getRelativeShortage($detail->contract_detail_id, $searchModel->target_term);
            $collectionRemainsTotal += $detail->getChargeRemains($terms[11]);
        }
        $collectionRemainsTotalText = number_format($collectionRemainsTotal, 0);
        $delinquencyTotalText = number_format($delinquencyTotal, 0);
?>
                <th><?= $collectionRemainsTotalText ?></th>
                <th><?= $delinquencyTotalText ?></th>
                <th>&nbsp;</th>
            </tr>
            <tr>
                <th colspan="11" class="sticky-header1 text-end">回収予定額合計</th>
<?php
        $terms2 = $terms;
        while($term = array_shift($terms2)) {
            $termText = $term->termDateTime->format('Y/m');
            $chargeSubtotalText = number_format($chargeSubtotals[$termText], 0);
?>
                <th colspan="2" class="text-end"><?= $chargeSubtotalText ?></th>
<?php
        }
        $chargeTotalText = number_format($chargeTotal, 0);
?>
                <th><?= $chargeTotalText ?></th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </tr>
            <tr>
                <th colspan="11" class="sticky-header1 text-end">回収額合計</th>
<?php
        $terms2 = $terms;
        while($term = array_shift($terms2)) {
            $termText = $term->termDateTime->format('Y/m');
            $collectionSubtotalText = number_format($collectionSubtotals[$termText], 0);
?>
                <th colspan="2" class="text-end"><?= $collectionSubtotalText ?></th>
<?php
        }
        $collectionTotalText = number_format($collectionTotal, 0);
?>
                <th><?= $collectionTotalText ?></th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </tr>
            <tr>
                <th colspan="11" class="sticky-header1 text-end">残額合計</th>
<?php
        $terms3 = $terms;
        while($term = array_shift($terms3)) {
            $termText = $term->termDateTime->format('Y/m');
            $remainsSubtotalText = number_format($remainsSubtotals[$termText], 0);
?>
                <th colspan="2" class="text-end"><?= $remainsSubtotalText ?></th>
<?php
        }
?>
                <th class="text-end"><?= $collectionRemainsTotalText ?></th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </tr>
        </tfoot>
    </table>
</div>
<?php endfor; ?>