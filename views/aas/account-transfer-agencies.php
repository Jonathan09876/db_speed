<?php
/**
 * @var $this \yii\web\View;
 * @var $searchModel \app\models\AccountTransferAgencySearch;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\bootstrap5\Html;
$this->title = '回収先マスタ管理';
?>
<div class="row mb-2">
    <div class="col-8 offset-2">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">回収先検索</h5>
            </div>
            <?php $form = ActiveForm::begin([
                'layout' => 'horizontal',
                'fieldConfig' => [
                    'template' => "{label}\n{beginWrapper}\n{input}\n{hint}\n{error}\n{endWrapper}",
                    'horizontalCssClasses' => [
                        'label' => 'col-md-2',
                        'offset' => 'offset-md-2',
                        'wrapper' => 'col-md-8',
                        'error' => '',
                        'hint' => '',
                    ],
                    'labelOptions' => ['class' => 'form-label col-md-2'],
                ],
            ]) ?>
            <div class="card-body">
                <?= $form->field($searchModel, 'code') ?>
                <?= $form->field($searchModel, 'name') ?>
            </div>
            <div class="card-footer text-end">
                <?= Html::submitButton('この内容で検索', ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
<div class="card" id="contactList">
    <div class="card-header">
        <div class="row align-items-center g-3">
            <div class="col-md-3">
                <h5 class="card-title mb-0">回収先一覧</h5>
            </div>
            <div class="col-md-auto ms-auto">
                <?= Html::a('新規回収先登録', ['/aas/account-transfer-agency'], ['class' => 'btn btn-success']) ?>
            </div>
            <!--end col-->
        </div>
        <!--end row-->
    </div>
    <!--end card-header-->

    <div class="card-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => 'code'
                ],
                [
                    'attribute' => 'name'
                ],
                [
                    'attribute' => 'transfer_fee',
                    'contentOptions' => ['class' => 'text-end'],
                    'content' => function($data){
                        return number_format($data->transfer_fee) . '円';
                    }
                ],
                [
                    'attribute' => 'basic_charge',
                    'contentOptions' => ['class' => 'text-end'],
                    'content' => function($data){
                        return number_format($data->basic_charge) . '円';
                    }
                ],
                [
                    'attribute' => 'transfer_charge',
                    'contentOptions' => ['class' => 'text-end'],
                    'content' => function($data){
                        return number_format($data->transfer_charge) . '円';
                    }
                ],
                [
                    'attribute' => 'registration_date',
                    'content' => function($data){
                        return "毎月{$data->registration_date}日";
                    }
                ],
                [
                    'content' => function($data, $key){
                        return Html::a('<i class="ri-pencil-line"></i>', ['/aas/account-transfer-agency', 'id' => $key], ['class' => 'btn btn-sm btn-success me-1']) .
                            Html::a('<i class="ri-delete-bin-2-line"></i>', ['/aas/remove-account-transfer-agency', 'id' => $key], ['class' => 'btn btn-sm btn-danger']);
                    }
                ]
            ],
        ]) ?>
    </div>
</div>