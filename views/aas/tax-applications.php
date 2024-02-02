<?php
/**
 * @var $this \yii\web\View;
 * @var $searchModel \app\models\RepaymentPatternSearch;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\bootstrap5\Html;

$this->title = '税区分マスタ管理';
?>
<section id="repayment-patterns">
    <div class="container">
        <div class="row mb-2">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center g-3">
                            <div class="col-md-3">
                                <h5 class="card-title mb-0">税区分検索</h5>
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
                        <?= $form->field($searchModel, 'application_name') ?>
                        <?= $form->field($searchModel, 'tax_rate')->dropDownList(\app\models\ConsumptionTaxRate::getRates(), ['prompt' => '税率を選択']) ?>
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
                        <h5 class="card-title mb-0">税区分一覧</h5>
                    </div>
                    <div class="col-md-auto ms-auto">
                        <?= Html::a('新規税区分登録', ['/aas/tax-application'], ['class' => 'btn btn-success']) ?>
                    </div>
                </div>
            </div>
            <?php \yii\widgets\Pjax::begin([
                'id' => 'pjax-grid-wrapper',
                'options' => [
                    'class' => 'card-body'
                ],
                'linkSelector' => '.btn-pjax,th a',
                'timeout' => 3000
            ]); ?>
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        [
                            'attribute' => 'application_name',
                        ],
                        [
                            'header' => '税率(現時点)',
                            'content' => function($data){
                                if ($data->fixed == 1) {
                                    return ($data->tax_rate * 100) . '%(固定)';
                                }
                                return \app\models\ConsumptionTaxRate::getCurrentRate()->rateString.'(変動)';
                            }
                        ],
                        [
                            'content' => function($data, $key){
                                $btnUp = $data->disp_order > 1 ? Html::a('<i class="ri-arrow-up-line"></i>', ['/aas/rankup-tax-application', 'id' => $key], ['class' => 'btn btn-sm btn-info me-1 btn-pjax']) : '';
                                $btnDown = $data->disp_order < \app\models\TaxApplication::find()->max('disp_order') ? Html::a('<i class="ri-arrow-down-line"></i>', ['/aas/rankdown-tax-application', 'id' => $key], ['class' => 'btn btn-sm btn-info me-1 btn-pjax']) : '';
                                return Html::a('<i class="ri-pencil-line"></i>', ['/aas/tax-application', 'id' => $key], ['class' => 'btn btn-sm btn-success me-1']) .
                                    $btnUp .
                                    $btnDown .
                                    Html::a('<i class="ri-delete-bin-2-line"></i>', ['/aas/remove-tax-application', 'id' => $key], ['class' => 'btn btn-sm btn-danger']);
                            }
                        ]
                    ],
                ]) ?>
            <?php \yii\widgets\Pjax::end(); ?>
        </div>
    </div>
</section>
