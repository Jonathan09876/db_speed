<?php
/**
 * @var $this \yii\web\View
 * @var $searchModel \app\models\ScheduleSearch;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

$term = empty($searchModel->target_term) ? (new \DateTime())->format('Y年m月') : $searchModel->target_term;
$targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $term));
$lastTerm = (clone $targetTerm)->modify('-1 month');
$stored = \app\models\TargetTermMonthlyChargeStored::isStored($targetTerm->format('Y-m-d'), $searchModel->client_corporation_id, $searchModel->repayment_pattern_id);

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
    'COUNT(DISTINCT cd.contract_detail_id) AS `rowspan`',
    $sql,
])
    ->groupBy(['c.customer_id']);
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



$filename = sprintf("回収支払表-%s-%s.csv", $searchModel->clientCorporation->shorten_name, $searchModel->target_term);

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=$filename");

$fp = fopen('php://output', 'w');
stream_filter_prepend($fp,'convert.iconv.UTF-8/CP932//TRANSLIT', STREAM_FILTER_WRITE);
$header = [
    'CF', '支払方法', '顧客名', '契約No.', '税率', '登録年月日', '登録No.', 'リース期間(月数)', '回収', '先月回数', '先月回収予定額', '今月回数', '回収区分', '回収予定額', '増減', '会社別'
];
fputcsv($fp, $header);

$dataProvider->pagination = false;
foreach($dataProvider->models as $model) {
    $exporter = new \app\components\CollectionDataRowExporter([
        'model' => $model,
        'targetTerm' => $targetTerm,
        'lastTerm' => $lastTerm,
        'totals' => $totals,
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
    '前月合計',
    '',
    '口座振替合計',
    $lastChargeFurikaeTotal,
    '',
    '当月合計',
    $chargeFurikaeTotal,
    $diffFurikaeTotal,
    ''
];
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
    '振込合計',
    $lastChargeFurikomiTotal,
    '',
    '',
    $chargeFurikomiTotal,
    $diffFurikomiTotal,
    '',
];
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
    'その他合計',
    $lastChargeOthersTotal,
    '',
    '',
    $chargeOthersTotal,
    $diffOthersTotal,
    '',
];
$footer4 = [
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '回収額合計',
    $lastChargeTotal,
    '',
    '',
    $chargeTotal,
    $diffTotal,
    ''
];
fputcsv($fp, $footer1);
fputcsv($fp, $footer2);
fputcsv($fp, $footer3);
fputcsv($fp, $footer4);
fclose($fp);