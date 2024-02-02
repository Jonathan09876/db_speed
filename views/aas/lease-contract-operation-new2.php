<?php
/**
 * @var $this \yii\web\View
 * @var $searchModel \app\models\MonthlyChargeSearch2;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

use app\models\MonthlyChargeSearch2;
use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\widgets\ListView;
use yii\bootstrap5\Html;
use app\widgets\datetimepicker\Datetimepicker;
use app\models\RepaymentPattern;
use kartik\typeahead\Typeahead;
use yii\helpers\Url;

$this->title = '回収予定一覧';

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
    $('#monthlychargesearch2-customer_id').val(current.data('id'));
    $('#monthlychargesearch2-customer_code').val(current.data('code'));
    $('#monthlychargesearch2-customer_name').val(current.data('name'));
    listShown = false;
    $('#customer-list').remove();
}
function setupSticky() {
    let width1 = $('.sticky-header1').outerWidth();
    let width2 = $('.sticky-header2').outerWidth();
    let width3 = $('.sticky-header3').outerWidth();
    setCssStyle('.sticky-header2', 'left', width1+'px;')
    setCssStyle('.sticky-header3', 'left', (width1+width2)+'px;')
    setCssStyle('.sticky-header4', 'left', (width1+width2+width3)+'px;')
    setCssStyle('.sticky-cell2', 'left', width1+'px;')
    setCssStyle('.sticky-cell3', 'left', (width1+width2)+'px;')
    setCssStyle('.sticky-cell4', 'left', (width1+width2+width3)+'px;')
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
    .on('focus', '#monthlychargesearch2-customer_code,#monthlychargesearch2-customer_name', function(){
        if ($('[name="MonthlyChargeSearch2[client_corporation_id]"]:checked').length == 0) {
            $(this).blur();
            alert('まず先に「会社」を選択してください。得意先コード、得意先名の検索には「会社」の指定が必要です。');
        }
        else {
            client_corp_id = $('[name="MonthlyChargeSearch2[client_corporation_id]"]:checked').val();
        }
    })
    .on('click', '[name="MonthlyChargeSearch2[client_corporation_id]"]', function(){
        $('#monthlychargesearch2-customer_code').val('');
        $('#monthlychargesearch2-customer_name').val('');
    })
    .on('keydown', '#monthlychargesearch2-customer_code,#monthlychargesearch2-customer_name', function(evt){
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
                if (evt.target.id == 'monthlychargesearch2-customer_code') {
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
    .on('pjax:complete', setupSticky)
    .on('change', '#monthlychargesearch2-hide_collection', function(){
        if ($(this).is(':checked') && $('#monthlychargesearch2-hide_payment').is(':checked')) {
            $('#monthlychargesearch2-hide_payment:checked').prop('checked',false);
        }
    })
    .on('change', '#monthlychargesearch2-hide_payment', function(){
        if ($(this).is(':checked') && $('#monthlychargesearch2-hide_collection').is(':checked')) {
            $('#monthlychargesearch2-hide_collection:checked').prop('checked',false);
        }
    })
    .on('click', '.editable', updateContent)
$('.formatted').format();
setupSticky();
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
}
.table-wrapper table.table th,
.table-wrapper table.table td {
    border-left-width: 0;
}
.table-wrapper table.table thead th {
    position: sticky;
    top:0;
    z-index:2;
}
.sticky-header1 {
    position:sticky;
    top:0;
    left: 0px;
    border-left-width: 1;
    z-index:3 !important;
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
    z-index:3 !important;
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
    z-index:3 !important;
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
    z-index:3 !important;
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
.table-wrapper table.table th.current-term {
    background-color: var(--vz-indigo);
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
                                <h5 class="card-title mb-0">回収予定一覧検索</h5>
                            </div>
                        </div>
                    </div>
                    <?php $form = ActiveForm::begin([
                        'layout' => 'horizontal',
                    ]) ?>
                    <div class="card-body">
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
                        <div class="row">
                            <div class="col-md-4">
                                <?php $current = (int)date('Y');
                                $years = range($current - 5, $current + 5);
                                $yearSelect = array_combine($years, array_map(function($year){return "{$year}年";}, $years)); ?>
                                <?= Html::activeHiddenInput($searchModel, 'target_term') ?>
                                <?= $form->field($searchModel, 'target_term_year', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-first',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-sm-5',
                                ],])->dropDownList($yearSelect, ['class' => 'form-control form-select']) ?>
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
                        <div class="hstack gap-2 mb-3">
                            <label class="form-label col-first">収支</label>
                            <?= Html::activeCheckbox($searchModel, 'hide_collection', ['class' => 'form-check-input', 'value' => 1, 'uncheck' => null]) ?>
                            <?= Html::activeCheckbox($searchModel, 'hide_payment', ['class' => 'form-check-input', 'value' => 1, 'uncheck' => null]) ?>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <?= Html::submitButton('この内容で検索', ['class' => 'btn btn-primary']) ?>
                        <?= Html::submitInput('引落データ作成', ['class' => 'btn btn-info', 'name' => 'MonthlyChargeSearch2[calc_collection_data]']) ?>
                        <?= Html::submitInput('売掛買掛集計（リース会社別）', ['class' => 'btn btn-success', 'name' => 'MonthlyChargeSearch2[credit_debt_collection_by_agency]', 'value' => 'servicer']) ?>
                        <?= Html::submitInput('売掛買掛集計（顧客別）', ['class' => 'btn btn-success', 'name' => 'MonthlyChargeSearch2[credit_debt_collection_by_customer]', 'value' => 'customer']) ?>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <h5 class="card-title mb-0">回収実績登録一覧</h5>
                    </div>
                </div>
            </div>
            <?php \yii\widgets\Pjax::begin([
                'id' => 'pjax-grid-wrapper',
                'options' => [
                    'class' => 'card-body',
                ],
                'linkSelector' => 'th a',
                'timeout' => 5000.
            ]); ?>
                <?php
                $term = empty($searchModel->target_term) ? (new \DateTime())->format('Y年m月') : $searchModel->target_term;
                $targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $term));
                preg_match('/(\d+)年(\d+)月/', $term, $matched);
                $current = clone $targetTerm;
                $closing_month = $searchModel->getClientCorporation()->account_closing_month;
                $current->setDate((int)$matched[1] - ($closing_month == 12 ? 1 : 0), $closing_month == 12 ? 1 : $closing_month+1, 1);
                if ($current > $targetTerm) {
                    $current = $current->modify('-1 year');
                }
                $terms = [];
                for ($i = 0; $i < 12; $i++) {
                    $interval = $targetTerm->diff($current);
                    $relative_month = -((int)$targetTerm->format('n') - ((int)$current->format('n') - ((int)$targetTerm->format('Y') - (int)$current->format('Y')) * 12));
                    $terms[] = [
                        'the_term' => clone $current,
                        'relative_month' => $relative_month,
                    ];
                    $current = $current->modify('+1 month');
                }
                $cf_sortable = $dataProvider->sort->link('cf');
                $rp_sortable = $dataProvider->sort->link('rp');
                $widget = new \app\widgets\PageSizeLimitChanger([
                    'pjax_id' => 'pjax-grid-wrapper',
                ]);
                $summaryLayout = $widget->summaryLayout;
                $dataProvider->pagination = $widget->pagination;
                $layout = <<<EOL
{$summaryLayout}
<div class="table-wrapper">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th class="sticky-header1">{$cf_sortable}</th>
                <th class="sticky-header2">{$rp_sortable}</th>
                <th class="sticky-header3">顧客名
                <th class="sticky-header4">契約No.</th>
                <th>税率</th>
                <th>リース開始<br>リース終了</th>
                <th>登録No.</th>
                <th>リース期間<br/>会社</th>
                <th>収支</th>
EOL;
                foreach($terms as $the_term) {
                    $termText = $the_term['the_term']->format('Y/m');
                    if ($term != $the_term['the_term']->format('Y年n月')) {
                        $layout .= <<<EOL
                <th>回数</th>
                <th style="width:140px;">{$termText}</th>
EOL;
                    }
                    else {
                        $layout .= <<<EOL
                <th class="current-term">回数</th>
                <th class="current-term" style="width:140px;">{$termText}<br/>予定額<sub>（税込）</sub></th>
                <th class="current-term" style="width:140px;">修正額<sub>（税込）</sub><br>不足額</th>
EOL;
                    }
                }
                $layout .= <<<EOL
                <th>合計</th>
                <th>リース開始<br>残り回数</th>
                <th>債務残高</th>
                <th>月額リース料</th>
            </tr>
        </thead>
        <tbody>
            {items}
        </tbody>
    </table>
</div>
{pager}
EOL;
                ?>
                <?= ListView::widget([
                    'dataProvider' => $dataProvider,
                    'itemView' => 'iv-lease-contract-operation3',
                    'itemOptions' => ['tag' => false],
                    'viewParams' => compact("targetTerm", "terms", "searchModel"),
                    'layout' => $layout,
                ]) ?>
            <?php \yii\widgets\Pjax::end(); ?>
        </div>
    <div id="updater"></div>
    <div id="updater-overlay"></div>
</section>

