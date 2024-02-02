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
use yii\bootstrap5\Modal;

$dataProvider = $searchModel->search(Yii::$app->request->post());

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
function calcCheckedTotal() {
    let checked_total = 0;
    $('[type="checkbox"][name$="[collected]"]:checked').each(function(){
        checked_total += $(this).parent().next().next().find('input[name$="[repayment_amount]"]').numVal();
    })
    $('[type="checkbox"][name$="[cancelled]"]').each(function(){
        checked_total += $(this).parent().parent().next().next().find('input.formatted').numVal();
    })
    $('#checked-total').text(numberFormat(checked_total));
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
                    $('#register-repayments-form').submit();
                    console.log('submitted');
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
.table-wrapper table.table th.current-term {
    background-color: var(--vz-indigo);
}
.paid {
    background-color: #e8e8e8!important;
}
.deficient {
    background-color: #ffd4d4!important;
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
EOS;
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
    $widget = new \app\widgets\PageSizeLimitChanger(['pjax_id' => 'pjax-grid-wrapper']);
    $dataProvider->pagination = $widget->pagination;
    $cloned = (clone $dataProvider);
    $cloned->pagination = false;
    $models = $cloned->models;
    $currentChargeTotal = array_sum(array_map(function($mc){return $mc->temporaryAmountWithTax;}, $models));

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
    $currentChargeTotalText = number_format($currentChargeTotal, 0);
    $currentFurikaeRepaymentTotalText = number_format($currentFurikaeRepaymentTotal, 0);
    $currentFurikaeUnchargedTotalText = number_format($currentChargeTotal - $currentFurikomiRepaymentTotal - $currentFurikomiRepaymentTotal, 0);
    $currentFurikomiRepaymentTotalText = number_format($currentFurikomiRepaymentTotal,0);
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
    $lastMonthFurikaeRepaymentTotalText = number_format($lastMonthFurikaeRepaymentTotal, 0);
    $lastMonthFurikaeRepaymentUnchargedTotalText = number_format($lastMonthCollectionTotal - $lastMonthFurikaeRepaymentTotal - $lastMonthFurikomiRepaymentTotal, 0);
    $lastMonthFurikomiRepaymentTotalText = number_format($lastMonthFurikomiRepaymentTotal, 0);
    $summary = $widget->summaryLayout;
    $checkbox = Html::checkbox('check_all', false, ['id' => 'check-all']);
    $checkbox2 = Html::checkbox('check_cancel_all', false, ['id' => 'check-cancell-all']);
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
                <th class="sticky-header4"></th>
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
                <th class="current-term"></th>
                <th class="current-term"></th>
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
                <th colspan="17" class="text-end border-bottom">確定分合計</th>
                <th class="text-end formatted border-bottom" id="checked-total"></th>
                <th colspan="3" class="border-bottom"></th>
            </tr>
            <tr>
                <th colspan="9" rowspan="4" class="text-end pe-3"><span style="writing-mode: vertical-rl;">前月合計</span></th>
                <th colspan="2" class="text-end">口座振替実績</th>
                <th class="text-end">{$lastMonthFurikaeRepaymentTotalText}</th>
                <th colspan="4" rowspan="4" class="text-end pe-3"><span style="writing-mode: vertical-rl;">当月合計</span></th>
                <th class="text-end">口座振替実績</th>
                <th class="text-end">{$currentFurikomiRepaymentTotalText}</th>
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
                <th colspan="2" class="text-end border-bottom">振替実績</th>
                <th class="text-end border-bottom">{$lastMonthFurikomiRepaymentTotalText}</th>
                <th class="text-end border-bottom">振替実績</th>
                <th class="text-end border-bottom">{$currentFurikomiRepaymentTotalText}</th>
                <th colspan="3"></th>
            </tr>
            <tr>
                <th colspan="2" class="text-end">回収実績</th>
                <th class="text-end">{$lastMonthCollectionTotalText}</th>
                <th class="text-end">回収実績</th>
                <th class="text-end">{$currentChargeTotalText}</th>
                <th colspan="3"></th>
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
    <div class="hstack">
        <label class="form-label me-3">支払方法</label><?= Html::activeCheckboxList($searchModel, 'repayment_type_id', RepaymentType::getTypes(), ['inline' => true]) ?>
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
        'itemView' => 'iv-register-stored-collection-data3',
        'itemOptions' => ['tag' => false],
        'viewParams' => ['selectedModel' => $model, 'targetTerm' => $targetTerm, 'dataProvider' => $dataProvider, 'totals' => $totals],
        'layout' => $layout,
        'emptyText' => strtr($layout, ['{summary}' => '&nbsp;', '{items}' => '', '{pager}'=> '']),
    ]) ?>
    <?php ActiveForm::end(); ?>
    <?php \yii\widgets\Pjax::end(); ?>
</div>
</section>

