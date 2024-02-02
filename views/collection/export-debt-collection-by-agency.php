<?php
/**
 * @var $this \yii\web\View
 * @var $searchModel \app\models\ScheduleSearch;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

$term = empty($searchModel->target_term) ? (new \DateTime())->format('Y年m月') : $searchModel->target_term;
$targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $term));
$span = $searchModel->getTermsFromSpan();
$query = clone $dataProvider->query;

$query->select([
    '`ls`.`lease_servicer_id`',
    'GROUP_CONCAT(DISTINCT cd.contract_detail_id ORDER BY lc.disp_order DESC) as cdids',
])
    ->groupBy(['ls.lease_servicer_id']);
$totals = \yii\helpers\ArrayHelper::index($query->asArray()->all(), 'lease_servicer_id');

$header = ['CF',
    '支払方法',
    '顧客名',
    '契約No.',
    'リース開始',
    'リース終了',
    '登録No.',
    'リース期間',
    'リース料発生月',
    '回数',
    "{$span['to']->format('Y/m')}\n回数",
    'リース料',
    "非課税未収\n売掛金",
    "8%未収\n売掛金",
    "10%未収\n売掛金",
    "非課税\n前払い",
    "8%\n前払い",
    "10%\n前払い", 'リース会社',
    "{$span['to']->format('Y/m')}\n回数", "支払リース料",
    "非課税未払\n買掛金",
    "8%未払\n買掛金",
    "10%未払\n買掛金",
    "非課税\n前払い",
    "8%\n前払い",
    "10%\n前払い"
];
$filename = sprintf("売掛買掛集計（リース会社別）-%s-%s.csv", $searchModel->clientCorporation->shorten_name, date('YmdHis'));

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=$filename");

$fp = fopen('php://output', 'w');
stream_filter_prepend($fp,'convert.iconv.UTF-8/CP932//TRANSLIT', STREAM_FILTER_WRITE);
fputcsv($fp, $header);


$term = empty($searchModel->target_term) ? (new \DateTime())->format('Y年m月') : $searchModel->target_term;
$targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $term));
$span = $searchModel->getTermsFromSpan();
$dataProvider->pagination = false;
$query = clone $dataProvider->query;

$query->select([
    '`ls`.`lease_servicer_id`',
    'GROUP_CONCAT(DISTINCT cd.contract_detail_id ORDER BY lc.disp_order DESC) as cdids',
])
    ->groupBy(['ls.lease_servicer_id']);
$totals = \yii\helpers\ArrayHelper::index($query->asArray()->all(), 'lease_servicer_id');
foreach($dataProvider->models as $model) {
    $exporter = new \app\components\DebtByAgencyRowExporter([
        'model' => $model,
        'targetTerm' => $targetTerm,
        'totals' => $totals,
        'searchModel' => $searchModel,
        'span' => $span,
    ]);
    $rows = $exporter->export();
    while($row = array_shift($rows)) {
        fputcsv($fp, $row);
    }
}
