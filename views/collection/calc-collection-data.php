<?php
/**
 * @var $this \yii\web\View
 * @var $searchModel \app\models\ScheduleSearch;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

use app\models\ScheduleSearch;
use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\widgets\ListView;
use yii\bootstrap5\Html;
use app\widgets\datetimepicker\Datetimepicker;
use app\models\RepaymentPattern;
use kartik\typeahead\Typeahead;
use yii\helpers\Url;

$this->title = 'データ作成';

$script = <<<EOS
class CellUpdater {
    target;
    self;
    
    constructor(){
        this.self = this;
    }
    
    async monthly_charge(attr, id){
        let tag, updated, response, timer, taxIncluded, theInput, formatted, shiftOn = false;
        switch(attr){
            case 'temporary_charge_amount':
                tag = await this.getUpdateMonthlyChargeTag(attr, id);
                $('#updater').html(tag);
                $('#monthly_charge-'+attr+'-'+id).format().focus();
                $('#monthly_charge-'+attr+'-'+id).on('keydown', async evt => {
                    theInput = evt.target;
                    if (evt.key == 'Enter') {
                        updated = $('#monthly_charge-'+attr+'-'+id).numVal();
                        response = await this.updateMonthlyCharge(attr, id, updated);
                        if (response.success) {
                            var target = $(theInput).parent().parent().next().find('input');
                            taxIncluded = await this.calcTaxIncluded('monthly_charge', id, updated);
                            target.val(taxIncluded);
                            target.format();
                            formatted = target.val();
                            $(this.target).html(formatted);
                            $('#updater').empty();
                            $('#updater-overlay').hide();
                            var wrapper = $(this.target).parents('#pjax-grid-wrapper');
                            $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
                        }
                    }
                    else {
                        if (timer) {
                            clearTimeout(timer);
                        }
                        timer = setTimeout(async () => {
                            var target = $(theInput).parent().parent().next().find('input');
                            taxIncluded = await this.calcTaxIncluded('monthly_charge', id, $(evt.target).numVal());
                            target.val(taxIncluded);
                            target.format();
                            formatted = target.val();
                        }, 200);
                    }
                })
                $(document).one('click', '#updater-overlay', evt => {
                    $('#updater').empty();
                    $('#updater-overlay').hide();
                })
                break;
            case 'repayment_type_id':
                tag = await this.getUpdateMonthlyChargeTag(attr, id);
                $('#updater').html(tag);
                var target_tag = $('#monthly_charge-'+attr+'-'+id);
                target_tag.on('change', async evt => {
                    updated = target_tag.val();
                    var updatedType = target_tag.find('option[value="'+updated+'"]').text()
                    response = await this.updateMonthlyCharge(attr, id, updated);
                    if (response.success) {
                        $(this.target).html(updatedType);
                        $('#updater').empty();
                        $('#updater-overlay').hide();
                    }
                })
                $(document).one('click', '#updater-overlay', evt => {
                    $('#updater').empty();
                    $('#updater-overlay').hide();
                })
                break;
        }
    }
    
    async getUpdateMonthlyChargeTag(attr, id) {
        let tag = await $.ajax({
            method: 'post',
            url: '/update/monthly-charge',
            data: {attr:attr, id:id},
            dataType: 'html',
        });
        return tag;
    }

    async updateMonthlyCharge(attr, id, value) {
        let response = await $.ajax({
            method: 'post',
            data: {attr: attr, id: id, value: value},
            url: '/update/monthly-charge-value',
            dataType: 'json'
        });
        return response;
    }
    async calcTaxIncluded(type, id, amount) {
        let json = await $.getJSON('/update/calc-tax-included?type='+type+'&id='+id+'&amount='+amount);
        return json.value;
    }
}
function updateContent(evt){
    var updater = new CellUpdater;
    var targetClass = Array.prototype.slice.apply(evt.target.classList).find(function(className){
        return null !== className.match(/^cell-/);
    }), id = $(evt.target).data('id'), position = $(evt.target).offset(), overlayPosition = $(document.body).offset();
    var matched = targetClass.match(/cell-([^-]+)-([^-]+)/);
    if (matched) {
        updater.target = evt.target;
        updater[matched[1]](matched[2], id);
        $('#updater').offset(position);
        $('#updater-overlay').show();
    }
}
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
    $('#schedulesearch-customer_id').val(current.data('id'));
    $('#schedulesearch-customer_code').val(current.data('code'));
    $('#schedulesearch-customer_name').val(current.data('name'));
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
    .on('focus', '#schedulesearch-customer_code,#schedulesearch-customer_name', function(){
        if ($('[name="ScheduleSearch[client_corporation_id]"]:checked').length == 0) {
            $(this).blur();
            alert('まず先に「会社」を選択してください。得意先コード、得意先名の検索には「会社」の指定が必要です。');
        }
        else {
            client_corp_id = $('[name="ScheduleSearch[client_corporation_id]"]:checked').val();
        }
    })
    .on('click', '[name="ScheduleSearch[client_corporation_id]"]', async function(){
        $('#schedulesearch-customer_id').val('');
        $('#schedulesearch-customer_code').val('');
        $('#schedulesearch-customer_name').val('');
        var id = $(this).val(), patterns = await $.get('/collection/update-contract-pattern?id='+id);
        $('#schedulesearch-contract_pattern_id').replaceWith(patterns);
    })
    .on('click', '.btn-clear-customer', function(){
        $('#schedulesearch-customer_id').val('');
        $('#schedulesearch-customer_code').val('');
        $('#schedulesearch-customer_name').val('');
    })
    .on('keydown', '#schedulesearch-customer_code,#schedulesearch-customer_name', function(evt){
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
                if (evt.target.id == 'schedulesearch-customer_code') {
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
    .on('click', '.editable', updateContent)
    .on('focus', '.cell-text-input input', function(){
        $(this).select();
    })
    .on('change', '.cell-dropdown select', async function(){
        var selected = $(this).val(), mcid = $(this).data('id'), response;
        response = await $.ajax({
            method: 'post',
            data: {attr: 'repayment_type_id', id: mcid, value: selected},
            url: '/update/monthly-charge-value',
            dataType: 'json'
        });
    })
    .on('keydown', '.cell-text-input input', async function(evt){
        var mcid, value, formatted, response;
        if (evt.key == 'Enter') {
            mcid = $(this).data('id');
            formatted = $(this).val();
            value = $(this).numVal();
            if (confirm('回収予定額を「'+formatted+'」に更新します。よろしいですか？')) {
                response = await $.ajax({
                    method: 'post',
                    data: {attr: 'amount_with_tax', id: mcid, value: value},
                    url: '/update/monthly-charge-value',
                    dataType: 'json'
                });
                var wrapper = $(this).parents('#pjax-grid-wrapper');
                wrapper.addClass('position-relative');
                wrapper.append('<div style="background-color: rgba(0,0,0,.1);position:absolute;left:0;top:0;" class="w-100 h-100 d-flex align-items-center justify-content-center"><div style="width:100px;height:100px;" class="spinner-border text-secondary" role="status"><span class="sr-only">Loading...</span></div></div>')
                $('#'+wrapper.attr('id')).one('pjax:complete', function(){
                    $('.formatted').format();
                });
                $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
            }
        }
    })
$('.formatted').format();
let width1 = $('.sticky-header1').outerWidth();
let width2 = $('.sticky-header2').outerWidth();
let width3 = $('.sticky-header3').outerWidth();
let height1 = $('.sticky-row1 th:nth-child(1)').outerHeight();
setCssStyle('.sticky-header2', 'left', width1+'px;')
setCssStyle('.sticky-header3', 'left', (width1+width2)+'px;')
setCssStyle('.sticky-header4', 'left', (width1+width2+width3)+'px;')
setCssStyle('.sticky-cell2', 'left', width1+'px;')
setCssStyle('.sticky-cell3', 'left', (width1+width2)+'px;')
setCssStyle('.sticky-cell4', 'left', (width1+width2+width3)+'px;')
setCssStyle('.sticky-row2 th', 'top', height1+'px!important')

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
.table-wrapper table.table th,
.table-wrapper table.table thead th {
    position: sticky;
    top:0;
    z-index:2;
}
.table-wrapper table.table tr.has-registered-repayments {
    background-color: #e4e4e4;
}
.table-wrapper table td:not(.sticky-cell):hover {
    position: relative;
}
.table-wrapper table td:hover::before {
    content: ' ';
    position: absolute;
    left:0;
    top:0;
    width: 100%;
    height: 100%;
    z-index: 2;
    border: 2px solid #226622;
    pointer-events:none;
}
.sticky-cell {
    position:sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
}
.sticky-header1 {
    position:sticky;
    top:0;
    left: 0px;
    border-left-width: 1;
    z-index:3!important;
}
.sticky-cell1 {
    left: 0px;
    border-left-width: 1;
}
.sticky-header2 {
    position:sticky;
    top:0;
    z-index:3!important;
}
.sticky-cell2 {
}
.sticky-header3 {
    position:sticky;
    top:0;
    z-index:3!important;
}
.sticky-cell3 {
}
.sticky-header4 {
    position: sticky;
    top:0;
    z-index:3!important;
}
.sticky-cell4 {
}
.sticky-row1 th {
    top: 0;
}
.sticky-row2 th {
    top: 0;
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
.table-wrapper table.table th.current-term {
    background-color: var(--vz-indigo);
}
td.element-cell {
    padding: 0 !important;
}
td.element-cell .form-control {
    font-size: 12px;
}
td.element-cell.cell-dropdown {
    min-width: 110px;
}
td.element-cell.cell-text-input {
    min-width: 90px;
}
.formatted {
    text-align: right;
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
                                <h5 class="card-title mb-0">回収データ計算</h5>
                            </div>
                        </div>
                    </div>
                    <?php $form = ActiveForm::begin([
                        'layout' => 'horizontal',
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
                                ],'inputOptions' => ['placeholder' => '得意先コードを入力', 'autocomplete' => 'off'],
                                'template' => '{label}<div class="col-auto"><div class="input-group has-validation">{input}<button type="button" class="btn btn-outline-secondary btn-clear-customer"><i class="ri-close-line"></i></button>{error}</div></div>'
                                ]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($searchModel, 'customer_name', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-auto',
                                ],'inputOptions' => ['placeholder' => '得意先名入力', 'autocomplete' => 'off'],
                                    'template' => '{label}<div class="col-md-6"><div class="input-group has-validation">{input}<button type="button" class="btn btn-outline-secondary btn-clear-customer"><i class="ri-close-line"></i></button>{error}</div></div>'
                                ]) ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3 row field-contract-codes">
                                    <label class="form-label col-first">契約番号</label>
                                    <div class="col-md-8 hstack gap-1">
                                        <div class="col-auto">
                                            <?= Html::activeDropDownList($searchModel, 'contract_pattern_id', \app\models\ContractPattern::getContractPatterns($searchModel->client_corporation_id ? $searchModel->client_corporation_id : null), ['class' => 'form-control', 'prompt' => '契約マスタ選択']) ?>
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
                                ],])->dropDownList(RepaymentPattern::getPatterns(), ['prompt' => '回収条件を選択']) ?>
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
                        <?php /* <div class="hstack gap-2 mb-3">
                            <label class="form-label col-first">回収区分</label>
                            <?= Html::activeCheckboxList($searchModel, 'repayment_types', \app\models\RepaymentType::getDefaultTypes(), ['inline' => true]) ?>
                        </div> */ ?>
                        <div class="hstack gap-2 mb-3">
                            <label class="form-label col-first">登録状況</label>
                            <?= Html::hiddenInput('ScheduleSearch[register_repayments]', null) ?>
                            <div class="form-check">
                                <?= Html::checkbox('ScheduleSearch[register_repayments]', !!$searchModel->register_repayments, ['class' => 'form-check-input', 'id' => 'schedulesearch-register_repayments', 'value' => 1]) ?>
                                <label for="schedulesearch-register_repayments">実績登録済を除く</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <?= Html::activeHiddenInput($searchModel, 'calc_collection_data', ['value' => 1]) ?>
                        <?php
                        $term = empty($searchModel->target_term) ? (new \DateTime())->format('Y年m月') : $searchModel->target_term;
                        $targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $term));
                        $q = clone $dataProvider->query;
                        ?>

                        <?php
                        $stored = \app\models\TargetTermMonthlyChargeStored::isStored($targetTerm->format('Y-m-d'), $searchModel->client_corporation_id, $searchModel->repayment_pattern_id);
                        $updateEnable = \app\models\TargetTermMonthlyChargeStored::isUpdateEnable($targetTerm->format('Y-m-d'), $searchModel->client_corporation_id, $searchModel->repayment_pattern_id);
                        ?>
                        <?= Html::submitButton('引落データ作成', ['class' => 'btn btn-primary']); ?>
                        <?= !$stored && \app\components\PrivilegeManager::hasPrivilege('aas/store-collection-data') && $searchModel->calc_collection_data && $q->count() > 0 ? Html::submitInput('回収予定を確定する', ['class' => 'btn btn-success ms-1', 'name' => 'ScheduleSearch[store_collection_data]']) : '' ?>
                        <?= $stored && $updateEnable && \app\components\PrivilegeManager::hasPrivilege('aas/store-collection-data') ? Html::a('確定を解除する', ['/collection/remove-stored-collection-data', 'id' => $stored], ['class' => 'btn btn-danger me-1']) : '' ?>
                        <?= $stored && !$updateEnable && \app\components\PrivilegeManager::hasPrivilege('aas/store-collection-data') ? Html::button('締め処理済み', ['class' => 'btn btn-secondary disabled']) : '' ?>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
