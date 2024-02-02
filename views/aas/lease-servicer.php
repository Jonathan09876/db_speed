<?php
/**
 * @var $this \yii\web\View;
 * @var $model \app\models\AccountTransferAgency;
 * @var $bankModel BankAccount
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use app\models\BankAccount;

$script =<<<EOS
$(document)
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
EOS;
$this->registerJs($script);
$style=<<<EOS
.list-group.position-absolute {
    top:calc(100% - 1rem);
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
EOS;
$this->registerCss($style);
?>
<section id="registration-form">
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?= $model->isNewRecord ? '新規回収先登録' : '回収先情報編集' ?></h5>
            </div>
            <?php $form = ActiveForm::begin([]) ?>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($model, 'name') ?>
                    </div>
                    <div class="col-md-2">
                        <?= $form->field($model, 'shorten_name') ?>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <?= $form->field($model, 'for_internal')->checkbox() ?>
                    </div>
                    <div class="col-md-2">
                        <?= $form->field($model, 'transfer_date', ['template' => '{label}<div class="input-group">{input}<span class="input-group-text">日</span>{error}</div>']) ?>
                    </div>
                    <div class="col-md-3 position-relative">
                        <?= $form->field($bankModel, 'bank_name', ['template' => '{label}<div class="input-group">{input}<button type="button" class="btn btn-secondary" id="btn-search-bank-name"><i class="ri-search-line"></i></button>{error}</div>', 'inputOptions' => ['class' => 'form-control']]) ?>
                    </div>
                    <div class="col-md-3 position-relative">
                        <?= $form->field($bankModel, 'bank_code', ['template' => '{label}<div class="input-group">{input}<button type="button" class="btn btn-secondary" id="btn-search-bank-code"><i class="ri-search-line"></i></button>{error}</div>', 'inputOptions' => ['class' => 'form-control']]) ?>
                    </div>
                    <div class="col-md-3 position-relative">
                        <?= $form->field($bankModel, 'branch_name', ['template' => '{label}<div class="input-group">{input}<button type="button" class="btn btn-secondary" id="btn-search-branch-name"><i class="ri-search-line"></i></button>{error}</div>', 'inputOptions' => ['class' => 'form-control']]) ?>
                    </div>
                    <div class="col-md-3 position-relative">
                        <?= $form->field($bankModel, 'branch_code', ['template' => '{label}<div class="input-group">{input}<button type="button" class="btn btn-secondary" id="btn-search-branch-code"><i class="ri-search-line"></i></button>{error}</div>', 'inputOptions' => ['class' => 'form-control']]) ?>
                    </div>
                    <div class="col-md-2">
                        <?= $form->field($bankModel, 'account_type')->dropDownList(['1' => '普通', '2' => '当座'], ['class' => 'form-control', 'prompt' => '区分を選択']) ?>
                    </div>
                    <div class="col-md-2">
                        <?= $form->field($bankModel, 'account_number') ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($bankModel, 'account_name') ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($bankModel, 'account_name_kana') ?>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <?= Html::submitButton($model->isNewRecord ? 'この内容で登録' : 'この内容で更新', ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</section>
