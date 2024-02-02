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

$this->title = '遅延集計';

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
    $('#schedulesearch-customer_id').val(current.data('id'));
    $('#schedulesearch-customer_code').val(current.data('code'));
    $('#schedulesearch-customer_name').val(current.data('name'));
    listShown = false;
    $('#customer-list').remove();
}
function setupSticky() {
    let width1 = $('.sticky-header1').outerWidth();
    let width2 = $('.sticky-header2').outerWidth();
    let width3 = $('.sticky-header3').outerWidth();
    let width4 = $('.sticky-header4').outerWidth();
    let width5 = $('.sticky-header5').outerWidth();
    let width6 = $('.sticky-header6').outerWidth();
    let width7 = $('.sticky-header7').outerWidth();
    let width8 = $('.sticky-header8').outerWidth();
    let width9 = $('.sticky-header9').outerWidth();
    setCssStyle('.sticky-header2', 'left', width1+'px;');
    setCssStyle('.sticky-header3', 'left', (width1+width2)+'px;');
    setCssStyle('.sticky-header4', 'left', (width1+width2+width3)+'px;');
    setCssStyle('.sticky-header5', 'left', (width1+width2+width3+width4)+'px;');
    setCssStyle('.sticky-header6', 'left', (width1+width2+width3+width4+width5)+'px;');
    setCssStyle('.sticky-header7', 'left', (width1+width2+width3+width4+width5+width6)+'px;');
    setCssStyle('.sticky-header8', 'left', (width1+width2+width3+width4+width5+width6+width7)+'px;');
    setCssStyle('.sticky-header9', 'left', (width1+width2+width3+width4+width5+width6+width7+width8)+'px;');
    setCssStyle('.sticky-header10', 'left', (width1+width2+width3+width4+width5+width6+width7+width8+width9)+'px;');
    setCssStyle('.sticky-cell2', 'left', width1+'px;');
    setCssStyle('.sticky-cell3', 'left', (width1+width2)+'px;');
    setCssStyle('.sticky-cell4', 'left', (width1+width2+width3)+'px;');
    setCssStyle('.sticky-cell5', 'left', (width1+width2+width3+width4)+'px;');
    setCssStyle('.sticky-cell6', 'left', (width1+width2+width3+width4+width5)+'px;');
    setCssStyle('.sticky-cell7', 'left', (width1+width2+width3+width4+width5+width6)+'px;');
    setCssStyle('.sticky-cell8', 'left', (width1+width2+width3+width4+width5+width6+width7)+'px;');
    setCssStyle('.sticky-cell9', 'left', (width1+width2+width3+width4+width5+width6+width7+width8)+'px;');
    setCssStyle('.sticky-cell10', 'left', (width1+width2+width3+width4+width5+width6+width7+width8+width9)+'px;');
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
    .on('pjax:complete', setupSticky)
    .on('change', '#schedulesearch-hide_collection', function(){
        if ($(this).is(':checked') && $('#schedulesearch-hide_payment').is(':checked')) {
            $('#schedulesearch-hide_payment:checked').prop('checked',false);
        }
    })
    .on('change', '#schedulesearch-hide_payment', function(){
        if ($(this).is(':checked') && $('#schedulesearch-hide_collection').is(':checked')) {
            $('#schedulesearch-hide_collection:checked').prop('checked',false);
        }
    })
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
.sticky-header5 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell5 {
    position: sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
}
.sticky-header6 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell6 {
    position: sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
}
.sticky-header7 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell7 {
    position: sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
}
.sticky-header8 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell8 {
    position: sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
}
.sticky-header9 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell9 {
    position: sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
}
.sticky-header10 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell10 {
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
.paid {
    background-color: #e8e8e8!important;
}
.deficient {
    background-color: #ffd4d4!important;
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
                            <h5 class="card-title mb-0">遅延集計検索</h5>
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
                    <?= Html::submitInput('引落データ作成', ['class' => 'btn btn-info', 'name' => 'ScheduleSearch[calc_collection_data]']) ?>
                    <?= Html::submitInput('遅延集計', ['class' => 'btn btn-secondary', 'name' => 'ScheduleSearch[delinquencies]', 'value' => 'delinquencies']) ?>
                    <?= Html::submitInput('売掛買掛集計（リース会社別）', ['class' => 'btn btn-success', 'name' => 'ScheduleSearch[credit_debt_collection_by_agency]', 'value' => 'servicer']) ?>
                    <?= Html::submitInput('売掛買掛集計（顧客別）', ['class' => 'btn btn-success', 'name' => 'ScheduleSearch[credit_debt_collection_by_customer]', 'value' => 'customer']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
    <div class="card mb-2">
        <div class="card-header">
            <div class="row align-items-center justify-content-between g-3">
                <div class="col-md-3">
                    <h5 class="card-title mb-0">遅延集計</h5>
                </div>
                <div class="col-auto">
                    <?= Html::a('PDF出力', ['publish/delinquencies'], ['class' => 'btn btn-sm btn-secondary', 'target' => '_blank']) ?>
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
        $span = $searchModel->getTermsFromSpan();
        $targetTerm = new \DateTime($searchModel->target_term_year . date('-m-01'));
        $current = $span['from'];
        $terms = [];
        for ($i = 0; $i < 12; $i++) {
            $interval = $targetTerm->diff($current);
            $term = \app\models\Term::findOne(['term' => $current->format('Y-m-d')]);
            $term->relative_month = -((int)$targetTerm->format('n') - ((int)$current->format('n') - ((int)$targetTerm->format('Y') - (int)$current->format('Y')) * 12));
            $terms[] = $term;
            $current = $current->modify('+1 month');
        }
        $cf_sortable = $dataProvider->sort->link('cf');
        $rp_sortable = $dataProvider->sort->link('rp');
        $widget = new \app\widgets\PageSizeLimitChanger([
            'pjax_id' => 'pjax-grid-wrapper',
        ]);
        $summaryLayout = $widget->summaryLayout;
        $dataProvider->pagination = $widget->pagination;
        $prev = (clone $terms[0])->termDateTime->modify('-1 month');
        $prevMonthText = $prev->format('Y/m');
        $targetYearMonth = preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $searchModel->target_term);
        $currentMonthTerm = \app\models\Term::findOne(['term' => $targetYearMonth]);
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
                <th class="sticky-header5">税率</th>
                <th class="sticky-header6">リース開始<br>リース終了</th>
                <th class="sticky-header7">登録No.</th>
                <th class="sticky-header8">毎月回収額</th>
                <th class="sticky-header9">{$prevMonthText}<br/>回収残高</th>
                <th class="sticky-header10">回収額<br/>残額</th>
EOL;
        $delinquencyTotal = 0;
        $delinquencySubtotals = [];
        $collectionTotal = 0;
        $collectionSubtotals = [];
        $remainsSubtotals = [];
        foreach($terms as $term) {
            $termText = $term->termDateTime->format('Y/m');
            $layout .= <<<EOL
                <th style="width:140px;">{$termText}</th>
EOL;
            $delinquencySubtotal = 0;
            $collectionSubtotal = 0;
            $remainsSubtotal = 0;
            $models = $dataProvider->models;
            while($detail = array_shift($models)) {
                $collectionCell = \app\models\CollectionCell::getInstance($detail->contract_detail_id, $term->term_id);
                $delinquency = $collectionCell->monthly_charge_amount_with_tax - $collectionCell->repayment_amount_with_tax;
                $options = json_decode($collectionCell->options, true);
                $collectionSubtotal += (isset($options['rpid']) && !empty($options['rpid']) ? $collectionCell->repayment_amount_with_tax : 0);
                if ($term->termDateTime <= $currentMonthTerm->termDateTime) {
                    $delinquencySubtotal += $delinquency;
                }
                $remainsSubtotal += $detail->getChargeRemains($term);
            }
            $delinquencyTotal += $delinquencySubtotal;
            $delinquencySubtotals[$termText] = $delinquencySubtotal;
            $collectionTotal += $collectionSubtotal;
            $collectionSubtotals[$termText] = $collectionSubtotal;
            $remainsSubtotals[$termText] = $remainsSubtotal;
        }
        $layout .= <<<EOL

                <th >回収残額合計</th>
                <th >遅延額合計</th>
                <th>特記事項</th>
            </tr>
        </thead>
        <tbody>
            {items}
        </tbody>
        <tfoot>
            <tr>
                <th colspan="10" class="sticky-header1 text-end">未回収額合計</th>
EOL;
        $terms1 = $terms;
        while($term = array_shift($terms1)) {
            $termText = $term->termDateTime->format('Y/m');
            $subTotalText = number_format($delinquencySubtotals[$termText], 0);
            $layout .= <<<EOL
                <th class="text-end">{$subTotalText}</th>
EOL;
        }
        $collectionRemainsTotal = 0;
        $models = $dataProvider->models;
        while($detail = array_shift($models)) {
            $collectionRemainsTotal += $detail->getChargeRemains($terms[11]);
        }
        $collectionRemainsTotalText = number_format($collectionRemainsTotal, 0);
        $delinquencyTotalText = number_format($delinquencyTotal, 0);
        $layout .= <<<EOL
                <th>{$collectionRemainsTotalText}</th>
                <th>{$delinquencyTotalText}</th>
                <th>&nbsp;</th>
            </tr>
            <tr>
                <th colspan="10" class="sticky-header1 text-end">回収額合計</th>
EOL;
        $terms2 = $terms;
        while($term = array_shift($terms2)) {
            $termText = $term->termDateTime->format('Y/m');
            $collectionSubtotalText = number_format($collectionSubtotals[$termText], 0);
            $layout .= <<<EOL
                <th class="text-end">{$collectionSubtotalText}</th>
EOL;
        }
        $collectionTotalText = number_format($collectionTotal, 0);
        $layout .= <<<EOL
                <th>&nbsp;</th>
                <th>{$collectionTotalText}</th>
                <th>&nbsp;</th>
            </tr>
            <tr>
                <th colspan="10" class="sticky-header1 text-end">残額合計</th>
EOL;
        $terms3 = $terms;
        while($term = array_shift($terms3)) {
            $termText = $term->termDateTime->format('Y/m');
            $remainsSubtotalText = number_format($remainsSubtotals[$termText], 0);
            $layout .= <<<EOL
                <th class="text-end">{$remainsSubtotalText}</th>
EOL;
        }
        $layout .= <<<EOL
                <th class="text-end">{$collectionRemainsTotalText}</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </tr>
        </tfoot>
    </table>
</div>
{pager}
EOL;
        ?>
        <?= ListView::widget([
            'dataProvider' => $dataProvider,
            'itemView' => 'iv-collection-delinquencies-latest',
            'itemOptions' => ['tag' => false],
            'viewParams' => compact("terms", "searchModel"),
            'layout' => $layout,
        ]) ?>
        <?php \yii\widgets\Pjax::end(); ?>
    </div>
    <div id="updater"></div>
    <div id="updater-overlay"></div>
</section>


