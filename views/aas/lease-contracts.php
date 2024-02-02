<?php
/**
 * @var $this \yii\web\View
 * @var $searchModel \app\models\LeaseContractSearch;
 * @var $customerSearchModel \app\models\CustomerSearch;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\bootstrap5\Html;
use app\widgets\datetimepicker\Datetimepicker;
use app\models\RepaymentPattern;
use kartik\typeahead\Typeahead;
use yii\helpers\Url;

$script = <<<EOS
$(document)
    .on('keyup', '.formatted', function(){
        $(this).format();
    })
    .on('click', '[name="LeaseContractSearch[client_corporation_id]"]', async function(){
        $('#leasecontractsearch-customer_code').val('');
        $('#leasecontractsearch-customer_name').val('');
        leasecontractsearch_customer_code_data_1.clear();
        leasecontractsearch_customer_code_data_1.clearRemoteCache();
        leasecontractsearch_customer_code_data_1.clearPrefetchCache();
        leasecontractsearch_customer_name_data_1.clear();
        leasecontractsearch_customer_name_data_1.clearRemoteCache();
        leasecontractsearch_customer_name_data_1.clearPrefetchCache();
        let selected = $('[name="LeaseContractSearch[client_corporation_id]"]:checked').val();
        let response = await $.ajax({
            method: 'get',
            url: '/aas/set-client-corporation?id='+selected,
            dataType: 'json'
        });
    })
    .on('typeahead:select', '#leasecontractsearch-customer_code', function(evt, selected){
        $('#leasecontractsearch-customer_id').val(selected.customer_id);
        $('#leasecontractsearch-customer_name').val(selected.name);
    })
    .on('typeahead:select', '#leasecontractsearch-customer_name', function(evt, selected){
        $('#leasecontractsearch-customer_id').val(selected.customer_id);
        $('#leasecontractsearch-customer_code').val(selected.customer_code);
    })

EOS;
$this->registerJs($script);
$style=<<<EOS
.col-first {
    -webkit-box-flex: 0;
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    width: 100px;
}
.hstack>.col-first {
    margin-right: -0.5rem
}
EOS;
$this->registerCss($style);
$this->title = '契約情報一覧';
?>
<section id="customers">
        <div class="row mb-2">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center g-3">
                            <div class="col-md-3">
                                <h5 class="card-title mb-0">契約情報検索</h5>
                            </div>
                        </div>
                    </div>
                    <?php $form = ActiveForm::begin([
                        'layout' => 'horizontal'
                    ]) ?>
                    <div class="card-body">
                        <div class="hstack gap-2 mb-3">
                            <label class="form-label col-first">リース期間</label>
                            <div class="col-md-2">
                                <?= Datetimepicker::widget([
                                    'id' => "term-start-at",
                                    'model' => $searchModel,
                                    'attribute' => 'term_from',
                                    'clientOptions' => [
                                        'locale' => 'ja',
                                        'format' => 'YYYY年M月',
                                        'viewMode' => 'months',
                                    ]
                                ]) ?>
                            </div>
                            <div class="col-autp">〜</div>
                            <div class="col-md-2">
                                <?= Datetimepicker::widget([
                                    'id' => "term-end-at",
                                    'model' => $searchModel,
                                    'attribute' => 'term_to',
                                    'clientOptions' => [
                                        'locale' => 'ja',
                                        'format' => 'YYYY年M月',
                                        'viewMode' => 'months',
                                    ]
                                ]) ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <?php $children = Yii::$app->user->identity->clientCorporation->clientCorporationChildren; ?>
                                <?= $form->field($searchModel, 'client_corporation_id', ['inline' => true, 'horizontalCssClasses' => [
                                    'label' => 'form-label col-first',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-auto',
                                ],])->radioList(\yii\helpers\ArrayHelper::map($children, 'client_corporation_id', 'shorten_name')) ?>
                            </div>
                            <div class="col-auto">
                                <?= Html::activeHiddenInput($searchModel, 'customer_id') ?>
                                <?= $form->field($searchModel, 'customer_code', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-first',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-auto',
                                ],])->widget(Typeahead::class, [
                                    'options' => ['placeholder' => '得意先コードを入力'],
                                    'scrollable' => true,
                                    'pluginOptions' => ['hint' => false, 'highlight' => true],
                                    'dataset' => [
                                        [
                                            'datumTokenizer' => "Bloodhound.tokenizers.obj.whitespace('customer_code')",
                                            'display' => 'customer_code',
                                            'prefetch' => Url::to(['/aas/get-customers']),
                                            'remote' => [
                                                'url' => Url::to(['/aas/get-customers']) . '?q=%QUERY',
                                                'wildcard' => '%QUERY'
                                            ]
                                        ],
                                    ]
                                ]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($searchModel, 'customer_name', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-auto',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-md-6',
                                ],])->widget(Typeahead::class, [
                                    'options' => ['placeholder' => '得意先名入力'],
                                    'scrollable' => true,
                                    'pluginOptions' => ['hint' => false, 'highlight' => true],
                                    'dataset' => [
                                        [
                                            'datumTokenizer' => "Bloodhound.tokenizers.obj.whitespace('name')",
                                            'display' => 'name',
                                            'prefetch' => Url::to(['/aas/get-customers-by-name']),
                                            'remote' => [
                                                'url' => Url::to(['/aas/get-customers-by-name']) . '?q=%QUERY',
                                                'wildcard' => '%QUERY'
                                            ]
                                        ],
                                    ]
                                ]) ?>
                            </div>
                        </div>
                        <div class="hstack gap-2 mb-3">
                            <label class="form-label col-first">契約番号</label>
                            <div class="col-auto">
                                <?= Html::activeDropDownList($searchModel, 'contract_pattern_id', \app\models\ContractPattern::getContractPatterns(), ['class' => 'form-control', 'prompt' => '契約マスタ選択']) ?>
                            </div>
                            <div class="col-auto">
                                <?= Html::activeTextInput($searchModel, 'contract_number', ['class' => 'form-control']) ?>
                            </div>
                            -
                            <div class="col-auto">
                                <?= Html::activeTextInput($searchModel, 'contract_code', ['class' => 'form-control']) ?>
                            </div>
                            -
                            <div class="col-1">
                                <?= Html::activeTextInput($searchModel, 'contract_sub_code', ['class' => 'form-control']) ?>
                            </div>
                        </div>
                        <div class="row">
                            <?= $form->field($searchModel, 'target_word', ['horizontalCssClasses' => [
                                'label' => 'form-label col-first',
                                'offset' => 'col-sm-offset-2',
                                'wrapper' => 'col-sm-5',
                            ],]) ?>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <?= $form->field($searchModel, 'lease_servicer_id', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-first',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-sm-8',
                                ],])->dropDownList(\app\models\LeaseServicer::getServicers(), ['prompt' => 'リース会社選択']) ?>
                            </div>
                            <div class="col-md-4">
                                <?= $form->field($searchModel, 'repayment_pattern_id', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-first',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-sm-8',
                                ],])->dropDownList(RepaymentPattern::getPatterns(), ['prompt' => '支払条件を選択']) ?>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <?= $form->field($searchModel, "contract_type", ['inline' => true, 'horizontalCssClasses' => [
                                'label' => 'form-label col-first',
                                'offset' => 'col-sm-offset-2',
                                'wrapper' => 'col-sm-5',
                            ],])->radioList(['ordinary' => '通常リース', 'meintenance' => 'メンテナンスリース', ]) ?>
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
                        <h5 class="card-title mb-0">契約情報一覧</h5>
                    </div>
                    <div class="col-md-auto ms-auto">
                        <?= Html::a('新規契約情報登録', ['/aas/lease-contract'], ['class' => 'btn btn-success']) ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        [
                            'attribute' => 'customer.customer_code',
                        ],
                        [
                            'attribute' => 'customer.name',
                        ],
                        [
                            'header' => '契約番号',
                            'content' => function($data){
                                return $data->contractNumber;
                            },
                        ],
                        [
                            'attribute' => 'contractDetails.0.term_start_at',
                            'header' => 'リース開始',
                        ],
                        [
                            'attribute' => 'contractDetails.0.term_end_at',
                            'header' => '終了',
                        ],
                        [
                            'attribute' => 'contractDetails.0.term_months_count',
                            'header' => '回数',
                        ],
                        [
                            'content' => function($data, $key){
                                return Html::a('編集', ['/aas/lease-contract', 'id' => $key], ['class' => 'btn btn-sm btn-success me-1']) .
                                    Html::a('<i class="ri-delete-bin-2-line"></i>', ['/aas/remove-lease-contract', 'id' => $key], ['disabled' => 'disabled', 'class' => 'btn btn-sm btn-danger disabled']);
                            }
                        ]
                    ],
                ]) ?>
            </div>
        </div>
</section>

