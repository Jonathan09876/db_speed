<?php

namespace app\controllers;

use app\models\ContractPattern;
use app\models\Repayment;
use Yii;
use yii\bootstrap5\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\web\Controller;
use app\models\ScheduleSearch;
use app\models\TargetTermMonthlyChargeStored;

class CollectionController extends Controller
{
    public function actionSchedules()
    {
       
        set_time_limit(300);
        $session = Yii::$app->session;
        $searchModel = new ScheduleSearch([
            'target_term_year' => date('Y'),
            'client_corporation_id' => Yii::$app->user->identity->client_corporation_id,
        ]);
        $searchModel->updateSession();
        $params = Yii::$app->request->isPost ? Yii::$app->request->post() : (Yii::$app->request->get('page') || Yii::$app->request->get('sort') || Yii::$app->request->get('span') || Yii::$app->request->get('bulk') || Yii::$app->request->isAjax ? $session['schedule_search_params'] : []);
       
        if ($span = Yii::$app->request->get('span', false)) {
            $params['ScheduleSearch']['span'] = $span;
        }
        $when = null;
        if (isset($params['ScheduleSearch']['store_collection_data'])) {
            TargetTermMonthlyChargeStored::register($params);
            //return $this->redirect(['/aas/stored-collection-data']);
        }
        if (isset($params['ScheduleSearch']['calc_collection_data'])) {
            $when = 'calc_collection_data';
        }
        else if (isset($params['ScheduleSearch']['credit_debt_collection_by_agency'])) {
            $when = 'credit_debt_collection_by_agency';
        }
        else if (isset($params['ScheduleSearch']['credit_debt_collection_by_customer'])) {
            $when = 'credit_debt_collection_by_customer';
        }
        else if (isset($params['ScheduleSearch']['delinquencies-recent'])) {
            $when = 'delinquencies-recent';
        }
        else if (isset($params['ScheduleSearch']['delinquencies'])) {
            $when = 'delinquencies';
        }
        $dataProvider = $searchModel->search($params, $when);
        echo $dataProvider->pagination->pageCount;
        if ($when == 'calc_collection_data') {
            return $this->render('calc-collection-data', compact("searchModel", "dataProvider"));
        }
        else if ($when == 'credit_debt_collection_by_agency') {
            return $this->render('credit-debt-collection-by-agency', compact("searchModel", "dataProvider"));
        }
        else if ($when == 'credit_debt_collection_by_customer') {
            return $this->render('credit-debt-collection-by-customer', compact("searchModel", "dataProvider"));
        }
        else if ($when == 'delinquencies') {
            $template = $searchModel->group_by_customer ? 'delinquencies-alternative' : 'delinquencies-new';
            return $this->render($template, compact("searchModel", "dataProvider"));
        }
        /*
        else if ($when == 'delinquencies-recent') {
            return $this->render('delinquencies', compact("searchModel", "dataProvider"));
        }
        */
        else {
            return $this->render('schedules', compact("searchModel", "dataProvider"));
        }
    }

    public function actionCalcData()
    {
        set_time_limit(300);
        $session = Yii::$app->session;
        $searchModel = new ScheduleSearch([
            'target_term_year' => date('Y'),
            'client_corporation_id' => Yii::$app->user->identity->client_corporation_id,
        ]);
        $searchModel->updateSession();
        $params = Yii::$app->request->isPost ? Yii::$app->request->post() : (Yii::$app->request->get('page') || Yii::$app->request->get('sort') || Yii::$app->request->get('span') || Yii::$app->request->get('bulk') || Yii::$app->request->isAjax ? $session['schedule_search_params'] : []);
        if ($span = Yii::$app->request->get('span', false)) {
            $params['ScheduleSearch']['span'] = $span;
        }
        if (isset($params['ScheduleSearch']['store_collection_data'])) {
            TargetTermMonthlyChargeStored::register($params);
            //return $this->redirect(['/aas/stored-collection-data']);
        }
        $when = 'calc_collection_data';

        $dataProvider = $searchModel->search($params, $when);

        return $this->render('calc-collection-data', compact("searchModel", "dataProvider"));
    }

    public function actionDelinquencies()
    {
        set_time_limit(300);
        $session = Yii::$app->session;
        $searchModel = new ScheduleSearch([
            'target_term_year' => date('Y'),
            'client_corporation_id' => Yii::$app->user->identity->client_corporation_id,
        ]);
        $searchModel->updateSession();
        $params = Yii::$app->request->isPost ? Yii::$app->request->post() : (Yii::$app->request->get('page') || Yii::$app->request->get('sort') || Yii::$app->request->get('span') || Yii::$app->request->get('bulk') || Yii::$app->request->isAjax ? $session['schedule_search_params'] : []);
        if ($span = Yii::$app->request->get('span', false)) {
            $params['ScheduleSearch']['span'] = $span;
        }
        $when = 'delinquencies';

        $dataProvider = $searchModel->search($params, $when);

        $template = $searchModel->group_by_customer ? 'delinquencies-alternative' : 'delinquencies-new';
        return $this->render($template, compact("searchModel", "dataProvider"));
    }

