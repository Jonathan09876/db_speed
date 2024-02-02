<?php

namespace app\controllers;

use app\models\MonthlyCharge;
use app\models\Repayment;

class RegisterController extends \yii\web\Controller
{
    public $enableCsrfValidation = false;

    public function actionGetRegistrationForm($id)
    {
        $monthlyCharge = MonthlyCharge::findOne($id);
        $model = new Repayment();
        $tag = $this->renderPartial('registration-form', compact("model", "monthlyCharge"));
        return $this->asJson(compact("tag"));
    }

    public function actionRepayment($id, $date)
    {
        $monthlyCharge = MonthlyCharge::findOne($id);
        $repayemntPattern = $monthlyCharge->contractDetail->leaseContract->customer->clientContract->repaymentPattern;
        $term = new \DateTime($date);
        // if ($repayemntPattern->target_month == 'next') {
        //     $term = $term->modify('+1 month');
        // }
        $format = $repayemntPattern->transfer_date == 31 ? 'Y-m-t' : "Y-m-{$repayemntPattern->transfer_date}";
        if ($monthlyCharge->getRepayments()->count() == 0) {
            $model = new Repayment([
                'monthly_charge_id' => $monthlyCharge->monthly_charge_id,
                'registered' => date('Y-m-d H:i:s'),
                'processed' => $term->format('Y-m-d'),
            ]);
        }
        else {
            $model = $monthlyCharge->repayments[0];
        }
        $success = false;
        if ($model->load(\Yii::$app->request->post()) && $model->validate()) {
            $success = $model->save();
            $errors = $model->errors;
        }
        else {
            $success = false;
            $errors = $model->errors;
        }
        return $this->asJson(compact("success", "errors"));
    }
}