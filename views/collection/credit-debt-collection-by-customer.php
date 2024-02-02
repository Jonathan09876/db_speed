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

$this->title = '売掛買掛集計（顧客別）';

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
    .on('change', '[name="span"]', function(){
        var span=$('[name="span"]:checked').val();
        location.href='/collection/schedules?span='+span;
    })
$('.formatted').format();
let width1 = $('.sticky-header1').outerWidth();
let width2 = $('.sticky-header2').outerWidth();
let width3 = $('.sticky-header3').outerWidth();
let width4 = $('.sticky-header4').outerWidth();
let width5 = $('.sticky-header5').outerWidth();
let width6 = $('.sticky-header6').outerWidth();
let width7 = $('.sticky-header7').outerWidth();
let width8 = $('.sticky-header8').outerWidth();
let width9 = $('.sticky-header9').outerWidth();
setCssStyle('.sticky-header2', 'left', width1+'px;')
setCssStyle('.sticky-header3', 'left', (width1+width2)+'px;')
setCssStyle('.sticky-header4', 'left', (width1+width2+width3)+'px;')
setCssStyle('.sticky-header5', 'left', (width1+width2+width3+width4)+'px;')
setCssStyle('.sticky-header6', 'left', (width1+width2+width3+width4+width5)+'px;')
setCssStyle('.sticky-header7', 'left', (width1+width2+width3+width4+width5+width6)+'px;')
setCssStyle('.sticky-header8', 'left', (width1+width2+width3+width4+width5+width6+width7)+'px;')
setCssStyle('.sticky-header9', 'left', (width1+width2+width3+width4+width5+width6+width7+width8)+'px;')
setCssStyle('.sticky-header10', 'left', (width1+width2+width3+width4+width5+width6+width7+width8+width9)+'px;')
setCssStyle('.sticky-cell2', 'left', width1+'px;')
setCssStyle('.sticky-cell3', 'left', (width1+width2)+'px;')
setCssStyle('.sticky-cell4', 'left', (width1+width2+width3)+'px;')
setCssStyle('.sticky-cell5', 'left', (width1+width2+width3+width4)+'px;')
setCssStyle('.sticky-cell6', 'left', (width1+width2+width3+width4+width5)+'px;')
setCssStyle('.sticky-cell7', 'left', (width1+width2+width3+width4+width5+width6)+'px;')
setCssStyle('.sticky-cell8', 'left', (width1+width2+width3+width4+width5+width6+width7)+'px;')
setCssStyle('.sticky-cell9', 'left', (width1+width2+width3+width4+width5+width6+width7+width8)+'px;')
setCssStyle('.sticky-cell10', 'left', (width1+width2+width3+width4+width5+width6+width7+width8+width9)+'px;')

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

.sticky-header1 {
    position:sticky;
    top:0;
    left: 0px;
    border-left-width: 1;
    z-index:3!important;
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
    z-index:3!important;
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
    z-index:3!important;
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
    z-index:3!important;
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
    z-index:3!important;
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
    z-index:3!important;
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
    z-index:3!important;
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
    z-index:3!important;
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
    z-index:3!important;
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
    z-index:3!important;
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
.text-red {
    color: #ff0000;
}
.total-row td {
    background-color: #ffffdd !important;
}
.table-wrapper table.table td.charge-side {
    background-color: #ffeeee !important;
}
.table-wrapper table.table td.debt-side {
    background-color: #eeeeee !important;
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
                                <h5 class="card-title mb-0">売掛買掛集計検索</h5>
                            </div>
                        </div>
                    </div>
                    <?php $form = ActiveForm::begin([
                        'layout' => 'horizontal',
                    ]) ?>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <?php $current = (int)date('Y');
                                $years = range($current - 5, $current + 5);
                                $yearSelect = array_combine($years, array_map(function($year){return "{$year}年";}, $years)); ?>
                                <?= $form->field($searchModel, 'target_term_year', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-first',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-sm-5',
                                ],])->dropDownList($yearSelect, ['class' => 'form-control form-select']) ?>
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
                        <?= Html::submitInput('売掛買掛集計（リース会社別）', ['class' => 'btn btn-success', 'name' => 'ScheduleSearch[credit_debt_collection_by_agency]', 'value' => 'servicer']) ?>
                        <?= Html::submitInput('売掛買掛集計（顧客別）', ['class' => 'btn btn-success', 'name' => 'ScheduleSearch[credit_debt_collection_by_customer]', 'value' => 'customer']) ?>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
