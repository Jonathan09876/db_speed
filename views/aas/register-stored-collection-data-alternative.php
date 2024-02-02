<?php
/**
 * @var $this \yii\web\View
 * @var $model \app\models\TargetTermMonthlyChargeStored
 * @var $searchModel \app\models\RepaymentSearch
 */

use yii\bootstrap5\ActiveForm;
use yii\widgets\ListView;
use yii\bootstrap5\Html;
use app\models\Repayment;
use app\models\RepaymentType;
use app\models\ContractPattern;
use yii\bootstrap5\Modal;

$dataProvider = $searchModel->search(Yii::$app->request->post());
Yii::$app->db->createCommand('SET group_concat_max_len = 5120')->execute();
$sql = "SUM(CASE cd.fraction_processing_pattern 
        WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
        WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    END) AS `customer_total`";
$query = clone $dataProvider->query;
$query->select([
    '`c`.`customer_id`',
    'GROUP_CONCAT(DISTINCT cd.contract_detail_id ORDER BY lc.disp_order ASC) AS cdids',
    'MIN(`cd`.`contract_detail_id`) AS `first_cdid`',
    'COUNT(DISTINCT mc.monthly_charge_id) AS `rowspan`',
    $sql,
])
    ->innerJoin('tax_application ta', 'cd.tax_application_id=ta.tax_application_id')
    ->groupBy(['c.customer_id']);
$totals = \yii\helpers\ArrayHelper::index($query->asArray()->all(), 'customer_id');

