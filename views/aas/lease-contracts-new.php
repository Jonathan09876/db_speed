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
use app\widgets\PageSizeLimitChanger;

$script = <<<EOS
let checked_status = $('[name="LeaseContractSearch[current_status]"]:checked').val(), imo = true, client_corp_id, timer, part, data, wrapper, list, listHeight, listShown = false, pos = 0, current, currentHeight;
function customerSelected(current) {
    $('#leasecontractsearch-customer_id').val(current.data('id'));
    $('#leasecontractsearch-customer_code').val(current.data('code'));
    $('#leasecontractsearch-customer_name').val(current.data('name'));
    listShown = false;
    $('#customer-list').remove();
}
$(document)
    .on('keyup', '.formatted', function(){
        $(this).format();
    })
    .on('change', '[name="LeaseContractSearch[client_corporation_id]"]', async function(){
        $('#leasecontractsearch-customer_id').val('');
        $('#leasecontractsearch-customer_code').val('');
        $('#leasecontractsearch-customer_name').val('');
        var id = $(this).val(), patterns = await $.get('/aas/update-contract-pattern?id='+id);
        $('#leasecontractsearch-contract_pattern_id').replaceWith(patterns);
    })
    .on('click', '.btn-clear-customer', function(){
        $('#leasecontractsearch-customer_id').val('');
        $('#leasecontractsearch-customer_code').val('');
        $('#leasecontractsearch-customer_name').val('');
    })
    .on('focus', '#leasecontractsearch-customer_code,#leasecontractsearch-customer_name', function(){
        if ($('[name="LeaseContractSearch[client_corporation_id]"]:checked').length == 0) {
            $(this).blur();
            alert('まず先に「会社」を選択してください。得意先コード、得意先名の検索には「会社」の指定が必要です。');
        }
        else {
            client_corp_id = $('[name="LeaseContractSearch[client_corporation_id]"]:checked').val();
        }
    })
    .on('keydown', '#leasecontractsearch-customer_code,#leasecontractsearch-customer_name', function(evt){
        if (listShown && (evt.key == 'Escape' || evt.key == 'ArrowDown' || evt.key == 'ArrowUp' || evt.key == 'Enter')) {
            imo = true;
            evt.preventDefault();
            if (listShown && evt.key == 'Escape') {
                listShown = false;
                $('#customer-list').remove();
            }
            if (listShown && evt.key == 'ArrowDown') {
                if ($('.list-group-item', list).length >= pos) {
                    pos++;
                }
                $('.list-group-item.active', list).removeClass('active');
                current = $('.list-group-item:nth-child('+pos+')');
                currentHeight = current.outerHeight();
                current.addClass('active');
                if ($(list).offset().top + listHeight < current.offset().top + currentHeight) {
                    $(list).scrollTop(currentHeight * pos - $(list).innerHeight());
                }
            }
            if (listShown && evt.key == 'ArrowUp') {
                if (pos > 1) {
                    pos--;
                }
                $('.list-group-item.active', list).removeClass('active');
                current = $('.list-group-item:nth-child('+pos+')');
                currentHeight = current.outerHeight();
                current.addClass('active');
                if ($(list).offset().top > current.offset().top) {
                    $(list).scrollTop(currentHeight * (pos - 1));
                }
            }
            if (listShown && evt.key == 'Enter' && !evt.originalEvent.isComposing) {
                if (current) {
                    customerSelected(current);
                }
            }
            return false;
        }
        else {
            if (timer) {
                clearTimeout(timer);
            }
            timer = setTimeout(async function(){
                part = $(evt.target).val();
                if (!part) return;
                wrapper = $(evt.target).parents('.position-relative');
                $('ul.list-group', wrapper).remove();
                list = $('<ul id="customer-list" class="collapse list-group overflow-auto position-absolute w-100"></ul>');
                data = {
                    "CustomerSearch[client_corporation_id]": client_corp_id,
                };
                if (evt.target.id == 'leasecontractsearch-customer_code') {
                    data['CustomerSearch[customer_code]'] = part;
                }
                else {
                    data['CustomerSearch[name]'] = part
                }
                result = await $.ajax({
                    method: 'post',
                    url: '/customer/info',
                    data: data,
                    dataType: 'json'
                })
                if (result.length > 0) {
                    result.forEach(function(row){
                        list.append('<li class="list-group-item" data-id="'+row.customer_id+'" data-code="'+row.customer_code+'" data-name="'+row.name+'">['+row.customer_code+']'+row.name+'</li>');
                    });
                    $(evt.target).parents('.position-relative').append(list);
                    list.addClass('show');
                    listShown = true;
                    listHeight = $(list).innerHeight();
                }
            }, 300);
        }
    })
    .on('mousemove', '#customer-list', function(){
        imo = false;
    })
    .on('mouseover', '#customer-list>.list-group-item', function(evt){
        if (imo) return;
        current = $(evt.target);
        if (!current.is('.active')) {
            $('#customer-list>.list-group-item.active').removeClass('active');
            current.addClass('active');
            pos = $('#customer-list>.list-group-item').index(evt.target);
        }
    })
    .on('click', ':not(#customer-list)', function(){
        if (listShown) {
            listShown = false;
            $('#customer-list').remove();
        }
    })
    .on('click', '#customer-list>.list-group-item', function(evt){
        current = $(evt.target);
        customerSelected(current);
    })
    .on('click', '.btn-pjax', async function(evt){
        evt.preventDefault();
        var response = await $.getJSON(this.href);
        $.pjax.reload('#pjax-grid-wrapper', {timeout: false});
        return false;
    })
    .on('click', '.btn-remove-contract', async function(evt){
        evt.preventDefault();
        if(confirm('この契約を削除します。よろしいですか？')) {
            var lcid = $(this).data('id'), response;
            response = await $.ajax({
                method: 'get',
                url: '/aas/remove-lease-contract?id='+lcid,
                dataType: 'json'
            });
            $.pjax.reload('#pjax-grid-wrapper', {timeout: false});
        }
        return false;
    })
