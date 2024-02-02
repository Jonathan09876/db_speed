<?php
/**
 * @var $this \yii\web\View
 * @var $searchModel \app\models\MonthlyChargeSearch;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

use app\models\MonthlyChargeSearch;
use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\widgets\ListView;
use yii\bootstrap5\Html;
use app\widgets\datetimepicker\Datetimepicker;
use app\models\RepaymentPattern;
use kartik\typeahead\Typeahead;
use yii\helpers\Url;

$term = empty($searchModel->target_term) ? (new \DateTime())->format('Y年n月') : $searchModel->target_term;
$targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $term));
$lastTerm = (clone $targetTerm)->modify('-1 month');

$dataProvider->pagination = false;

$filename = "回収データ-{$term}-".date('Ymdhis').".csv";

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=$filename");

$header_columns = ['No.', '得意先コード', '引落名', '回収先', '振替日', '口座振替管理コード', '金融機関コード', '支店コード', '口座区分', '口座番号', '口座名義カナ', '口座振替日', '振替額'];
$fp = fopen('php://output', 'w');
stream_filter_prepend($fp,'convert.iconv.UTF-8/CP932//TRANSLIT', STREAM_FILTER_WRITE);
fputcsv($fp, $header_columns);
$count = 0;
$sql = "SUM(CASE cd.fraction_processing_pattern 
    WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
END) AS `customer_total`";
$query = clone $dataProvider->query;
$query->select([
    '`c`.`customer_id`',
    '`rp`.`repayment_pattern_id`',
    '`mc`.`transfer_date`',
    $sql,
])
    ->leftJoin('monthly_charge mc', 'cd.contract_detail_id=mc.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc.term + INTERVAL 1 MONTH ELSE mc.term END = :term')
    ->groupBy(['c.customer_id', 'mc.transfer_date'])
    ->params([':term' => $targetTerm->format('Y-m-01')]);
foreach($query->asArray()->all() as $data) {
    $customer = \app\models\Customer::findOne($data['customer_id']);
    $bankAccount = $customer->bankAccount;
    if ($data['customer_total']) {
        $row = [
            ++$count,
            $customer->customer_code,
            $customer->use_transfer_name ? $customer->transfer_name : $customer->name,
            $customer->clientContract->repaymentPattern->accountTransferAgency->name,
            $data['transfer_date'],
            $customer->clientContract->account_transfer_code,
            $bankAccount->bank_code,
            $bankAccount->branch_code,
            $bankAccount->account_type,
            $bankAccount->account_number,
            $bankAccount->account_name_kana,
            $data['customer_total'],
        ];
        fputcsv($fp, $row);
    }
}
fclose($fp);