$script = <<<JS
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
function calcCheckedTotal() {
    let checked_total = 0;
    $('[type="checkbox"][name$="[collected]"]:checked').each(function(){
        checked_total += $(this).parent().next().next().find('input[name$="[repayment_amount]"]').numVal();
    })
    //$('[type="checkbox"][name$="[cancelled]"]').each(function(){
    //    checked_total += $(this).parent().parent().next().next().find('input.formatted').numVal();
    //})
    $('#checked-total').text(numberFormat(checked_total));
    $('#checked-total2').text(numberFormat(checked_total));
}
function checkCancelEnable() {
    if ($('.cell-checkbox input[type="checkbox"][name$="[cancelled]"]:checked').length > 0) {
        $('.btn-cancel-registration').removeClass('disabled');
    }
    else {
        $('.btn-cancel-registration').addClass('disabled');
    }
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
var modalSubmit = false, modalShown = false;
$(document)
    .on('keyup', '.formatted', function(){
        $(this).format();
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
        $('#'+wrapper.attr('id')).on('pjax:success', function(evt){
            let width1 = $('.sticky-header1').outerWidth();
            let width2 = $('.sticky-header2').outerWidth();
            let width3 = $('.sticky-header3').outerWidth();
            let height1 = $('.sticky-row1 th.sticky-header1').outerHeight();
            setCssStyle('.sticky-header2', 'left', width1+'px;');
            setCssStyle('.sticky-header3', 'left', (width1+width2)+'px;');
            setCssStyle('.sticky-header4', 'left', (width1+width2+width3)+'px;');
            setCssStyle('.sticky-cell2', 'left', width1+'px;');
            setCssStyle('.sticky-cell3', 'left', (width1+width2)+'px;');
            setCssStyle('.sticky-cell4', 'left', (width1+width2+width3)+'px;');
            setCssStyle('.sticky-row2 th', 'top', height1+'px!important');
        });
        $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
    })
    .on('keydown', '.form-filter', function(evt){
        if (evt.key == 'Enter') {
            $(this).change();
            evt.preventDefault();
            return false;
        }
    })
    .on('click', '#check-all', function(){
        $('.cell-checkbox input[type="checkbox"]:not([name$="[cancelled]"])').prop('checked', $(this).is(':checked'));
        $('input[type="checkbox"][name^="check_customer_all"]').prop('checked', $(this).is(':checked'));
        checkCancelEnable();
        calcCheckedTotal();
    })
    .on('change', '.cell-checkbox input[type="checkbox"][name$="[cancelled]"]', checkCancelEnable)
    .on('click', '#check-cancell-all', function(){
        $('.cell-checkbox input[type="checkbox"][name$="[cancelled]"]').prop('checked', $(this).is(':checked'));
        checkCancelEnable();
    })
    .on('click', 'input[type="checkbox"][name^="check_customer_all"]', function(){
        var cid = this.name.match(/check_customer_all\[(\d+)\]/)[1];
        $('.cell-checkbox input[type="checkbox"][data-cid="'+cid+'"]').prop('checked', $(this).is(':checked'));
        checkCancelEnable();
        calcCheckedTotal();
    })
    .on('click', '[type="checkbox"][name$="[collected]"]', calcCheckedTotal)
    .on('click', '.btn-cancel-repayment', async function(){
        if (confirm('この回収実績を取消しても良いですか？')) {
            let id = $(this).data('id');
            let response = await $.getJSON('/update/deletable-repayment?id='+id)
            if (response.success) {
                $.pjax.reload('#pjax-grid-wrapper', {timeout : false});
            }
        }
    })
    .on('click', '.btn-set-amount-zero', function(){
        $('.cell-checkbox input[type="checkbox"][name$="[collected]"]:checked').parent().next().next().find('[name$="[repayment_amount]"]').val(0);
        modalSubmit = true;
        modalShown = true;
        $(this).parents('form').submit();
    })
    .on('click', '.btn-cancel-registration', function(){
        modalSubmit = true;
        modalShown = true;
        $(this).parents('form').submit();
    })
    .on('beforeSubmit', '#register-repayments-form', function(evt){
        if (!modalSubmit) {
            evt.preventDefault();
            if (!modalShown) {
                modalShown = true;
                $('#modal-register-stored-collection-data').modal('show');
                $('.btn-form-submit').on('click', function(){
                    modalSubmit = true;
                    //$('#register-repayments-form').submit();
                    var params = [];
                    $('#register-repayments-form').serializeArray().forEach(function(param){
                        if (param.name.match(/^Repayment/) == null) {
                            params.push(param);
                        }
                    });
                    var rows_count = $('#register-repayments-form .table tbody input[type="checkbox"][name$="[collected]"]:checked').length;
                    var count = 0;
                    var indexs = [];
                    var datas = [];
                    $('#register-repayments-form .table tbody tr').each(async function(){
                        var row = $(this), collected_check = $('input[type="checkbox"][name$="[collected]"]', this),
                            index = row.data('index');
                        if (collected_check.length > 0 && $(collected_check).is(':checked')) {
                            var data = params.concat($(':input', this).serializeArray());
                            tag = false;

                            indexs.push(index); datas.push(data);

                            tag = await $.ajax({
                                type: 'POST',
                                url: '/aas/register-stored-collection-data-by-row'+location.search+'&index='+index,
                                data: data,
                            });
                            count++;
                            if (count == 1) {
                                var rate = (100 / rows_count).toFixed();
                                $('#modal-register-stored-collection-data .modal-footer').append('<div id="progress-registered" class="progress"><div class="progress-bar" role="progressbar" style="width: '+rate+'%;" aria-valuenow="'+rate+'" aria-valuemin="0" aria-valuemax="100"></div></div>');
                            }
                            else {
                                var rate = (count * 100 / rows_count).toFixed();
                                $('#progress-registered').html('<div class="progress-bar" role="progressbar" style="width: '+rate+'%;" aria-valuenow="'+rate+'" aria-valuemin="0" aria-valuemax="100"></div>');
                            }
                            
                        }
                        if (rows_count == count) {
                            //$('#modal-register-stored-collection-data').modal('hide');
                            location.reload();
                        }
                    });

                    // $.ajax({
                    //     type: 'POST',
                    //     url: '/aas/register-stored-collection-data-by-row'+location.search,
                    //     data: data,
                    //     success: function(data) {
                    //         location.reload();
                    //     }
                    // })
                });
                $('#modal-register-stored-collection-data').on('hidden.bs.modal', function(){
                    $('.btn-form-submit').off('click');
                    modalShown = false;
                });
            }
            return false;
        }
        else {
            if (modalShown) {
                return true;
            }
            else {
                evt.preventDefault();
                return false;
            }
        }
    });
calcCheckedTotal();
$('.formatted').format();
let width1 = $('.sticky-header1').outerWidth();
let width2 = $('.sticky-header2').outerWidth();
let width3 = $('.sticky-header3').outerWidth();
let height1 = $('.sticky-row1 th.sticky-header1').outerHeight();
setCssStyle('.sticky-header2', 'left', width1+'px;')
setCssStyle('.sticky-header3', 'left', (width1+width2)+'px;')
setCssStyle('.sticky-header4', 'left', (width1+width2+width3)+'px;')
setCssStyle('.sticky-cell2', 'left', width1+'px;')
setCssStyle('.sticky-cell3', 'left', (width1+width2)+'px;')
setCssStyle('.sticky-cell4', 'left', (width1+width2+width3)+'px;')
setCssStyle('.sticky-row2 th', 'top', height1+'px!important')
JS;
$this->registerJs($script);
$style = <<<CSS
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
.table-wrapper {
    max-height: calc(100vh - 336px);
    position: relative;
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
.table-wrapper table.table tbody th {
    z-index:1;
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
.sticky-header1 {
    position:sticky;
    top:0;
    left: 0px;
    border-left-width: 1;
    z-index:3!important;
}
.sticky-cell {
    position:sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
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
    top:0;
}
.sticky-row2 th {
    top:0;
}
.bg-gray .sticky-cell1 {
    background-color: var(--vz-gray-300)!important;
}
.bg-gray .sticky-cell2 {
    background-color: var(--vz-gray-300)!important;
}
.bg-gray .sticky-cell3 {
    background-color: var(--vz-gray-300)!important;
}
.bg-gray .sticky-cell4 {
    background-color: var(--vz-gray-300)!important;
}
.col-first {
    -webkit-box-flex: 0;
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    width: 70px;
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
.table-wrapper table.table th.current-term {
    background-color: var(--vz-indigo);
}
.paid {
    background-color: #e8e8e8!important;
}
.deficient {
    background-color: #ffd4d4!important;
}
#checked-total2 {
    display:block;
    text-align: right;
}
.ajax-loader {
    position: absolute;
    top: 0;
    left: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: rgba(0,0,0,.1);
}
.modal-ajax-loader {
    border: 5px solid #f3f3f3;
    -webkit-animation: spin 1s linear infinite;
    animation: spin 1s linear infinite;
    border-top: 5px solid #555;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    margin: auto;
}

@-webkit-keyframes spin {
    0% {
        -webkit-transform: rotate(0deg);
        -ms-transform: rotate(0deg);
        transform: rotate(0deg);
    }

    100% {
        -webkit-transform: rotate(360deg);
        -ms-transform: rotate(360deg);
        transform: rotate(360deg);
    }
}

@keyframes spin {
    0% {
        -webkit-transform: rotate(0deg);
        -ms-transform: rotate(0deg);
        transform: rotate(0deg);
    }

    100% {
        -webkit-transform: rotate(360deg);
        -ms-transform: rotate(360deg);
        transform: rotate(360deg);
    }
}
#modal-register-stored-collection-data .modal-footer {
    position: relative;
}
#modal-register-stored-collection-data #progress-registered {
    width: 100%;
    position: absolute;
    left: -4px;
    bottom: -5px;
}
CSS;
$this->registerCss($style);

?>
<section id="customers">
<div class="card mb-2">
    <div class="card-header">
        <div class="row align-items-center justify-content-between g-3">
            <div class="col-md-3">
                <h5 class="card-title mb-0">回収実績登録</h5>
            </div>
            <?php if (Yii::$app->session->hasFlash('register-stored-collection-data')) : ?>
            <div class="col-auto">
                    <!-- Primary Alert -->
                    <div class="alert alert-primary" role="alert">
                        <?= Yii::$app->session->getFlash('register-stored-collection-data') ?>
                    </div>
            </div>
            <?php endif; ?>
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
    $targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $model->target_term));
    $currentTerm = \app\models\Term::findOne(['term' => $targetTerm->format('Y-m-d')]);
    $session = Yii::$app->session;
    $filter_name = $session['filter-name'] ?? '';
    $filter_code = $session['filter-code'] ?? '';
    $filter_repayment_type_id = $session['filter-repayment_type_id'] ?? '';
    $filter_contract_code = $session['filter-contract_code'] ?? '';
    $widget = new \app\widgets\PageSizeLimitChanger(['pjax_id' => 'pjax-grid-wrapper']);
    $dataProvider->pagination = $widget->pagination;
    $cloned = (clone $dataProvider);
    $cloned->pagination = false;
    $models = $cloned->models;
    $currentChargeTotal = array_sum(array_map(function($mc){return $mc->temporaryAmountWithTax;}, $cloned->models));

    /*
    $currentChargeTotal = ($cloned->query)
        ->innerJoin('collection_cell coc', 'cd.contract_detail_id=coc.contract_detail_id AND coc.term_id=:id')
        ->params([':id' => $currentTerm->term_id])
        ->sum('coc.monthly_charge_amount_with_tax');
    */
    $currentFurikaeRepaymentTotal = array_sum(array_map(function($mc){
        $repayment = $mc->repayments[0] ?? false;
        return $repayment && $repayment->repayment_type_id == 1 ? $repayment->repayment_amount : 0;
    }, $cloned->models));
    $currentFurikomiRepaymentTotal = array_sum(array_map(function($mc){
        $repayment = $mc->repayments[0] ?? false;
        return $repayment && in_array($repayment->repayment_type_id, [2,3,4,14]) ? $repayment->repayment_amount : 0;
    }, $cloned->models));
    $currentFurikomiUnchargedTotal = array_sum(array_map(function($mc){
        $repayment = $mc->repayments[0] ?? false;
        return !$repayment && in_array($mc->repaymentType->repayment_type_id, [2,3,4,14]) ? $mc->temporaryAmountWithTax : 0;
    }, $cloned->models));
    $currentChargeTotalText = number_format($currentChargeTotal, 0);
    $currentFurikaeRepaymentTotalText = number_format($currentFurikaeRepaymentTotal, 0);
    $currentFurikaeUnchargedTotalText = number_format($currentChargeTotal - $currentFurikaeRepaymentTotal - $currentFurikomiRepaymentTotal - $currentFurikomiUnchargedTotal, 0);
    $currentFurikomiRepaymentTotalText = number_format($currentFurikomiRepaymentTotal,0);
    $currentFurikomiUnchargedTotalText = number_format($currentFurikomiUnchargedTotal, 0);
    $lastMonthTerm = (clone $targetTerm)->modify('-1 month');
    $lastTerm = \app\models\Term::findOne(['term' => $lastMonthTerm->format('Y-m-d')]);
    $lastMonthCollectionTotal = (clone $dataProvider->query)
        ->innerJoin('collection_cell coc', 'cd.contract_detail_id=coc.contract_detail_id AND coc.term_id=:id')
        ->params([':id' => $lastTerm->term_id])
        ->sum('coc.repayment_amount_with_tax');
    $lastMonthCollectionTotalText = number_format($lastMonthCollectionTotal,0);
    $lastMonthFurikaeRepaymentTotal = array_sum(array_map(function($mc)use($lastTerm){
        $total = 0;
        $lastTermCollectionCell = \app\models\CollectionCell::getInstance($mc->contract_detail_id, $lastTerm->term_id);
        $options = $lastTermCollectionCell ? json_decode($lastTermCollectionCell->options, true) : [];
        if ($lastTermCollectionCell) {
            if (isset($options['rpid']) && !empty($options['rpid'])) {
                $rpid = explode(',', $options['rpid']);
                foreach($rpid as $id) {
                    $repayment = Repayment::findOne($id);
                    $total += ($repayment->repayment_type_id == 1 ? $repayment->repayment_amount : 0);
                }
            }
        }
        return $total;
    }, $cloned->models));
    $lastMonthFurikomiRepaymentTotal = array_sum(array_map(function($mc)use($lastTerm){
        $total = 0;
        $lastTermCollectionCell = \app\models\CollectionCell::getInstance($mc->contract_detail_id, $lastTerm->term_id);
        $options = $lastTermCollectionCell ? json_decode($lastTermCollectionCell->options, true) : [];
        if ($lastTermCollectionCell) {
            if (isset($options['rpid']) && !empty($options['rpid'])) {
                $rpid = explode(',', $options['rpid']);
                foreach($rpid as $id) {
                    $repayment = Repayment::findOne($id);
                    $total += (in_array($repayment->repayment_type_id, [2,3,4,14]) ? $repayment->repayment_amount : 0);
                }
            }
        }
        return $total;
    }, $cloned->models));
    $lastMonthFurikomiRepaymentUnchargedTotal = array_sum(array_map(function($mc)use($lastTerm){
        $total = 0;
        $lastTermCollectionCell = \app\models\CollectionCell::getInstance($mc->contract_detail_id, $lastTerm->term_id);
        $options = $lastTermCollectionCell ? json_decode($lastTermCollectionCell->options, true) : [];
        if ($lastTermCollectionCell) {
            if (!isset($options['rpid']) || empty($options['rpid'])) {
                $total += $lastTermCollectionCell->monthly_charge_amount_with_tax;
            }
        }
        return $total;
    }, $cloned->models));
    $lastMonthFurikaeRepaymentTotalText = number_format($lastMonthFurikaeRepaymentTotal, 0);
    $lastMonthFurikaeRepaymentUnchargedTotalText = number_format($lastMonthCollectionTotal - $lastMonthFurikaeRepaymentTotal - $lastMonthFurikomiRepaymentTotal - $lastMonthFurikomiRepaymentUnchargedTotal, 0);
    $lastMonthFurikomiRepaymentTotalText = number_format($lastMonthFurikomiRepaymentTotal, 0);
    $lastMonthFurikomiRepaymentUnchargedTotalText = number_format($lastMonthFurikomiRepaymentUnchargedTotal, 0);
    $summary = $widget->summaryLayout;
    $checkbox = Html::checkbox('check_all', false, ['id' => 'check-all']);
    $checkbox2 = Html::checkbox('check_cancel_all', false, ['id' => 'check-cancell-all']);
    $filter_repayment_type = Html::dropDownList('filter_repayment_type_id', $filter_repayment_type_id, RepaymentType::getTypes(), ['prompt' => '回収区分を選択', 'class' => 'form-filter form-control form-select', 'data-attr' => 'repayment_type_id']);

    //追加集計分
    $contract_pattern_filters = ContractPattern::getFilteredPatterns($model->client_corporation_id, '自社リース');
    $testPattern = join(',', $contract_pattern_filters);
    /*
    $currentFurikaeRepaymentTotal = array_sum(array_map(function($mc){
        $repayment = $mc->repayments[0] ?? false;
        return $repayment && $repayment->repayment_type_id == 1 ? $repayment->repayment_amount : 0;
    }, $cloned->models));
    $currentFurikomiRepaymentTotal = array_sum(array_map(function($mc){
        $repayment = $mc->repayments[0] ?? false;
        return $repayment && in_array($repayment->repayment_type_id, [2,3,4,14]) ? $repayment->repayment_amount : 0;
    }, $cloned->models));
    */
    function calcFilteredTotal($monthlyCharges, $types, $patterns, $rate = null)
    {
        return array_sum(array_map(function($mc)use($types, $patterns, $rate){
            if ($repayment = $mc->repayments[0] ?? false) {
                if (in_array($mc->contractDetail->leaseContract->contract_pattern_id, $patterns)) {
                    if (in_array($repayment->repayment_type_id, $types)) {
                        if ($rate) {
                            $applicated_rate = Yii::$app->db->createCommand("SELECT 100 * CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE :term >= application_from AND :term <= IFNULL(application_to, '2099-12-31')) END FROM tax_application ta WHERE ta.tax_application_id=:id")
                                ->bindValues([
                                    ':term' => $mc->term,
                                    ':id' => $mc->contractDetail->tax_application_id
                                ])
                                ->queryScalar();
                            if ($applicated_rate == $rate) {
                                return $repayment->repayment_amount;
                            }
                        }
                        else {
                            return $repayment->repayment_amount;
                        }
                    }
                }
            }
            return 0;
        }, $monthlyCharges));
    }
    $summaries = [
        [
            number_format(calcFilteredTotal($cloned->models, [1], ContractPattern::getFilteredPatterns($model->client_corporation_id, '自社リース'), 8), 0),
            number_format(calcFilteredTotal($cloned->models, [1], ContractPattern::getFilteredPatterns($model->client_corporation_id, '自社リース'), 10), 0),
            number_format(calcFilteredTotal($cloned->models, [1], ContractPattern::getFilteredPatterns($model->client_corporation_id, '転リース'), 8), 0),
            number_format(calcFilteredTotal($cloned->models, [1], ContractPattern::getFilteredPatterns($model->client_corporation_id, '転リース'), 10), 0),
            number_format(calcFilteredTotal($cloned->models, [1], ContractPattern::getFilteredPatterns($model->client_corporation_id, '割賦')), 0),
            number_format(calcFilteredTotal($cloned->models, [1], ContractPattern::getFilteredPatterns($model->client_corporation_id, '転割賦')), 0),
            number_format(calcFilteredTotal($cloned->models, [1], ContractPattern::getFilteredPatterns($model->client_corporation_id, '売掛金')), 0),
            number_format(calcFilteredTotal($cloned->models, [1], ContractPattern::getFilteredPatterns($model->client_corporation_id, '手形')), 0),
        ],
        [
            number_format(calcFilteredTotal($cloned->models, [2,3,4,14], ContractPattern::getFilteredPatterns($model->client_corporation_id, '自社リース'), 8), 0),
            number_format(calcFilteredTotal($cloned->models, [2,3,4,14], ContractPattern::getFilteredPatterns($model->client_corporation_id, '自社リース'), 10), 0),
            number_format(calcFilteredTotal($cloned->models, [2,3,4,14], ContractPattern::getFilteredPatterns($model->client_corporation_id, '転リース'), 8), 0),
            number_format(calcFilteredTotal($cloned->models, [2,3,4,14], ContractPattern::getFilteredPatterns($model->client_corporation_id, '転リース'), 10), 0),
            number_format(calcFilteredTotal($cloned->models, [2,3,4,14], ContractPattern::getFilteredPatterns($model->client_corporation_id, '割賦')), 0),
            number_format(calcFilteredTotal($cloned->models, [2,3,4,14], ContractPattern::getFilteredPatterns($model->client_corporation_id, '転割賦')), 0),
            number_format(calcFilteredTotal($cloned->models, [2,3,4,14], ContractPattern::getFilteredPatterns($model->client_corporation_id, '売掛金')), 0),
            number_format(calcFilteredTotal($cloned->models, [2,3,4,14], ContractPattern::getFilteredPatterns($model->client_corporation_id, '手形')), 0),
        ],
    ];





    $layout = <<<EOL
