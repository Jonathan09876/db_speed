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
use app\models\ContractPattern;
use kartik\typeahead\Typeahead;
use yii\helpers\Url;

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
$dataProvider->pagination = false;
$prev = (clone $terms[0])->termDateTime->modify('-1 month');
$prevMonthText = $prev->format('Y/m');
$targetYearMonth = preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $searchModel->target_term);
$currentMonthTerm = \app\models\Term::findOne(['term' => $targetYearMonth]);

$header = ['CF', '支払方法', '顧客名', '担当', '契約No.', '税率', "リース開始\nリース終了\n初年度登録月", '登録No.', "回収予定合計\n毎月回収額\nリース回数", "{$prevMonthText}\n回収残高", "回収額\n残額"];

$delinquencyTotal = 0;
$delinquencySubtotals = [];
$chargeTotal = 0;
$chargeSubtotals = [];
$collectionTotal = 0;
$collectionSubtotals = [];
$remainsSubtotals = [];

$terms1 = $terms;
while($term = array_shift($terms1)) {
    $termText = $term->termDateTime->format('Y/m');
    $header[] = $termText;
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

$header = array_merge($header, ['合計', '遅延額合計', '特記事項']);

$filename = sprintf("実績集計(契約単位)-%s-%s.csv", $searchModel->clientCorporation->shorten_name, date('YmdHis'));

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=$filename");

$fp = fopen('php://output', 'w');
stream_filter_prepend($fp,'convert.iconv.UTF-8/CP932//TRANSLIT', STREAM_FILTER_WRITE);
fputcsv($fp, $header);

$dataProvider->pagination = false;
$models = $dataProvider->models;
while($model = array_shift($models)) {
    $exporter = new \app\components\DelinquencyRowExporter([
        'model' => $model,
        'terms' => $terms,
        'searchModel' => $searchModel,
    ]);
    $rows = $exporter->export();
    while($row = array_shift($rows)) {
        fputcsv($fp, $row);
    }
    unset($exporter);
}

$row = array_fill(0, 26, '');
fputcsv($fp, $row);

$footer1 = ['','','','','','','','','','','未回収額合計'];
$terms1 = $terms;
$delinquencyWholeTotal = 0;
while($term = array_shift($terms1)) {
    $termText = $term->termDateTime->format('Y/m');
    $footer1[] = '';
    $footer1[] = $delinquencySubtotals[$termText];
    $delinquencyWholeTotal += $delinquencySubtotals[$termText];
}
$collectionRemainsTotal = 0;
$models = $dataProvider->models;
while($detail = array_shift($models)) {
    $delinquencyTotal += \app\models\MonthlyCharge::getRelativeShortage($detail->contract_detail_id, $searchModel->target_term);
    $collectionRemainsTotal += $detail->getChargeRemains($terms[11]);
}
$footer1[] = $delinquencyWholeTotal;
$footer1[] = $delinquencyTotal;
$footer1[] = '';

$footer2 = ['','','','','','','','','','','回収予定額合計'];
$terms2 = $terms;
while($term = array_shift($terms2)) {
    $termText = $term->termDateTime->format('Y/m');
    $footer2[] = '';
    $footer2[] = $chargeSubtotals[$termText];
}
$footer2[] = $chargeTotal;
$footer2[] = '';
$footer2[] = '';

$footer3 = ['','','','','','','','','','','回収額合計'];
$terms2 = $terms;
while($term = array_shift($terms2)) {
    $termText = $term->termDateTime->format('Y/m');
    $footer3[] = '';
    $footer3[] = $collectionSubtotals[$termText];
}
$footer3[] = $collectionTotal;
$footer3[] = '';
$footer3[] = '';

$footer4 = ['','','','','','','','','','','残額合計'];
$terms3 = $terms;
while($term = array_shift($terms3)) {
    $termText = $term->termDateTime->format('Y/m');
    $footer4[] = '';
    $footer4[] = $remainsSubtotals[$termText];
}
$footer4[] = $collectionRemainsTotal;
$footer4[] = '';
$footer4[] = '';
fputcsv($fp, $footer1);
fputcsv($fp, $footer2);
fputcsv($fp, $footer3);
fputcsv($fp, $footer4);
fclose($fp);