    public function actionExportDelinquencies()
    {
        set_time_limit(300);
        $session = Yii::$app->session;
        $searchModel = new ScheduleSearch([
            'target_term_year' => date('Y'),
            'client_corporation_id' => Yii::$app->user->identity->client_corporation_id,
        ]);
        $searchModel->updateSession();
        $params = $session['schedule_search_params'];
        $when = 'delinquencies';

        $dataProvider = $searchModel->search($params, $when);

        $template = $searchModel->group_by_customer ? 'export-delinquencies-alternative' : 'export-delinquencies-new';
        return $this->renderPartial($template, compact("searchModel", "dataProvider"));
    }

    public function actionExportSchedules()
    {
        set_time_limit(300);
        $session = Yii::$app->session;
        $searchModel = new ScheduleSearch([
            'target_term_year' => date('Y'),
            'client_corporation_id' => Yii::$app->user->identity->client_corporation_id,
        ]);
        $searchModel->updateSession();
        $params = $session['schedule_search_params'];
        $dataProvider = $searchModel->search($params, null);
        return $this->renderPartial('export-schedules', compact("searchModel", "dataProvider"));
    }

    public function actionRemoveStoredCollectionData($id)
    {
        $model = TargetTermMonthlyChargeStored::findOne($id);
        Yii::$app->db->createCommand()->delete('stored_monthly_charge', ['target_term_monthly_charge_stored_id' => $model->target_term_monthly_charge_stored_id])
            ->execute();
        Yii::$app->db->createCommand()->delete('stored_monthly_charge_repayment_registered', ['target_term_monthly_charge_stored_id' => $model->target_term_monthly_charge_stored_id])
            ->execute();
        $session = Yii::$app->session;
        $session['requery-stored-collection-data'] = [
            'target_term' => (new \DateTime($model->target_term))->format('Y年n月'),
            'client_corporation_id' => $model->client_corporation_id,
            'repayment_pattern_id' => $model->repayment_pattern_id,
        ];
        $model->delete();
        $this->redirect(['/collection/calc-data']);
    }


    public function actionUpdateContractPattern($id)
    {
        $patterns = ContractPattern::getContractPatterns($id);
        $model = new ScheduleSearch();
        return Html::activeDropDownList($model, 'contract_pattern_id', $patterns, ['class' => 'form-control', 'prompt' => '契約マスタ選択']);
    }

    public function actionUpdateSearchContractPattern($id)
    {
        $patterns = ContractPattern::getContractNamePatterns($id);
        $model = new ScheduleSearch();
        return Html::activeCheckboxList($model, 'contract_pattern_id', $patterns, ['inline' => true, 'item' => function($index, $label, $name, $checked, $value)use($id){
            return Html::tag('div', Html::checkbox($name, $checked, array_merge([
                'value' => $value,
                'label' => $label,
            ], ['id' => "item{$id}-{$index}", 'class' => 'form-check-input'])), [
                'class' => 'form-check form-check-inline'
            ]);
        }]);
    }

    public function actionExportDebtCollectionByCustomer()
    {
        set_time_limit(300);
        $session = Yii::$app->session;
        $searchModel = new ScheduleSearch([
            'target_term_year' => date('Y'),
            'client_corporation_id' => Yii::$app->user->identity->client_corporation_id,
        ]);
        $searchModel->updateSession();
        $params = $session['schedule_search_params'];
        $dataProvider = $searchModel->search($params, 'credit_debt_collection_by_customer');
        return $this->renderPartial('export-debt-collection-by-customer', compact("searchModel", "dataProvider"));
    }

    public function actionExportDebtCollectionByAgency()
    {
        set_time_limit(300);
        $session = Yii::$app->session;
        $searchModel = new ScheduleSearch([
            'target_term_year' => date('Y'),
            'client_corporation_id' => Yii::$app->user->identity->client_corporation_id,
        ]);
        $searchModel->updateSession();
        $params = $session['schedule_search_params'];
        $dataProvider = $searchModel->search($params, 'credit_debt_collection_by_agency');
        return $this->renderPartial('export-debt-collection-by-agency', compact("searchModel", "dataProvider"));
    }

    public function actionRepaymentMemo($id)
    {
        $repayment = Repayment::findOne($id);
        return $this->asJson([
            'id' => $repayment->repayment_id,
            'processed' => $repayment->processed,
            'memo' => $repayment->monthlyCharge->memo,
        ]);
    }

    public function actionExportCollectionData()
    {
        set_time_limit(300);
        $session = Yii::$app->session;
        $searchModel = new ScheduleSearch([
            'target_term_year' => date('Y'),
            'client_corporation_id' => Yii::$app->user->identity->client_corporation_id,
        ]);
        $searchModel->updateSession();
        $params = $session['schedule_search_params'];
        $dataProvider = $searchModel->search($params, 'calc_collection_data');
        return $this->renderPartial('export-collection-data', compact("searchModel", "dataProvider"));
    }
}