<?php
/**
 * @var $this \yii\web\View;
 * @var $model \app\models\LeaseContract;
 * @var $targetModel \app\models\LeaseTarget;
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use kartik\date\DatePicker;
use app\models\ContractDetail;
use app\widgets\datetimepicker\Datetimepicker;
use kartik\typeahead\Typeahead;
use yii\helpers\Url;
use yii\bootstrap5\Modal;

$lcid = !$model->isNewRecord ? $model->lease_contract_id : 'null';
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
            case 'transfer_date':
                tag = await this.getUpdateMonthlyChargeTag(attr, id);
                if ($(this.target).is('.skippable')) {
                    tag = '<a href="/update/skip-this?id='+id+'" id="btn-skip-'+id+'" class="btn btn-sm btn-info">この月の回収をスキップ</a>' + tag;
                }
                if ($(this.target).is('.unskippable')) {
                    tag = '<a href="/update/unskip-this?id='+id+'" id="btn-unskip-'+id+'" class="btn btn-sm btn-info">スキップ取消</a>' + tag;
                }
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
                $('#btn-skip-'+id).on('click', async evt => {
                    evt.preventDefault();
                    response = await $.getJSON(evt.target.href);
                    if (response.success) {
                        $('#updater').empty();
                        $('#updater-overlay').hide();
                        var wrapper = $(this.target).parents('.contract-grid-wrapper');
                        $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
                    }
                    else {
                        alert('スキップ出来ません。');
                    }
                    return false;
                })
                $('#btn-unskip-'+id).on('click', async evt => {
                    evt.preventDefault();
                    $(this).addClass('disabled').prop('disabled', true);
                    response = await $.getJSON(evt.target.href);
                    if (response.success) {
                        $('#updater').empty();
                        $('#updater-overlay').hide();
                        var wrapper = $(this.target).parents('.contract-grid-wrapper');
                        $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
                    }
                    else {
                        alert('スキップ取消出来ません。');
                    }
                    return false;
                })
                $(document).one('click', '#updater-overlay', evt => {
                    $('#updater').empty();
                    $('#updater-overlay').hide();
                })
                break;
            case 'temporary_charge_amount':
                tag = await this.getUpdateMonthlyChargeTag(attr, id);
                $('#updater').html(tag);
                $('#monthly_charge-'+attr+'-'+id).format().focus().select();
                $('#monthly_charge-'+attr+'-'+id).on('keydown', async evt => {
                    theInput = evt.target;
                    if (evt.key == 'Enter') {
                        $('#monthly_charge-'+attr+'-'+id).format();
                        updated = $('#monthly_charge-'+attr+'-'+id).numVal();
                        response = await this.updateMonthlyCharge('temporary_charge_amount_with_tax', id, updated);
                        if (response.success) {
                            formatted = $('#monthly_charge-'+attr+'-'+id).val();
                            $(this.target).html(formatted);
                            $('#updater').empty();
                            $('#updater-overlay').hide();
                            var wrapper = $(this.target).parents('.contract-grid-wrapper');
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
                        var wrapper = $(this.target).parents('.contract-grid-wrapper');
                        $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
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
                            var wrapper = $(this.target).parents('.contract-grid-wrapper');
                            $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
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
                            var wrapper = $(this.target).parents('.contract-grid-wrapper');
                            $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
                        }
                    }
                    else {
                        if (timer) {
                            clearTimeout(timer);
                        }
                        timer = setTimeout(async () => {
                            var target = $(theInput).parent().parent().next().find('input');
                            taxIncluded = await this.calcTaxIncluded('monthly_payment', id, $(evt.target).numVal());
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
            case 'payment_amount_with_tax':
                tag = await this.getUpdateMonthlyPaymentTag(attr, id);
                $('#updater').html(tag);
                $('#monthly_payment-'+attr+'-'+id).format().focus().select();
                $('#monthly_payment-'+attr+'-'+id).on('keydown', async evt => {
                    theInput = evt.target;
                    if (evt.key == 'Enter') {
                        updated = $('#monthly_payment-'+attr+'-'+id).format().numVal();
                        formatted = $('#monthly_payment-'+attr+'-'+id).val();
                        response = await this.updateMonthlyPayment(attr, id, updated);
                        if (response.success) {
                            $(this.target).html(formatted);
                            $('#updater').empty();
                            $('#updater-overlay').hide();
                            var wrapper = $(this.target).parents('.contract-grid-wrapper');
                            $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
                        }
                    }
                })
                $(document).one('click', '#updater-overlay', evt => {
                    $('#updater').empty();
                    $('#updater-overlay').hide();
                })
                break;
            
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
                            var wrapper = $(this.target).parents('.contract-grid-wrapper');
                            $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
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
class CellRegistrant {
    target;
    self;
    
    constructor(){
        this.self = this;
    }
    
    async repayment(id){
        let tag, data, action, response, result, timer, taxIncluded, theInput, formatted, shiftOn = false;
        response = await this.getRegistrationForm(id);
        tag = response.tag;
        $('#updater').html(tag);
        $('#repayment-repayment_amount').on('keydown', async evt => {
            if (evt.key == 'Enter') {
                $('#register-repayment-form .btn-submit').addClass('disabled').attr('disabled', true);
                action = '/register/repayment?id='+id;
                data = $('#register-repayment-form').serialize();
                result = await this.registerRepayment(action, data);
                $('#updater').empty();
                $('#updater-overlay').hide();
                var wrapper = $(this.target).parents('.contract-grid-wrapper');
                $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
                return false;
            }
        })
        $('#register-repayment-form .btn-submit').on('click', async evt => {
            $('#register-repayment-form .btn-submit').addClass('disabled').attr('disabled', true);
            var date = $('.dateupdate').val();
            action = '/register/repayment?id='+id+'&date='+date;
            data = $('#register-repayment-form').serialize();
            result = await this.registerRepayment(action, data);
            $('#updater').empty();
            $('#updater-overlay').hide();
            var wrapper = $(this.target).parents('.contract-grid-wrapper');
            $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
        });
        $(document).one('click', '#updater-overlay', evt => {
            $('#updater').empty();
            $('#updater-overlay').hide();
        });
    }
    
    async getRegistrationForm(id) {
        let json = await $.ajax({
            method: 'get',
            url: '/register/get-registration-form',
            data: {id:id},
            dataType: 'json',
        });
        return json;
    }
    
    async registerRepayment(action, data) {
        let response = await $.ajax({
            method: 'post',
            url: action,
            data: data,
            dataType: 'json'
        })
        return response;
    }

}
function registerContent(evt){
    var registrant = new CellRegistrant;
    var targetClass = Array.prototype.slice.apply(evt.target.classList).find(function(className){
        return null !== className.match(/^cell-/);
    }), id = $(evt.target).data('mcid'), position = $(evt.target).offset(), overlayPosition = $(document.body).offset();
    var matched = targetClass.match(/cell-([^-]+)/);
    if (matched) {
        registrant.target = evt.target;
        registrant[matched[1]](id);
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
var imo = true, client_corp_id, timer, part, data, wrapper, list, listHeight, listShown = false, pos = 0, current, currentHeight, modalShown = false, modalSubmit = false, storedBtn;
function customerSelected(current) {
    $('#leasecontract-customer_id').val(current.data('id'));
    $('#leasecontract-customer_code').val(current.data('code'));
    $('#leasecontract-customer_name').val(current.data('name'));
    $('#row-bank-account-info').load('/aas/get-bank-account?id='+current.data('bid'));
    $('#default-repayment-type').val(current.data('rtype'));
    $('#transfer-agency').val(current.data('agency'));
    listShown = false;
    $('#customer-list').remove();
}
$(document)
    .on('pjax:start', '.contract-grid-wrapper', function(){
        $(this).append('<div class="pjax-loading"></div>')
    })
    .on('click', '.btn-append-detail', function(){
        let index = $(this).data('index'), lcid = {$lcid}, btn = $(this);
        storedBtn = btn.prop('outerHTML');
        $.ajax({
            type: 'get',
            url: '/aas/contract-detail?index='+index+(lcid ? '&lcid='+lcid : ''),
            dataType: 'html',
        }).then(function(html){
            html = '<div class="divider"><hr /></div>' + html + '<button class="btn btn-outline-success btn-remove-appeded-detail">追加したリース詳細を削除</button>';
            $(html).insertAfter(btn);
            btn.remove();
            $('[name$="[monthly_charge]"], [name$="[monthly_payment]"]').change();
        });
    })
    .on('click', '.btn-remove-appeded-detail', function(){
        $('.divider').nextAll().remove();
        $('.divider').replaceWith(storedBtn);
    })
    .on('change', '[name="LeaseContract[customer_client_corporation]"]', function(){
        var id=$(this).val();
        $('#leasecontract-customer_id').val('');
        $('#leasecontract-customer_code').val('');
        $('#leasecontract-customer_name').val('');
        $('#leasecontract-contract_pattern_id').parent().load('/aas/update-contract-patterns?id='+id);
    })
    .on('focus', '#leasecontract-customer_code,#leasecontract-customer_name', function(){
        if (!$('[name="LeaseContract[customer_client_corporation]"]').val()) {
            $(this).blur();
            alert('まず先に「会社」を選択してください。得意先コード、得意先名の検索には「会社」の指定が必要です。');
        }
        else {
            client_corp_id = $('[name="LeaseContract[customer_client_corporation]"]').val();
        }
    })
    .on('keydown', 'input:not(#leasecontract-customer_code,#leasecontract-customer_name)', function(evt){
        if (evt.key == 'Enter') {
            $(this).change();
            evt.preventDefault();
            return false;
        }
    })
    .on('keydown', '#leasecontract-customer_code,#leasecontract-customer_name', function(evt){
        if (listShown && (evt.key == 'Escape' || evt.key == 'ArrowDown' || evt.key == 'ArrowUp' || evt.key == 'Enter')) {
            imo = true;
            evt.preventDefault();
            if (listShown && evt.key == 'Escape') {
                listShown = false;
                $('#customer-list').remove();
            }
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
                if (!part) return;
                wrapper = $(evt.target).parents('.position-relative');
                $('ul.list-group', wrapper).remove();
                list = $('<ul id="customer-list" class="collapse list-group overflow-auto position-absolute w-100"></ul>');
                data = {
                    "CustomerSearch[client_corporation_id]": client_corp_id,
                };
                if (evt.target.id == 'leasecontract-customer_code') {
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
                if (result.length > 0) {
                    result.forEach(function(row){
                        list.append('<li class="list-group-item" data-id="'+row.customer_id+'" data-code="'+row.customer_code+'" data-name="'+row.name+'" data-bid="'+row.bank_account_id+'" data-rtype="'+row.type+'" data-agency="'+row.agency+'">['+row.customer_code+']'+row.name+'</li>');
                    });
                    $(evt.target).parents('.position-relative').append(list);
                    list.addClass('show');
                    listShown = true;
                    listHeight = $(list).innerHeight();
                }
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
    .on('click', ':not(#customer-list)', function(){
        if (listShown) {
            listShown = false;
            $('#customer-list').remove();
        }
    })
    .on('click', '#customer-list>.list-group-item', function(evt){
        current = $(evt.target);
        customerSelected(current);
    })
    .on('keyup', '.formatted', function(){
        //$(this).format();
    })
    .on('focus', '[name$="[monthly_charge]"],[name$="[monthly_payment]"]', function(){
        $(this).select();
    })
    .on('click', '.editable', updateContent)
    .on('click', '.registerable', registerContent)
    .on('change', '#leasecontract-current_status', async function(){
        var lcid = $(this).data('lcid'), selected = $(this).val(), selectedText = $(this).find('option[value="'+selected+'"]').text(), response;
        if (confirm('契約ステータスを「'+selectedText+'」に変更します。よろしいですか？')) {
            response = await $.getJSON('/aas/update-lease-contract-status?id='+lcid+'&status_type_id='+selected);
        }
        else {
            response = await $.getJSON('/aas/update-lease-contract-status?id='+lcid);
        }
        $(this).parent('#current-status-wrapper').html(response.tag);
    })
    .on('click', '.btn-increment-monthly-charge', async function(){
        var cdid = $(this).data('cdid'), transfer_date = $(this).data('transfer_date'), response;
        if (confirm('回収を'+transfer_date+'に追加します。よろしいですか？')) {
            response = await $.getJSON('/aas/increment-monthly-charge?id='+cdid);
            var wrapper = $(this).parents('.contract-grid-wrapper');
            $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
        }
JS;
if (!$model->isNewRecord) {
    $script .= <<<JS
    })
    .on('beforeSubmit', '#lease-contract-form', function(evt){
        console.log('before-submit');
        if (!modalSubmit) {
            evt.preventDefault();
            if (!modalShown) {
                modalShown = true;
                $('#modal-regenerate-monthly-charges-payments-confirm').modal('show');
                $('.btn-form-submit').on('click', function(){
                    modalSubmit = true;
                    $('#lease-contract-form').submit();
                    console.log('submitted');
                });
                $('#modal-regenerate-monthly-charges-payments-confirm').on('hidden.bs.modal', function(){
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
JS;
} else {
    $script .= <<<JS
    });
JS;

}
$this->registerJs($script);

$style=<<<CSS
.list-group.position-absolute {
    top:calc(100% + 0.2rem);
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
.table-wrapper table.table thead tr th {
    position: sticky;
    top: 0;
    z-index: 3;
}
.col-2-narrow {
    -webkit-box-flex: 0;
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    width: 11.11111111%;
}
.col-2-wide {
    -webkit-box-flex: 0;
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    width: 22.22222222%;
}
.col-first {
    -webkit-box-flex: 0;
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    width: 85px;
}
CSS;
$this->registerCss($style);
$this->title = $model->isNewRecord ? '契約情報新規登録' : '契約情報編集';
?>
<section id="customer">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-12">
                        <h5 class="card-title mb-0"><?= $model->isNewRecord ? '契約情報新規登録' : '契約情報編集' ?><?= Yii::$app->request->get('dc', false) ? Html::tag('span', '複製された契約情報です。まだこの契約情報は保存されていません。', ['style' => 'color:#57b959;', 'class' => 'ms-5']) : '' ?></h5>
                    </div>
                </div>
            </div>
            <?php $form = ActiveForm::begin([
                'id' => 'lease-contract-form'
            ]) ?>
            <?php if (Yii::$app->request->get('dc', false)) {
                $form->action = ['/aas/lease-contract'];
            } ?>
            <?php Modal::begin([
                'id' => 'modal-regenerate-monthly-charges-payments-confirm',
                'title' => '更新前確認',
                'centerVertical' => true,
                'footer' => Html::button('上記内容を確認して更新', ['class' => 'btn btn-primary btn-form-submit']),
            ]); ?>
            <p>更新時には回収/支払情報は更新しません。更新する場合は「回収情報を更新する」「支払情報を更新する」をチェックしてください。<br/><span class="text-danger">（期間変更時のみ更新してください。回収/支払方法更新時にはスキップ等の設定はリセットされます。）</span></p>
            <?= Html::activeCheckbox($model, 'regenerateMonthlyCharges', ['label' => '回収情報を更新する']) ?><br />
            <?= Html::activeCheckbox($model, 'regenerateMonthlyPayments', ['label' => '支払情報を更新する']) ?>
            <?php Modal::end(); ?>
            <div class="card-body">

                <div class="hstack gap-2 mb-3 position-relative">
                    <label class="col-auto form-label">会社</label>
                    <div class="col-2-narrow">
                        <?= Html::activeDropDownList($model, 'customer_client_corporation', \yii\helpers\ArrayHelper::map(Yii::$app->user->identity->clientCorporation->clientCorporationChildren, 'client_corporation_id', function($data){
                            return "{$data->code} : {$data->shorten_name}";
                        }), ['class' => 'form-control form-select', 'prompt' => '会社コード選択']) ?>
                        <?= Html::error($model, 'customer_client_corporation') ?>
                    </div>
                    <label class="col-auto form-label">得意先コード</label>
                    <div class="col-2-narrow<?= $model->hasErrors('customer_id') ? ' is-invalid' : '' ?>">
                        <?= Html::activeHiddenInput($model, 'customer_id') ?>
                        <?= Html::activeTextInput($model, 'customer_code', ['class' => 'form-control', 'placeholder' => '得意先コードを入力', 'autocomplete' => 'off']) ?>
                        <?= Html::error($model, 'customer_code') ?>
                    </div>
                    <label class="col-auto form-label">得意先名</label>
                    <div class="col-md-4">
                        <?= Html::activeTextInput($model, 'customer_name', ['class' => 'form-control', 'placeholder' => '得意先コードを入力', 'autocomplete' => 'off']) ?>
                    </div>
                    <?= Html::error($model, 'customer_name') ?>
                </div>
                <div class="hstack gap-2 mb-3">
                    <label class="form-label col-first">契約番号</label>
                    <div class="col-2-narrow">
                        <?= Html::activeDropDownList($model, 'contract_pattern_id', \app\models\LeaseContract::getContractPatterns($model->customer_client_corporation), ['class' => 'form-control form-select'.($model->hasErrors('contract_number_check') || $model->hasErrors('contract_pattern_id') ? ' is-invalid' : ''), 'prompt' => '契約マスタ選択']) ?>
                        <?= Html::error($model, 'contract_pattern_id') ?>
                    </div>
                    <div class="col-2-narrow">
                        <?= Html::activeTextInput($model, 'contract_number', ['class' => 'form-control'.($model->hasErrors('contract_number_check') || $model->hasErrors('contract_number') ? ' is-invalid' : '')]) ?>
                        <?= Html::error($model, 'contract_number') ?>
                    </div>
                    -
                    <div class="col-2-narrow">
                        <?= Html::activeTextInput($model, 'contract_code', ['class' => 'form-control'.($model->hasErrors('contract_number_check') || $model->hasErrors('contract_code') ? ' is-invalid' : '')]) ?>
                        <?= Html::error($model, 'contract_code') ?>
                    </div>
                    -
                    <div class="col-md-1">
                        <?= Html::activeTextInput($model, 'contract_sub_code', ['class' => 'form-control'.($model->hasErrors('contract_number_check') || $model->hasErrors('contract_sub_code') ? ' is-invalid' : '')]) ?>
                        <?= Html::error($model, 'contract_sub_code') ?>
                    </div>
                </div>
                <div id="contract_number_check" class="check-contract_number_check<?= $model->hasErrors('contract_number_check') ? ' is-invalid': '' ?>"></div>
                <?= Html::error($model, 'contract_number_check') ?>
                <div class="hstack gap-2 mb-3">
                    <label class="form-label col-first">物件名</label>
                    <div class="col-md-2">
                        <?= Html::activeTextInput($targetModel, 'name', ['class' => 'form-control']) ?>
                        <?= Html::error($targetModel, 'name') ?>
                    </div>
                    <label class="form-label col-auto">登録ナンバー</label>
                    <div class="col-2-narrow">
                        <?= Html::activeTextInput($targetModel, 'registration_number', ['class' => 'form-control']) ?>
                        <?= Html::error($targetModel, 'registration_number') ?>
                    </div>
                    <label class="form-label col-auto">物件属性</label>
                    <div class="col-md-2">
                        <?= Html::activeTextInput($targetModel, 'attributes', ['class' => 'form-control']) ?>
                        <?= Html::error($targetModel, 'attributes') ?>
                    </div>
                    <label class="form-label col-auto">物件備考</label>
                    <div class="col-md-3">
                        <?= Html::activeTextarea($targetModel, 'memo', ['class' => 'form-control', 'rows' => 1]) ?>
                        <?= Html::error($targetModel, 'memo') ?>
                    </div>

                </div>
                <div class="hstack gap-2 mb-3">
                    <label class="form-label col-first">引落口座</label>
                    <div id="row-bank-account-info" class="col-md-11">
                        <?php if (!$model->isNewRecord) : ?>
                        <?= $model->customer->bankAccount ?>
                        <?php else : ?>
                        <?php if ($model->customer_id) : ?>
                        <?php $customer = \app\models\Customer::findOne($model->customer_id);
                        if ($customer) {
                            echo $customer->bankAccount;
                        } ?>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hstack gap-2 mb-3">
                    <label class="form-label col-first">契約日</label>
                    <div class="col-md-2">
                        <?= Datetimepicker::widget([
                            'model' => $model,
                            'attribute' => 'contract_date',
                            'clientOptions' => [
                                'locale' => 'ja',
                                'format' => 'YYYY-MM-DD',
                            ]
                        ]) ?>
                        <?= Html::error($model, 'contract_date') ?>
                    </div>
                </div>
                <div class="hstack gap-2 mb-3">
                    <label class="form-label col-first">回収方法</label>
                    <div class="col-auto">
                        <?= Html::textInput('default_repayment_type', $model->isNewRecord ? '' : $model->customer->clientContract->repaymentPattern->name, ['id' => 'default-repayment-type', 'class' => 'form-control', 'readonly' => true]) ?>
                    </div>
                    <label class="form-label col-auto">回収先</label>
                    <div class="col-md-2">
                        <?= Html::textInput('transfer_agency', $model->isNewRecord ? '' : $model->customer->clientContract->repaymentPattern->accountTransferAgency->name, ['id' => 'transfer-agency', 'class' => 'form-control', 'readonly' => true]) ?>
                    </div>

                </div>
                <div id="contract-datail-pane">
                    <?php $i = 0; foreach($details as $detailModel) : ?>
                    <?= $this->render('contract-detail2', ['form' => $form, 'model' => $detailModel, 'index' => $i++]) ?>
                    <?php endforeach; ?>
                    <?= Html::button('リース詳細を追加', ['class' => 'btn btn-outline-success btn-append-detail', 'data-index' => $i]) ?>
                </div>
                <div class="row mt-1 mb-3">
                    <div class="col-md-12 position-relative">
                        <label class="form-label col-first">登録状況</label>
                        <?= Html::activeCheckbox($model, 'registration_incomplete', ['value' => 0, 'uncheck' => 1]) ?>
                        &nbsp;
                        <?= Html::activeCheckbox($model, 'collection_application_complete') ?>
                        <label class="form-label col-auto ms-3">特記事項</label>
                        <?= Html::activeTextArea($model, 'memo', ['class' => 'form-control', 'style' => 'position: absolute; width: calc(70% - 150px); top:0; right: 12%;']) ?>
                    </div>
                </div>
                <div class="hstack gap-2 mb-3">
                    <label class="form-label col-first">ステータス</label>
                    <div id="current-status-wrapper" class="col-2">
                        <?php
                            if($detailModel->term_end_at < date("Y-m-d"))
                                $model->current_status = 6;
                        ?>
                        <?= Html::activeDropDownList($model, 'current_status', \app\models\LeaseContractStatusType::getTypes(), ['class' => 'form-control form-select', 'prompt' => '', 'data-lcid' => $model->lease_contract_id]) ?>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <?= Html::submitButton('この内容で' . ($model->isNewRecord ? '登録' : '更新'), ['class' => 'btn btn-primary']) ?>
                <?= $model->isNewRecord ? '' : Html::a('この内容を複製して新規登録', ['/aas/lease-contract', 'dc' => $model->lease_contract_id], ['class' => 'btn btn-success']) ?>
                <?= Html::a('新規登録', ['/aas/lease-contract'], ['class' => 'btn btn-outline-success']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">回収/支払予定一覧表</h5>
            </div>

            <div class="card-body">
                <?php foreach($model->contractDetails as $contractDetail) : ?>
                <?= $this->render('partial-contract-chart-new2', ['model' => $contractDetail]) ?>
                <?php endforeach; ?>
            </div>

        </div>
        <div id="updater"></div>
        <div id="updater-overlay"></div>

</section>

