<?php
/**
 * @var $this \yii\web\View
 * @var $model \app\models\LeaseContract;
 */

use yii\grid\GridView;
use app\models\MonthlyCharge;
use app\models\MonthlyPayment;
use app\models\Repayment;
use yii\widgets\Pjax;

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
            case 'transfer_date':
                tag = await this.getUpdateMonthlyChargeTag(attr, id);
                $('#updater').html(tag);
                $('#monthly_charge-'+attr+'-'+id).on('dp.change', async evt => {
                    updated = $('#monthly_charge-'+attr+'-'+id).val();
                    response = await this.updateMonthlyCharge(attr, id, updated);
                    if (response.success) {
                        $(this.target).html(updated);
                        $('#updater').empty();
                        $('#updater-overlay').hide();
                    }
                })
                $(document).one('click', '#updater-overlay', evt => {
                    $('#updater').empty();
                    $('#updater-overlay').hide();
                })
                break;
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
                            $.pjax.reload('#pjax-grid-wrapper', {timeout : false});
                        }
                    }
                    else {
                        if (!timer) {
                            clearTimeout(timer);
                        }
                        timer = setTimeout(async () => {
                            var target = $(theInput).parent().parent().next().find('input');
                            taxIncluded = await this.calcTaxIncluded('monthly_charge', id, $(evt.target).numVal());
                            target.val(taxIncluded);
                            target.format();
                            formatted = target.val();
                        }, 300);
                    }
                })
                $(document).one('click', '#updater-overlay', evt => {
                    $('#updater').empty();
                    $('#updater-overlay').hide();
                })
                break;
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
                    if (shiftOn && evt.key == 'Enter') {
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
    
    async repayment(attr, id){
        let tag, updated, updatedType, response, timer, taxIncluded, theInput, formatted, shiftOn = false;
        switch(attr){
            case 'repayment_type_id':
                tag = await this.getUpdateRepaymentTag(attr, id);
                $('#updater').html(tag);
                $('#repayment-'+attr+'-'+id).on('change', async evt => {
                    updated = $('#repayment-'+attr+'-'+id).val();
                    updatedType = $('#repayment-'+attr+'-'+id).find('[value="'+updated+'"]').text();
                    response = await this.updateRepayment(attr, id, updated);
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
            case 'processed':
                tag = await this.getUpdateRepaymentTag(attr, id);
                $('#updater').html(tag);
                $('#repayment-'+attr+'-'+id).on('dp.change', async evt => {
                    updated = $('#repayment-'+attr+'-'+id).val();
                    response = await this.updateRepayment(attr, id, updated);
                    if (response.success) {
                        $(this.target).html(updated);
                        $('#updater').empty();
                        $('#updater-overlay').hide();
                    }
                })
                $(document).one('click', '#updater-overlay', evt => {
                    $('#updater').empty();
                    $('#updater-overlay').hide();
                })
                break;
            case 'repayment_amount':
            case 'chargeback_amount':
                tag = await this.getUpdateRepaymentTag(attr, id);
                $('#updater').html(tag);
                $('#repayment-'+attr+'-'+id).format().focus();
                $('#repayment-'+attr+'-'+id).on('keydown', async evt => {
                    theInput = evt.target;
                    if (evt.key == 'Enter') {
                        updated = $('#repayment-'+attr+'-'+id).numVal();
                        formatted = $('#repayment-'+attr+'-'+id).val();
                        response = await this.updateRepayment(attr, id, updated);
                        if (response.success) {
                            $(this.target).html(formatted);
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
    
    async getUpdateRepaymentTag(attr, id) {
        let tag = await $.ajax({
            method: 'post',
            url: '/update/repayment',
            data: {attr:attr, id:id},
            dataType: 'html',
        });
        return tag;
    }
        
    async updateRepayment(attr, id, value) {
        let response = await $.ajax({
            method: 'post',
            data: {attr: attr, id: id, value: value},
            url: '/update/repayment-value',
            dataType: 'json'
        });
        return response;
    }
    
    async monthly_payment(attr, id){
        let tag, updated, response, timer, taxIncluded, theInput, formatted, shiftOn = false;
        switch(attr){
            case 'payment_date':
                tag = await this.getUpdateMonthlyPaymentTag(attr, id);
                $('#updater').html(tag);
                $('#monthly_payment-'+attr+'-'+id).on('dp.change', async evt => {
                    updated = $('#monthly_payment-'+attr+'-'+id).val();
                    response = await this.updateMonthlyPayment(attr, id, updated);
                    if (response.success) {
                        $(this.target).html(updated);
                        $('#updater').empty();
                        $('#updater-overlay').hide();
                    }
                })
                $(document).one('click', '#updater-overlay', evt => {
                    $('#updater').empty();
                    $('#updater-overlay').hide();
                })
                break;
            case 'payment_amount':
                tag = await this.getUpdateMonthlyPaymentTag(attr, id);
                $('#updater').html(tag);
                $('#monthly_payment-'+attr+'-'+id).format().focus();
                $('#monthly_payment-'+attr+'-'+id).on('keydown', async evt => {
                    theInput = evt.target;
                    if (evt.key == 'Enter') {
                        updated = $('#monthly_payment-'+attr+'-'+id).numVal();
                        response = await this.updateMonthlyPayment(attr, id, updated);
                        if (response.success) {
                            var target = $(theInput).parent().parent().next().find('input');
                            taxIncluded = await this.calcTaxIncluded('monthly_payment', id, updated);
                            target.val(taxIncluded);
                            target.format();
                            formatted = target.val();
                            $(this.target).html(formatted);
                            $('#updater').empty();
                            $('#updater-overlay').hide();
                        }
                    }
                    else {
                        if (!timer) {
                            clearTimeout(timer);
                        }
                        timer = setTimeout(async () => {
                            var target = $(theInput).parent().parent().next().find('input');
                            taxIncluded = await this.calcTaxIncluded('monthly_payment', id, $(evt.target).numVal());
                            target.val(taxIncluded);
                            target.format();
                            formatted = target.val();
                        }, 300);
                    }
                })
                $(document).one('click', '#updater-overlay', evt => {
                    $('#updater').empty();
                    $('#updater-overlay').hide();
                })
                break;
            case 'memo':
                tag = await this.getUpdateMonthlyPaymentTag(attr, id);
                $('#updater').html(tag);
                $('#monthly_payment-'+attr+'-'+id).focus();
                $('#monthly_payment-'+attr+'-'+id).on('keyup', async evt => {
                    if (evt.key == 'Shift') {
                        shiftOn = false;
                    }
                })
                $('#monthly_payment-'+attr+'-'+id).on('keydown', async evt => {
                    theInput = evt.target;
                    if (evt.key == 'Shift') {
                        shiftOn = true;
                    }
                    if (shiftOn && evt.key == 'Enter') {
                        updated = $('#monthly_payment-'+attr+'-'+id).val();
                        response = await this.updateMonthlyPayment(attr, id, updated);
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
    
    async getUpdateMonthlyPaymentTag(attr, id) {
        let tag = await $.ajax({
            method: 'post',
            url: '/update/monthly-payment',
            data: {attr:attr, id:id},
            dataType: 'html',
        });
        return tag;
    }
        
    async updateMonthlyPayment(attr, id, value) {
        let response = await $.ajax({
            method: 'post',
            data: {attr: attr, id: id, value: value},
            url: '/update/monthly-payment-value',
            dataType: 'json'
        });
        return response;
    }
    
    async lease_payment(attr, id){
        let tag, updated, updatedType, response, timer, taxIncluded, theInput, formatted, shiftOn = false;
        switch(attr){
            case 'processed':
                tag = await this.getUpdateLeasePaymentTag(attr, id);
                $('#updater').html(tag);
                $('#lease_payment-'+attr+'-'+id).on('dp.change', async evt => {
                    updated = $('#lease_payment-'+attr+'-'+id).val();
                    response = await this.updateLeasePayment(attr, id, updated);
                    if (response.success) {
                        $(this.target).html(updated);
                        $('#updater').empty();
                        $('#updater-overlay').hide();
                    }
                })
                $(document).one('click', '#updater-overlay', evt => {
                    $('#updater').empty();
                    $('#updater-overlay').hide();
                })
                break;
            case 'payment_amount':
                tag = await this.getUpdateLeasePaymentTag(attr, id);
                $('#updater').html(tag);
                $('#lease_payment-'+attr+'-'+id).format().focus();
                $('#lease_payment-'+attr+'-'+id).on('keydown', async evt => {
                    theInput = evt.target;
                    if (evt.key == 'Enter') {
                        updated = $('#lease_payment-'+attr+'-'+id).numVal();
                        formatted = $('#lease_payment-'+attr+'-'+id).val();
                        response = await this.updateLeasePayment(attr, id, updated);
                        if (response.success) {
                            $(this.target).html(formatted);
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
    
    async getUpdateLeasePaymentTag(attr, id) {
        let tag = await $.ajax({
            method: 'post',
            url: '/update/lease-payment',
            data: {attr:attr, id:id},
            dataType: 'html',
        });
        return tag;
    }
        
    async updateLeasePayment(attr, id, value) {
        let response = await $.ajax({
            method: 'post',
            data: {attr: attr, id: id, value: value},
            url: '/update/lease-payment-value',
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
function numberFormat(num) {
    return num.toString().replace(/(\d+?)(?=(?:\d{3})+$)/g, '$1,');
}
$.fn.extend({
    format: function(){
        return $(this).each(function(){
            var v = $(this).val();
            $(this).val(numberFormat(v.toString().replace(/[^\d]/g,'')));
            return $(this);
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
    .on('click', '.editable', updateContent)

EOS;
$this->registerJs($script);
?>
<section id="repayment-patterns">
        <div class="row mb-2">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                            <h5 class="card-title">回収/支払予定一覧表</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = MonthlyCharge::find()->alias('mc')
                            ->innerJoin('contract_detail cd', 'mc.contract_detail_id=cd.contract_detail_id')
                            ->where(['cd.lease_contract_id' => $model->lease_contract_id]);
                        $dataProvider = new \yii\data\ActiveDataProvider([
                            'query' => $query,
                            'pagination' => false,
                        ])
                        ?>
                        <?php Pjax::begin([
                            'id' => 'pjax-grid-wrapper'
                        ]) ?>
                        <?php $layout =<<<EOL
{summary}
<div class="table-wrapper">
{items}
</div>
{pager}
EOL; ?>
                        <?= GridView::widget([
                            'dataProvider' => $dataProvider,
                            'showFooter' => true,
                            'layout' => $layout,
                            'columns' => [
                                [
                                    'header' => '回数',
                                    'class' => \yii\grid\SerialColumn::class,
                                ],
                                [
                                    'header' => '回収予定日',
                                    'attribute' => 'transfer_date',
                                    'contentOptions' => function($data){
                                        return ['data-id' => $data->monthly_charge_id, 'class' => 'editable cell-monthly_charge-transfer_date'];
                                    },
                                ],
                                [
                                    'header' => '回収予定額',
                                    'attribute' => 'amountWithTax',
                                    'content' => function($data){
                                        return number_format($data->getAmountWithTax('amount'), 0);
                                    },
                                    'contentOptions' => [
                                        'class' => 'text-end',
                                    ],
                                    'footerOptions' => [
                                        'class' => 'text-end',
                                    ],
                                    'footer' => "総額:".number_format(MonthlyCharge::getTotal($dataProvider->models, 'temporaryAmountWithTax'), 0),
                                ],
                                [
                                    'header' => '修正回収額',
                                    'contentOptions' => function($data){
                                        return ['data-id' => $data->monthly_charge_id, 'class' => 'text-end editable cell-monthly_charge-temporary_charge_amount'];
                                    },
                                    'content' => function($data){
                                        $amount = $data->getAmountWithTax('temporary_charge_amount');
                                        return number_format($amount, 0);
                                    },
                                ],
                                [
                                    'header' => '前払',
                                    'content' => function($data){
                                        return '&nbsp;';
                                    },
                                ],
                                [
                                    'header' => '回収方法',
                                    'contentOptions' => function($data){
                                        $repayment = $data->repayments[0] ?? false;
                                        return $repayment ? ['data-id' => $repayment->repayment_id, 'class' => 'editable cell-repayment-repayment_type_id'] : [];
                                    },
                                    'content' => function($data){
                                        $repayment = $data->repayments[0] ?? false;
                                        return $repayment ? $repayment->repaymentType->type : '';
                                    },
                                ],
                                [
                                    'header' => '回収日',
                                    'contentOptions' => function($data){
                                        $repayment = $data->repayments[0] ?? false;
                                        return $repayment ? ['data-id' => $repayment->repayment_id, 'class' => 'editable cell-repayment-processed'] : [];
                                    },
                                    'content' => function($data){
                                        $repayment = $data->repayments[0] ?? false;
                                        return $repayment ? $repayment->processed : '';
                                    },
                                ],
                                [
                                    'header' => '回収額<sub>（税込）</sub>',
                                    'contentOptions' => function($data){
                                        $repayment = $data->repayments[0] ?? false;
                                        return $repayment ? ['data-id' => $repayment->repayment_id, 'class' => 'text-end editable cell-repayment-repayment_amount'] : [];
                                    },
                                    'content' => function($data){
                                        $repayment = $data->repayments[0] ?? false;
                                        return $repayment ? number_format((int)$repayment->repayment_amount,0) : '';
                                    },
                                    'footerOptions' => [
                                        'class' => 'text-end',
                                    ],
                                    'footer' => "総額:".number_format(Repayment::getTotal($dataProvider->models, 'repayment_amount') - Repayment::getTotal($dataProvider->models, 'chargeback_amount'), 0)
                                ],
                                [
                                    'header' => '返金額',
                                    'contentOptions' => function($data){
                                        $repayment = $data->repayments[0] ?? false;
                                        return $repayment ? ['data-id' => $repayment->repayment_id, 'class' => 'text-end editable cell-repayment-chargeback_amount'] : [];
                                    },
                                    'content' => function($data){
                                        $repayment = $data->repayments[0] ?? false;
                                        return $repayment ? number_format((int)$repayment->chargeback_amount,0) : '';
                                    },
                                ],
                                [
                                    'header' => '回収残額',
                                    'contentOptions' => [
                                        'class' => 'text-end',
                                    ],
                                    'content' => function($data) use($dataProvider){
                                        $total = Repayment::find()->where(['and',
                                            ['contract_detail_id' => $data->contract_detail_id],
                                            ['<=', 'DATE_FORMAT(processed, "%Y%m")', (new \DateTime($data->term))->format('Ym')]
                                        ])->sum('repayment_amount');
                                        $remains = MonthlyCharge::getTotal($dataProvider->models, 'amountWithTax') - $total;
                                        return number_format($remains, 0);
                                    },
                                    'footerOptions' => [
                                        'class' => 'text-end',
                                    ],
                                    'footer' => "残額:".number_format(MonthlyCharge::getTotal($dataProvider->models, 'temporaryAmountWithTax') - (Repayment::getTotal($dataProvider->models, 'repayment_amount') - Repayment::getTotal($dataProvider->models, 'chargeback_amount')), 0)
                                ],
                                [
                                    'header' => '支払予定日',
                                    'contentOptions' => function($data){
                                        $monthlyPayment = $data->monthlyPayment;
                                        return $monthlyPayment ? [
                                            'data-id' => $monthlyPayment->monthly_payment_id,
                                            'class' => 'editable cell-monthly_payment-payment_date'
                                        ] : [];
                                    },
                                    'content' => function($data){
                                        $monthlyPayment = $data->monthlyPayment;
                                        return $monthlyPayment ? $monthlyPayment->payment_date : '';
                                    },
                                ],
                                [
                                    'header' => '支払予定額',
                                    'contentOptions' => function($data){
                                        $monthlyPayment = $data->monthlyPayment;
                                        return $monthlyPayment ? [
                                            'data-id' => $monthlyPayment->monthly_payment_id,
                                            'class' => 'text-end editable cell-monthly_payment-payment_amount'
                                        ] : [];
                                    },
                                    'content' => function($data){
                                        $monthlyPayment = $data->monthlyPayment;
                                        return $monthlyPayment ? number_format($monthlyPayment->amountWithTax,0) : '';
                                    },
                                    'footerOptions' => ['class' => 'text-end'],
                                    'footer' => "総額:".number_format(MonthlyPayment::getTotal($dataProvider->models, 'amountWithTax'), 0),
                                ],
                                [
                                    'header' => '支払日',
                                    'contentOptions' => function($data){
                                        $payment = $data->payments[0] ?? false;
                                        return $payment ? ['data-id' => $payment->lease_payment_id, 'class' => 'text-end editable cell-lease_payment-processed'] : [];
                                    },
                                    'content' => function($data){
                                        $payment = $data->payments[0] ?? false;
                                        return $payment ? $payment->processed : '';
                                    }
                                ],
                                [
                                    'header' => '支払額<sub>（税込）</sub>',
                                    'contentOptions' => function($data){
                                        $payment = $data->payments[0] ?? false;
                                        return $payment ? ['data-id' => $payment->lease_payment_id, 'class' => 'text-end editable cell-lease_payment-payment_amount'] : [];
                                    },
                                    'content' => function($data){
                                        $payment = $data->payments[0] ?? false;
                                        return $payment ? number_format((int)$payment->payment_amount,0) : '';
                                    },
                                    'footerOptions' => [
                                        'class' => 'text-end',
                                    ],
                                    'footer' => "支払残額:".number_format(MonthlyPayment::getTotal($dataProvider->models, 'amountWithTax') - \app\models\LeasePayment::getTotal($dataProvider->models, 'payment_amount'), 0)
                                ],
                                [
                                    'header' => '消費税',
                                    'contentOptions' => [
                                        'class' => 'text-end',
                                    ],
                                    'content' => function($data){
                                        $payment = $data->payments[0] ?? false;
                                        if ($payment) {
                                            $method = $data->contractDetail->fraction_processing_pattern;
                                            $methods = [
                                                'floor' => 'CEIL',
                                                'ceil' => 'FLOOR',
                                                'roundup' => 'ROUND'
                                            ];
                                            $sql = "SELECT :amount - {$methods[$method]}(:amount / (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE :term >= application_from AND :term <= IFNULL(application_to, '2099-12-31')) END)) FROM tax_application ta WHERE ta.tax_application_id=:id";
                                            $value = Yii::$app->db->createCommand($sql)->bindValues([
                                                ':amount' => $payment->payment_amount,
                                                ':term' => $payment->processed,
                                                ':id' => (int)$data->contractDetail->tax_application_id,
                                            ])->queryScalar();
                                            return number_format($value, 0);
                                        }
                                        return '';
                                    },
                                ],
                                [
                                    'header' => '税率',
                                    'content' => function($data){
                                        $payment = $data->payments[0] ?? false;
                                        if ($payment) {
                                            $sql = "SELECT CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE :term >= application_from AND :term <= IFNULL(application_to, '2099-12-31')) END FROM tax_application ta WHERE ta.tax_application_id=:id";
                                            $value = Yii::$app->db->createCommand($sql)->bindValues([
                                                ':term' => $payment->processed,
                                                ':id' => (int)$data->contractDetail->tax_application_id,
                                            ])->queryScalar();
                                            return (string)($value * 100).'%';
                                        }
                                        return '';
                                    },
                                ],
                                [
                                    'header' => 'コメント',
                                    'contentOptions' => function($data){
                                        return [
                                            'style' => 'min-width: 120px;',
                                            'class' => 'editable cell-monthly_charge-memo',
                                            'data-id' => $data->monthly_charge_id,
                                        ];
                                    },
                                    'content' => function($data){
                                        return $data->memo;
                                    },
                                ],
                            ],
                        ]) ?>
                        <?php Pjax::end(); ?>
                    </div>
                </div>
                <div id="updater"></div>
            </div>
        </div>
    <div id="updater-overlay"></div>
</section>
