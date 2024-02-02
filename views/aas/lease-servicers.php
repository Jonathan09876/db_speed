<?php
/**
 * @var $this \yii\web\View;
 * @var $searchModel \app\models\LeaseServicerSearch;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\bootstrap5\Html;

$this->title = 'リース会社マスタ管理';
?>
<section id="lease-servicers">
    <div class="container">
        <div class="row mb-2">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <div class="col-md-3">
                            <h5 class="card-title mb-0">リース会社検索</h5>
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
                        <?= $form->field($searchModel, 'shorten_name') ?>
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
                        <h5 class="card-title mb-0">リース会社一覧</h5>
                    </div>
                    <div class="col-md-auto ms-auto">
                        <?= Html::a('新規リース会社登録', ['/aas/lease-servicer'], ['class' => 'btn btn-success']) ?>
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
                            'attribute' => 'shorten_name'
                        ],
                        [
                            'header' => '振込先銀行口座',
                            'content' => function($data){
                                return $data->bankAccount ? $data->bankAccount : '- 未設定 -';
                            }
                        ],
                        [
                            'attribute' => 'transfer_date',
                            'content' => function($data){
                                return $data->transfer_date ? "毎月{$data->transfer_date}日" : '- 未設定 -';
                            }
                        ],
                        [
                            'content' => function($data, $key){
                                return Html::a('<i class="ri-pencil-line"></i>', ['/aas/lease-servicer', 'id' => $key], ['class' => 'btn btn-sm btn-success me-1']) .
                                    Html::a('<i class="ri-delete-bin-2-line"></i>', ['/aas/remove-lease-servicer', 'id' => $key], ['class' => 'btn btn-sm btn-danger']);
                            }
                        ]
                    ],
                ]) ?>
            </div>
        </div>
    </div>
</section>
