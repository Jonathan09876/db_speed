<?php

namespace app\controllers;

use Yii;
use app\models\Customer;
use app\models\CustomerSearch;
use yii\web\Controller;

class CustomerController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionInfo()
    {
        $model = new CustomerSearch();
        $query = Customer::find()->alias('c')
            ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
            ->innerJoin('client_corporation clc', 'cc.client_corporation_id=clc.client_corporation_id')
            ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id')
            ->innerJoin('repayment_type rt', 'rp.repayment_type_id=rt.repayment_type_id')
            ->innerJoin('account_transfer_agency ta', 'rp.account_transfer_agency_id=ta.account_transfer_agency_id');
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            return $this->asJson($query
                ->select(['c.customer_id', 'c.customer_code', 'IF(c.use_transfer_name=1, c.transfer_name, c.name) as `name`', 'c.bank_account_id', 'rt.type', 'ta.name as agency', 'clc.shorten_name'])
                ->andWhere(['cc.client_corporation_id' => (int)$model->client_corporation_id])
                ->andFilterWhere(['like', 'customer_code', mb_convert_kana($model->customer_code, "as", 'UTF-8')])
                ->andFilterWhere(['like', 'IF(c.use_transfer_name, c.transfer_name, c.name)', $model->name])
                ->asArray()->all());
        }
        return [];
    }
}