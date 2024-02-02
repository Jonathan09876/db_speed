<?php
/**
 * @var $this \yii\web\View
 * @var $model \app\models\Customer;
 * @var $bankModel \app\models\BankAccount;
 * @var $locationModel \app\models\Location;
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\ArrayHelper;
use app\models\RepaymentPattern;
use app\models\AccountTransferAgency;

$this->title = $model->isNewRecord ? '顧客情報新規登録' : '顧客情報編集';

$script =<<<EOS
function postalcode(){
    let zipcode = $(this).prev('input[type="text"]').val().replace(/[^\d]+/, ''),
        address = $('[name="Location[address]"]');
    $.ajax({
        url: 'https://apis.postcode-jp.com/api/v5/postcodes/'+zipcode+'?key=67P8xZLNArThe1tqRxjwdYtXDnKeVZvTTSvzWdk',
        type: 'GET',
        dataType: 'jsonp',
        jsonpCallback: 'callback'
    }).done(function(json){
        if (json.length) {
            let result = json[0];
            address.val(result.allAddress);
        }
        else {
            alert('住所が見つかりません。');
        }
    })
    return false;
}
$(document)
    .on('focus', '#bankaccount-bank_name,#bankaccount-bank_code,#bankaccount-branch_name,#bankaccount-branch_code', function(){
        $(this).select();
    })
    .on('click', '.btn-address-search', postalcode)
    .on('click', '#btn-search-bank-name', async function(){
        let wrapper = $(this).parents('.position-relative');
        $('ul.list-group', wrapper).remove();
        let name = $(this).prev('input').val();
        let list = $('<ul id="bank-name-list" class="collapse list-group overflow-auto position-absolute w-100"></ul>');
        let searched = await $.getJSON('/aas/get-bank-name?name='+name);
        searched.forEach(function(row){
            list.append('<li class="list-group-item" data-code="'+row.bank_code+'">'+row.name+'</li>');
        });
        $(this).parents('.position-relative').append(list);
        list.addClass('show');
    })
    .on('click', '#bank-name-list .list-group-item', function(){
        let selected = $(this).text(), code = $(this).data('code');
        $('#bankaccount-bank_name').val(selected);
        $('#bankaccount-bank_code').val(code);
        $('#bank-name-list').remove();
    })
    .on('click', '#btn-search-bank-code', async function(){
        let wrapper = $(this).parents('.position-relative');
        $('ul.list-group', wrapper).remove();
        let code = $(this).prev('input').val();
        let list = $('<ul id="bank-name-list" class="collapse list-group overflow-auto position-absolute w-100"></ul>');
        let searched = await $.getJSON('/aas/get-bank-code?code='+code);
        searched.forEach(function(row){
            list.append('<li class="list-group-item" data-code="'+row.bank_code+'">'+row.name+'</li>');
        });
        $(this).parents('.position-relative').append(list);
        list.addClass('show');
    })
    .on('click', '#bank-name-list .list-group-item', function(){
        let selected = $(this).text(), code = $(this).data('code');
        $('#bankaccount-bank_name').val(selected);
        $('#bankaccount-bank_code').val(code);
        $('#bank-name-list').remove();
    })
    .on('click', '#btn-search-branch-name', async function(){
        let wrapper = $(this).parents('.position-relative');
        $('ul.list-group', wrapper).remove();
        let code = $('#bankaccount-bank_code').val();
        let name = $(this).prev('input').val();
        let list = $('<ul id="branch-name-list" class="collapse list-group overflow-auto position-absolute w-100"></ul>');
        let searched = await $.getJSON('/aas/get-branch-name?code='+code+'&name='+name);
        searched.forEach(function(row){
            list.append('<li class="list-group-item" data-code="'+row.branch_code+'">'+row.name+'</li>');
        });
        $(this).parents('.position-relative').append(list);
        list.addClass('show');
    })
    .on('click', '#branch-name-list .list-group-item', function(){
        let selected = $(this).text(), code = $(this).data('code');
        $('#bankaccount-branch_name').val(selected);
        $('#bankaccount-branch_code').val(code);
        $('#branch-name-list').remove();
    })
    .on('click', '#btn-search-branch-code', async function(){
        let wrapper = $(this).parents('.position-relative');
        $('ul.list-group', wrapper).remove();
        let code = $('#bankaccount-bank_code').val();
        let code2 = $(this).prev('input').val();
        let list = $('<ul id="branch-name-list" class="collapse list-group overflow-auto position-absolute w-100"></ul>');
        let searched = await $.getJSON('/aas/get-branch-code?code='+code+'&code2='+code2);
        searched.forEach(function(row){
            list.append('<li class="list-group-item" data-code="'+row.branch_code+'">'+row.name+'</li>');
        });
        $(this).parents('.position-relative').append(list);
        list.addClass('show');
    })
    .on('click', '#branch-name-list .list-group-item', function(){
        let selected = $(this).text(), code = $(this).data('code');
        $('#bankaccount-branch_name').val(selected);
        $('#bankaccount-branch_code').val(code);
        $('#branch-name-list').remove();
    })
    .on('click', '.btn-new-phone', function(){
        let wrapper = $(this).parent().parent().parent().parent();
        let index = wrapper.data('index') + 1;
        let element = '<div class="col-md-3" data-index="{{index}}">\\n' +
                   '    <div class="mb-3 row field-phone-number">\\n' +
                   '        <label class="col-auto form-label" for="phone-{{index}}-number">電話番号'+(index+1)+'</label>\\n' +
                   '        <div class="col-md-8" data-index="{{index}}">\\n' +
                   '            <div class="input-group"><input type="text" id="phone-{{index}}-number" name="Phone[{{index}}][number]" class="form-control" value="">\\n' +
                   '            <button type="button" class="col-auto btn btn-sm btn-outline-success btn-new-phone">追加</button></div>\\n' +
                   '        </div>\\n' +
                   '    </div>\\n' +
                   '</div>';
        let tag = element.replace(/{{index}}/g, String(index));
        $(this).remove();
        $(tag).insertAfter(wrapper);
    })
    .on('click', '.btn-new-mail_address', function(){
        let wrapper = $(this).parent().parent().parent().parent();
        let index = wrapper.data('index') + 1;
        let element = '<div class="col-md-6" data-index="{{index}}">\\n' +
                   '    <div class="mb-3 row field-mailaddress-mail_address">\\n' +
                   '        <label class="col-auto form-label" for="mailaddress-{{index}}-mail_address">メールアドレス'+(index+1)+'</label>\\n' +
                   '        <div class="col-md-9">\\n' +
                   '            <div class="input-group">\\n' +
                   '                <input type="text" id="mailaddress-{{index}}-mail_address" name="MailAddrss[{{index}}][mail_address]" class="form-control" value="">\\n' +
                   '                <button type="button" class="btn btn-sm btn-outline-success btn-new-mail_address">追加</button>\\n' +
                   '            </div>\\n' +
                   '        </div>\\n' +
                   '    </div>\\n' +
                   '</div>';
        let tag = element.replace(/{{index}}/g, String(index));
        $(this).remove();
        $(tag).insertAfter(wrapper);
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
.col-first {
    -webkit-box-flex: 0;
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    width: 115px;
}
.hstack>.col-first {
    margin-right: -0.5rem
}
EOS;
$this->registerCss($style);
?>
<section id="customer">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <h5 class="card-title mb-0"><?= $model->isNewRecord ? '顧客情報新規登録' : '顧客情報編集' ?></h5>
                    </div>
                </div>
            </div>
            <?php $form = ActiveForm::begin([
                    'layout' => 'horizontal',
            ]) ?>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <?= $form->field($contractModel, 'client_corporation_id', ['inline' => true, 'horizontalCssClasses' => [
                            'label' => 'col-first form-label',
                            'offset' => 'col-sm-offset-2',
                            'wrapper' => 'col-auto',
                        ],])->radioList(\app\models\ClientCorporation::getClientCorporations()) ?>
                    </div>
                    <div class="col-auto">
                        <?= $form->field($model, 'customer_code', ['horizontalCssClasses' => [
                            'label' => 'col-first form-label',
                            'wrapper' => 'col-auto',
                        ],]) ?>
                    </div>
                    <div class="col-md-5">
                        <?= $form->field($model, 'name', ['horizontalCssClasses' => [
                            'label' => 'col-auto form-label',
                            'offset' => 'col-sm-offset-2',
                            'wrapper' => 'col-md-10',
                        ],]) ?>
                    </div>
                    <div class="col-auto">
                        <?= $form->field($model, 'position', ['horizontalCssClasses' => [
                            'label' => 'col-auto form-label',
                            'offset' => 'col-sm-offset-2',
                            'wrapper' => 'col-auto',
                        ],]) ?>
                    </div>
                    <div class="col-md-5">
                        <?= $form->field($model, 'transfer_name', ['horizontalCssClasses' => [
                            'label' => 'col-first form-label',
                            'offset' => 'col-sm-offset-2',
                            'wrapper' => 'col-md-8',
                        ],]) ?>
                    </div>
                    <div class="col-md-7">
                        <?= $form->field($model, 'use_transfer_name', ['inline' => true, 'horizontalCssClasses' => [
                            'label' => 'col-auto form-label',
                            'offset' => 'col-sm-offset-2',
                            'wrapper' => 'col-sm-10',
                        ],])->radioList(['0' => '得意先名', '1' => '個人名']) ?>
                    </div>
                    <div class="col-md-3">
                        <?php $postalBtn = Html::button('検索', ['class' => 'btn btn-outline-success btn-address-search']) ?>
                        <?= $form->field($locationModel, 'zip_code', ['horizontalCssClasses' => [
                            'label' => 'col-first form-label',
                            'offset' => 'col-sm-offset-2',
                        ], 'template' => "{label}\n<div class=\"col-md-7\"><div class=\"input-group\">{input}{$postalBtn}</div></div>\n{hint}\n{error}"]) ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($locationModel, 'address', ['horizontalCssClasses' => [
                            'label' => 'col-auto form-label',
                            'offset' => 'col-sm-offset-2',
                            'wrapper' => 'col-md-10',
                        ],]) ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($locationModel, 'address_optional', ['horizontalCssClasses' => [
                            'label' => 'col-auto form-label',
                            'offset' => 'col-sm-offset-2',
                            'wrapper' => 'col-md-8',
                        ],]) ?>
                    </div>
                </div>
                <div class="row">
                <?php $registeredPhones = count($model->phones); $registeringPhones = count($model->phoneModels); ?>
                <?php $i = 0; if ($registeredPhones > 0) : foreach($model->phones as $phone) : ?>
                    <div class="col-md-3" data-index="<?= $i ?>">
                        <?= Html::hiddenInput("Phone[{$i}][phone_id]", $phone->phone_id) ?>
                        <div class="mb-3 row field-phone-number">
                            <label class="col-first form-label" for="phone-<?= $i ?>-number">電話番号<?= $i + 1 ?></label>
                            <div class="col-md-7" data-index="<?= $i ?>">
                                <div class="input-group">
                                    <?= Html::textInput("Phone[{$i}][number]", $phone->number, ['id' => "phone-{$i}-number", 'class' => 'form-control']) ?>
                                    <div class="invalid-feedback"><?= $phone->getFirstError('number') ?></div>
                                    <?php if ($registeringPhones == 0 && $registeredPhones == $i + 1) : ?>
                                        <?= Html::button('追加', ['class' => 'col-auto btn btn-sm btn-outline-success btn-new-phone']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $i++; endforeach; endif; ?>
                <?php if ($registeringPhones > 0) :foreach($model->phoneModels as $phoneModel) : ?>
                    <div class="col-md-3" data-index="<?= $i ?>">
                        <div class="mb-3 row field-phone-number">
                            <label class="col-first form-label" for="phone-<?= $i ?>-number">電話番号<?= $i + 1 ?></label>
                            <div class="col-md-7" data-index="<?= $i ?>">
                                <div class="input-group">
                                    <?= Html::textInput("Phone[{$i}][number]", $phoneModel->number, ['id' => "phone-{$i}-number", 'class' => 'form-control']) ?>
                                    <div class="invalid-feedback"><?= $phoneModel->getFirstError('number') ?></div>
                                    <?php if ($registeringPhones == $i +1) : ?>
                                        <?= Html::button('追加', ['class' => 'col-auto btn btn-sm btn-outline-success btn-new-phone']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $i++; endforeach; elseif ($registeredPhones == 0 && $registeringPhones == 0): ?>
                    <div class="col-md-3" data-index="<?= $i ?>">
                        <div class="mb-3 row field-phone-number">
                            <label class="col-first form-label" for="phone-<?= $i ?>-number">電話番号<?= $i + 1 ?></label>
                            <div class="col-md-7" data-index="<?= $i ?>">
                                <div class="input-group">
                                    <?= Html::textInput("Phone[{$i}][number]", '', ['id' => "phone-{$i}-number", 'class' => 'form-control']) ?>
                                    <?= Html::button('追加', ['class' => 'col-auto btn btn-sm btn-outline-success btn-new-phone']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                </div>
                <div class="row">
                <?php $registeredMailAddresses = count($model->mailAddresses); $registeringMailAddresses = count($model->mailAddressModels); ?>
                <?php $i = 0; if ($registeredMailAddresses > 0) : foreach($model->mailAddresses as $mailAddress) : ?>
                    <div class="col-md-6" data-index="<?= $i ?>">
                        <?= Html::hiddenInput("MailAddress[{$i}][mail_address_id]", $mailAddress->mail_address_id) ?>
                        <div class="mb-3 row field-mailaddress-mail_address">
                            <label class="col-first form-label" for="mailaddress-<?= $i ?>-mail_address">メールアドレス<?= $i + 1 ?></label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <?= Html::textInput("MailAddress[{$i}][mail_address]", $mailAddress->mail_address, ['id' => "mailaddress-{$i}-mail_address", 'class' => 'form-control']) ?>
                                    <?php if ($registeringMailAddresses == 0 && $registeredMailAddresses == $i + 1) : ?>
                                        <?= Html::button('別のメールアドレスを登録', ['class' => 'btn btn-sm btn-outline-success btn-new-mail_address']) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="invalid-feedback"><?= $mailAddress->getFirstError('mail_address') ?></div>
                            </div>
                        </div>
                    </div>
                    <?php $i++; endforeach; endif; ?>
                <?php if ($registeringMailAddresses > 0) :foreach($model->mailAddressModels as $mailAddressModel) : ?>
                    <div class="col-md-6" data-index="<?= $i ?>">
                        <div class="mb-3 row field-mailaddress-mail_address">
                            <label class="col-first form-label" for="mailaddress-<?= $i ?>-mail_address">メールアドレス<?= $i + 1 ?></label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <?= Html::textInput("MailAddress[{$i}][mail_address]", $mailAddressModel->mail_address, ['id' => "mailaddress-{$i}-mail_address", 'class' => 'form-control']) ?>
                                    <?php if ($registeringMailAddresses == $i + 1) : ?>
                                        <?= Html::button('別のメールアドレスを登録', ['class' => 'btn btn-sm btn-outline-success btn-new-mail_address']) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="invalid-feedback"><?= $mailAddressModel->getFirstError('mail_address') ?></div>
                            </div>
                        </div>
                    </div>
                    <?php $i++; endforeach; elseif ($registeredMailAddresses == 0 && $registeringMailAddresses == 0): ?>
                    <div class="col-md-6" data-index="<?= $i ?>">
                        <div class="mb-3 row field-mailaddress-mail_address">
                            <label class="col-first form-label" for="mailaddress-<?= $i ?>-mail_address">メールアドレス<?= $i + 1 ?></label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <?= Html::textInput("MailAddress[{$i}][mail_address]", '', ['id' => "mailaddress-{$i}-mail_address", 'class' => 'form-control']) ?>
                                    <?= Html::button('追加', ['class' => 'btn btn-sm btn-outline-success btn-new-mail_address']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <?= $form->field($contractModel, 'repayment_pattern_id', ['horizontalCssClasses' => [
                            'label' => 'form-label col-first',
                            'offset' => 'col-sm-offset-2',
                            'wrapper' => 'col-sm-7',
                        ],])->dropDownList(RepaymentPattern::getPatterns(), ['prompt' => '支払条件を選択']) ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($contractModel, 'account_transfer_code', ['horizontalCssClasses' => [
                            'label' => 'form-label col-auto',
                            'offset' => 'col-sm-offset-2',
                            'wrapper' => 'col-sm-8',
                        ],]) ?>
                    </div>
                </div>
                <div class="hstack gap-2">
                    <div class="col-suto position-relative">
                        <?= $form->field($bankModel, 'bank_name', ['horizontalCssClasses' => [
                            'label' => 'form-label col-first',
                        ],'template' => '{label}<div class="col-md-8"><div class="input-group">{input}<button type="button" class="btn btn-secondary" id="btn-search-bank-name"><i class="ri-search-line"></i></button>{error}</div></div>', 'inputOptions' => ['class' => 'form-control']]) ?>
                    </div>
                    <div class="col-suto position-relative">
                        <?= $form->field($bankModel, 'bank_code', ['horizontalCssClasses' => [
                            'label' => 'form-label col-auto',
                        ],'template' => '{label}<div class="col-md-8"><div class="input-group">{input}<button type="button" class="btn btn-secondary" id="btn-search-bank-code"><i class="ri-search-line"></i></button>{error}</div></div>', 'inputOptions' => ['class' => 'form-control']])->label('コード') ?>
                    </div>
                    <div class="col-auto position-relative">
                        <?= $form->field($bankModel, 'branch_name', ['horizontalCssClasses' => [
                            'label' => 'form-label col-auto',
                        ],'template' => '{label}<div class="col-md-8"><div class="input-group">{input}<button type="button" class="btn btn-secondary" id="btn-search-branch-name"><i class="ri-search-line"></i></button>{error}</div></div>', 'inputOptions' => ['class' => 'form-control']]) ?>
                    </div>
                    <div class="col-auto position-relative">
                        <?= $form->field($bankModel, 'branch_code', ['horizontalCssClasses' => [
                            'label' => 'form-label col-auto',
                        ],'template' => '{label}<div class="col-md-8"><div class="input-group">{input}<button type="button" class="btn btn-secondary" id="btn-search-branch-code"><i class="ri-search-line"></i></button>{error}</div></div>', 'inputOptions' => ['class' => 'form-control']]) ?>
                    </div>
                </div>
                <div class="hstack gap-2 mb-3">
                    <label class="form-label col-first">口座区分</label>
                    <div class="col-auto">
                        <?= Html::activeDropDownList($bankModel, 'account_type', ['1' => '普通', '2' => '当座'], ['class' => 'form-control form-select', 'prompt' => '区分を選択']) ?>
                        <?= Html::error($bankModel, 'account_type') ?>
                    </div>
                    <label class="form-label col-auto">口座番号</label>
                    <div class="col-2-narrow">
                        <?= Html::activeTextInput($bankModel, 'account_number', ['class' => 'form-control', 'prompt' => '口座番号']) ?>
                        <?= Html::error($bankModel, 'account_number') ?>
                    </div>
                    <label class="form-label col-auto">口座名義</label>
                    <div class="col-2-wide">
                        <?= Html::activeTextInput($bankModel, 'account_name', ['class' => 'form-control', 'prompt' => '口座番号']) ?>
                        <?= Html::error($bankModel, 'account_name') ?>
                    </div>
                    <label class="form-label col-auto">口座名義カナ</label>
                    <div class="col-2-wide">
                        <?= Html::activeTextInput($bankModel, 'account_name_kana', ['class' => 'form-control', 'prompt' => '口座番号']) ?>
                        <?= Html::error($bankModel, 'account_name_kana') ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <?= $form->field($model, 'sales_person_id', ['horizontalCssClasses' => [
                            'label' => 'form-label col-first',
                            'offset' => 'col-sm-offset-2',
                            'wrapper' => 'col-sm-7',
                        ],])->dropDownList(\app\models\SalesPerson::getPersons(), ['prompt' => '営業担当者を選択']) ?>
                    </div>
                    <div class="col-md-8">
                        <?= $form->field($model, 'memo', ['horizontalCssClasses' => [
                            'label' => 'form-label col-auto',
                            'offset' => 'col-sm-offset-2',
                            'wrapper' => 'col-sm-10',
                        ],])->textarea(['rows' => 2]) ?>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <?= Html::submitButton('この内容で' . ($model->isNewRecord ? '登録' : '更新'), ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end() ?>
        </div>
</section>
