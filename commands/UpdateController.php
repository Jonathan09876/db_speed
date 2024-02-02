<?php

namespace app\commands;

use app\models\CollectionCell;
use app\models\ContractDetail;
use app\models\MonthlyCharge;
use app\models\Term;
use yii\helpers\VarDumper;

class UpdateController extends \yii\console\Controller
{
    public function actionTransferDate()
    {
        $query = MonthlyCharge::find()->alias('mc');
/*
            ->innerJoin('contract_detail cd', 'mc.contract_detail_id=cd.contract_detail_id')
            ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
            ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
            ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
            ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id');
*/
        /** @var  $monthlyCharge MonthlyCharge */
        foreach($query->each() as $monthlyCharge) {
            $repaymentPattern = $monthlyCharge->contractDetail->leaseContract->customer->clientContract->repaymentPattern;
            $term = new \DateTime($monthlyCharge->term);
            $format = $repaymentPattern->transfer_date == 31 ? 'Y-m-t' : "Y-m-{$repaymentPattern->transfer_date}";
            $monthlyCharge->transfer_date = $term->format($format);
            $monthlyCharge->save();
        }
    }

    public function actionContractDetail()
    {
        $query = ContractDetail::find();
        foreach($query->each() as $contractDetail) {
            $contractDetail->save();
            echo "{$contractDetail->leaseContract->contractNumber} updated.\n";
        }
    }

    public function actionCollectionCell($force = false, $from = 1)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        //\Yii::$app->db->createCommand('TRUNCATE `collection_cell`;')->execute();
        $query = ContractDetail::find()->alias('cd')->distinct()
            ->innerJoin('monthly_charge_span mcs', 'cd.contract_detail_id=mcs.contract_detail_id')
            ->innerJoin('monthly_payment_span mps', 'cd.contract_detail_id=mcs.contract_detail_id')
            ->select([
                'cd.contract_detail_id',
                'IF(mcs.first_term > mps.first_term, mps.first_term, mcs.first_term) as first_term',
                'IF(mcs.last_term > mps.last_term, mcs.last_term, mps.last_term) as last_term'
            ])
            ->where(['>', 'cd.contract_detail_id', $from])
            ->orderBy(['cd.contract_detail_id' =>SORT_ASC])
            ->asArray();
        $rows = $query->all();
        while($row = array_shift($rows)) {
            $terms = Term::find()
                ->where(['between', 'term', $row['first_term'], $row['last_term']])
                ->orderBy(['term' =>SORT_ASC])
                ->all();
            while($term = array_shift($terms)) {
                $instance = CollectionCell::getInstance($row['contract_detail_id'], $term->term_id, false);
                if ($force) {
                    $instance->updateContent();
                }
            }
            $size = memory_get_usage(true);
            echo "[" . @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i] . "] {$row['contract_detail_id']} processed.\n";
            unset($terms, $term, $instance);
        }
    }

    public function actionMonthlySpans()
    {
        $query = ContractDetail::find()->alias('cd')
            ->leftJoin('monthly_charge_span mcs', 'cd.contract_detail_id=mcs.contract_detail_id')
            ->leftJoin('monthly_payment_span mps', 'cd.contract_detail_id=mps.contract_detail_id')
            ->where(['or',['mcs.contract_detail_id' => null], ['mps.contract_detail_id' => null]]);
        foreach($query->each() as $detail) {
            $detail->updateSpans();
            "ID[{$detail->contract_detail_id}] processed.\n";
        }
    }
}