EOS;
$this->registerJs($script);
$style=<<<EOS
.list-group.position-absolute {
    top:calc(100%);
    max-height: 200px;
    -webkit-box-shadow: 0 5px 10px rgba(30,32,37,.12);
    box-shadow: 0 5px 10px rgba(30,32,37,.12);
    -webkit-animation-name: DropDownSlide;
    animation-name: DropDownSlide;
    -webkit-animation-duration: .3s;
    animation-duration: .3s;
    -webkit-animation-fill-mode: both;
    animation-fill-mode: both;
    z-index: 1000;
}
.position-absolute .list-group-item {
    cursor:pointer
}
.col-first {
    -webkit-box-flex: 0;
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    width: 100px;
}
.hstack>.col-first {
    margin-right: -0.5rem
}
.btn-rankup, .btn-rankdown {
  --vz-btn-padding-y: 0;
  --vz-btn-padding-x: .25rem;
  --vz-btn-font-size: 1.2rem;
  line-height: .75;
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
                        <?php /*
                        <div class="row">
                            <div class="col-md-4">
                                <?= $form->field($searchModel, 'target_term', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-first',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-sm-5',
                                ],])->widget(Datetimepicker::class, ['clientOptions' => [
                                    'locale' => 'ja',
                                    'format' => 'YYYY年M月',
                                    'viewMode' => 'months',
                                ]]) ?>
                            </div>
                        </div>
                        */ ?>
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
                        </div>
                        <div class="row position-relative">
                            <div class="col-auto">
                                <?= Html::activeHiddenInput($searchModel, 'customer_id') ?>
                                <?= $form->field($searchModel, 'customer_code', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-first',
                                    'wrapper' => 'col-auto',
                                ],'inputOptions' => ['placeholder' => '得意先コードを入力', 'autocomplete'=>'off'],
                                    'template' => '{label}<div class="col-auto"><div class="input-group has-validation">{input}<button type="button" class="btn btn-outline-secondary btn-clear-customer"><i class="ri-close-line"></i></button>{error}</div></div>'
                                ]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($searchModel, 'customer_name', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-auto',
                                    'wrapper' => 'col-md-6',
                                ],'inputOptions' => ['placeholder' => '得意先名を入力', 'autocomplete'=>'off'],
                                    'template' => '{label}<div class="col-md-6"><div class="input-group has-validation">{input}<button type="button" class="btn btn-outline-secondary btn-clear-customer"><i class="ri-close-line"></i></button>{error}</div></div>'
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
                            ],])->checkboxList(\app\models\ContractDetail::$contractTypes) ?>
                        </div>
                        <div class="hstack gap-2 mb-3">
                            <label class="form-label col-first">登録状況</label>
                            <div class="col-auto">
                                <?= Html::activeCheckBoxList($searchModel, 'registration_incomplete', [1 => '契約情報未完', 0 => '契約情報完'], ['inline' => true]) ?>
                            </div>
                        </div>
                        <div class="hstack gap-2 mb-3">
                            <label class="form-label col-first">&nbsp;</label>
                            <div class="col-auto">
                                <?= Html::activeCheckBoxList($searchModel, 'collection_application_complete', [0 => '回収代行申請未完', 1 => '回収代行申請完'], ['inline' => true]) ?>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <?= $form->field($searchModel, "current_status", ['inline' => true, 'horizontalCssClasses' => [
                                'label' => 'form-label col-first',
                                'offset' => 'col-sm-offset-2',
                                'wrapper' => 'col-sm-8',
                            ],])->checkboxList(\app\models\LeaseContractStatusType::getTypes()) ?>
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
            <?php \yii\widgets\Pjax::begin([
                'id' => 'pjax-grid-wrapper',
                'linkSelector' => '.pagination a',
                'options' => [
                    'class' => 'card-body',
                ],
                'timeout' => false,
            ]) ?>
                <?php
                $widget = new PageSizeLimitChanger(['pjax_id' => 'pjax-grid-wrapper']);
                $dataProvider->pagination = $widget->pagination;
                ?>
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'layout' => $widget->layout,
                    'rowOptions' => function($data){
                        if ($data->currentStatus && $data->currentStatus->leaseContractStatusType->bg_color) {
                            return ['style' => 'background-color:'.$data->currentStatus->leaseContractStatusType->bg_color];
                        }
                    },
                    'columns' => [
                        [
                            'header' => '会社',
                            'attribute' => 'customer.clientContract.clientCorporation.shorten_name',
                        ],
                        [
                            'attribute' => 'customer.customer_code',
                        ],
                        [
                            'header' => '表示順',
                            'content' => function($data, $key){
                                $btnUp = Html::a('<i class=" ri-arrow-up-s-fill"></i>', ['/aas/rankup-lease-contract', 'id' => $key], ['class' => 'btn btn-sm btn-light btn-pjax btn-rankup' . ($data->disp_order > 1 ? '' : ' disabled')]);
                                $btnDown = Html::a('<i class=" ri-arrow-down-s-fill"></i>', ['/aas/rankdown-lease-contract', 'id' => $key], ['class' => 'btn btn-sm btn-light btn-pjax btn-rankdown' . ($data->disp_order < \app\models\LeaseContract::find()->max('disp_order') ? '' : ' disabled')]);
                                return '<div class="btn-group-vertical" role="group" aria-label="Vertical button group">' . $btnUp . $btnDown . '</div>';
                            },
                        ],
                        [
                            'attribute' => 'customer.name',
                            'content' => function($data){
                                return Html::a($data->customer->getName(), ['/aas/customer', 'id' => $data->customer_id]);
                            },
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
                            'header' => 'ステータス',
                            'content' => function($data){
                                return $data->currentStatus ? $data->currentStatus->leaseContractStatusType->type : '';
                            },
                        ],
                        [
                            'content' => function($data, $key){
                                $icon = $data->collection_application_complete ? '<span class="btn btn-sm ms-1 btn-info disabled"><i class="ri-file-edit-line"></i></span>' : '';
                                return Html::a('編集', ['/aas/lease-contract', 'id' => $key], ['class' => 'btn btn-sm btn-success me-1']) .
                                    Html::a('<i class="ri-delete-bin-2-line"></i>', ['/aas/remove-lease-contract', 'id' => $key], ['class' => 'btn btn-sm btn-danger btn-remove-contract', 'data-id' => $key])
                                    . $icon;
                            }
                        ]
                    ],
                ]) ?>
            <?php \yii\widgets\Pjax::end(); ?>
        </div>
</section>

