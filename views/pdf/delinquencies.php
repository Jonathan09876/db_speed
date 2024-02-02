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
use kartik\typeahead\Typeahead;
use yii\helpers\Url;

$searchModel = $self->searchModel;
$dataProvider = $searchModel->search(Yii::$app->session['schedule_search_params'], 'delinquencies');
$this->title = '延滞管理一覧';

$style = <<<EOS
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
    left: 0px;
    border-left-width: 1;
    z-index:3 !important;
}
.sticky-cell1 {
    position:sticky;
    top:0;
    left: 0px;
    background-color: #fff !important;
    border-left-width: 1;
    z-index:1;
}
.sticky-header2 {
    position:sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell2 {
    position:sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
}
.sticky-header3 {
    position:sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell3 {
    position:sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
}
.sticky-header4 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell4 {
    position: sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
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
EOS;
$this->registerCss($style);
?>
<?php
$span = $searchModel->getTermsFromSpan();
$targetTerm = new \DateTime($searchModel->target_term_year . date('-m-01'));
$current = $span['from'];
$terms = [];
for ($i = 0; $i < 12; $i++) {
    $interval = $targetTerm->diff($current);
    $term = \app\models\Term::findOne(['term' => $current->format('Y-m-d')]);
    $term->relative_month = -((int)$targetTerm->format('n') - ((int)$current->format('n') - ((int)$targetTerm->format('Y') - (int)$current->format('Y')) * 12));
    $terms[] = $term;
    $current = $current->modify('+1 month');
}
$dataProvider->pagination = [
    'totalCount' => $dataProvider->totalCount,
    'pageSize' => 30
];//ここは暫定
$prev = (clone $terms[0])->termDateTime->modify('-1 month');
$targetYearMonth = preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $searchModel->target_term);
$currentMonthTerm = \app\models\Term::findOne(['term' => $targetYearMonth]);
$prevMonthText = $prev->format('Y/m');
$pageCount = $dataProvider->pagination->pageCount;
for($page = 0; $page < $pageCount; $page++) :
$dataProvider->pagination->page = $page;
$dataProvider->pagination->pageSize = $page == $pageCount - 1 ? 28 : 30;
$dataProvider->prepare(true); ?>
<div style="padding: 5mm 5mm; font-size: 8.5px;<?php if ($page != $pageCount) : ?>page-break-after: always;<?php endif; ?>">
    <h4 class="text-center">延滞管理一覧</h4>
    <p class="pull-right">Page: <?= $page + 1 ?> / <?= $pageCount ?></p>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>CF</th>
                <th>支払方法</th>
                <th>顧客名
                <th>契約No.</th>
                <th>税率</th>
                <th>リース開始<br>リース終了</th>
                <th>登録No.</th>
                <th>毎月回収額</th>
                <th><?= $prevMonthText ?><br/>回収残高</th>
                <th>回収額<br/>残額</th>
<?php
        foreach($terms as $term) {
            $termText = $term->termDateTime->format('Y/m'); ?>
                <th><?= $termText ?></th>
<?php
        } ?>
                <th>回収残額合計</th>
                <th>遅延額合計</th>
                <th>特記事項</th>
            </tr>
        </thead>
        <tbody>
<?php foreach($dataProvider->models as $model) {
    echo $this->render('iv-collection-delinquencies-latest', compact("model", "terms", "searchModel"));
} ?>
        </tbody>
<?php if ($page == $pageCount - 1) :
    $terms0 = $terms;
    $delinquencyTotal = 0;
    $delinquencySubtotals = [];
    $collectionTotal = 0;
    $collectionSubtotals = [];
    $remainsSubtotals = [];
    while($term = array_shift($terms0)) {
        $delinquencySubtotal = 0;
        $collectionSubtotal = 0;
        $remainsSubtotal = 0;
        $termText = $term->termDateTime->format('Y/m');
        $dataProvider->pagination->page = 1;
        $dataProvider->pagination->pageSize = false;
        $dataProvider->prepare(true);
        $models = $dataProvider->models;
        while($detail = array_shift($models)) {
            $collectionCell = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
            $delinquency = $collectionCell->monthly_charge_amount_with_tax - $collectionCell->repayment_amount_with_tax;
            $options = json_decode($collectionCell->options, true);
            $collectionSubtotal += (isset($options['rpid']) && !empty($options['rpid']) ? $collectionCell->repayment_amount_with_tax : 0);
            if ($term->termDateTime <= $currentMonthTerm->termDateTime) {
                $delinquencySubtotal += $delinquency;
            }
            $remainsSubtotal += $detail->getChargeRemains($term);
        }
        $delinquencyTotal += $delinquencySubtotal;
        $delinquencySubtotals[$termText] = $delinquencySubtotal;
        $collectionTotal += $collectionSubtotal;
        $collectionSubtotals[$termText] = $collectionSubtotal;
        $remainsSubtotals[$termText] = $remainsSubtotal;

    }
?>
        <tfoot>
        <tr>
            <th colspan="10" class="sticky-header1 text-end">未回収額合計</th>
<?php
        $terms1 = $terms;
        while($term = array_shift($terms1)) {
            $termText = $term->termDateTime->format('Y/m');
?>
            <th class="text-end"><?= number_format($delinquencySubtotals[$termText], 0) ?></th>
<?php
        }
        $collectionRemainsTotal = 0;
        $models = $dataProvider->models;
        while($detail = array_shift($models)) {
            $collectionRemainsTotal += $detail->getChargeRemains($terms[11]);
        }
?>
            <th class="text-end"><?= number_format($collectionRemainsTotal, 0) ?></th>
            <th class="text-end"><?= number_format($delinquencyTotal, 0) ?></th>
            <th>&nbsp;</th>
        </tr>
        <tr>
            <th colspan="10" class="sticky-header1 text-end">回収額合計</th>
<?php
        $terms2 = $terms;
        while($term = array_shift($terms2)) {
            $termText = $term->termDateTime->format('Y/m');
?>
            <th class="text-end"><?= number_format($collectionSubtotals[$termText], 0) ?></th>
<?php
        }
?>
            <th>&nbsp;</th>
            <th class="text-end"><?= number_format($collectionTotal, 0) ?></th>
            <th>&nbsp;</th>
        </tr>
        <tr>
            <th colspan="10" class="sticky-header1 text-end">残額合計</th>
<?php
        $terms3 = $terms;
        while($term = array_shift($terms3)) {
            $termText = $term->termDateTime->format('Y/m');
?>
            <th class="text-end"><?= number_format($remainsSubtotals[$termText], 0) ?></th>
<?php
        }
?>
            <th class="text-end"><?= number_format($collectionRemainsTotal, 0) ?></th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
        </tr>
        </tfoot>
<?php endif; ?>
    </table>
</div>
<?php endfor; ?>


