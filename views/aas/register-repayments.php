<?php
/**
 * @var $this \yii\web\View
 * @var $searchModel \app\models\MonthlyChargeSearch;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

use app\models\MonthlyChargeSearch;
use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\widgets\ListView;
use yii\bootstrap5\Html;
use app\widgets\datetimepicker\Datetimepicker;
use app\models\RepaymentPattern;
use kartik\typeahead\Typeahead;
use yii\helpers\Url;

$this->title = '回収実績登録';

$script = <<<EOS
let imo = false, client_corp_id, pos = 0, current, currentHeight, list, listHeight, listShown = false, timer, part, wrapper, data, result;
function setCssStyle(selectorText, style, value) {
    var CSSstyle = undefined;
    for(var m in document.styleSheets) {
        rules = document.styleSheets[m][document.all ? 'rules' : 'cssRules'];
        for(var n in rules) {
            if(rules[n].selectorText == selectorText){
                CSSstyle = rules[n].style;
            }
        }
    }
    if (CSSstyle) {
        let current = CSSstyle.cssText;
        CSSstyle.cssText = current + ' ' + style + ': ' + value + ';';
    }
}
function numberFormat(num) {
    return num.toString().replace(/(\d+?)(?=(?:\d{3})+$)/g, '$1,');
}
function customerSelected(current) {
    $('#monthlychargesearch-customer_id').val(current.data('id'));
    $('#monthlychargesearch-customer_code').val(current.data('code'));
    $('#monthlychargesearch-customer_name').val(current.data('name'));
    listShown = false;
    $('#customer-list').remove();
}
$.fn.extend({
    format: function(){
        return $(this).each(function(){
            var v = $(this).val();
            $(this).val(numberFormat(v.toString().replace(/[^\d]/g,'')));
        });
    },
    unformat: function(){
        return $(this).each(function(){
            var v = $(this).val();
            $(this).val(v.toString().replace(/[^\d]/g,''));
        });
    },
    numVal: function(){
        return $(this).val().replace(/[^\d]/g,'')*1;
    },
});
$(document)
    .on('keyup', '.formatted', function(){
        $(this).format();
    })
    .on('focus', '#monthlychargesearch-customer_code,#monthlychargesearch-customer_name', function(){
        if ($('[name="MonthlyChargeSearch[client_corporation_id]"]:checked').length == 0) {
            $(this).blur();
            alert('まず先に「会社」を選択してください。得意先コード、得意先名の検索には「会社」の指定が必要です。');
        }
        else {
            client_corp_id = $('[name="MonthlyChargeSearch[client_corporation_id]"]:checked').val();
        }
    })
    .on('click', '[name="MonthlyChargeSearch[client_corporation_id]"]', function(){
        $('#monthlychargesearch-customer_code').val('');
        $('#monthlychargesearch-customer_name').val('');
    })
    .on('keydown', '#monthlychargesearch-customer_code,#monthlychargesearch-customer_name', function(evt){
        if (evt.key == 'ArrowDown' || evt.key == 'ArrowUp' || evt.key == 'Enter') {
            imo = true;
            evt.preventDefault();
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
                wrapper = $(evt.target).parents('.position-relative');
                $('ul.list-group', wrapper).remove();
                list = $('<ul id="customer-list" class="collapse list-group overflow-auto position-absolute w-100"></ul>');
                data = {
                    "CustomerSearch[client_corporation_id]": client_corp_id,
                };
                if (evt.target.id == 'monthlychargesearch-customer_code') {
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
    .on('click', '#customer-list>.list-group-item', function(evt){
        current = $(evt.target);
        customerSelected(current);
    })
    /*
    .on('change', '[name="Repayment[][repayment_type_id]"]', function(){
        let selected = $(this).val(), checkbox = $(this).parents('tr').find('[type="checkbox"]');
        if (selected != '1') {
            checkbox.prop('checked', false);
        } 
    })
    */
    .on('click', '[type="checkbox"][name="Repayment[][collected]"]', function(){
        let checked = $(this).is(':checked'), amount = $(this).parent().data('amount');
        if (checked) {
            $(this).parents('tr').find('[name="Repayment[][repayment_type_id]"]').val(1);
            $(this).parents('tr').find('[name="Repayment[][repayment_amount]"]').val(amount);
            $(this).parents('tr').find('[name="Repayment[][repayment_amount]"]').format();
        }
    })
    .on('change', '.form-filter', async function(){
        let attr = $(this).data('attr'), word = $(this).val();
        let response = await $.ajax({
            method: 'post',
            url: '/update/registration-filter',
            data: {attr: attr, word: word},
            datatype: 'json',
        });
        var wrapper = $(this).parents('#pjax-grid-wrapper');
        $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
    })
    .on('keydown', '.form-filter', function(evt){
        if (evt.key == 'Enter') {
            $(this).change();
            evt.preventDefault();
            return false;
        }
    })
