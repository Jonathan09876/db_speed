<?php
/**
 * @var $this \yii\web\View
 * @var $model \app\models\TargetTermMonthlyChargeStored
 * @var $formModel \app\models\RepaymentByCustomerForm
 * @var $customer \app\models\Customer
 * @var $query \yii\db\ActiveQuery
 */

use app\widgets\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use app\models\CollectionCell;
use app\widgets\datetimepicker\Datetimepicker;

$sql = $query->select(['coc.*', 'tm.term', 'cd.term_start_at'])
    ->orderBy([
        'lc.disp_order' => SORT_ASC,
        'cd.contract_type' => SORT_ASC,
        'cd.term_start_at' =>SORT_ASC
    ])
    ->createCommand()
    ->rawSql;
//Yii::$app->db->createCommand('SET @priority=0;')->execute();
//$sql = "SELECT @priority:=@priority+1 as `priority`, c.* FROM ({$sql}) as c";
$ccs = Yii::$app->db->createCommand($sql)->queryAll();
$priority = 1; foreach($ccs as &$row){
    $row['priority'] = $priority++;
}
$collectionCells = ArrayHelper::index($ccs, 'term', 'contract_detail_id');
$terms = array_unique(array_reduce(array_map(function($values){
    return array_keys($values);
}, $collectionCells), 'array_merge', []));
$terms = array_values($terms);
sort($terms);

$script = <<<JS
class CellUpdater {
    target;
    self;
    
    constructor(){
        this.self = this;
    }
    
