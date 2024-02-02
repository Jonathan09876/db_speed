<?php
/**
 * @var $this \yii\web\View;
 * @var $searchModel \app\models\RepaymentPatternSearch;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\bootstrap5\Html;

$this->title = '顧客マスタ一覧';
?>
<section id="customers">
        <div class="row mb-2">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center g-3">
                            <div class="col-md-3">
                                <h5 class="card-title mb-0">顧客マスタ検索</h5>
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
                        <?= $form->field($searchModel, 'customer_code') ?>
                        <?= $form->field($searchModel, 'name') ?>
                        <?= $form->field($searchModel, 'keyword') ?>
                    </div>
                    <div class="card-footer text-end">
                        <?= Html::submitButton('この内容で検索', ['class' => 'btn btn-primary']) ?>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
            <?php if (Yii::$app->session->hasFlash('register-customer')) : ?>
                <div class="alert alert-primary" role="alert">
                    <strong>顧客登録完了</strong> <?= Yii::$app->session->getFlash('register-customer') ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <h5 class="card-title mb-0">顧客マスタ一覧</h5>
                    </div>
                    <div class="col-md-auto ms-auto">
                        <?= Html::a('新規顧客情報登録', ['/aas/customer'], ['class' => 'btn btn-success']) ?>
                    </div>
                </div>
            </div>
            <?php \yii\widgets\Pjax::begin([
                'id' => 'pjax-grid-wrapper',
                'options' => [
                    'class' => 'card-body',
                ],
                'timeout' => 3000,
            ]) ?>
                <?php
                $widget = new \app\widgets\PageSizeLimitChanger(['pjax_id' => 'pjax-grid-wrapper']);
                $dataProvider->pagination = $widget->pagination;
                ?>
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'layout' => $widget->layout,
                    'columns' => [
                        [
                            'attribute' => 'clientContract.clientCorporation.code',
                        ],
                        [
                            'attribute' => 'customer_code',
                        ],
                        [
                            'attribute' => 'name',
                            'content' => function($data){
                                return $data->getName();
                            },
                        ],
                        [
                            'attribute' => 'bankAccount.account_name',
                        ],
                        [
                            'attribute' => 'bankAccount.bank_name',
                        ],
                        [
                            'attribute' => 'bankAccount.bank_code',
                        ],
                        [
                            'attribute' => 'bankAccount.branch_name',
                        ],
                        [
                            'attribute' => 'bankAccount.branch_code',
                        ],
                        [
                            'attribute' => 'bankAccount.account_number',
                        ],
                        [
                            'attribute' => 'memo',
                            'contentOptions' => ['style' => 'min-width:100px;text-overflow: ellipsis;'],
                        ],
                        [
                            'content' => function($data, $key){
                                return Html::a('<i class="ri-pencil-line"></i>', ['/aas/customer', 'id' => $key], ['class' => 'btn btn-sm btn-success me-1']) .
                                    Html::a('<i class="ri-delete-bin-2-line"></i>', ['/aas/remove-customer', 'id' => $key], ['disabled' => 'disabled', 'class' => 'btn btn-sm btn-danger disabled']);
                            }
                        ]
                    ],
                ]) ?>
            <?php \yii\widgets\Pjax::end(); ?>
        </div>
</section>