<?php if ($searchModel->do_search) : ?>
    <div class="card mb-2">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-auto">
                        <h5 class="card-title mb-0">売掛買掛一覧（顧客別）</h5>
                    </div>
                    <div class="col-auto">
                        <div class="btn-group" role="group" aria-label="期間切替">
                            <input type="radio" class="btn-check" name="span" id="vbtn-radio1" value="year"<?= $searchModel->span=='year' ? ' checked' : ''?>>
                            <label class="btn btn-sm btn-outline-secondary" for="vbtn-radio1">今期</label>
                            <input type="radio" class="btn-check" name="span" id="vbtn-radio2" value="quarter1"<?= $searchModel->span=='quarter1' ? ' checked' : ''?>>
                            <label class="btn btn-sm btn-outline-secondary" for="vbtn-radio2">第1四半期</label>
                            <input type="radio" class="btn-check" name="span" id="vbtn-radio3" value="quarter2"<?= $searchModel->span=='quarter2' ? ' checked' : ''?>>
                            <label class="btn btn-sm btn-outline-secondary" for="vbtn-radio3">第2四半期</label>
                            <input type="radio" class="btn-check" name="span" id="vbtn-radio4" value="quarter3"<?= $searchModel->span=='quarter3' ? ' checked' : ''?>>
                            <label class="btn btn-sm btn-outline-secondary" for="vbtn-radio4">第3四半期</label>
                            <input type="radio" class="btn-check" name="span" id="vbtn-radio5" value="quarter4"<?= $searchModel->span=='quarter4' ? ' checked' : ''?>>
                            <label class="btn btn-sm btn-outline-secondary" for="vbtn-radio5">第4四半期</label>
                        </div>
                    </div>
                    <div class="col-auto">
                        <?php $span = $searchModel->getTermsFromSpan() ?>
                        <?= $span['from']->format('Y年n月〜').$span['to']->format('Y年n月')?>
                    </div>
                    <div class="col d-flex justify-content-end">
                        <?= Html::a('CSVエクスポート', ['/collection/export-debt-collection-by-customer'], ['class' => 'me-1 btn btn-sm btn-success']) ?>
                        <?= Html::a('PDF出力', ['/publish/debt-collection-by-customer'], ['class' => 'btn btn-sm btn-secondary']) ?>
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
                $span = $searchModel->getTermsFromSpan();
                $query = clone $dataProvider->query;

                $query->select([
                    '`c`.`customer_id`',
                    'GROUP_CONCAT(DISTINCT cd.contract_detail_id ORDER BY lc.disp_order DESC) as cdids',
                ])
                    ->groupBy(['c.customer_id']);

                $totals = \yii\helpers\ArrayHelper::index($query->asArray()->all(), 'customer_id');
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
                <th class="sticky-header5">リース開始</th>
                <th class="sticky-header6">リース終了</th>
                <th class="sticky-header7">登録No.</th>
                <th class="sticky-header8">リース期間</th>
                <th class="sticky-header9">リース料発生月</th>
                <th class="sticky-header10">回数</th>
                <th>{$span['to']->format('Y/m')}<br/>回数</th>
                <th>リース料</th>
                <th>非課税未収<br/>売掛金</th>
                <th>8%未収<br/>売掛金</th>
                <th>10%未収<br/>売掛金</th>
                <th>非課税<br/>前払い</th>
                <th>8%<br/>前払い</th>
                <th>10%<br/>前払い</th>
                <th>リース会社</th>
                <th>{$span['to']->format('Y/m')}<br/>回数</th>
                <th>支払リース料</th>
                <th>非課税未払<br/>買掛金</th>
                <th>8%未払<br/>買掛金</th>
                <th>10%未払<br/>買掛金</th>
                <th>非課税<br/>前払い</th>
                <th>8%<br/>前払い</th>
                <th>10%<br/>前払い</th>
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
                    'itemView' => 'iv-credit-debt-collection-by-customer-new',
                    'itemOptions' => ['tag' => false],
                    'viewParams' => ['targetTerm' => $targetTerm, 'dataProvider' => $dataProvider, 'totals' => $totals, 'searchModel' => $searchModel, 'span' => $span],
                    'layout' => $layout,
                ]) ?>
            <?php \yii\widgets\Pjax::end(); ?>
        </div>

    <?php endif; ?>
</section>
