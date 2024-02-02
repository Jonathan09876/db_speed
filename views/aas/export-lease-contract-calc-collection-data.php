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

$header_columns = ['No.', '契約No.', '引落名', '口座振替管理コード', '金融機関コード', '支店コード', '口座区分', '口座番号', '口座名義カナ', '回数', '口座振替日', '振替額'];
$fp = fopen('php://output', 'w');
stream_filter_prepend($fp,'convert.iconv.UTF-8/CP932//TRANSLIT', STREAM_FILTER_WRITE);
fputcsv($fp, $header_columns);
$count = 0;
foreach($dataProvider->models as $contract) {
    $customer = $contract->customer;
    $bankAccount = $customer->bankAccount;
    foreach ($contract->contractDetails as $detail) {
        $monthlyCharge = \app\models\MonthlyCharge::getSibling($detail->contract_detail_id, $targetTerm->format('Ym'), 0);
        $chargeAmount = $monthlyCharge ? (is_null($monthlyCharge->temporary_charge_amount) ? $monthlyCharge->charge_amount : $monthlyCharge->temporary_charge_amount) : 0;
        if ($chargeAmount == 0) {
            continue;
        }
        $row = [
            ++$count,
            $contract->contractNumber,
            $customer->use_transfer_name ? $customer->transfer_name : $customer->name,
            $customer->clientContract->account_transfer_code,
            $bankAccount->bank_code,
            $bankAccount->branch_code,
            $bankAccount->account_type,
            $bankAccount->account_number,
            $bankAccount->account_name_kana,
            $monthlyCharge->orderCount,
            $monthlyCharge->transfer_date,
            $chargeAmount,
        ];
        fputcsv($fp, $row);
    }
}
fclose($fp);