$('.formatted').format();
let width1 = $('.sticky-header1').outerWidth();
let width2 = $('.sticky-header2').outerWidth();
let width3 = $('.sticky-header3').outerWidth();
setCssStyle('.sticky-header2', 'left', width1+'px;')
setCssStyle('.sticky-header3', 'left', (width1+width2)+'px;')
setCssStyle('.sticky-header4', 'left', (width1+width2+width3)+'px;')
setCssStyle('.sticky-cell2', 'left', width1+'px;')
setCssStyle('.sticky-cell3', 'left', (width1+width2)+'px;')
setCssStyle('.sticky-cell4', 'left', (width1+width2+width3)+'px;')

EOS;
$this->registerJs($script);
$style = <<<EOS
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
.table-wrapper table.table {
	border-collapse:separate;
	border-spacing:0;
	min-width:100%;
}
.table-wrapper table.table th,
.table-wrapper table.table td {
    border-left-width: 0;
}
.sticky-header1 {
    position:sticky;
    top:0;
    left: 0px;
    border-left-width: 1;
    z-index:1;
}
.sticky-cell1 {
    position:sticky;
    top:0;
    left: 0px;
    background-color: #fff !important;
    border-left-width: 1;
    z-index:1;
}
.sticky-header2 {
    position:sticky;
    top:0;
    z-index:1;
}
.sticky-cell2 {
    position:sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
}
.sticky-header3 {
    position:sticky;
    top:0;
    z-index:1;
}
.sticky-cell3 {
    position:sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
}
.sticky-header4 {
    position: sticky;
    top:0;
    z-index:1;
}
.sticky-cell4 {
    position: sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
}
.col-first {
    -webkit-box-flex: 0;
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    width: 110px;
}
.text-red {
    color: #ff0000;
}
td.element-cell {
    padding: 0 !important;
}
td.element-cell.cell-checkbox {
    vertical-align: middle;
}
td.element-cell .form-control {
    font-size: 12px;
}
EOS;
$this->registerCss($style);
?>
<section id="customers">
        <div class="row mb-2">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center g-3">
                            <div class="col-md-3">
                                <h5 class="card-title mb-0">回収実績登録一覧検索</h5>
                            </div>
                        </div>
                    </div>
                    <?php $form = ActiveForm::begin([
                        'layout' => 'horizontal',
                        'action' => '/aas/lease-contract-operation'
                    ]) ?>
                    <div class="card-body">
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
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-auto',
                                ],'inputOptions' => ['placeholder' => '得意先コードを入力', 'autocomplete' => 'off']]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($searchModel, 'customer_name', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-auto',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-md-6',
                                ],'inputOptions' => ['placeholder' => '得意先名入力', 'autocomplete' => 'off']]) ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3 row field-contract-codes">
                                    <label class="form-label col-first">契約番号</label>
                                    <div class="col-md-8 hstack gap-1">
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
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($searchModel, 'target_word', ['horizontalCssClasses' => [
                                    'label' => 'col-first form-label',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-md-8',
                                ], 'inputOptions' => ['placeholder' => '登録ナンバー等']]) ?>
                            </div>
                            <div class="col-md-12">
                                <div class="row field-contract-codes">
                                    <label class="form-label col-first">リース期間</label>
                                    <div class="col-md-8 hstack gap-1">
                                        <div class="col-auto">
                                            <?= $form->field($searchModel, "term_from", ['horizontalCssClasses' => [
                                                'offset' => 'col-sm-offset-2',
                                                'wrapper' => 'col-auto',
                                            ],])->label(false)->widget(Datetimepicker::class, ['id' => "term-start-at", 'clientOptions' => [
                                                'locale' => 'ja',
                                                'format' => 'YYYY年M月',
                                                'viewMode' => 'months',
                                            ]]) ?>
                                        </div>
                                        <div class="col-autp">〜</div>
                                        <div class="col-6">
                                            <?= $form->field($searchModel, "term_to", ['horizontalCssClasses' => [
                                                'offset' => 'col-sm-offset-2',
                                                'wrapper' => 'col-auto',
                                            ],])->label(false)->widget(Datetimepicker::class, ['id' => "term-end-at", 'clientOptions' => [
                                                'locale' => 'ja',
                                                'format' => 'YYYY年M月',
                                                'viewMode' => 'months',
                                            ]]) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <?= $form->field($searchModel, 'repayment_pattern_id', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-first',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-auto',
                                ],])->dropDownList(RepaymentPattern::getPatterns(), ['prompt' => '支払条件を選択']) ?>
                            </div>
                        </div>
                        <div class="hstack gap-2 mb-3">
                            <label class="form-label col-first">契約ステータス</label>
                            <?= Html::activeCheckboxList($searchModel, 'lease_contract_status_type_id', \app\models\LeaseContractStatusType::getTypes(), ['inline' => true]) ?>
                        </div>
                        <div class="hstack gap-2 mb-3">
                            <label class="form-label col-first">契約情報</label>
                            <?= Html::activeCheckboxList($searchModel, 'contract_pattern_id', \app\models\ContractPattern::getContractNamePatterns(), ['inline' => true]) ?>
                        </div>
                        <div class="hstack gap-2 mb-3">
                            <label class="form-label col-first">税区分</label>
                            <?= Html::activeCheckboxList($searchModel, 'tax_application_id', \app\models\TaxApplication::getTaxApplications(), ['inline' => true]) ?>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <?= Html::submitButton('この内容で検索', ['class' => 'btn btn-primary']) ?>
                        <?= Html::submitInput('引落データ計算', ['class' => 'btn btn-success', 'name' => 'MonthlyChargeSearch[calc_collection_data]', 'value' => 1]) ?>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <h5 class="card-title mb-0">回収実績登録</h5>
                    </div>
                </div>
            </div>
            <?php \yii\widgets\Pjax::begin([
                'id' => 'pjax-grid-wrapper',
                'options' => [
                    'class' => 'card-body'
                ],
                'linkSelector' => '.pagination a',
                'timeout' => 3000,
            ]); ?>
            <?php
                $term = empty($searchModel->target_term) ? (new \DateTime())->format('Y年m月') : $searchModel->target_term;
                $targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $term));
                $lastTerm = (clone $targetTerm)->modify('-1 month');
                $session = Yii::$app->session;
                $filter_name = $session['filter-name'] ?? '';
                $filter_code = $session['filter-code'] ?? '';
                $widget = new \app\widgets\PageSizeLimitChanger(['pjax_id' => 'pjax-grid-wrapper']);
                $dataProvider->pagination = $widget->pagination;
                $summary = $widget->summaryLayout;
                $layout = <<<EOL
{$summary}
<div class="table-wrapper">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th class="sticky-header1">CF</th>
                <th class="sticky-header2">支払方法</th>
                <th class="sticky-header3">顧客名
                <th class="sticky-header4">契約No.</th>
                <th>税率</th>
                <th>登録年月日</th>
                <th>登録No.</th>
                <th>リース期間</th>
                <th>収支</th>
                <th>回数</th>
                <th>{$targetTerm->format('Y/m')}</th>
                <th>確定</th>
                <th style="min-width:110px;">回収区分</th>
                <th style="min-width:85px;">回収額</sub></th>
                <th>顧客名</sub></th>
                <th>CF</th>
            </tr>
            <tr>
                <th class="sticky-header1"><input type="text" name="filter_code" value="{$filter_code}" class="form-control form-filter" data-attr="code"></th>
                <th class="sticky-header2"></th>
                <th class="sticky-header3"><input type="text" name="filter_name" value="{$filter_name}" class="form-control form-filter" data-attr="name"></th>
                <th class="sticky-header4"></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th><input type="text" name="filter_name" value="{$filter_name}" class="form-control form-filter" data-attr="name"></th>
                <th><input type="text" name="filter_code" value="{$filter_code}" class="form-control form-filter" data-attr="code"></th>
            </tr>
        </thead>
        <tbody>
            {items}
        </tbody>
    </table>
    <div class="text-end">
        <button type="submit" class="btn btn-primary">実績登録</button>
    </div>
</div>
{pager}
EOL;
                ?>
            <?php $registerForm = ActiveForm::begin([
                    'id' => 'register-repayments-form'
            ]) ?>
                <?= ListView::widget([
                    'dataProvider' => $dataProvider,
                    'itemView' => 'iv-register-repayments',
                    'itemOptions' => ['tag' => false],
                    'viewParams' => ['targetTerm' => $targetTerm, 'dataProvider' => $dataProvider],
                    'layout' => $layout,
                    'emptyText' => '実績登録完了しました。'
                ]) ?>
            <?php ActiveForm::end(); ?>
            <?php \yii\widgets\Pjax::end(); ?>
        </div>
</section>

