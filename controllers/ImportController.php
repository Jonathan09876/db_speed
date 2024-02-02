<?php

namespace app\controllers;

use app\models\ImportContractDetail;
use app\models\ImportContractDetailForm;
use app\models\ImportCustomer;
use app\models\ImportForm;
use app\models\ImportLeaseContract;
use app\models\ImportLeaseContractForm;
use app\models\ImportPayment;
use app\models\ImportRepayment;
use app\models\ImportRepaymentForm;
use app\models\ImportPaymentForm;
use Yii;
use app\models\ImportCustomerForm;
use yii\helpers\VarDumper;

class ImportController extends \yii\web\Controller
{
    public function actionCustomers()
    {
        $model = new ImportCustomerForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($model->validate()) {
                $model->loadFile($model->import_file->tempName);
            }
            else {
                VarDumper::dump($model->errors, 10, 1);die();
            }
        }
        else {
            VarDumper::dump([$_REQUEST, $_FILES], 10, 1);die();
        }
        return $this->render('import-customers', compact("model"));
    }

    public function actionLeaseContracts()
    {
        $model = new ImportLeaseContractForm();
        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            if ($model->validate()) {
                $model->loadFile($model->import_file->tempName);
            }
            else {
                VarDumper::dump($model->errors, 10, 1);die();
            }
        }
        return $this->render('import-lease-contracts', compact("model"));
    }

    public function actionContractDetails()
    {
        $model = new ImportContractDetailForm();
        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            if ($model->validate()) {
                $model->loadFile($model->import_file->tempName);
            }
            else {
                VarDumper::dump($model->errors, 10, 1);die();
            }
        }
        return $this->render('import-lease-contracts', compact("model"));
    }

    public function actionRepayments()
    {
        $model = new ImportRepaymentForm();
        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            if ($model->validate()) {
                $model->loadFile($model->import_file->tempName);
            }
            else {
                VarDumper::dump($model->errors, 10, 1);die();
            }
        }
        return $this->render('import-repayments', compact("model"));
    }

    public function actionPayments()
    {
        $model = new ImportPaymentForm();
        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            if ($model->validate()) {
                $model->loadFile($model->import_file->tempName);
            }
            else {
                VarDumper::dump($model->errors, 10, 1);die();
            }
        }
        return $this->render('import-payments', compact("model"));
    }

    public function actionCsvFiles()
    {
        $model = new ImportForm();
        $registered_rows = [
            'customers' => ImportCustomer::find()->count(),
            'lease_contracts' => ImportLeaseContract::find()->count(),
            'contract_details' => ImportContractDetail::find()->count(),
            'repayments' => ImportRepayment::find()->count(),
            'payments' => ImportPayment::find()->count(),
        ];
        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            if ($model->validate()) {
                $registered_rows = $model->loadFiles();
            }
            else {
                VarDumper::dump($model->errors, 10, 1);die();
            }
        }
        return $this->render('import-csv-files', compact("model","registered_rows"));
    }

    public function actionRegisteredRows()
    {
        $model = new ImportForm();
        $registered = $model->processImporting();
        return $this->render('import-registered-row', compact("model", "registered"));
    }
}