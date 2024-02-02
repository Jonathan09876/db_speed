<?php
/**
 * @var $this \yii\web\View
 * @var $searchModel \app\models\ScheduleSearch;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

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

$header = [
    'CF','支払方法','顧客名','契約No.','税率','リース開始','リース終了','登録No.','リース期間/会社','収支',
];
$current = (new \DateTime())->setDate(date('Y'), date('n'), 1);
$lastMonth = $current->modify('-1 month');
foreach($terms as $term) {
    $header[] = '回数';
    $header[] = $term->termDateTime->format('Y/m');
}
$header = array_merge($header, [
    '今期合計',
    'リース開始年月/債務回収数',
    '前払回数',
    '前払リース料',
    '特記事項'
]);

$filename = sprintf("回収予定一覧-%s-%s.csv", $searchModel->clientCorporation->shorten_name, date('YmdHis'));

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=$filename");

$fp = fopen('php://output', 'w');
stream_filter_prepend($fp,'convert.iconv.UTF-8/CP932//TRANSLIT', STREAM_FILTER_WRITE);
fputcsv($fp, $header);


$dataProvider->pagination = false;
foreach($dataProvider->models as $model) {
    $exporter = new \app\components\RowExporter([
        'model' => $model,
        'targetTerm' => $targetTerm,
        'terms' => $terms,
        'searchModel' => $searchModel,
        'lastMonth' => $lastMonth,
    ]);
    $rows = $exporter->export();
    while($row = array_shift($rows)) {
        fputcsv($fp, $row);
    }
}
$footer1 = [
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '回収予定合計',
];
foreach($terms as $the_term) {
    $footer1[] = '';
    $footer1[] = \app\models\ContractDetail::getTermTotalChargeAmountWithTax($dataProvider, $the_term);
}
$footer1 = array_merge($footer1, [
    \app\models\ContractDetail::getTermsDetailsTotalChargeAmountWithTax($dataProvider, $terms),
    \app\models\ContractDetail::getWholeTotalChargeAmountWithTax($dataProvider)
]);
$footer2 = [
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '回収実績合計',
];
foreach($terms as $the_term) {
    $footer2[] = '';
    $footer2[] = \app\models\ContractDetail::getTermRepaymentTotal($dataProvider, $the_term);
}
$footer2 = array_merge($footer2, [
    \app\models\ContractDetail::getTermsDetailsTotalRepaymentAmountWithTax($dataProvider, $terms),
    \app\models\ContractDetail::getWholeTotalRepaymentAmountWithTax($dataProvider)
]);
$footer3 = [
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '支払合計',
];
foreach($terms as $the_term) {
    $footer3[] = '';
    $footer3[] = \app\models\ContractDetail::getTermTotalPaymentAmountWithTax($searchModel, $dataProvider, $the_term);
}
$footer3 = array_merge($footer3, [
    \app\models\ContractDetail::getTermsDetailsTotalPaymentAmountWithTax($dataProvider, $terms),
    \app\models\ContractDetail::getWholeTotalPaymentAmountWithTax($searchModel, $dataProvider)
]);
fputcsv($fp, $footer1);
fputcsv($fp, $footer2);
fputcsv($fp, $footer3);
fclose($fp);
