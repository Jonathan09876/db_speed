<?php
/**
 * @var $this \yii\web\View
 * @var $model \app\models\TargetTermMonthlyChargeStored
 * @var $searchModel \app\models\RepaymentSearch
 * @var $monthlyCharge \app\models\MonthlyCharge
 * @var $index integer
 */

use yii\bootstrap5\ActiveForm;
use yii\widgets\ListView;
use yii\bootstrap5\Html;
use app\models\Repayment;
use app\models\RepaymentType;
use yii\bootstrap5\Modal;

$dataProvider = $searchModel->search(Yii::$app->request->post());

$sql = "SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    END) AS `customer_total`";
$query = clone $dataProvider->query;
$query->select([
    '`c`.`customer_id`',
    'GROUP_CONCAT(DISTINCT cd.contract_detail_id ORDER BY lc.disp_order ASC) AS cdids',
    'MIN(`cd`.`contract_detail_id`) AS `first_cdid`',
    'COUNT(DISTINCT mc.monthly_charge_id) AS `rowspan`',
    $sql,
])
    ->innerJoin('tax_application ta', 'cd.tax_application_id=ta.tax_application_id')
    ->groupBy(['c.customer_id']);
$totals = \yii\helpers\ArrayHelper::index($query->asArray()->all(), 'customer_id');


$targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $model->target_term));
$currentTerm = \app\models\Term::findOne(['term' => $targetTerm->format('Y-m-d')]);
$session = Yii::$app->session;
$filter_name = $session['filter-name'] ?? '';
$filter_code = $session['filter-code'] ?? '';
$widget = new \app\widgets\PageSizeLimitChanger(['pjax_id' => 'pjax-grid-wrapper']);
$dataProvider->pagination = $widget->pagination;
$lastMonthTerm = (clone $targetTerm)->modify('-1 month');
$lastTerm = \app\models\Term::findOne(['term' => $lastMonthTerm->format('Y-m-d')]);

echo $this->render('iv-register-stored-collection-data-alternative', ['model' => $monthlyCharge, 'selectedModel' => $model, 'targetTerm' => $targetTerm, 'dataProvider' => $dataProvider, 'totals' => $totals, 'index' => $index]);