<?php if ($searchModel->do_search && $searchModel->calc_collection_data) : ?>
        <div class="card mb-2">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <h5 class="card-title mb-0">回収支払表</h5>
                    </div>
                    <div class="col-md-9 text-end">
                        <?= Html::a('CSVエクスポート', ['/collection/export-collection-data'], ['class' => 'btn btn-sm btn-success']) ?>
                        <?= Html::a('PDF出力', ['/publish/collection-data'], ['class' => 'btn btn-sm btn-secondary', 'target' => '_blank']) ?>
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

                $sql = "SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    END) AS `customer_total`";
                $query = clone $dataProvider->query;
                //echo $query->createCommand()->rawSql;
                $query->select([
                    '`c`.`customer_id`',
                    'GROUP_CONCAT(DISTINCT cd.contract_detail_id ORDER BY lc.disp_order ASC) AS cdids',
                    'MIN(`cd`.`contract_detail_id`) AS `first_cdid`',
                    'COUNT(DISTINCT cd.contract_detail_id) AS `rowspan`',
                    $sql,
                ])
                    ->groupBy(['c.customer_id']);
                    //->orderBy(['cc.client_corporation_id' => SORT_ASC, 'c.customer_code' =>SORT_ASC, 'lc.disp_order' => SORT_ASC, 'cd.term_start_at' =>SORT_ASC]);
                //echo $query->createCommand()->rawSql;
                $totals = \yii\helpers\ArrayHelper::index($query->asArray()->all(), 'customer_id');
                if (Yii::$app->request->get('check', false)) {
                    echo $query->createCommand()->rawSql;
                    \yii\helpers\VarDumper::dump($totals, 10, 1);die();
                }
                if ($stored) {
                    $lastRepaymentTotal = current((clone $dataProvider->query)
                        ->leftJoin('monthly_charge mc2', 'cd.contract_detail_id=mc2.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc2.term + INTERVAL 1 MONTH ELSE mc2.term END = :lastterm')
                        ->leftJoin('repayment r2', 'r2.monthly_charge_id=mc2.monthly_charge_id')
                        ->params([':term' => $targetTerm->format('Y-m-01'), ':stored_id' => $stored, ':lastterm' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(['sum(r2.repayment_amount) as `repayment_total`'])
                        ->column());
                    $lastRepaymentFurikaeTotal = current((clone $dataProvider->query)
                        ->leftJoin('monthly_charge mc2', 'cd.contract_detail_id=mc2.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc2.term + INTERVAL 1 MONTH ELSE mc2.term END = :lastterm')
                        ->leftJoin('repayment r2', 'r2.monthly_charge_id=mc2.monthly_charge_id')
                        ->params([':term' => $targetTerm->format('Y-m-01'), ':stored_id' => $stored, ':lastterm' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(['sum(r2.repayment_amount) as `repayment_total`'])
                        ->andWhere(['IFNULL(r2.repayment_type_id, IFNULL(mc2.repayment_type_id, rp.repayment_type_id))' => 1]) //口座振替
                        ->column());
                    $lastRepaymentFurikomiTotal = current((clone $dataProvider->query)
                        ->leftJoin('monthly_charge mc2', 'cd.contract_detail_id=mc2.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc2.term + INTERVAL 1 MONTH ELSE mc2.term END = :lastterm')
                        ->leftJoin('repayment r2', 'r2.monthly_charge_id=mc2.monthly_charge_id')
                        ->params([':term' => $targetTerm->format('Y-m-01'), ':stored_id' => $stored, ':lastterm' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(['sum(r2.repayment_amount) as `repayment_total`'])
                        ->andWhere(['IFNULL(r2.repayment_type_id, IFNULL(mc2.repayment_type_id, rp.repayment_type_id))' => [2,3,4,14]]) //口座振替
                        ->column());
                    $lastChargeTotal = current((clone $dataProvider->query)
                        ->leftJoin('monthly_charge mc2', 'cd.contract_detail_id=mc2.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc2.term + INTERVAL 1 MONTH ELSE mc2.term END = :lastterm')
                        ->leftJoin('repayment r2', 'r2.monthly_charge_id=mc2.monthly_charge_id')
                        ->params([':term' => $targetTerm->format('Y-m-01'), ':stored_id' => $stored, ':lastterm' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])->column());

                    $lastChargeFurikaeTotal = current((clone $dataProvider->query)
                        ->leftJoin('monthly_charge mc2', 'cd.contract_detail_id=mc2.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc2.term + INTERVAL 1 MONTH ELSE mc2.term END = :lastterm')
                        ->leftJoin('repayment r2', 'r2.monthly_charge_id=mc2.monthly_charge_id')
                        ->params([':term' => $targetTerm->format('Y-m-01'), ':stored_id' => $stored, ':lastterm' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])
                        ->andWhere(['IFNULL(mc2.repayment_type_id, rp.repayment_type_id)' => 1]) //口座振替
                        ->column());
                    $lastChargeFurikomiTotal = current((clone $dataProvider->query)
                        ->leftJoin('monthly_charge mc2', 'cd.contract_detail_id=mc2.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc2.term + INTERVAL 1 MONTH ELSE mc2.term END = :lastterm')
                        ->leftJoin('repayment r2', 'r2.monthly_charge_id=mc2.monthly_charge_id')
                        ->params([':term' => $targetTerm->format('Y-m-01'), ':stored_id' => $stored, ':lastterm' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])
                        ->andWhere(['IFNULL(mc2.repayment_type_id, rp.repayment_type_id)' => [2,3,4,14]]) //口座振替
                        ->column());
                    $lastChargeOthersTotal = current((clone $dataProvider->query)
                        ->leftJoin('monthly_charge mc2', 'cd.contract_detail_id=mc2.contract_detail_id AND CASE rp.target_month WHEN "next" THEN mc2.term + INTERVAL 1 MONTH ELSE mc2.term END = :lastterm')
                        ->leftJoin('repayment r2', 'r2.monthly_charge_id=mc2.monthly_charge_id')
                        ->params([':term' => $targetTerm->format('Y-m-01'), ':stored_id' => $stored, ':lastterm' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc2.temporary_charge_amount, mc2.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc2.term >= application_from AND mc2.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])
                        ->andWhere(['not', ['IFNULL(mc2.repayment_type_id, rp.repayment_type_id)' => [1,2,3,4,14]]]) //口座振替
                        ->column());
                }
                else {
                    $lastRepaymentTotal = current((clone $dataProvider->query)
                        ->params([':term' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(['sum(r.repayment_amount) as `repayment_total`'])
                        ->column());
                    $lastRepaymentFurikaeTotal = current((clone $dataProvider->query)
                        ->params([':term' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(['sum(r.repayment_amount) as `repayment_total`'])
                        ->andWhere(['IFNULL(r.repayment_type_id, IFNULL(mc.repayment_type_id, rp.repayment_type_id))' => 1]) //口座振替
                        ->column());
                    $lastRepaymentFurikomiTotal = current((clone $dataProvider->query)
                        ->params([':term' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(['sum(r.repayment_amount) as `repayment_total`'])
                        ->andWhere(['IFNULL(r.repayment_type_id, IFNULL(mc.repayment_type_id, rp.repayment_type_id))' => [2,3,4,14]]) //口座振替
                        ->column());
                    $lastChargeTotal = current((clone $dataProvider->query)
                        ->params([':term' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])->column());

                    $lastChargeFurikaeTotal = current((clone $dataProvider->query)
                        ->params([':term' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])
                        ->andWhere(['IFNULL(mc.repayment_type_id, rp.repayment_type_id)' => 1]) //口座振替
                        ->column());
                    $lastChargeFurikomiTotal = current((clone $dataProvider->query)
                        ->params([':term' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])
                        ->andWhere(['IFNULL(mc.repayment_type_id, rp.repayment_type_id)' => [2,3,4,14]]) //口座振替
                        ->column());
                    $lastChargeFurikomiTotal = current((clone $dataProvider->query)
                        ->params([':term' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])
                        ->andWhere(['IFNULL(mc.repayment_type_id, rp.repayment_type_id)' => [2,3,4,14]]) //口座振替
                        ->column());
                    $lastChargeOthersTotal = current((clone $dataProvider->query)
                        ->params([':term' => $lastTerm->format('Y-m-01'), ':term2' => $lastTerm->format('Y-m-01')])
                        ->select(["SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    END) as amount"])
                        ->andWhere(['not', ['IFNULL(mc.repayment_type_id, rp.repayment_type_id)' => [1,2,3,4,14]]]) //口座振替
                        ->column());
                }
                $repaymentTotal = current((clone $dataProvider->query)
                    ->select(['sum(r.repayment_amount) as `repayment_total`'])
                    ->column());
                $repaymentFurikaeTotal = current((clone $dataProvider->query)
                    ->select(['sum(r.repayment_amount) as `repayment_total`'])
                    ->andWhere(['IFNULL(r.repayment_type_id, IFNULL(mc.repayment_type_id, rp.repayment_type_id))' => 1]) //口座振替
                    ->column());
                $repaymentFurikomiTotal = current((clone $dataProvider->query)
                    ->select(['sum(r.repayment_amount) as `repayment_total`'])
                    ->andWhere(['IFNULL(r.repayment_type_id, IFNULL(mc.repayment_type_id, rp.repayment_type_id))' => [2,3,4,14]]) //口座振替
                    ->column());
                $chargeTotal = current((clone $dataProvider->query)
                    ->select(["SUM(CASE cd.fraction_processing_pattern 
            WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
            WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
            WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        END) as amount"])->column());
                $chargeFurikaeTotal = current((clone $dataProvider->query)
                    ->select(["SUM(CASE cd.fraction_processing_pattern 
            WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
            WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
            WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        END) as amount"])
                    ->andWhere(['IFNULL(mc.repayment_type_id, rp.repayment_type_id)' => 1]) //口座振替
                    ->column());
                $chargeFurikomiTotal = current((clone $dataProvider->query)
                ->select(["SUM(CASE cd.fraction_processing_pattern 
            WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
            WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
            WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        END) as amount"])
                ->andWhere(['IFNULL(mc.repayment_type_id, rp.repayment_type_id)' => [2,3,4,14]]) //口座振替
                ->column());
                $chargeOthersTotal = current((clone $dataProvider->query)
                ->select(["SUM(CASE cd.fraction_processing_pattern 
            WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
            WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
            WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        END) as amount"])
                ->andWhere(['not',['IFNULL(mc.repayment_type_id, rp.repayment_type_id)' => [1,2,3,4,14]]]) //口座振替
                ->column());
                $diffTotal = $chargeTotal - $lastChargeTotal;
                $diffFurikaeTotal = $chargeFurikaeTotal - $lastChargeFurikaeTotal;
                $diffFurikomiTotal = $chargeFurikomiTotal - $lastChargeFurikomiTotal;
                $diffOthersTotal = $chargeOthersTotal - $lastChargeOthersTotal;
                $diffRepaymentTotal = $repaymentTotal - $lastRepaymentTotal;
                $diffRepaymentFurikaeTotal = $repaymentFurikaeTotal - $lastRepaymentFurikaeTotal;
                $diffRepaymentFurikomiTotal = $repaymentFurikomiTotal - $lastRepaymentFurikomiTotal;
                $lastRepaymentTotalText = number_format($lastRepaymentTotal, 0);
                $lastRepaymentFurikaeTotalText = number_format($lastRepaymentFurikaeTotal, 0);
                $lastRepaymentFurikomiTotalText = number_format($lastRepaymentFurikomiTotal, 0);
                $lastChargeTotalText = number_format($lastChargeTotal, 0);
                $lastChargeFurikaeTotalText = number_format($lastChargeFurikaeTotal, 0);
                $lastChargeFurikomiTotalText = number_format($lastChargeFurikomiTotal, 0);
                $lastChargeOthersTotalText = number_format($lastChargeOthersTotal, 0);
                $diffTotalText = number_format($diffTotal, 0);
                $diffFurikaeTotalText = number_format($diffFurikaeTotal, 0);
                $diffFurikomiTotalText = number_format($diffFurikomiTotal, 0);
                $diffOthersTotalText = number_format($diffOthersTotal, 0);
                $diffRepaymentTotalText = number_format($diffRepaymentTotal, 0);
                $diffRepaymentFurikaeTotalText = number_format($diffRepaymentFurikaeTotal, 0);
                $diffRepaymentFurikomiTotalText = number_format($diffRepaymentFurikomiTotal, 0);
                $repaymentTotalText = number_format($repaymentTotal, 0);
                $repaymentFurikaeTotalText = number_format($repaymentFurikaeTotal, 0);
                $repaymentFurikomiTotalText = number_format($repaymentFurikomiTotal, 0);
                $chargeTotalText = number_format($chargeTotal, 0);
                $chargeFurikaeTotalText = number_format($chargeFurikaeTotal, 0);
                $chargeFurikomiTotalText = number_format($chargeFurikomiTotal, 0);
                $chargeOthersTotalText = number_format($chargeOthersTotal, 0);
                $widget = new \app\widgets\PageSizeLimitChanger(['pjax_id' => 'pjax-grid-wrapper']);
                $dataProvider->pagination = $widget->pagination;
                Yii::debug(\yii\helpers\VarDumper::dumpAsString($widget->pagination,10));
                $summary = $widget->summaryLayout;
                $client_corporation_id = $searchModel->client_corporation_id;
                $repayment_pattern_id = $searchModel->repayment_pattern_id;
                $collectionUpdateEnable = \app\models\TargetTermMonthlyChargeStored::isUpdateEnable($targetTerm->format('Y-m-d'), $client_corporation_id, $repayment_pattern_id);
                $layout = <<<EOL
{$summary}
<div class="table-wrapper">
    <table class="table table-bordered">
        <thead>
            <tr class="sticky-row1">
                <th colspan="9">&nbsp;</th>
                <th colspan="2">先月回収予定</th>
                <th colspan="3" class="current-term">今月回収予定額</th>
                <th colspan="4">&nbsp;</th>
            </tr>
            <tr class="sticky-row2">
                <th class="sticky-header1">CF</th>
                <th class="sticky-header2">支払方法</th>
                <th class="sticky-header3">顧客名
                <th class="sticky-header4">契約No.</th>
                <th>税率</th>
                <th>登録年月日</th>
                <th>登録No.</th>
                <th>リース期間</th>
                <th>回収</th>
                <th>回数</th>
                <th>{$lastTerm->format('Y/m')}</th>
                <th class="current-term">回数</th>
                <th colspan="2" class="current-term">{$targetTerm->format('Y/m')}</th>
                <th>増減</th>
                <th>会社別</sub></th>
                <th>顧客名</sub></th>
                <th>CF</th>
            </tr>
        </thead>
        <tbody>
            {items}
        </tbody>
        <tfoot>
            <tr>
                <th colspan="8" rowspan="4" class="text-end pe-3"><span style="writing-mode: vertical-rl;">前月合計</span></th>
                <th colspan="2" class="text-end">口座振替合計</th>
                <th class="text-end">{$lastChargeFurikaeTotalText}</th>
                <th colspan="2" rowspan="4" class="text-end pe-3"><span style="writing-mode: vertical-rl;">当月合計</span></th>
                <th class="text-end">{$chargeFurikaeTotalText}</th>
                <th class="text-end">{$diffFurikaeTotalText}</th>
                <th colspan="3"></th>
            </tr>
            <tr>
                <th colspan="2" class="text-end">振込合計</th>
                <th class="text-end">{$lastChargeFurikomiTotalText}</th>
                <th class="text-end">{$chargeFurikomiTotalText}</th>
                <th class="text-end">{$diffFurikomiTotalText}</th>
                <th colspan="3"></th>
            </tr>
            <tr>
                <th colspan="2" class="text-end">その他合計</th>
                <th class="text-end">{$lastChargeOthersTotalText}</th>
                <th class="text-end">{$chargeOthersTotalText}</th>
                <th class="text-end">{$diffOthersTotalText}</th>
                <th colspan="3"></th>
            </tr>
            <tr>
                <th colspan="2" class="text-end">回収額合計</th>
                <th class="text-end">{$lastChargeTotalText}</th>
                <th class="text-end">{$chargeTotalText}</th>
                <th class="text-end">{$diffTotalText}</th>
                <th colspan="3"></th>
            </tr>
        </tfoot>
    </table>
</div>
{pager}
EOL;
?>
                <?= ListView::widget([
                    'dataProvider' => $dataProvider,
                    'itemView' => 'iv-lease-contract-calc-collection-data',
                    'itemOptions' => ['tag' => false],
                    'viewParams' => ['targetTerm' => $targetTerm, 'lastTerm' => $lastTerm, 'dataProvider' => $dataProvider, 'totals' => $totals, 'isUpdateEnable' => $collectionUpdateEnable],//compact("targetTerm", "lastTerm", "totals"),
                    'layout' => $layout,
                ]) ?>
            <?php \yii\widgets\Pjax::end(); ?>
        </div>
    <div id="updater"></div>
    <div id="updater-overlay"></div>
<?php endif; ?>
</section>
