<?php

namespace app\commands;

use app\models\ImportContractDetail;
use app\models\ImportForm;
use app\models\ImportRepayment;
use app\models\LeaseContract;
use app\models\MonthlyCharge;
use app\models\Repayment;
use app\models\RepaymentType;
use app\models\Term;

class ImportController extends \yii\console\Controller
{
    public function actionRegiseredRows()
    {
        $model = new ImportForm();
        $registered = $model->processImporting();
        print_r($registered);
    }

    public function actionCheck($id='030001-003')
    {
        $detailRow = ImportContractDetail::findOne($id);
        $query = ImportRepayment::find()->where([
            'import_contract_detail_id' => $detailRow->import_contract_detail_id
        ]);
        echo "\nR[{$detailRow->import_contract_detail_id}]";
        foreach($query->each() as $repaymentRow) {
            $repaymentType = RepaymentType::findOne(['type' => $repaymentRow->repayment_type, 'removed' => null]);
            if (!$repaymentType) {
                $repaymentType = new RepaymentType([
                    'type' => $repaymentRow->repayment_type,
                ]);
                $repaymentType->save();
                //throw new Exception('回収方法が特定出来ません。登録情報をご確認ください。');
            }
            $repaymentPocessed = new \DateTime($repaymentRow->processed);
            $repaymentRegistered = new \DateTime($repaymentRow->registered);
            $term = ($repaymentPocessed->format('Ym') < $repaymentRegistered->format('Ym')) ? $repaymentRegistered : $repaymentPocessed;
            if ($contract->repaymentPattern->target_month == 'next') {
                $term->modify('-1 month');
            }
            $monthlyCharge = MonthlyCharge::find()->alias('mc')
                ->leftJoin('repayment r', 'mc.monthly_charge_id=r.monthly_charge_id')
                ->where([
                    'mc.contract_detail_id' => $contractDetail->contract_detail_id,
                    'DATE_FORMAT(mc.term, "%Y%m")' => $term->format('Ym'),
                    'r.repayment_id' => null,
                ])->limit(1)->one();
            if (!$monthlyCharge) {
                echo "=";
                continue;
                //throw new Exception('関連する回収予定情報が登録されていません。'.json_encode($repaymentRow->attributes));
            }
            $repayment = new Repayment([
                'scenario' => 'import',
                'contract_detail_id' => $contractDetail->contract_detail_id,
                'monthly_charge_id' => $monthlyCharge->monthly_charge_id,
                'repayment_type_id' => $repaymentType->repayment_type_id,
                'repayment_amount' => $repaymentRow->repayment_amount,
                'processed' => $repaymentRow->registered,//$repaymentRow->processed,
                'registered' => $repaymentRow->registered,
            ]);
            if (!$repayment->save()) {
                echo "[SKIP:{$repayment->repaymentType->type}]";
                //throw new Exception('回収情報が登録出来ません。' . VarDumper::dumpAsString($repayment->errors));
            }
            else {
                $registered['repayment'] += 1;
            }
            echo "*";
        }
        echo "\n";
    }

    public function actionGenerateTerms($start = '2010-01-01', $to = '2099-12-01')
    {
        $start = new \DateTime($start);
        $to = new \DateTime($to);
        do {
            $instance = new Term([
                'term' => $start->format('Y-m-d')
            ]);
            if (!$instance->save()) {
                echo json_encode($instance->errors) . "\n";
            }
            $start = $start->modify('+1 month');
            echo $start->format('Y-m-d') . " generated.\n";
        } while ($start <= $to);
    }
}