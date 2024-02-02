<?php
/**
 * @var $this \yii\web\View
 * @var $form \yii\bootstrap5\ActiveForm;
 * @var $model \app\models\ContractDetail;
 * @var $index  integer
 */

use yii\bootstrap5\Html;
use kartik\date\DatePicker;
use app\widgets\datetimepicker\Datetimepicker;

$script = <<<EOS
function numberFormat(num) {
    return num.toString().replace(/(\d+?)(?=(?:\d{3})+$)/g, '$1,');
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
function monthsCount(startAt, endAt){
    let startMatched = startAt.match(/(\\d+)-(\\d+)-\\d+/), endMatched = endAt.match(/(\\d+)-(\\d+)-\\d+/), count,
        startMonthCount, endMonthCount;
    if (startMatched && endMatched) {
        startMonthCount = startMatched[1] * 12 + startMatched[2] * 1 - 1;
        endMonthCount = endMatched[1] * 12 + endMatched[2] * 1 - 1;
        count = endMonthCount - startMonthCount;
        if (count == 0) {
            count = 1;
        }
    }
    else {
        count = null;
    }
    return count;
}
function monthsCountRecent(startAt, endAt){
    let startMatched = startAt.match(/(\\d+)-(\\d+)-\\d+/), endMatched = endAt.match(/(\\d+)-(\\d+)-\\d+/), count;
    if (startMatched && endMatched) {
        let startYear = startMatched[1] * 1, startMonth = startMatched[2] * 1,
            endYear = endMatched[1] * 1, endMonth = endMatched[2] * 1;
        count = endMonth < startMonth ? 
            1 + endMonth + 12 - startMonth + (endYear - startYear - 1) * 12 :
            1 + endMonth - startMonth + (endYear - startYear) * 12; 
    }
    else {
        count = null;
    }
    count -= 1;
    if (count == 0) {
        count = 1;
    }
    return count;
}
function calcTaxIncluded(name){
    var matched = this.name.match(/\\[(\\d+)\\]\\[(\\w+)\\]/),
        index = matched[1];
    if (typeof name === 'object') {
        var index = matched[1],
            name = matched[2],
            that = $(this);
    }
    else {
        var that = $('[name$="[' + index + '][' + name + ']"]');
    }
    var amount = that.numVal() * 1,
        tax_app_id = $('[name$="['+index+'][tax_application_id]"]').val(),
        method = $('[name$="['+index+'][fraction_processing_pattern]"]:checked').val();
    if (tax_app_id !== '' && method != undefined && amount > 0) {
        $.getJSON('/aas/calc-tax-included?id='+tax_app_id+'&amount='+amount+'&method='+method).then(function(json){
            let tag = [
            '    <label class="form-label col-auto tax-included">税込額</label>',
            '    <div class="col-2-narrow tax-included">',
            '       <div class="input-group">',
            '           <input type="text" value="' + json.value +'" class="form-control formatted text-end" readonly/>',
            '           <span class="input-group-text">円</span>',
            '       </div>',
            '    </div>',
            ];
            that.parents('.hstack').find('.tax-included').remove();
            $(tag.join("\\n")).insertAfter($(that).parents('.col-2'));
            //$('.formatted').format();
        })
    }
    //$('.formatted').format();
}
async function calcTotals(){
    let matched = this.name.match(/\\[(\\d+)\\]\\[(\\w+)\\]/),
        index = matched[1],
        monthsCount = $('[name$="['+index+'][term_months_count]"]').numVal() * 1,
        tax_app_id = $('[name$="['+index+'][tax_application_id]"]').val(),
        method = $('[name$="['+index+'][fraction_processing_pattern]"]:checked').val(),
        monthly_charge = $('[name$="['+index+'][monthly_charge]"]').numVal() * 1,
        monthly_payment = $('[name$="['+index+'][monthly_payment]"]').numVal() * 1,
        tax_included_monthly_charge = $('[name$="['+index+'][monthly_charge]"]').numVal() * 1,
        tax_included_monthly_payment = $('[name$="['+index+'][monthly_payment]"]').numVal() * 1;

    let totalCharge = monthly_charge * monthsCount,
        totalPayment = monthly_payment * monthsCount,
        tax_included_totalCharge, tax_included_totalPayment;
        
    if (tax_app_id !== '' && method != undefined && monthly_charge > 0) {
        let json = await $.getJSON('/aas/calc-tax-included?id='+tax_app_id+'&amount='+monthly_charge+'&method='+method);
        tax_included_totalCharge = json.value * monthsCount;
    }
    if (tax_app_id !== '' && method != undefined && monthly_payment > 0) {
        let json = await $.getJSON('/aas/calc-tax-included?id='+tax_app_id+'&amount='+monthly_payment+'&method='+method);
        tax_included_totalPayment = json.value * monthsCount;
    }
        
        
    let start = moment($('[name$="['+index+'][term_start_at]"]').val()),
        end = moment($('[name$="['+index+'][term_end_at]"]').val());

    if ($('[name$="['+index+'][use_bonus_1]"]:checked').val() == 1 || $('[name$="['+index+'][use_bonus_2]"]:checked').val() == 1) {
        do {
            if (start.month() + 1 == $('[name$="['+index+'][bonus_month_1]"]').val()) {
                let additional_charge_1 = $('[name$="['+index+'][bonus_additional_charge_1]"]').numVal() * 1,
                    additional_payment_1 = $('[name$="['+index+'][bonus_additional_payment_1]"]').numVal() * 1;
                totalCharge += additional_charge_1;
                totalPayment += additional_payment_1;

                let json1 = await $.getJSON('/aas/calc-tax-included?id='+tax_app_id+'&amount='+additional_charge_1+'&method='+method),
                    json2 = await $.getJSON('/aas/calc-tax-included?id='+tax_app_id+'&amount='+additional_payment_1+'&method='+method);
                console.log(json1, json2);
                tax_included_totalCharge += json1.value*1;
                tax_included_totalPayment += json2.value*1;
            }
            if (start.month() + 1 == $('[name$="['+index+'][bonus_month_2]"]').val()) {
                let additional_charge_2 = $('[name$="['+index+'][bonus_additional_charge_2]"]').numVal() * 1,
                    additional_payment_2 = $('[name$="['+index+'][bonus_additional_payment_2]"]').numVal() * 1;
                totalCharge += additional_charge_2;
                totalPayment += additional_payment_2;

                let json1 = await $.getJSON('/aas/calc-tax-included?id='+tax_app_id+'&amount='+additional_charge_2+'&method='+method),
                    json2 = await $.getJSON('/aas/calc-tax-included?id='+tax_app_id+'&amount='+additional_payment_2+'&method='+method);
                console.log(json1, json2);
                tax_included_totalCharge += json1.value*1;
                tax_included_totalPayment += json2.value*1;
            }
            start.add(1, 'months');
        } while(start.isBefore(end));
    }
    let totalChargeInput = $('[name$="['+index+'][total_charge_amount]"]'),
        totalPaymentInput = $('[name$="['+index+'][total_payment_amount]"]');
    totalChargeInput.val(totalCharge);
    totalPaymentInput.val(totalPayment);
    let tag1 = [
        '<label class="form-label col-auto tax-included">税込額</label>',
        '<div class="col-md-2 tax-included">',
        '    <div class="input-group">',
        '        <input type="text" value="' + tax_included_totalCharge + '" class="form-control formatted text-end" readonly/>',
        '        <span class="input-group-text">円</span>',
        '    </div>',
        '</div>',
    ];
    let tag2 = [
        '<label class="form-label col-auto tax-included">税込額</label>',
        '<div class="col-md-2 tax-included">',
        '    <div class="input-group">',
        '        <input type="text" value="' + tax_included_totalPayment + '" class="form-control formatted text-end" readonly/>',
        '        <span class="input-group-text">円</span>',
        '    </div>',
        '</div>',
    ];
    if (tax_included_totalCharge != undefined) {
        totalChargeInput.parents('.hstack').find('.tax-included').remove();
        totalChargeInput.parents('.hstack').append(tag1.join("\\n"));
    }
    if (tax_included_totalPayment != undefined) {
        totalPaymentInput.parents('.hstack').find('.tax-included').remove();
        totalPaymentInput.parents('.hstack').append(tag2.join("\\n"));
    }
    $('.formatted').format();
}
$(document)
    .on('dp.change', '[name="LeaseContract[contract_date]"]', function(){
        var selected = $(this).val(), selected_year_month;
        selected_year_month = selected.replace(/(\\d+)-0?(\\d+)-(\\d+)/, '$1年$2月');
        $('[name$="[term_start_at]"]').val(selected);
        if ($('[name$="[lease_start_at]"]').val() == '') {
            $('[name$="[lease_start_at]"]').val(selected_year_month);
        }
        if ($('[name$="[collection_start_at]"]').val() == '') {
            $('[name$="[collection_start_at]"]').val(selected_year_month);
        }
        if ($('[name$="[payment_start_at]"]').val() == '') {
            $('[name$="[payment_start_at]"]').val(selected_year_month);
        }
    })
    .on('change', '[name*="use_bonus"]', function(){
        let value = $(this).val()*1;
        if (value) {
            $(this).parents('.row,.hstack').find('input[type="text"]').prop("disabled", false);
        }
        else {
            $(this).parents('.row,.hstack').find('input[type="text"]').prop("disabled", true);
        }
    })
    .on('dp.change', '[name$="[term_start_at]"]', function(){
        let startAt = $(this).val(), endAt = $(this).parents('.hstack').find('[name$="[term_end_at]"]').val();
        $(this).parents('.hstack').find('[name$="[term_months_count]"]').val(monthsCount(startAt, endAt)).change();
    })
    .on('dp.change', '[name$="[term_end_at]"]', function(){
        let endAt = $(this).val(), startAt = $(this).parents('.hstack').find('[name$="[term_start_at]"]').val();
        $(this).parents('.hstack').find('[name$="[term_months_count]"]').val(monthsCount(startAt, endAt)).change();
    })
    .on('dp.change', '[name$="[lease_start_at]"]', function(){
        var selected = $(this).val();
        if ($('[name$="[collection_start_at]"]').val() == '') {
            $('[name$="[collection_start_at]"]').val(selected);
        }
        if ($('[name$="[payment_start_at]"]').val() == '') {
            $('[name$="[payment_start_at]"]').val(selected);
        }
    })
    .on('_dp.change', '[name$="[collection_start_at]"]', function(){
        let idx = $(this).attr('name').match(/\w+\[(\d+)\]\[\w+\]/)[1], leaseStartAt = $('[name$="['+idx+'][lease_start_at]"]').val(),
            startMonth = $(this).val();
        if (!leaseStartAt) return;
        let lsm = leaseStartAt.replace(/(\d+)年(\d+)月/, '$1-$2-1'), csm = startMonth.replace(/(\d+)年(\d+)月/, '$1-$2-1'),
            fcc = (lsm == csm ? 1 : monthsCount(lsm, csm) + 1);
        $(this).parents('.hstack').find('[name$="[first_collection_count]"]').val(fcc);
    })
    .on('_dp.change', '[name$="[payment_start_at]"]', function(){
        let idx = $(this).attr('name').match(/\w+\[(\d+)\]\[\w+\]/)[1], leaseStartAt = $('[name$="['+idx+'][lease_start_at]"]').val(),
            startMonth = $(this).val();
        if (!leaseStartAt) return;
        let lsm = leaseStartAt.replace(/(\d+)年(\d+)月/, '$1-$2-1'), csm = startMonth.replace(/(\d+)年(\d+)月/, '$1-$2-1'),
            fsc = (lsm == csm ? 1 : monthsCount(lsm, csm) + 1);
        $(this).parents('.hstack').find('[name$="[first_payment_count]"]').val(fsc);
    })
    .on('click', '.btn-copy-term', function(){
        let index = $(this).data('index') - 1,
            startAt = $('[name$="['+index+'][term_start_at]"').val(), 
            endAt = $('[name$="['+index+'][term_end_at]"').val(),
            monthsCount = $('[name$="['+index+'][term_months_count]"').val(),
            row = $(this).parents('.hstack');
        row.find('[name$="[term_start_at]"]').val(startAt);
        row.find('[name$="[term_end_at]"]').val(endAt);
        row.find('[name$="[term_months_count]"]').val(monthsCount);
    })
    .on('change', '[name$="[monthly_charge]"], [name$="[monthly_payment]"]', calcTaxIncluded)
    .on('change', '[name$="[tax_application_id]"], [name$="[fraction_processing_pattern]"]', function(){
        calcTaxIncluded.apply(this, ['monthly_charge']);
        calcTaxIncluded.apply(this, ['monthly_payment']);
    })
    .on('change', '[name$="[monthly_charge]"]', function(){
        if ($(this).numVal() == 0) {
            $(this).parents('.input-group').parent().nextAll().find('input').prop('disabled', true);
        }
        else {
            $(this).parents('.input-group').parent().nextAll().find('input').prop('disabled', false);
        }
    })
    .on('change', '[name$="[monthly_payment]"]', function(){
        if ($(this).numVal() == 0) {
            $(this).parents('.input-group').parent().nextAll().find('input').prop('disabled', true);
        }
        else {
            $(this).parents('.input-group').parent().nextAll().find('input').prop('disabled', false);
        }
    })
    .on('change', '[name$="[term_months_count]"],[name$="[monthly_charge]"],[name$="[monthly_payment]"],[name$="[bonus_additional_charge_1]"],[name$="[bonus_additional_payment_1]"],[name$="[bonus_additional_charge_2]"],[name$="[bonus_additional_payment_2]"]', calcTotals)
$('[name$="[monthly_charge]"], [name$="[monthly_payment]"]').change();
//$('.formatted').format();
EOS;
$style = <<<EOS
.col-first {
    -webkit-box-flex: 0;
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    width: 85px;
}
EOS;
$this->registerCss($style);
if ($index == 0) {
    $this->registerJs($script);
}
?>
                    <div class="hstack gap-2 mb-3">
                        <label class="form-label col-first">リース区分</label>
                        <div class="col-auto">
                            <?= Html::activeRadioList($model, "[{$index}]contract_type", ['ordinary' => '通常リース', 'meintenance' => 'メンテナンスリース'], ['inline' => true, 'class' => ($model->hasErrors("contract_type") ? 'is-invalid' : '')]) ?>
                            <?= Html::error($model, "[{$index}]contract_type") ?>
                        </div>
                    </div>
                    <div class="hstack gap-2 mb-3">
                        <label class="form-label col-first">リース期間</label>
                        <div class="col-2">
                            <?= Datetimepicker::widget([
                                'model' => $model,
                                'attribute' => "[{$index}]term_start_at",
                                'id' => "term-start-at-{$index}",
                                'clientOptions' => [
                                    'locale' => 'ja',
                                    'format' => 'YYYY-MM-DD',
                                ],
                            ]) ?>
                            <?= Html::error($model, "[{$index}]term_start_at") ?>
                        </div>
                        <div class="col-auto">〜</div>
                        <div class="col-2">
                            <?= Datetimepicker::widget([
                                'model' => $model,
                                'attribute' => "[{$index}]term_end_at",
                                'id' => "term-end-at-{$index}",
                                'options' => [
                                    'autocomplete' => 'off',
                                ],
                                'clientOptions' => [
                                    'locale' => 'ja',
                                    'format' => 'YYYY-MM-DD',
                                    'useCurrent' => false,
                                ],
                            ]) ?>
                            <?= Html::error($model, "[{$index}]term_end_at") ?>

                        </div>
                        <label class="form-label col-auto">リース回数</label>
                        <div class="col-1">
                            <div class="input-group<?= $model->hasErrors("term_months_count") ? ' is-invalid' : '' ?>">
                                <?= Html::activeTextInput($model, "[{$index}]term_months_count", ['class' => 'form-control'.($model->hasErrors("term_months_count") ? ' is-invalid' : '')]) ?>
                                <span class="input-group-text">回</span>
                            </div>
                            <?= Html::error($model, "[{$index}]term_months_count") ?>
                        </div>
                        <label class="form-label col-auto">初年度登録月</label>
                        <div class="col-2">
                            <?= Datetimepicker::widget([
                                'model' => $model,
                                'attribute' => "[{$index}]lease_start_at",
                                'id' => "lease-start-at-{$index}",
                                'clientOptions' => [
                                    'locale' => 'ja',
                                    'format' => 'YYYY年M月',
                                    'viewMode' => 'months',
                                ]
                            ]) ?>
                            <?= Html::error($model, "[{$index}]lease_start_at") ?>
                        </div>
                        <?php if ($index > 0 && $model->isNewRecord) : ?>
                        <div class="col-auto ms-3">
                            <?= Html::button('上記リース期間をコピー', ['class' => 'mb-3 btn btn-outline-success btn-copy-term', 'data-index' => $index]) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="hstack gap-2 mb-3">
                        <label class="form-label col-first">リース会社</label>
                        <div class="col-2-narrow">
                            <?= Html::activeDropDownList($model, "[{$index}]lease_servicer_id", \app\models\LeaseServicer::getServicers(), ['class' => 'form-control form-select'.($model->hasErrors('lease_servicer_id') ? ' is-invalid' : ''), 'prompt' => 'リース会社選択']) ?>
                            <?= Html::error($model, "[{$index}]lease_servicer_id") ?>
                        </div>
                        <label class="form-label col-auto">税区分</label>
                        <div class="col-2-narrow">
                            <?= Html::activeDropDownList($model, "[{$index}]tax_application_id", \app\models\TaxApplication::getTaxApplications(), ['class' => 'form-control form-select'.($model->hasErrors('tax_application_id') ? ' is-invalid' : '')]) ?>
                            <?= Html::error($model, "[{$index}]tax_application_id") ?>
                        </div>
                        <label class="form-label col-auto">端数処理</label>
                        <div class="col-md-8">
                            <?= Html::activeRadioList($model, "[{$index}]fraction_processing_pattern", ['floor' => '切り捨て', 'ceil' => '切り上げ', 'roundup' => '四捨五入'], ['inline' => true, 'class' => ($model->hasErrors('fraction_processing_pattern') ? ' is-invalid' : '')]) ?>
                            <?= Html::error($model, "[{$index}]fraction_processing_pattern") ?></div>
                    </div>
                    <div class="hstack gap-2 mb-3">
                        <label class="form-label col-first">毎月回収額</label>
                        <div class="col-2">
                            <div class="input-group<?= $model->hasErrors('monthly_charge') ? ' is-invalid' : '' ?>">
                                <?= Html::activeTextInput($model, "[{$index}]monthly_charge", ['class' => 'form-control formatted text-end'.($model->hasErrors('monthly_charge') ? ' is-invalid' : '')]) ?>
                                <span class="input-group-text">円<sub>(税抜)</sub></span>
                            </div>
                            <?= Html::error($model, "[{$index}]monthly_charge") ?>
                        </div>
                        <?php if (!empty($model->monthly_charge)) : ?>
                            <label class="form-label col-auto tax-included">税込額</label>
                            <div class="col-2-narrow tax-included">
                                <div class="input-group">
                                    <input type="text" value="<?= $model->monthlyChargeWithTax ?>" class="form-control formatted text-end" readonly/>
                                    <span class="input-group-text">円</span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <label class="form-label col-auto">回収開始月</label>
                        <div class="col-2-narrow">
                            <?= Datetimepicker::widget([
                                'model' => $model,
                                'attribute' => "[{$index}]collection_start_at",
                                'id' => "collection_start_at-{$index}",
                                'clientOptions' => [
                                    'locale' => 'ja',
                                    'format' => 'YYYY年M月',
                                    'viewMode' => 'months',
                                ]
                            ]) ?>
                            <?= Html::error($model, "[{$index}]collection_start_at") ?>
                        </div>
                        <label class="form-label col-auto">回収初月回数</label>
                        <div class="col-1">
                            <div class="input-group">
                                <?= Html::activeTextInput($model, "[{$index}]first_collection_count", ['class' => 'form-control']) ?>
                                <span class="input-group-text">回</span>
                            </div>
                        </div>
                    </div>
                    <div class="hstack gap-2 mb-3">
                        <label class="form-label col-first">毎月支払額</label>
                        <div class="col-2">
                            <div class="input-group<?= $model->hasErrors('monthly_payment') ? ' is-invalid' : '' ?>">
                                <?= Html::activeTextInput($model, "[{$index}]monthly_payment", ['class' => 'form-control formatted text-end'.($model->hasErrors('monthly_payment') ? ' is-invalid' : '')]) ?>
                                <span class="input-group-text">円<sub>(税抜)</sub></span>
                            </div>
                            <?= Html::error($model, "[{$index}]monthly_payment") ?>
                        </div>
                        <?php if (!empty($model->monthly_payment)) : ?>
                            <label class="form-label col-auto tax-included">税込額</label>
                            <div class="col-2-narrow tax-included">
                                <div class="input-group">
                                    <input type="text" value="<?= $model->monthlyPaymentWithTax ?>" class="form-control formatted text-end" readonly/>
                                    <span class="input-group-text">円</span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <label class="form-label col-auto">支払開始月</label>
                        <div class="col-2-narrow">
                            <?= Datetimepicker::widget([
                                'model' => $model,
                                'attribute' => "[{$index}]payment_start_at",
                                'id' => "payment_start_at-{$index}",
                                'clientOptions' => [
                                    'locale' => 'ja',
                                    'format' => 'YYYY年M月',
                                    'viewMode' => 'months',
                                ]
                            ]) ?>
                            <?= Html::error($model, "[{$index}]payment_start_at") ?>
                        </div>
                        <label class="form-label col-auto">支払初月回数</label>
                        <div class="col-1">
                            <div class="input-group">
                                <?= Html::activeTextInput($model, "[{$index}]first_payment_count", ['class' => 'form-control']) ?>
                                <span class="input-group-text">回</span>
                            </div>
                        </div>
                    </div>
                    <div class="hstack gap-2 mb-3">
                        <label class="form-label col-first">前払リース回収</label>
                        <div class="col-2-narrow">
                            <div class="input-group">
                                <?= Html::activeDropDownList($model, "[{$index}]advance_repayment_count", array_combine(range(0, 4), range(0,4)), ['class' => 'form-control form-select']) ?>
                                <span class="input-group-text">回</span>
                            </div>
                            <?= Html::error($model, "[{$index}]advance_repayment_count") ?>
                        </div>
                    </div>
                    <div class="hstack gap-2 mb-3">
                        <label class="form-label col-first">ボーナス加算1</label>
                        <div class="col-auto field-<?= $index ?>-use-bonus-1">
                            <input type="hidden" name="ContractDetail[<?= $index ?>][use_bonus_1]" value="">
                            <div id="<?= $index ?>-use-bonus-1" role="radiogroup">
                                <div class="form-check form-check-inline">
                                    <?php $checked = !!$model->bonus_month_1 && !!$model->bonus_additional_charge_1 && !!$model->bonus_additional_payment_1; ?>
                                    <input type="radio" id="<?= $index ?>-use-bonus-1-0" class="form-check-input" name="ContractDetail[<?= $index ?>][use_bonus_1]" value="1"<?= $checked ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="<?= $index ?>-use-bonus-1-0">あり</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" id="<?= $index ?>-use-bonus-1-1" class="form-check-input" name="ContractDetail[<?= $index ?>][use_bonus_1]" value="0"<?= !$checked ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="<?= $index ?>-use-bonus-1-1">なし</label>
                                </div>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <label class="form-label col-auto">ボーナス加算月1</label>
                        <div class="col-1">
                            <div class="input-group">
                                <?= Html::activeTextInput($model, "[{$index}]bonus_month_1", ['class' => 'form-control text-end', 'disabled'=>!$checked]) ?>
                                <span class="input-group-text">月</span>
                            </div>
                            <?= Html::error($model, "[{$index}]bonus_month_1") ?>
                        </div>
                        <label class="form-label col-auto">ボーナス回収加算額1</label>
                        <div class="col-2">
                            <div class="input-group">
                                <?= Html::activeTextInput($model, "[{$index}]bonus_additional_charge_1", ['class' => 'form-control text-end', 'disabled'=>!$checked]) ?>
                                <span class="input-group-text">円<sub>(税抜)</sub></span>
                            </div>
                            <?= Html::error($model, "[{$index}]bonus_additional_charge_1") ?>
                        </div>
                        <label class="form-label col-auto">ボーナス支払加算額1</label>
                        <div class="col-2">
                            <div class="input-group">
                                <?= Html::activeTextInput($model, "[{$index}]bonus_additional_payment_1", ['class' => 'form-control text-end', 'disabled'=>!$checked]) ?>
                                <span class="input-group-text">円<sub>(税抜)</sub></span>
                            </div>
                            <?= Html::error($model, "[{$index}]bonus_additional_payment_1") ?>
                        </div>
                    </div>
                    <div class="hstack gap-2 mb-3">
                        <label class="form-label col-first">ボーナス加算2</label>
                        <div class="col-auto field-<?= $index ?>-use-bonus-2">
                            <input type="hidden" name="ContractDetail[<?= $index ?>][use_bonus_2]" value="">
                            <div id="<?= $index ?>-use-bonus-2" role="radiogroup">
                                <div class="form-check form-check-inline">
                                    <?php $checked = !!$model->bonus_month_2 && !!$model->bonus_additional_charge_2 && !!$model->bonus_additional_payment_2; ?>
                                    <input type="radio" id="<?= $index ?>-use-bonus-2-0" class="form-check-input" name="ContractDetail[<?= $index ?>][use_bonus_2]" value="1"<?= $checked ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="<?= $index ?>-use-bonus-2-0">あり</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" id="<?= $index ?>-use-bonus-2-1" class="form-check-input" name="ContractDetail[<?= $index ?>][use_bonus_2]" value="0"<?= !$checked ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="<?= $index ?>-use-bonus-2-1">なし</label>
                                </div>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <label class="form-label col-auto">ボーナス加算月2</label>
                        <div class="col-1">
                            <div class="input-group">
                                <?= Html::activeTextInput($model, "[{$index}]bonus_month_2", ['class' => 'form-control text-end', 'disabled'=>!$checked]) ?>
                                <span class="input-group-text">月</span>
                            </div>
                            <?= Html::error($model, "[{$index}]bonus_month_2") ?>
                        </div>
                        <label class="form-label col-auto">ボーナス回収加算額2</label>
                        <div class="col-2">
                            <div class="input-group">
                                <?= Html::activeTextInput($model, "[{$index}]bonus_additional_charge_2", ['class' => 'form-control text-end', 'disabled'=>!$checked]) ?>
                                <span class="input-group-text">円<sub>(税抜)</sub></span>
                            </div>
                            <?= Html::error($model, "[{$index}]bonus_additional_charge_2") ?>
                        </div>
                        <label class="form-label col-auto">ボーナス支払加算額2</label>
                        <div class="col-2">
                            <div class="input-group">
                                <?= Html::activeTextInput($model, "[{$index}]bonus_additional_payment_2", ['class' => 'form-control text-end', 'disabled'=>!$checked]) ?>
                                <span class="input-group-text">円<sub>(税抜)</sub></span>
                            </div>
                            <?= Html::error($model, "[{$index}]bonus_additional_payment_2") ?>
                        </div>
                    </div>
                    <div class="hstack gap-2 mb-3">
                        <label class="form-label col-first">総回収額</label>
                        <div class="col-2">
                            <div class="input-group">
                                <?= Html::activeTextInput($model, "[{$index}]total_charge_amount", ['class' => 'form-control formatted text-end']) ?>
                                <span class="input-group-text">円<sub>(税抜)</sub></span>
                            </div>
                        </div>
                        <?= Html::error($model, "[{$index}]total_charge_amount") ?>
                    </div>
                    <div class="hstack gap-2 mb-3">
                        <label class="form-label col-first">総支払額</label>
                        <div class="col-2">
                            <div class="input-group">
                                <?= Html::activeTextInput($model, "[{$index}]total_payment_amount", ['class' => 'form-control formatted text-end']) ?>
                                <span class="input-group-text">円<sub>(税抜)</sub></span>
                            </div>
                        </div>
                        <?= Html::error($model, "[{$index}]total_payment_amount") ?>
                    </div>
