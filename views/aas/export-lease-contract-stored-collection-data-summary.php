<?php
/**
 * @var $this \yii\web\View
 * @var $model \app\models\TargetTermMonthlyChargeStored;
 */

use app\models\LeaseContract;
use app\models\MonthlyChargeSearch;
use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\widgets\ListView;
use yii\bootstrap5\Html;
use app\widgets\datetimepicker\Datetimepicker;
use app\models\RepaymentPattern;
use kartik\typeahead\Typeahead;
use yii\helpers\Url;

$targetTerm = new \DateTime($model->target_term);
$lastTerm = (clone $targetTerm)->modify('-1 month');


$filename = "回収データ-{$targetTerm->format('Y年n月')}-".date('Ymdhis').".csv";

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
$query = \app\models\MonthlyCharge::find()->alias('mc')
    ->innerJoin('stored_monthly_charge smc', 'mc.monthly_charge_id=smc.monthly_charge_id')
    ->innerJoin('contract_detail cd', 'mc.contract_detail_id=cd.contract_detail_id')
    ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
    ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
    ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
    ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id')
    ->innerJoin('tax_application ta', 'cd.tax_application_id=ta.tax_application_id')
    ->where(['smc.target_term_monthly_charge_stored_id' => $model->target_term_monthly_charge_stored_id]);

$query->select([
    '`c`.`customer_id`',
    '`rp`.`repayment_pattern_id`',
    '`mc`.`transfer_date`',
    $sql,
])
    ->groupBy(['c.customer_id', 'mc.transfer_date']);
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