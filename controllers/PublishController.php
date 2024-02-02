<?php

namespace app\controllers;

use Yii;
use app\components\FormPublisher;
use app\models\ScheduleSearch;

class PublishController extends \yii\web\Controller
{
    public function actionTest()
    {
        header("Content-Type: application/pdf");
        readfile((new FormPublisher([
            'searchModel' => new ScheduleSearch([]),
            'template' => 'test',
        ]))->generatePDF());
    }

    public function actionDelinquenciesPreview()
    {
        set_time_limit(0);
        $session = Yii::$app->session;
        $searchModel = new ScheduleSearch([]);
        $params = $session['schedule_search_params'];
        return (new FormPublisher([
            'searchModel' => $searchModel,
            'template' => $params['ScheduleSearch']['group_by_customer'] ? 'delinquencies-alternative' : 'delinquencies-new',
        ]))->preview();
    }

    public function actionDelinquencies()
    {
        set_time_limit(0);
        header("Content-Type: application/pdf");
        $session = Yii::$app->session;
        $searchModel = new ScheduleSearch([]);
        $params = $session['schedule_search_params'];
        readfile((new FormPublisher([
            'searchModel' => $searchModel,
            'template' => $params['ScheduleSearch']['group_by_customer'] ? 'delinquencies-alternative' : 'delinquencies-new',
        ]))->generatePDF());
    }

    public function actionCollectionSchedulesPreview()
    {
        set_time_limit(0);
        $searchModel = new ScheduleSearch([]);
        return (new FormPublisher([
            'searchModel' => $searchModel,
            'template' => 'collection-schedules-new',
        ]))->preview();
    }

    public function actionCollectionSchedules()
    {
        set_time_limit(0);
        header("Content-Type: application/pdf");
        $searchModel = new ScheduleSearch([]);
        readfile((new FormPublisher([
            'searchModel' => $searchModel,
            'template' => 'collection-schedules-new',
        ]))->generatePDF());
    }

    public function actionDebtCollectionByCustomerPreview()
    {
        set_time_limit(0);
        $searchModel = new ScheduleSearch([]);
        return (new FormPublisher([
            'searchModel' => $searchModel,
            'template' => 'debt-collection-by-customer',
        ]))->preview();
    }

    public function actionDebtCollectionByCustomer()
    {
        set_time_limit(0);
        header("Content-Type: application/pdf");
        $searchModel = new ScheduleSearch([]);
        readfile((new FormPublisher([
            'searchModel' => $searchModel,
            'template' => 'debt-collection-by-customer',
        ]))->generatePDF());
    }

    public function actionDebtCollectionByAgency()
    {
        set_time_limit(0);
        header("Content-Type: application/pdf");
        $searchModel = new ScheduleSearch([]);
        readfile((new FormPublisher([
            'searchModel' => $searchModel,
            'template' => 'debt-collection-by-agency',
        ]))->generatePDF());
    }

    public function actionCollectionDataPreview()
    {
        set_time_limit(0);
        $searchModel = new ScheduleSearch([]);
        return (new FormPublisher([
            'searchModel' => $searchModel,
            'template' => 'collection-data',
        ]))->preview();
    }

    public function actionCollectionData()
    {
        set_time_limit(0);
        header("Content-Type: application/pdf");
        $searchModel = new ScheduleSearch([]);
        readfile((new FormPublisher([
            'searchModel' => $searchModel,
            'template' => 'collection-data',
        ]))->generatePDF());
    }
}