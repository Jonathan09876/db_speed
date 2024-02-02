<?php
/**
 * @var $this \yii\web\View;
 * @var $searchModel \app\models\RepaymentPatternSearch;
 */

use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\bootstrap5\Html;

$this->title = 'アカウント管理';
?>
<section id="repayment-patterns">
        <div class="row mb-2">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center g-3">
                            <div class="col-md-3">
                                <h5 class="card-title mb-0">アカウント検索</h5>
                            </div>
                        </div>
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
                        <?= $form->field($searchModel, 'name') ?>
                        <?= $form->field($searchModel, 'username') ?>
                        <?= $form->field($searchModel, 'client_corporation_id', ['inline' => true])->radioList(\app\models\ClientCorporation::getClientCorporations()) ?>
                    </div>
                    <div class="card-footer text-end">
                        <?= Html::submitButton('この内容で検索', ['class' => 'btn btn-primary']) ?>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <h5 class="card-title mb-0">アカウント一覧</h5>
                    </div>
                    <div class="col-md-auto ms-auto">
                        <?= Html::a('新規アカウント登録', ['/system/user'], ['class' => 'btn btn-success']) ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        [
                            'attribute' => 'name'
                        ],
                        [
                            'attribute' => 'username'
                        ],
                        [
                            'attribute' => 'clientCorporation.name',
                        ],
                        [
                            'content' => function($data, $key){
                                return Html::a('<i class="ri-pencil-line"></i>', ['/system/user', 'id' => $key], ['class' => 'btn btn-sm btn-success me-1']) .
                                    Html::a('<i class="ri-delete-bin-2-line"></i>', ['/system/remove-user', 'id' => $key], ['class' => 'btn btn-sm btn-danger']);
                            }
                        ]
                    ],
                ]) ?>
            </div>
        </div>
</section>