<div class="text-end">
    <button type="button" class="btn btn-danger btn-set-amount-zero me-2">回収額ゼロ設定</button>
    <button type="submit" class="btn btn-success me-2">実績登録</button>
    <button type="button" class="btn btn-outline-success btn-cancel-registration disabled">実績登録取消</button>
</div>
{$summary}
<div class="table-wrapper">
    <table class="table table-bordered">
        <thead>
            <tr class="sticky-row1">
                <th class="sticky-header1">CF</th>
                <th class="sticky-header2">支払方法</th>
                <th class="sticky-header3">顧客名
                <th class="sticky-header4">契約No.</th>
                <th>税率</th>
                <th>登録年月日</th>
                <th>登録No.</th>
                <th>リース期間</th>
                <th>回収</th>
                <th colspan="3">{$lastMonthTerm->format('Y/m')}</th>
                <th class="current-term">回数</th>
                <th class="current-term">{$targetTerm->format('Y/m')}</th>
                <th colspan="2" class="current-term">確定</th>
                <th class="current-term" style="min-width:110px;">回収区分</th>
                <th class="current-term" style="min-width:85px;">回収額</sub></th>
                <th>会社別</th>
                <th>顧客名</sub></th>
                <th>CF</th>
            </tr>
            <tr class="sticky-row2">
                <th class="sticky-header1"><input type="text" name="filter_code" value="{$filter_code}" class="form-control form-filter" data-attr="code"></th>
                <th class="sticky-header2"></th>
                <th class="sticky-header3"><input type="text" name="filter_name" value="{$filter_name}" class="form-control form-filter" data-attr="name"></th>
                <th class="sticky-header4"><input type="text" name="filter_contract_code" value="{$filter_contract_code}" class="form-control form-filter" data-attr="contract_code"></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th>回数</th>
                <th>回収区分</th>
                <th>金額</th>
                <th class="current-term"></th>
                <th class="current-term"></th>
                <th class="current-term">登録<br/>{$checkbox}</th>
                <th class="current-term">取消<br/>{$checkbox2}</th>
                <th class="current-term">{$filter_repayment_type}</th>
                <th class="current-term">チェック合計<br/><span id="checked-total2"></span></th>
                <th></th>
                <th><input type="text" name="filter_name" value="{$filter_name}" class="form-control form-filter" data-attr="name"></th>
                <th><input type="text" name="filter_code" value="{$filter_code}" class="form-control form-filter" data-attr="code"></th>
            </tr>
        </thead>
        <tbody>
            {items}
        </tbody>
        <tbody>
            <tr>
                <th colspan="17" class="text-end border-bottom">チェック合計</th>
                <th class="text-end formatted border-bottom" id="checked-total"></th>
                <th colspan="3" class="border-bottom"></th>
            </tr>
            <tr>
                <th colspan="9" rowspan="5" class="text-end pe-3 border-bottom"><span style="writing-mode: vertical-rl;">前月合計</span></th>
                <th colspan="2" class="text-end">口座振替実績</th>
                <th class="text-end">{$lastMonthFurikaeRepaymentTotalText}</th>
                <th colspan="4" rowspan="5" class="text-end pe-3 border-bottom"><span style="writing-mode: vertical-rl;">当月合計</span></th>
                <th class="text-end">口座振替実績</th>
                <th class="text-end">{$currentFurikaeRepaymentTotalText}</th>
                <th colspan="3"></th>
            </tr>
            <tr>
                <th colspan="2" class="text-end" style="color:#000; background-color:#Ffaaaa;">口座振替未回収</th>
                <th class="text-end" style="color:#000; background-color:#Ffaaaa;">{$lastMonthFurikaeRepaymentUnchargedTotalText}</th>
                <th class="text-end" style="color:#000; background-color:#Ffaaaa;">口座振替未回収</th>
                <th class="text-end" style="color:#000; background-color:#Ffaaaa;">{$currentFurikaeUnchargedTotalText}</th>
                <th colspan="3"></th>
            </tr>
            <tr>
                <th colspan="2" class="text-end border-bottom">振込実績</th>
                <th class="text-end border-bottom">{$lastMonthFurikomiRepaymentTotalText}</th>
                <th class="text-end border-bottom">振込実績</th>
                <th class="text-end border-bottom">{$currentFurikomiRepaymentTotalText}</th>
                <th colspan="3"></th>
            </tr>
            <tr>
                <th colspan="2" class="text-end" style="color:#000; background-color:#Ffaaaa;">振込未回収</th>
                <th class="text-end" style="color:#000; background-color:#Ffaaaa;">{$lastMonthFurikomiRepaymentUnchargedTotalText}</th>
                <th class="text-end" style="color:#000; background-color:#Ffaaaa;">振込未回収</th>
                <th class="text-end" style="color:#000; background-color:#Ffaaaa;">{$currentFurikomiUnchargedTotalText}</th>
                <th colspan="3"></th>
            </tr>
            <tr>
                <th colspan="2" class="text-end border-bottom">回収実績</th>
                <th class="text-end border-bottom">{$lastMonthCollectionTotalText}</th>
                <th class="text-end border-bottom">回収実績</th>
                <th class="text-end border-bottom">{$currentChargeTotalText}</th>
                <th colspan="3" class="border-bottom"></th>
            </tr>
            <tr>
                <th colspan="16" rowspan="8" class="text-end pe-3 border-top border-bottom"><span style="writing-mode: vertical-rl;">口座振替実績</span></th>
                <th class="text-end">自社リース8%</th>
                <th class="text-end">{$summaries[0][0]}</th>
                <th colspan="3" rowspan="8" class="border-bottom"></th>
            </tr>
            <tr>
                <th class="text-end">自社リース10%</th>
                <th class="text-end">{$summaries[0][1]}</th>
            </tr>
            <tr>
                <th class="text-end">転リース8%</th>
                <th class="text-end">{$summaries[0][2]}</th>
            </tr>
            <tr>
                <th class="text-end">転リース10%</th>
                <th class="text-end">{$summaries[0][3]}</th>
            </tr>
            <tr>
                <th class="text-end">割賦</th>
                <th class="text-end">{$summaries[0][4]}</th>
            </tr>
            <tr>
                <th class="text-end">転割賦</th>
                <th class="text-end">{$summaries[0][5]}</th>
            </tr>
            <tr>
                <th class="text-end">売掛金</th>
                <th class="text-end">{$summaries[0][6]}</th>
            </tr>
            <tr>
                <th class="text-end border-bottom">手形</th>
                <th class="text-end border-bottom">{$summaries[0][7]}</th>
            </tr>
            <tr>
                <th colspan="16" rowspan="8" class="text-end pe-3"><span style="writing-mode: vertical-rl;">振込実績</span></th>
                <th class="text-end">自社リース8%</th>
                <th class="text-end">{$summaries[1][0]}</th>
                <th colspan="3" rowspan="8"></th>
            </tr>
            <tr>
                <th class="text-end">自社リース10%</th>
                <th class="text-end">{$summaries[1][1]}</th>
            </tr>
            <tr>
                <th class="text-end">転リース8%</th>
                <th class="text-end">{$summaries[1][2]}</th>
            </tr>
            <tr>
                <th class="text-end">転リース10%</th>
                <th class="text-end">{$summaries[1][3]}</th>
            </tr>
            <tr>
                <th class="text-end">割賦</th>
                <th class="text-end">{$summaries[1][4]}</th>
            </tr>
            <tr>
                <th class="text-end">転割賦</th>
                <th class="text-end">{$summaries[1][5]}</th>
            </tr>
            <tr>
                <th class="text-end">売掛金</th>
                <th class="text-end">{$summaries[1][6]}</th>
            </tr>
            <tr>
                <th class="text-end">手形</th>
                <th class="text-end">{$summaries[1][7]}</th>
            </tr>
        </tbody>
    </table>
