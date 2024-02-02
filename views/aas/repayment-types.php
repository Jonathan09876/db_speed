<?php
/**
 * @var $this \yii\web\View;
 * @var $searchModel \app\models\RepaymentPatternSearch;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\bootstrap5\Html;
use yii\widgets\Pjax;

$this->title = '支払区分管理';
?>
<section id="repayment-patterns">
    <div class="container">
        <div class="row mb-2">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center g-3">
                            <div class="col-md-3">
                                <h5 class="card-title mb-0">支払区分検索</h5>
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
                        <?= $form->field($searchModel, 'type') ?>
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
                        <h5 class="card-title mb-0">支払区分一覧</h5>
                    </div>
                    <div class="col-md-auto ms-auto">
                        <?= Html::a('新規支払区分登録', ['/aas/repayment-type'], ['class' => 'btn btn-success']) ?>
                    </div>
                </div>
            </div>
            <?php Pjax::begin([
                'id' => 'pjax-grid-wrapper',
                'linkSelector' => '.btn-pjax',
                'enablePushState' => false,
                'enableReplaceState' => false,
                'options' => [
                    'class' => 'card-body'
                ],
                'timeout' => 3000,
            ]) ?>
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        [
                            'attribute' => 'type',
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
                                $btnUp = $data->disp_order > 1 ? Html::a('<i class="ri-arrow-up-line"></i>', ['/aas/rankup-repayment-type', 'id' => $key], ['class' => 'btn btn-sm btn-info me-1 btn-pjax']) : '';
                                $btnDown = $data->disp_order < \app\models\RepaymentType::find()->max('disp_order') ? Html::a('<i class="ri-arrow-down-line"></i>', ['/aas/rankdown-repayment-type', 'id' => $key], ['class' => 'btn btn-sm btn-info me-1 btn-pjax']) : '';
                                $btnRemove = $data->getRepayments()->count() > 0 ? '' : Html::a('<i class="ri-delete-bin-2-line"></i>', ['/aas/remove-repayment-type', 'id' => $key], ['class' => 'btn btn-sm btn-danger']);
                                return Html::a('<i class="ri-pencil-line"></i>', ['/aas/repayment-type', 'id' => $key], ['class' => 'btn btn-sm btn-success me-1']) .
                                    $btnUp .
                                    $btnDown .
                                    $btnRemove;
                            }
                        ]
                    ],
                ]) ?>
            <?php Pjax::end(); ?>
        </div>
    </div>
</section>
