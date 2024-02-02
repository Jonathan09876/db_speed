<?php
/**
 * @var $this \yii\web\View;
 * @var $searchModel \app\models\RepaymentPatternSearch;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\bootstrap5\Html;
use app\models\RepaymentPattern;

$this->title = '回収条件マスタ管理';
?>
<section id="repayment-patterns">
    <div class="container">
        <div class="row mb-2">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center g-3">
                            <div class="col-md-3">
                                <h5 class="card-title mb-0">回収条件検索</h5>
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
                        <div class="row">
                            <div class="col-md-12">
                                <?= $form->field($searchModel, 'name') ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($searchModel, 'target_month')->dropDownList(RepaymentPattern::$target_months, ['prompt' => '対象月を選択']) ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($searchModel, 'transfer_date', ['labelOptions' => ['class' => 'form-label col-md-3']]) ?>
                            </div>
                        </div>
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
                        <h5 class="card-title mb-0">回収条件一覧</h5>
                    </div>
                    <div class="col-md-auto ms-auto">
                        <?= Html::a('新規回収条件登録', ['/aas/repayment-pattern'], ['class' => 'btn btn-success']) ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        [
                            'header' => '回収先',
                            'attribute' => 'accountTransferAgency.name'
                        ],
                        [
                            'attribute' => 'name'
                        ],
                        [
                            'header' => '回収タイミング',
                            'content' => function($data){
                                return RepaymentPattern::$target_months[$data->target_month].($data->transfer_date == 31 ? ' 末日' : " {$data->transfer_date}日");
                            }
                        ],
                        [
                            'header' => '基本回収区分',
                            'attribute' => 'repaymentType.type',
                        ],
                        [
                            'attribute' => 'bg_color',
                            'content' => function($data){
                                if (!empty($data->bg_color)) {
                                    return Html::tag('span', $data->bg_color, ['class' => 'color-tip', 'style' => 'background-color:'.$data->bg_color]);
                                }
                                return false;
                            },
                        ],
                        [
                            'content' => function($data, $key){
                                return Html::a('<i class="ri-pencil-line"></i>', ['/aas/repayment-pattern', 'id' => $key], ['class' => 'btn btn-sm btn-success me-1']) .
                                    Html::a('<i class="ri-delete-bin-2-line"></i>', ['/aas/remove-repayment-pattern', 'id' => $key], ['class' => 'btn btn-sm btn-danger']);
                            }
                        ]
                    ],
                ]) ?>
            </div>
        </div>
    </div>
</section>
