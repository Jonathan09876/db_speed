<?php
/**
 * @var $this \yii\web\View;
 * @var $model \app\models\LeaseContract;
 * @var $targetModel \app\models\LeaseTarget;
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use kartik\date\DatePicker;
use app\models\ContractDetail;
use app\widgets\datetimepicker\Datetimepicker;
use kartik\typeahead\Typeahead;
use yii\helpers\Url;

$lcid = !$model->isNewRecord ? $model->lease_contract_id : 'null';
$script = <<<EOS
let imo = true, client_corp_id, timer, part, data, wrapper, list, listHeight, listShown = false, pos = 0, current, currentHeight;
function customerSelected(current) {
    $('#leasecontract-customer_id').val(current.data('id'));
    $('#leasecontract-customer_code').val(current.data('code'));
    $('#leasecontract-customer_name').val(current.data('name'));
    listShown = false;
    $('#customer-list').remove();
}
$(document)
    .on('click', '.btn-append-detail', function(){
        let index = $(this).data('index'), lcid = {$lcid}, btn = $(this);
        $.ajax({
            type: 'get',
            url: '/aas/contract-detail?index='+index+(lcid ? '&lcid='+lcid : ''),
            dataType: 'html',
        }).then(function(html){
            $(html).insertAfter(btn);
            btn.remove();
        });
    })
    .on('change', '#leasecontract-customer_client_corporation', function(){
        console.log('changed');
        $('#leasecontract-customer_id').val('');
        $('#leasecontract-customer_code').val('');
        $('#leasecontract-customer_name').val('');
    })
    .on('focus', '#leasecontract-customer_code,#leasecontract-customer_name', function(){
        if ($('[name="LeaseContractSearch[client_corporation_id]"]:checked').length == 0) {
            $(this).blur();
            alert('まず先に「会社」を選択してください。得意先コード、得意先名の検索には「会社」の指定が必要です。');
        }
        else {
            client_corp_id = $('[name="LeaseContractSearch[client_corporation_id]"]:checked').val();
        }
    })
    .on('keydown', '#leasecontract-customer_code,#leasecontract-customer_name', function(evt){
        if (evt.key == 'Escape' || evt.key == 'ArrowDown' || evt.key == 'ArrowUp' || evt.key == 'Enter') {
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
                if (evt.target.id == 'leasecontract-customer_code') {
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
                result.forEach(function(row){
                    list.append('<li class="list-group-item" data-id="'+row.customer_id+'" data-code="'+row.customer_code+'" data-name="'+row.name+'">['+row.customer_code+']'+row.name+'</li>');
                });
                $(evt.target).parents('.position-relative').append(list);
                list.addClass('show');
                listShown = true;
                listHeight = $(list).innerHeight();
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
EOS;
$this->registerJs($script);

$style=<<<EOS
.list-group.position-absolute {
    top:calc(100% - 1.5rem);
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
.col-2-narrow {
    -webkit-box-flex: 0;
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    width: 11.11111111%;
}
.col-2-wide {
    -webkit-box-flex: 0;
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    width: 22.22222222%;
}
EOS;
$this->registerCss($style);
$this->title = $model->isNewRecord ? '契約情報新規登録' : '契約情報編集';
?>
<section id="customer">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <h5 class="card-title mb-0"><?= $model->isNewRecord ? '契約情報新規登録' : '契約情報編集' ?></h5>
                    </div>
                </div>
            </div>
            <?php $form = ActiveForm::begin([]) ?>
            <div class="card-body">
                <div class="hstack gap-2 mb-3 position-relative">
                    <label class="col-auto form-label">会社</label>
                    <div class="col-2-narrow">
                        <?= Html::activeDropDownList($model, 'customer_client_corporation', \yii\helpers\ArrayHelper::map(Yii::$app->user->identity->clientCorporation->clientCorporationChildren, 'client_corporation_id', 'code'), ['class' => 'form-control form-select', 'prompt' => '会社コード選択']) ?>
                        <?= Html::error($model, 'customer_client_corporation') ?>
                    </div>
                    <label class="col-auto form-label">得意先コード</label>
                    <div class="col-2-narrow<?= $model->hasErrors('customer_id') ? ' is-invalid' : '' ?>">
                        <?= Html::activeHiddenInput($model, 'customer_id') ?>
                        <?= Html::activeTextInput($model, 'customer_code', ['class' => 'form-control', 'placeholder' => '得意先コードを入力']) ?>
                        <?= Html::error($model, 'customer_code') ?>
                    </div>
                    <label class="col-auto form-label">得意先名</label>
                    <div class="col-md-4">
                        <?= Html::activeTextInput($model, 'customer_name', ['class' => 'form-control', 'placeholder' => '得意先コードを入力']) ?>
                    </div>
                    <?= Html::error($model, 'customer_name') ?>
                </div>
                <div class="hstack gap-2 mb-3">
                    <label class="form-label col-auto">契約番号</label>
                    <div class="col-2-narrow">
                        <?= Html::activeDropDownList($model, 'contract_pattern_id', \app\models\ContractPattern::getContractPatterns(), ['class' => 'form-control form-select'.($model->hasErrors('contract_number_check') || $model->hasErrors('contract_pattern_id') ? ' is-invalid' : ''), 'prompt' => '契約マスタ選択']) ?>
                        <?= Html::error($model, 'contract_pattern_id') ?>
                    </div>
                    <div class="col-2-narrow">
                        <?= Html::activeTextInput($model, 'contract_number', ['class' => 'form-control'.($model->hasErrors('contract_number_check') || $model->hasErrors('contract_number') ? ' is-invalid' : '')]) ?>
                        <?= Html::error($model, 'contract_number') ?>
                    </div>
                    -
                    <div class="col-2-narrow">
                        <?= Html::activeTextInput($model, 'contract_code', ['class' => 'form-control'.($model->hasErrors('contract_number_check') || $model->hasErrors('contract_code') ? ' is-invalid' : '')]) ?>
                        <?= Html::error($model, 'contract_code') ?>
                    </div>
                    -
                    <div class="col-md-1">
                        <?= Html::activeTextInput($model, 'contract_sub_code', ['class' => 'form-control'.($model->hasErrors('contract_number_check') || $model->hasErrors('contract_sub_code') ? ' is-invalid' : '')]) ?>
                        <?= Html::error($model, 'contract_sub_code') ?>
                    </div>
                </div>
                <div id="contract_number_check" class="check-contract_number_check<?= $model->hasErrors('contract_number_check') ? ' is-invalid': '' ?>"></div>
                <?= Html::error($model, 'contract_number_check') ?>
                <div class="hstack gap-2 mb-3">
                    <label class="form-label col-auto">物件名</label>
                    <div class="col-md-2">
                        <?= Html::activeTextInput($targetModel, 'name', ['class' => 'form-control']) ?>
                        <?= Html::error($targetModel, 'name') ?>
                    </div>
                    <label class="form-label col-auto">登録ナンバー</label>
                    <div class="col-2-narrow">
                        <?= Html::activeTextInput($targetModel, 'registration_number', ['class' => 'form-control']) ?>
                        <?= Html::error($targetModel, 'registration_number') ?>
                    </div>
                    <label class="form-label col-auto">物件属性</label>
                    <div class="col-md-2">
                        <?= Html::activeTextInput($targetModel, 'attributes', ['class' => 'form-control']) ?>
                        <?= Html::error($targetModel, 'attributes') ?>
                    </div>
                    <label class="form-label col-auto">物件備考</label>
                    <div class="col-md-3">
                        <?= Html::activeTextarea($targetModel, 'memo', ['class' => 'form-control', 'rows' => 1]) ?>
                        <?= Html::error($targetModel, 'memo') ?>
                    </div>

                </div>
                <div class="hstack gap-2 mb-3">
                    <label class="form-label col-auto">引落口座</label>
                    <div id="row-bank-account-info" class="col-md-11">
                        <?php if (!$model->isNewRecord) : ?>
                        <?= $model->customer->bankAccount ?>
                        <?php else : ?>
                        <?php if ($model->customer_id) : ?>
                        <?php $customer = \app\models\Customer::findOne($model->customer_id);
                        if ($customer) {
                            echo $customer->bankAccount;
                        } ?>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hstack gap-2 mb-3">
                    <label class="form-label col-auto">契約日</label>
                    <div class="col-md-2">
                        <?= Datetimepicker::widget([
                            'model' => $model,
                            'attribute' => 'contract_date',
                            'clientOptions' => [
                                'locale' => 'ja',
                                'format' => 'YYYY-MM-DD',
                            ]
                        ]) ?>
                        <?= Html::error($model, 'contract_date') ?>
                    </div>
                </div>
                <div id="contract-datail-pane">
                    <?php $i = 0; foreach($details as $detailModel) : ?>
                    <?= $this->render('contract-detail', ['form' => $form, 'model' => $detailModel, 'index' => $i++]) ?>
                    <?php endforeach; ?>
                    <?= Html::button('リース詳細を追加', ['class' => 'btn btn-outline-success btn-append-detail', 'data-index' => $i]) ?>
                </div>
            </div>
            <div class="card-footer text-end">
                <?= Html::submitButton('この内容で' . ($model->isNewRecord ? '登録' : '更新'), ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
</section>

