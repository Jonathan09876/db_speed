<?php

namespace app\controllers;

use app\models\ImportContractDetail;
use app\models\ImportCustomer;
use app\models\ImportForm;
use app\models\ImportLeaseContract;
use app\models\ImportPayment;
use app\models\ImportPaymentUpdate;
use app\models\ImportRepayment;
use app\models\ImportRepaymentUpdate;
use app\models\LeaseContract;
use app\models\LeasePaymentUpdater;
use app\models\MonthlyCharge;
use app\models\MonthlyCharges;
use app\models\MonthlyPayment;
use app\models\MonthlyPayments;
use app\models\MonthlyPaymentUpdater;
use app\models\RegistrationFilterModel;
use app\models\Repayment;
use app\models\RepaymentUpdater;
use app\models\UpdateForm;
use Yii;
use yii\bootstrap5\Html;
use app\models\MonthlyChargeUpdater;
use app\widgets\datetimepicker\Datetimepicker;
use yii\helpers\VarDumper;

class UpdateController extends \yii\web\Controller
{
    public $enableCsrfValidation = false;

    function actionMonthlyCharge()
    {
        $model = new MonthlyChargeUpdater();
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post()) && $model->validate()) {
            return $this->renderAjax('updater', ['tag' => $model->updater]);
        }
    }

    public function actionMonthlyChargeValue()
    {
        $model = new MonthlyChargeUpdater();
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post()) && $model->validate()) {
            return $this->asJson($model->updateValue());
        }
    }

    function actionRepayment()
    {
        $model = new RepaymentUpdater();
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post()) && $model->validate()) {
            return $this->renderAjax('updater', ['tag' => $model->updater]);
        }
    }

    public function actionRepaymentValue()
    {
        $model = new RepaymentUpdater();
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post()) && $model->validate()) {
            return $this->asJson($model->updateValue());
        }
    }

    function actionMonthlyPayment()
    {
        $model = new MonthlyPaymentUpdater();
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post()) && $model->validate()) {
            return $this->renderAjax('updater', ['tag' => $model->updater]);
        }
    }

    public function actionMonthlyPaymentValue()
    {
        $model = new MonthlyPaymentUpdater();
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post()) && $model->validate()) {
            return $this->asJson($model->updateValue());
        }
    }

    function actionLeasePayment()
    {
        $model = new LeasePaymentUpdater();
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post()) && $model->validate()) {
            return $this->renderAjax('updater', ['tag' => $model->updater]);
        }
    }

    public function actionLeasePaymentValue()
    {
        $model = new LeasePaymentUpdater();
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post()) && $model->validate()) {
            return $this->asJson($model->updateValue());
        }
    }

    public function actionCalcTaxIncluded($type, $id, $amount)
    {
        switch($type) {
            case 'monthly_charge':
                $instance = MonthlyCharge::findOne($id);
                $method = $instance->contractDetail->fraction_processing_pattern;
                $term = $instance->transfer_date;
                $taxAppId = $instance->contractDetail->tax_application_id;
                break;
            case 'monthly_payment':
                $instance = MonthlyPayment::findOne($id);
                $method = $instance->contractDetail->fraction_processing_pattern;
                $term = $instance->payment_date;
                $taxAppId = $instance->contractDetail->tax_application_id;
        }
        $methods = [
            'floor' => 'FLOOR',
            'ceil' => 'CEIL',
            'roundup' => 'ROUND'
        ];
        $sql = "SELECT {$methods[$method]}(:amount * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE :term >= application_from AND :term <= IFNULL(application_to, '2099-12-31')) END)) FROM tax_application ta WHERE ta.tax_application_id=:id";
        $value = Yii::$app->db->createCommand($sql)->bindValues([
            ':amount' => (int)$amount,
            ':term' => $term,
            ':id' => $taxAppId,
        ])->queryScalar();
        return $this->asJson(compact("value"));
    }

    public function actionRegistrationFilter()
    {
        $model = new RegistrationFilterModel();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            return $this->asJson($model->updateFilter());
        }
        return $this->asJson(['success' => false]);
    }

    public function actionPageSizeLimit($size)
    {
        $session = Yii::$app->session;
        $session['page-size-limit'] = $size;
        return $this->asJson(['page-size-limit' => $size]);
    }

    public function actionDeletableRepayment($id)
    {
        $repayment = Repayment::findOne($id);
        if ($repayment) {
            $repayment->delete();
            return $this->asJson(['success' => true]);
        }
        else {
            return $this->asJson(['success' => false]);
        }
    }

    public function actionSkipThis($id)
    {
        $monthlyCharge = MonthlyCharge::findOne($id);
        if ($monthlyCharge && $monthlyCharge->isSlidable) {
            return $this->asJson(['success' => $monthlyCharge->slideTerm(1)]);
        }
        else {
            return $this->asJson(['success' => false]);
        }
    }

    public function actionUnskipThis($id)
    {
        $monthlyCharge = MonthlyCharge::findOne($id);
        if ($monthlyCharge) {
            return $this->asJson(['success' => $monthlyCharge->slideTerm(-1)]);
        }
        else {
            return $this->asJson(['success' => false]);
        }
    }

    public function actionBulk($cdid = null)
    {
        $model = new MonthlyCharges();
        if (isset($cdid)) {
            array_push($model->contract_detail_ids, $cdid);
        }
        else {
            $session = Yii::$app->session;
            if (isset($session['monthly-charges-checked-cdids'])) {
                $model->contract_detail_ids = $session['monthly-charges-checked-cdids'];
            }
        }
        if (Yii::$app->request->isPost && Yii::$app->request->isAjax) {
            $model->load(Yii::$app->request->post());
            if ($model->validate()) {
                $model->bulkUpdate($cdid != null);
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return ['success' => true];
            }
        }
        return $this->renderAjax('bulk', compact("model"));
    }

    public function actionGetChecker($id)
    {
        $session = Yii::$app->session;
        if (!isset($session['monthly-charges-checked-cdids'])) {
            $session['monthly-charges-checked-cdids'] = [];
        }
        $checked = in_array($id, $session['monthly-charges-checked-cdids']);
        return Html::tag('div', Html::checkbox('MonthlyCharges[contract_detail_ids][]', $checked, ['value' => $id]), ['class' => 'form-check']);
    }

    public function actionChecker($id, $checked)
    {
        $session = Yii::$app->session;
        $cdids = [];
        if (!isset($session['monthly-charges-checked-cdids'])) {
            $session['monthly-charges-checked-cdids'] = [];
        }
        else {
            $cdids = $session['monthly-charges-checked-cdids'];
        }
        if ($checked) {
            if (!in_array($id, $cdids)) {
                $cdids[] = $id;
            }
        }
        else {
            if (in_array($id, $cdids)) {
                $tmp = array_flip($cdids);
                unset($tmp[$id]);
                $cdids = array_keys($tmp);
            }
        }
        $session['monthly-charges-checked-cdids'] = $cdids;
        return $this->asJson($session['monthly-charges-checked-cdids']);
    }

    public function actionPaymentBulk($cdid = null)
    {
        $model = new MonthlyPayments();
        if (isset($cdid)) {
            array_push($model->contract_detail_ids, $cdid);
        }
        if (Yii::$app->request->isPost && Yii::$app->request->isAjax) {
            $model->load(Yii::$app->request->post());
            if ($model->validate()) {
                $model->bulkUpdate(true);
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return ['success' => true];
            }
        }
        return $this->renderAjax('payment-bulk', compact("model"));
    }

    public function actionLeaseContractMemo($id)
    {
        $model = LeaseContract::findOne($id);
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            if ($model->validate()) {
                $model->save();
                return $this->asJson([
                    'success' => true,
                    'memo' => $model->memo
                ]);
            }
            return $this->asJson([
                'success' => false,
                'errors' => $model->errors
            ]);
        }
        return $this->renderAjax('lease-contract-memo', compact("model"));

    }

    public function actionCsvFiles()
    {
        $model = new UpdateForm();
        $registered_rows = [
            'repayments' => ImportRepaymentUpdate::find()->count(),
            'payments' => ImportPaymentUpdate::find()->count(),
        ];
        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            if ($model->validate()) {
                $registered_rows = $model->loadFiles();
            }
            else {
                VarDumper::dump($model->errors, 10, 1);die();
            }
        }
        return $this->render('update-csv-files', compact("model","registered_rows"));
    }

}