</div>
{pager}
    <div class="text-end">
        <button type="submit" class="btn btn-success ms-auto">実績登録</button>
    </div>
EOL;
    ?>
    <?php $searchForm = ActiveForm::begin([
        'id' => 'repayment-search-form'
    ]); ?>
    <div class="hstack mb-3">
        <label class="form-label col-first">支払方法</label><?= Html::activeCheckboxList($searchModel, 'repayment_type_id', RepaymentType::getTypes(), ['inline' => true]) ?>
    </div>
    <div class="hstack mb-3">
        <label class="form-label col-first">契約情報</label>
        <div id="contract-pattern-wrapper">
            <?php $patterns = ContractPattern::getContractNamePatterns($model->client_corporation_id); ?>
            <?= Html::activeCheckboxList($searchModel, 'contract_pattern_id', $patterns, ['inline' => true]) ?>
        </div>
    </div>
    <div class="d-flex justify-content-end mb-0">
        <?= Html::submitButton('この支払方法で絞り込む', ['class' => 'btn btn-sm btn-primary']) ?>
    </div>
    <?php ActiveForm::end(); ?>
    <?php $registerForm = ActiveForm::begin([
        'id' => 'register-repayments-form'
    ]) ?>
    <div class="row">
        <div class="col-auto">
            <label class="form-label">対象月</label>
            <p><?= (new \DateTime($model->target_term))->format('Y年n月') ?></p>
        </div>
        <div class="col-auto">
            <label class="form-label">対象会社</label>
            <p><?= $model->clientCorporation->name ?></p>
        </div>
        <div class="col-auto">
            <label class="form-label">回収条件</label>
            <p><?= $model->repaymentPattern->name ?></p>
        </div>
        <div class="col-auto">
            <label class="form-label">件数</label>
            <p><?= $model->getMonthlyCharges()->count() ?>件</p>
        </div>
        <div class="col-auto">
            <label class="form-label">実績未登録件数</label>
            <p><?= $model->getMonthlyCharges()->alias('mc')
                    ->leftJoin('repayment r', 'mc.monthly_charge_id=r.monthly_charge_id')
                    ->leftJoin('debt d', 'mc.monthly_charge_id=d.monthly_charge_id')
                    ->where(['r.repayment_id' => null, 'd.debt_id' => null])
                    ->count() ?>件</p>
        </div>
        <div class="col-auto">
            <?php /*= $registerForm->field($model, 'transfer_date')->label('登録年月日')->widget(\app\widgets\datetimepicker\Datetimepicker::class, [
                'clientOptions' => [
                    'locale' => 'ja',
                    'format' => 'YYYY-MM-DD',
                ],
            ]) */ ?>
        </div>
    </div>
    <?php Modal::begin([
        'id' => 'modal-register-stored-collection-data',
        'title' => '実績登録',
        'size' => Modal::SIZE_SMALL,
        'centerVertical' => true,
        'footer' => Html::button('登録年月日を指定して実績登録', ['class' => 'btn btn-primary btn-form-submit']),
    ]); ?>
    <?= $registerForm->field($model, 'transfer_date')->label('登録年月日')->widget(\app\widgets\datetimepicker\Datetimepicker::class, [
        'clientOptions' => [
            'locale' => 'ja',
            'format' => 'YYYY-MM-DD',
        ],
    ]) ?>
    <?php Modal::end(); ?>
    <?= ListView::widget([
        'dataProvider' => $dataProvider,
        'itemView' => 'iv-register-stored-collection-data-alternative',
        'itemOptions' => ['tag' => false],
        'viewParams' => ['selectedModel' => $model, 'targetTerm' => $targetTerm, 'dataProvider' => $dataProvider, 'totals' => $totals],
        'layout' => $layout,
        'emptyText' => strtr($layout, ['{summary}' => '&nbsp;', '{items}' => '', '{pager}'=> '']),
    ]) ?>
    <?php ActiveForm::end(); ?>
    <?php \yii\widgets\Pjax::end(); ?>
</div>
</section>

