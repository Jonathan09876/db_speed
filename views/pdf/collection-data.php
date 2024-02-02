<?php
/**
 * @var $this \yii\web\View
 * @var $searchModel \app\models\ScheduleSearch;
 * @var $dataProvider \yii\data\ActiveDataProvider;
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
$dataProvider = $searchModel->search(Yii::$app->session['schedule_search_params'], 'calc_collection_data');


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
	min-width:100%;
}
.table-wrapper table.table th,
.table-wrapper table.table td {
    border-left-width: 0;
}
.table-wrapper table.table th,
.table-wrapper table.table thead th {
    position: sticky;
    top:0;
    z-index:2;
}
.table-wrapper table.table tr.has-registered-repayments {
    background-color: #e4e4e4;
}
.customer-last-contract .border-bottom, .border-bottom-bold {
    border-bottom-width: 3px!important;
}
.sticky-header1 {
    position:sticky;
    top:0;
    left: 0px;
    border-left-width: 1;
    z-index:3!important;
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
    z-index:3!important;
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
    z-index:3!important;
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
    z-index:3!important;
}
.sticky-cell4 {
    position: sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
}
.sticky-row1 th {
    top: 0;
}
.sticky-row2 th {
    top: 0;
}
.col-first {
    -webkit-box-flex: 0;
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    width: 110px;
}
.text-red {
    color: #ff0000;
}
.table-wrapper table.table th.current-term {
    background-color: var(--vz-indigo);
}
td.element-cell {
    padding: 0 !important;
}
td.element-cell>div {
    padding: 4px 8px;
}
td.element-cell br {
    display:block;
    content: '';
    width: 100%;
    height: 0;
    border-bottom: 1px solid #000;
}
td.element-cell .form-control {
    font-size: 12px;
}
td.element-cell.cell-dropdown {
    min-width: 110px;
}
td.element-cell.cell-text-input {
    min-width: 90px;
}
.formatted {
    text-align: right;
}
EOS;
$this->registerCss($style);

$dataProvider->pagination = [
    'totalCount' => $dataProvider->totalCount,
    'pageSize' => 50
];//ここは暫定
$pageCount = $dataProvider->pagination->pageCount;
for($page = 0; $page < $pageCount; $page++) :
    $dataProvider->pagination->page = $page;
    $dataProvider->pagination->pageSize = 50;
    $dataProvider->prepare(true);

    $term = empty($searchModel->target_term) ? (new \DateTime())->format('Y年m月') : $searchModel->target_term;
    $targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $term));
?>
<div style="padding: 5mm 5mm; font-size: 8.5px;<?php if ($page != $pageCount) : ?>page-break-after: always;<?php endif; ?>">
    <h4 class="text-center">【<?= $searchModel->clientCorporation->name ?> : <?= $searchModel->repaymentPattern->name ?>】回収支払表-<?= $term ?></h4>
    <p class="pull-right">Page: <?= $page + 1 ?> / <?= $pageCount ?></p>
            <?php
                $stored = \app\models\TargetTermMonthlyChargeStored::isStored($targetTerm->format('Y-m-d'), $searchModel->client_corporation_id, $searchModel->repayment_pattern_id);
                $term = empty($searchModel->target_term) ? (new \DateTime())->format('Y年m月') : $searchModel->target_term;
                $targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $term));
                $lastTerm = (clone $targetTerm)->modify('-1 month');
                $sql = "SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    END) AS `customer_total`";
                Yii::$app->db->createCommand('SET group_concat_max_len = 5120')->execute();
                $query = clone $dataProvider->query;
                $query->select([
                    '`c`.`customer_id`',
                    'GROUP_CONCAT(DISTINCT cd.contract_detail_id ORDER BY lc.disp_order ASC) AS cdids',
                    'MIN(`cd`.`contract_detail_id`) AS `first_cdid`',
                    'COUNT(DISTINCT cd.contract_detail_id) AS `rowspan`',
                    $sql,
                ])
                    ->groupBy(['c.customer_id']);
                    //->orderBy(['cc.client_corporation_id' => SORT_ASC, 'c.customer_code' =>SORT_ASC, 'lc.disp_order' => SORT_ASC, 'cd.term_start_at' =>SORT_ASC]);
                $totals = \yii\helpers\ArrayHelper::index($query->asArray()->all(), 'customer_id');
                if ($stored) {
                    $lastRepaymentTotal = current((clone $dataProvider->query)
                        ->leftJoin('monthly_charge mc2', 'cd.contract_detail_id=mc2.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc2.term + INTERVAL 1 MONTH ELSE mc2.term END = :lastterm')
                        ->leftJoin('repayment r2', 'r2.monthly_charge_id=mc2.monthly_charge_id')
                        ->params([':term' => $targetTerm->format('Y-m-01'), ':stored_id' => $stored, ':lastterm' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(['sum(r2.repayment_amount) as `repayment_total`'])
                        ->column());
                    $lastRepaymentFurikaeTotal = current((clone $dataProvider->query)
                        ->leftJoin('monthly_charge mc2', 'cd.contract_detail_id=mc2.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc2.term + INTERVAL 1 MONTH ELSE mc2.term END = :lastterm')
                        ->leftJoin('repayment r2', 'r2.monthly_charge_id=mc2.monthly_charge_id')
                        ->params([':term' => $targetTerm->format('Y-m-01'), ':stored_id' => $stored, ':lastterm' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(['sum(r2.repayment_amount) as `repayment_total`'])
                        ->andWhere(['IFNULL(r2.repayment_type_id, IFNULL(mc2.repayment_type_id, rp.repayment_type_id))' => 1]) //口座振替
                        ->column());
                    $lastRepaymentFurikomiTotal = current((clone $dataProvider->query)
                        ->leftJoin('monthly_charge mc2', 'cd.contract_detail_id=mc2.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc2.term + INTERVAL 1 MONTH ELSE mc2.term END = :lastterm')
                        ->leftJoin('repayment r2', 'r2.monthly_charge_id=mc2.monthly_charge_id')
                        ->params([':term' => $targetTerm->format('Y-m-01'), ':stored_id' => $stored, ':lastterm' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(['sum(r2.repayment_amount) as `repayment_total`'])
                        ->andWhere(['IFNULL(r2.repayment_type_id, IFNULL(mc2.repayment_type_id, rp.repayment_type_id))' => [2,3,4,14]]) //口座振替
                        ->column());
                    $lastChargeTotal = current((clone $dataProvider->query)
                        ->leftJoin('monthly_charge mc2', 'cd.contract_detail_id=mc2.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc2.term + INTERVAL 1 MONTH ELSE mc2.term END = :lastterm')
                        ->leftJoin('repayment r2', 'r2.monthly_charge_id=mc2.monthly_charge_id')
                        ->params([':term' => $targetTerm->format('Y-m-01'), ':stored_id' => $stored, ':lastterm' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])->column());

                    $lastChargeFurikaeTotal = current((clone $dataProvider->query)
                        ->leftJoin('monthly_charge mc2', 'cd.contract_detail_id=mc2.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc2.term + INTERVAL 1 MONTH ELSE mc2.term END = :lastterm')
                        ->leftJoin('repayment r2', 'r2.monthly_charge_id=mc2.monthly_charge_id')
                        ->params([':term' => $targetTerm->format('Y-m-01'), ':stored_id' => $stored, ':lastterm' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])
                        ->andWhere(['IFNULL(mc2.repayment_type_id, rp.repayment_type_id)' => 1]) //口座振替
                        ->column());
                    $lastChargeFurikomiTotal = current((clone $dataProvider->query)
                        ->leftJoin('monthly_charge mc2', 'cd.contract_detail_id=mc2.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc2.term + INTERVAL 1 MONTH ELSE mc2.term END = :lastterm')
                        ->leftJoin('repayment r2', 'r2.monthly_charge_id=mc2.monthly_charge_id')
                        ->params([':term' => $targetTerm->format('Y-m-01'), ':stored_id' => $stored, ':lastterm' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])
                        ->andWhere(['IFNULL(mc2.repayment_type_id, rp.repayment_type_id)' => [2,3,4,14]]) //口座振替
                        ->column());
                    $lastChargeOthersTotal = current((clone $dataProvider->query)
                        ->leftJoin('monthly_charge mc2', 'cd.contract_detail_id=mc2.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc2.term + INTERVAL 1 MONTH ELSE mc2.term END = :lastterm')
                        ->leftJoin('repayment r2', 'r2.monthly_charge_id=mc2.monthly_charge_id')
                        ->params([':term' => $targetTerm->format('Y-m-01'), ':stored_id' => $stored, ':lastterm' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])
                        ->andWhere(['not', ['IFNULL(mc2.repayment_type_id, rp.repayment_type_id)' => [1,2,3,4,14]]]) //口座振替
                        ->column());
                }
                else {
                    $lastRepaymentTotal = current((clone $dataProvider->query)
                        ->params([':term' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(['sum(r.repayment_amount) as `repayment_total`'])
                        ->column());
                    $lastRepaymentFurikaeTotal = current((clone $dataProvider->query)
                        ->params([':term' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(['sum(r.repayment_amount) as `repayment_total`'])
                        ->andWhere(['IFNULL(r.repayment_type_id, IFNULL(mc.repayment_type_id, rp.repayment_type_id))' => 1]) //口座振替
                        ->column());
                    $lastRepaymentFurikomiTotal = current((clone $dataProvider->query)
                        ->params([':term' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(['sum(r.repayment_amount) as `repayment_total`'])
                        ->andWhere(['IFNULL(r.repayment_type_id, IFNULL(mc.repayment_type_id, rp.repayment_type_id))' => [2,3,4,14]]) //口座振替
                        ->column());
                    $lastChargeTotal = current((clone $dataProvider->query)
                        ->params([':term' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])->column());

                    $lastChargeFurikaeTotal = current((clone $dataProvider->query)
                        ->params([':term' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])
                        ->andWhere(['IFNULL(mc.repayment_type_id, rp.repayment_type_id)' => 1]) //口座振替
                        ->column());
                    $lastChargeFurikomiTotal = current((clone $dataProvider->query)
                        ->params([':term' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])
                        ->andWhere(['IFNULL(mc.repayment_type_id, rp.repayment_type_id)' => [2,3,4,14]]) //口座振替
                        ->column());
                    $lastChargeFurikomiTotal = current((clone $dataProvider->query)
                        ->params([':term' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])
                        ->andWhere(['IFNULL(mc.repayment_type_id, rp.repayment_type_id)' => [2,3,4,14]]) //口座振替
                        ->column());
                    $lastChargeOthersTotal = current((clone $dataProvider->query)
                        ->params([':term' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])
                        ->andWhere(['not', ['IFNULL(mc.repayment_type_id, rp.repayment_type_id)' => [1,2,3,4,14]]]) //口座振替
                        ->column());
                }
                $repaymentTotal = current((clone $dataProvider->query)
                    ->select(['sum(r.repayment_amount) as `repayment_total`'])
                    ->column());
                $repaymentFurikaeTotal = current((clone $dataProvider->query)
                    ->select(['sum(r.repayment_amount) as `repayment_total`'])
                    ->andWhere(['IFNULL(r.repayment_type_id, IFNULL(mc.repayment_type_id, rp.repayment_type_id))' => 1]) //口座振替
                    ->column());
                $repaymentFurikomiTotal = current((clone $dataProvider->query)
                    ->select(['sum(r.repayment_amount) as `repayment_total`'])
                    ->andWhere(['IFNULL(r.repayment_type_id, IFNULL(mc.repayment_type_id, rp.repayment_type_id))' => [2,3,4,14]]) //口座振替
                    ->column());
                $chargeTotal = current((clone $dataProvider->query)
                    ->select(["SUM(CASE cd.fraction_processing_pattern 
            WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
            WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
            WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        END) as amount"])->column());
                $chargeFurikaeTotal = current((clone $dataProvider->query)
                    ->select(["SUM(CASE cd.fraction_processing_pattern 
            WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
            WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
            WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        END) as amount"])
                    ->andWhere(['IFNULL(mc.repayment_type_id, rp.repayment_type_id)' => 1]) //口座振替
                    ->column());
                $chargeFurikomiTotal = current((clone $dataProvider->query)
                ->select(["SUM(CASE cd.fraction_processing_pattern 
            WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
            WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
            WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        END) as amount"])
                ->andWhere(['IFNULL(mc.repayment_type_id, rp.repayment_type_id)' => [2,3,4,14]]) //口座振替
                ->column());
                $chargeOthersTotal = current((clone $dataProvider->query)
                ->select(["SUM(CASE cd.fraction_processing_pattern 
            WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
            WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
            WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        END) as amount"])
                ->andWhere(['not',['IFNULL(mc.repayment_type_id, rp.repayment_type_id)' => [1,2,3,4,14]]]) //口座振替
                ->column());
                $diffTotal = $chargeTotal - $lastChargeTotal;
                $diffFurikaeTotal = $chargeFurikaeTotal - $lastChargeFurikaeTotal;
                $diffFurikomiTotal = $chargeFurikomiTotal - $lastChargeFurikomiTotal;
                $diffOthersTotal = $chargeOthersTotal - $lastChargeOthersTotal;
                $diffRepaymentTotal = $repaymentTotal - $lastRepaymentTotal;
                $diffRepaymentFurikaeTotal = $repaymentFurikaeTotal - $lastRepaymentFurikaeTotal;
                $diffRepaymentFurikomiTotal = $repaymentFurikomiTotal - $lastRepaymentFurikomiTotal;
                $lastRepaymentTotalText = number_format($lastRepaymentTotal, 0);
                $lastRepaymentFurikaeTotalText = number_format($lastRepaymentFurikaeTotal, 0);
                $lastRepaymentFurikomiTotalText = number_format($lastRepaymentFurikomiTotal, 0);
                $lastChargeTotalText = number_format($lastChargeTotal, 0);
                $lastChargeFurikaeTotalText = number_format($lastChargeFurikaeTotal, 0);
                $lastChargeFurikomiTotalText = number_format($lastChargeFurikomiTotal, 0);
                $lastChargeOthersTotalText = number_format($lastChargeOthersTotal, 0);
                $diffTotalText = number_format($diffTotal, 0);
                $diffFurikaeTotalText = number_format($diffFurikaeTotal, 0);
                $diffFurikomiTotalText = number_format($diffFurikomiTotal, 0);
                $diffOthersTotalText = number_format($diffOthersTotal, 0);
                $diffRepaymentTotalText = number_format($diffRepaymentTotal, 0);
                $diffRepaymentFurikaeTotalText = number_format($diffRepaymentFurikaeTotal, 0);
                $diffRepaymentFurikomiTotalText = number_format($diffRepaymentFurikomiTotal, 0);
                $repaymentTotalText = number_format($repaymentTotal, 0);
                $repaymentFurikaeTotalText = number_format($repaymentFurikaeTotal, 0);
                $repaymentFurikomiTotalText = number_format($repaymentFurikomiTotal, 0);
                $chargeTotalText = number_format($chargeTotal, 0);
                $chargeFurikaeTotalText = number_format($chargeFurikaeTotal, 0);
                $chargeFurikomiTotalText = number_format($chargeFurikomiTotal, 0);
                $chargeOthersTotalText = number_format($chargeOthersTotal, 0);
                $widget = new \app\widgets\PageSizeLimitChanger(['pjax_id' => 'pjax-grid-wrapper']);
                $dataProvider->pagination = $widget->pagination;
                Yii::debug(\yii\helpers\VarDumper::dumpAsString($widget->pagination,10));
                $summary = $widget->summaryLayout;
                $client_corporation_id = $searchModel->client_corporation_id;
                $repayment_pattern_id = $searchModel->repayment_pattern_id;
                $collectionUpdateEnable = \app\models\TargetTermMonthlyChargeStored::isUpdateEnable($targetTerm->format('Y-m-d'), $client_corporation_id, $repayment_pattern_id);
                $layout = <<<EOL
    <table class="table table-bordered">
        <thead>
            <tr>
                <th rowspan="2">CF</th>
                <th rowspan="2">支払方法</th>
                <th rowspan="2">顧客名
                <th rowspan="2">契約No.</th>
                <th rowspan="2">税率</th>
                <th rowspan="2">登録年月日</th>
                <th rowspan="2">登録No.</th>
                <th rowspan="2">リース期間</th>
                <th rowspan="2">回収</th>
                <th colspan="2">先月回収予定</th>
                <th colspan="3" class="current-term">今月回収予定額</th>
                <th rowspan="2">増減</th>
                <th rowspan="2">会社別</sub></th>
                <th rowspan="2">顧客名</sub></th>
                <th rowspan="2">CF</th>
            </tr>
            <tr>
                <th>回数</th>
                <th>{$lastTerm->format('Y/m')}</th>
                <th class="current-term">回数</th>
                <th colspan="2" class="current-term">{$targetTerm->format('Y/m')}</th>
            </tr>
        </thead>
        <tbody>
            {items}
        </tbody>
EOL;
                if ($page == $pageCount - 1) {
                    $layout .= <<<EOL
        <tfoot>
            <tr>
                <th colspan="8" rowspan="4" class="text-end pe-3"><span style="writing-mode: vertical-rl;">前月合計</span></th>
                <th colspan="2" class="text-end">口座振替合計</th>
                <th class="text-end">{$lastChargeFurikaeTotalText}</th>
                <th colspan="2" rowspan="4" class="text-end pe-3"><span style="writing-mode: vertical-rl;">当月合計</span></th>
                <th class="text-end">{$chargeFurikaeTotalText}</th>
                <th class="text-end">{$diffFurikaeTotalText}</th>
                <th colspan="3"></th>
            </tr>
            <tr>
                <th colspan="2" class="text-end">振込合計</th>
                <th class="text-end">{$lastChargeFurikomiTotalText}</th>
                <th class="text-end">{$chargeFurikomiTotalText}</th>
                <th class="text-end">{$diffFurikomiTotalText}</th>
                <th colspan="3"></th>
            </tr>
            <tr>
                <th colspan="2" class="text-end">その他合計</th>
                <th class="text-end">{$lastChargeOthersTotalText}</th>
                <th class="text-end">{$chargeOthersTotalText}</th>
                <th class="text-end">{$diffOthersTotalText}</th>
                <th colspan="3"></th>
            </tr>
            <tr>
                <th colspan="2" class="text-end">回収額合計</th>
                <th class="text-end">{$lastChargeTotalText}</th>
                <th class="text-end">{$chargeTotalText}</th>
                <th class="text-end">{$diffTotalText}</th>
                <th colspan="3"></th>
            </tr>
        </tfoot>
EOL;
                }
                $layout .=<<<EOL
    </table>
EOL;
?>
                <?= ListView::widget([
                    'dataProvider' => $dataProvider,
                    'itemView' => '/pdf/iv-lease-contract-calc-collection-data',
                    'itemOptions' => ['tag' => false],
                    'viewParams' => ['targetTerm' => $targetTerm, 'lastTerm' => $lastTerm, 'dataProvider' => $dataProvider, 'totals' => $totals, 'isUpdateEnable' => $collectionUpdateEnable],//compact("targetTerm", "lastTerm", "totals"),
                    'layout' => $layout,
                ]) ?>
</div>
<?php endfor; ?>