    async monthly_charge(attr, id){
        let tag, updated, response, timer, taxIncluded, theInput, formatted, shiftOn = false;
        switch(attr){
            case 'memo':
                tag = await this.getUpdateMonthlyChargeTag(attr, id);
                $('#updater').html(tag);
                $('#monthly_charge-'+attr+'-'+id).focus();
                $('#monthly_charge-'+attr+'-'+id).on('keyup', async evt => {
                    if (evt.key == 'Shift') {
                        shiftOn = false;
                    }
                })
                $('#monthly_charge-'+attr+'-'+id).on('keydown', async evt => {
                    theInput = evt.target;
                    if (evt.key == 'Shift') {
                        shiftOn = true;
                    }
                    if (!evt.originalEvent.isComposing && evt.key == 'Enter') {
                        updated = $('#monthly_charge-'+attr+'-'+id).val();
                        response = await this.updateMonthlyCharge(attr, id, updated);
                        if (response.success) {
                            $(this.target).html(updated);
                            $('#updater').empty();
                            $('#updater-overlay').hide();
                        }
                    }
                })
                $(document).one('click', '#updater-overlay', evt => {
                    $('#updater').empty();
                    $('#updater-overlay').hide();
                })
            
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
}
async function updateContent(evt){
    var updater = new CellUpdater;
    var targetClass = Array.prototype.slice.apply(evt.target.classList).find(function(className){
        return null !== className.match(/^cell-/);
    }), id = $(evt.target).data('id'), position = $(evt.target).offset(), overlayPosition = $(document.body).offset();
    var matched = targetClass ? targetClass.match(/cell-([^-]+)-([^-]+)/) : false;
    if (matched) {
        updater.target = evt.target;
        updater[matched[1]](matched[2], id);
        $('#updater').offset(position);
        $('#updater-overlay').show();
    }
    else if ($(evt.target).is('.delete-this')) {
        if (confirm('この回収情報を削除しても良いですか？')) {
            let id = $(evt.target).parents('.deletable').data('id');
            let response = await $.getJSON('/update/deletable-repayment?id='+id)
            if (response.success) {
                var wrapper = $(evt.target).parents('.contract-grid-wrapper');
                $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
            }
        }
    }
}


function calcTotal() {
    let totals = {
        deficientTotal: 0,
        repaymentTotal: 0
    };
    $('input.formatted[name^="Repayment["]').each(function(){
        totals.deficientTotal += $(this).data('amount')*1;
        totals.repaymentTotal += $(this).numVal();
    });
    $('#deficient-total input').val(totals.deficientTotal).format();
    $('#repayment-total input').val(totals.repaymentTotal).format();
    $('#remains-total input').val(totals.deficientTotal - totals.repaymentTotal).format();
    return totals;
}
function apportionAmount(amount) {
    let targets = $('input.formatted[name^="Repayment["]').sort(function(a,b){
        if ($(a).data('term') > $(b).data('term')) {
            return 1;
        } else if ($(a).data('term') < $(b).data('term')) {
            return -1;
        }
        else {
            $(a).data('priority')*1 > $(b).data('priority')*1 ? 1 : -1
        }
    });
    targets.each(function(){
        if (amount > $(this).data('amount')*1) {
            $(this).val($(this).data('amount')).format();
            amount -= $(this).data('amount')*1
        }
        else if (amount > 0) {
            $(this).val(amount).format();
            amount = 0;
        }
        else {
            $(this).val(0);
        }
    });
    $('#repaymentbycustomerform-pooled_repayment').val(amount).format();
}
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
$('.formatted').format();
$(document)
    .on('keyup', '.formatted', function(){
        $(this).format();
    })
    .on('click', '.editable', updateContent)
    .on('change', 'input.formatted[name^="Repayment["]', calcTotal)
    .on('click', '.btn-apportion-amount', function(){
        let amount = $('#pooled-amount input').numVal() + $('#repaymentbycustomerform-repayment_amount').numVal();
        apportionAmount(amount);
        calcTotal();
    })
    .on('click', 'button[type="submit"]', function(){
        $(this).addClass('disabled').prop('disabled', true);
        $(this).parents('form').submit();
    })
calcTotal();
JS;
$this->registerJs($script);

$styles = <<<CSS
.table-wrapper > .table > thead > tr > th {
    position:sticky;
    top: 0;
    z-index: 3;
}
.table-wrapper > .table > tbody > tr > td > input.form-control {
    min-width: 60px;
    text-align: right;
    font-size: 12px;
    padding: 2px;
    margin: 0:
}
.table-wrapper {
    padding-right:15px;
}
.table-wrapper > .table > tbody > tr > td.nc {
    background-color: #f3f3f3;
}
.row-uncollected td {
    background-color: #fdd;
}
.border-bottom-strong {
    border-bottom: 2px solid #888 !important;
}
CSS;
$this->registerCss($styles);
?>
<div class="card">
    <div class="card-header">
        <h2>一括入金登録</h2>
    </div>
    <div class="card-body">
        <?php $form = ActiveForm::begin([
            'layout' => 'horizontal',
        ]) ?>
        <h4>[CF: <?= $customer->customer_code ?>] [支払方法: <?= $customer->clientContract->repaymentPattern->name ?>] [顧客名: <?= $customer->name ?>]</h4>
        <div class="table-wrapper mb-5">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>契約No.</th>
                    <th>区分</th>
                    <?php foreach($terms as $termDate) : ?>
                    <th colspan="2"><?= (new \DateTime($termDate))->format('Y/m') ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php $totals = [
                    'monthly_charge' => [],
                    'repayment_amount' => [],
                    'deficient' => []
                ]; ?>
                <?php $index = 0;foreach($collectionCells as $contract_detail_id => $values) :
                    $detail = \app\models\ContractDetail::findOne($contract_detail_id);
                ?>
                    <tr>
                        <td rowspan="4" class="border-bottom-strong"><?= Html::a($detail->leaseContract->contractNumber, ['/aas/lease-contract', 'id' => $detail->lease_contract_id], ['target' =>'_blank']) ?></td>
                        <td>回収</td>
                        <?php foreach($terms as $termDate) :
                            $cellValue = isset($collectionCells[$contract_detail_id][$termDate]) ? $collectionCells[$contract_detail_id][$termDate] : false;
                            ?>
                            <?= $cellValue ? CollectionCell::renderRepaymentCell($cellValue, $index, 1) : "<td class=\"nc\"></td><td class=\"nc\"></td>\n" ?>
                            <?php if ($cellValue) {
                                $totals['monthly_charge'][$termDate] = isset($totals['monthly_charge'][$termDate]) ? $totals['monthly_charge'][$termDate] + $collectionCells[$contract_detail_id][$termDate]['monthly_charge_amount_with_tax'] : $collectionCells[$contract_detail_id][$termDate]['monthly_charge_amount_with_tax'];
                            } ?>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>実績</td>
                        <?php foreach($terms as $termDate) :
                            $cellValue = isset($collectionCells[$contract_detail_id][$termDate]) ? $collectionCells[$contract_detail_id][$termDate] : false;
                            ?>
                            <?= $cellValue ? CollectionCell::renderRepaymentCell($cellValue, $index, 2) : "<td class=\"nc\"></td><td class=\"nc\"></td>\n" ?>
                            <?php if ($cellValue) {
                            $totals['repayment_amount'][$termDate] = isset($totals['repayment_amount'][$termDate]) ? $totals['repayment_amount'][$termDate] + $collectionCells[$contract_detail_id][$termDate]['repayment_amount_with_tax'] : $collectionCells[$contract_detail_id][$termDate]['repayment_amount_with_tax'];
                        } ?>
                        <?php endforeach; ?>
                    </tr>
                    <tr class="row-uncollected">
                        <td>未回収</td>
                        <?php foreach($terms as $termDate) :
                            $cellValue = isset($collectionCells[$contract_detail_id][$termDate]) ? $collectionCells[$contract_detail_id][$termDate] : false;
                            ?>
                            <?= $cellValue ? CollectionCell::renderRepaymentCell($cellValue, $index, 3) : "<td class=\"nc\"></td><td class=\"nc\"></td>\n" ?>
                            <?php if ($cellValue) {
                            $totals['deficient'][$termDate] = isset($totals['deficient'][$termDate]) ? $totals['deficient'][$termDate] + $collectionCells[$contract_detail_id][$termDate]['monthly_charge_amount_with_tax'] - $collectionCells[$contract_detail_id][$termDate]['repayment_amount_with_tax'] : $collectionCells[$contract_detail_id][$termDate]['monthly_charge_amount_with_tax'] - $collectionCells[$contract_detail_id][$termDate]['repayment_amount_with_tax'];
                        } ?>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="border-bottom-strong">コメント</td>
                        <?php foreach($terms as $termDate) :
                            $cellValue = isset($collectionCells[$contract_detail_id][$termDate]) ? $collectionCells[$contract_detail_id][$termDate] : false;
                            ?>
                            <?= $cellValue ? CollectionCell::renderRepaymentCell($cellValue, $index, 4) : "<td class=\"nc\"></td><td class=\"nc\"></td>\n" ?>

                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr>
                    <th colspan="2">回収合計</th>
                    <?php foreach($terms as $termDate) : ?>
                        <th colspan="2" class="text-end"><?= number_format($totals['monthly_charge'][$termDate], 0) ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th colspan="2">実績合計</th>
                    <?php foreach($terms as $termDate) : ?>
                        <th colspan="2" class="text-end"><?= number_format($totals['repayment_amount'][$termDate], 0) ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th colspan="2">未回収合計</th>
                    <?php foreach($terms as $termDate) : ?>
                        <th colspan="2" class="text-end"><?= number_format($totals['deficient'][$termDate], 0) ?></th>
                    <?php endforeach; ?>
                </tr>
                </tfoot>
            </table>
        </div>
            <div class="row">
                <div class="col-md-3 offset-md-3">
                    <div class="mb-3 row">
                        <label class="col-form-label col-sm-6">回収合計</label>
                        <div class="col-sm-6" id="deficient-total">
                            <div class="input-group" style="margin-left:auto;width:150px;">
                                <input type="text" readonly class="form-control formatted text-end">
                                <span class="input-group-text">円</span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-form-label col-sm-6">振分済合計</label>
                        <div class="col-sm-6" id="repayment-total">
                            <div class="input-group" style="margin-left:auto;width:150px;">
                                <input type="text" readonly class="form-control formatted text-end">
                                <span class="input-group-text">円</span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-form-label col-sm-6">未回収合計</label>
                        <div class="col-sm-6" id="remains-total">
                            <div class="input-group" style="margin-left:auto;width:150px;">
                                <input type="text" readonly class="form-control formatted text-end">
                                <span class="input-group-text">円</span>
                            </div>
                        </div>
                    </div>
                    <?= $form->field($formModel, 'pooled_repayment', [
                        'labelOptions' => ['class' => 'col-form-label col-sm-6'],
                        'template' => '{label}<div class="col-sm-6"><div class="input-group" style="margin-left:auto;width:150px;">{input}<span class="input-group-text">円</span>{error}</div></div>', 'inputOptions' => ['class' => 'form-control formatted text-end']]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($formModel, 'repayment_type_id', [
                        'labelOptions' => ['class' => 'col-sm-6 col-form-label'],
                        'wrapperOptions' => ['class' => 'col-sm-6'],
                    ])->dropDownList(\app\models\RepaymentType::getTypes(), ['prompt' => '区分を選択', 'style' => 'margin-left:auto;width:150px;']) ?>
                    <?= $form->field($formModel, 'repayment_processed', [
                        'labelOptions' => ['class' => 'col-sm-6 col-form-label'],
                        'wrapperOptions' => ['class' => 'col-sm-6'],
                    ])->widget(DateTimePicker::class, [
                        'clientOptions' => [
                            'locale' => 'ja',
                            'format' => 'YYYY-MM-DD',
                        ],
                    ]) ?>
                    <div class="mb-3 row">
                        <label class="col-form-label col-sm-6">過入金預り金</label>
                        <div class="col-sm-6" id="pooled-amount">
                            <div class="input-group" style="margin-left:auto;width:150px;">
                                <input type="text" value="<?= $customer->pooled_repayment ?>" readonly class="form-control formatted text-end">
                                <span class="input-group-text">円</span>
                            </div>
                        </div>
                    </div>

                    <?= $form->field($formModel, 'repayment_amount', [
                        'labelOptions' => ['class' => 'col-sm-6 col-form-label'],
                        'template' => '{label}<div class="col-sm-6"><div class="input-group" style="margin-left:auto;width:150px;">{input}<span class="input-group-text">円</span>{error}</div></div>', 'inputOptions' => ['class' => 'form-control formatted text-end']]) ?>
                    <div class="text-end">
                        <?= Html::button('回収額を振分', ['class' => 'btn btn-outline-info btn-apportion-amount me-3']) ?>
                        <?= Html::submitButton('この内容で登録', ['class' => 'btn btn-primary']) ?>
                    </div>
                </div>
            </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
<div id="updater"></div>
<div id="updater-overlay"></div>
