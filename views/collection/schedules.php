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
use app\widgets\modal\src\ModalAjax;
use app\models\ContractPattern;

$this->title = '回収予定一覧';

$script = <<<EOS
class CellUpdater {
    target;
    self;
    
    constructor(){
        this.self = this;
    }
    
    async monthly_charge(attr, id){
        let attr1, attr2, tag, tag1, tag2, updated, response, timer, taxIncluded, theInput, formatted, shiftOn = false;
        switch(attr){
            case 'temporary_charge_amount_and_type':
                attr1 = 'temporary_charge_amount';
                attr2 = 'repayment_type_id';
                tag1 = await this.getUpdateMonthlyChargeTag(attr1, id);
                tag2 = await this.getUpdateMonthlyChargeTag(attr2, id);
                $('#updater').html(tag1+tag2);
                $('#monthly_charge-'+attr1+'-'+id).format().focus().select();
                $('#monthly_charge-'+attr1+'-'+id).on('keydown', async evt => {
                    theInput = evt.target;
                    if (evt.key == 'Enter') {
                        updated = $('#monthly_charge-'+attr1+'-'+id).format().numVal();
                        formatted = $('#monthly_charge-'+attr1+'-'+id).val();
                        response = await this.updateMonthlyCharge('amount_with_tax', id, updated);
                        if (response.success) {
                            $(this.target).html(formatted);
                            $('#updater').empty();
                            $('#updater-overlay').hide();
                            var wrapper = $(this.target).parents('#pjax-grid-wrapper');
                            $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
                        }
                    }
                })
                var target_tag = $('#monthly_charge-'+attr2+'-'+id);
                target_tag.on('change', async evt => {
                    updated = target_tag.val();
                    var updatedType = target_tag.find('option[value="'+updated+'"]').text()
                    response = await this.updateMonthlyCharge(attr2, id, updated);
                    if (response.success) {
                        $('#updater').empty();
                        $('#updater-overlay').hide();
                        var wrapper = $(this.target).parents('#pjax-grid-wrapper');
                        $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
                    }
                })
                $(document).one('click', '#updater-overlay', evt => {
                    $('#updater').empty();
                    $('#updater-overlay').hide();
                })
                break
            case 'temporary_charge_amount':
                tag = await this.getUpdateMonthlyChargeTag(attr, id);
                $('#updater').html(tag);
                $('#monthly_charge-'+attr+'-'+id).format().focus().select();
                $('#monthly_charge-'+attr+'-'+id).on('keydown', async evt => {
                    theInput = evt.target;
                    if (evt.key == 'Enter') {
                        updated = $('#monthly_charge-'+attr+'-'+id).format().numVal();
                        formatted = $('#monthly_charge-'+attr+'-'+id).val();
                        response = await this.updateMonthlyCharge('amount_with_tax', id, updated);
                        if (response.success) {
                            $(this.target).html(formatted);
                            $('#updater').empty();
                            $('#updater-overlay').hide();
                            var wrapper = $(this.target).parents('#pjax-grid-wrapper');
                            $.pjax.reload('#'+wrapper.attr('id'), {timeout : false});
                        }
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
            case 'repayment_amount':
            case 'chargeback_amount':
                tag = await this.getUpdateRepaymentTag(attr, id);
                $('#updater').html(tag);
                $('#repayment-'+attr+'-'+id).format().focus().select();
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
                            let wrapper = $('#pjax-grid-wrapper.card-body');
                            wrapper.addClass('position-relative');
                            wrapper.append('<div style="background-color: rgba(0,0,0,.1);position:absolute;left:0;top:0;" class="w-100 h-100 d-flex align-items-center justify-content-center"><div style="width:100px;height:100px;" class="spinner-border text-secondary" role="status"><span class="sr-only">Loading...</span></div></div>')
                            $.pjax.reload('#pjax-grid-wrapper', {timeout : false});
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
async function updateContractMemo(evt){
    var id = $(evt.target).data('id'), position = $(evt.target).offset(), overlayPosition = $(document.body).offset();
    var target = evt.target, height = $(target).innerHeight(), tag = await $.get('/update/lease-contract-memo?id='+id);
    $('#updater').offset(position);
    $('#updater-overlay').show();
    $('#updater').html(tag);
    $('#updater textarea').css({height: height});
    $(document).one('click', '#updater-overlay', function(evt){
        $('#updater').empty();
        $('#updater-overlay').hide();
    })

    $('#updater .btn-update-memo').on('click', async function(){
        var params = $(this).parents('form').serialize();
        var response = await $.ajax({
            method: 'POST',
            url: $(this).parents('form').attr('action'),
            data: params,
            dataType: 'json',
        });
        if (response.success) {
            $(target).text(response.memo);
            $('#updater').empty();
            $('#updater-overlay').hide();
        }
    })
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
        $('#repayment-repayment_amount').on('keydown', evt => {
            if (evt.key == 'Enter') return false;
        })
        $('#register-repayment-form .btn-submit').on('click', async evt => {
            action = '/register/repayment?id='+id;
            data = $('#register-repayment-form').serialize();
            result = await this.registerRepayment(action, data);
            $('#updater').empty();
            $('#updater-overlay').hide();
            let wrapper = $('#pjax-grid-wrapper.card-body');
            wrapper.addClass('position-relative');
            wrapper.append('<div style="background-color: rgba(0,0,0,.1);position:absolute;left:0;top:0;" class="w-100 h-100 d-flex align-items-center justify-content-center"><div style="width:100px;height:100px;" class="spinner-border text-secondary" role="status"><span class="sr-only">Loading...</span></div></div>')
            $.pjax.reload('#pjax-grid-wrapper', {timeout : false});
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
    }), id = $(evt.target).data('id'), position = $(evt.target).offset(), overlayPosition = $(document.body).offset();
    var matched = targetClass.match(/cell-([^-]+)/);
    if (matched) {
        registrant.target = evt.target;
        registrant[matched[1]](id);
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
function setupSticky() {
    let width1 = $('.sticky-header1').outerWidth();
    let width2 = $('.sticky-header2').outerWidth();
    let width3 = $('.sticky-header3').outerWidth();
    let width4 = $('.sticky-header4').outerWidth();
    let width5 = $('.sticky-header5').outerWidth();
    let width6 = $('.sticky-header6').outerWidth();
    let width7 = $('.sticky-header7').outerWidth();
    let width8 = $('.sticky-header8').outerWidth();
    setCssStyle('.sticky-header2', 'left', width1+'px;');
    setCssStyle('.sticky-header3', 'left', (width1+width2)+'px;');
    setCssStyle('.sticky-header4', 'left', (width1+width2+width3)+'px;');
    setCssStyle('.sticky-header5', 'left', (width1+width2+width3+width4)+'px;');
    setCssStyle('.sticky-header6', 'left', (width1+width2+width3+width4+width5)+'px;');
    setCssStyle('.sticky-header7', 'left', (width1+width2+width3+width4+width5+width6)+'px;');
    setCssStyle('.sticky-header8', 'left', (width1+width2+width3+width4+width5+width6+width7)+'px;');
    setCssStyle('.sticky-header9', 'left', (width1+width2+width3+width4+width5+width6+width7+width8)+'px;');
    setCssStyle('.sticky-cell2', 'left', width1+'px;');
    setCssStyle('.sticky-cell3', 'left', (width1+width2)+'px;');
    setCssStyle('.sticky-cell4', 'left', (width1+width2+width3)+'px;');
    setCssStyle('.sticky-cell5', 'left', (width1+width2+width3+width4)+'px;');
    setCssStyle('.sticky-cell6', 'left', (width1+width2+width3+width4+width5)+'px;');
    setCssStyle('.sticky-cell7', 'left', (width1+width2+width3+width4+width5+width6)+'px;');
    setCssStyle('.sticky-cell8', 'left', (width1+width2+width3+width4+width5+width6+width7)+'px;');
    setCssStyle('.sticky-cell9', 'left', (width1+width2+width3+width4+width5+width6+width7+width8)+'px;');
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
var keyupTimer, checker_toggle = false, menu_toggle = false;
$(document)
    .on('pjax:start', '#pjax-grid-wrapper', function(){
        $(this).append('<div class="pjax-loading"></div>')
    })
    .on('keyup', '.formatted', function(){
        let that = $(this);
        if (keyupTimer) {
            clearTimeout(keyupTimer);
        }
        keyupTimer = setTimeout(function(){
            that.format();
        }, 300);
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
        var id = $(this).val(), patterns = await $.get('/collection/update-contract-pattern?id='+id),
        items = await $.get('/collection/update-search-contract-pattern?id='+id);
        $('#schedulesearch-contract_pattern_id').replaceWith(patterns);
        $('#contract-pattern-wrapper').html(items);
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
                console.log(evt.target);
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
    .on('click', '.btn-toggle-checker', function(){
        checker_toggle = !checker_toggle;
        if (checker_toggle) {
            $('.btn-modal-bulk-update').removeClass('disabled');
            $('.sticky-cell1').each(async function(){
                var cdid = $(this).parents('tr').data('cdid');
                if (cdid != undefined) {
                    var tag = await $.get('/update/get-checker?id='+cdid);
                    $(this).append(tag);
                }
            })
        }
        else {
            $('.btn-modal-bulk-update').addClass('disabled');
            $('.sticky-cell1 .form-check').remove();
        }
    })
    .on('change', '.sticky-cell1 input', function(){
        var cdid = $(this).val(), check = $(this).is(':checked') ? 1 : 0;
        $.get('/update/checker?id='+cdid+'&checked='+check);
    })
    .on('click', '.editable', updateContent)
    .on('click', '.registerable', registerContent)
    .on('contextmenu', '.table-wrapper .table tbody tr td', async function(evt){
        evt.preventDefault();
        if ($(this).is('.row-repayments td[data-id]')) {
            var menu = $('#context-menu'), rpid = $(this).data('id'), context;
            if (!menu_toggle || menu_toggle != rpid) {
                menu_toggle = rpid;
                context = await $.get('/collection/repayment-memo?id='+rpid);
                menu.find('#processed-date span').text('回収日：'+context.processed);
                if (context.memo) {
                    menu.find('#memo span').text('メ　モ：'+context.memo);
                }
                else {
                    menu.find('#memo span').empty();
                }
                menu.css({display: 'block', top: evt.pageY, left:evt.pageX});
            }
        }
        else {
            $('#context-menu').css({display: 'none'});
            menu_toggle = false;
        }    
        return false;
    })
    .on('click', '#context-menu', function(){
        $('#context-menu').css({display: 'none'});
        menu_toggle = false;
    })
    .on('click', '.edit-memo', updateContractMemo)
$('.formatted').format();
setupSticky();
$('.btn-modal-bulk-update').addClass('disabled');
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
.table-wrapper table.table th:nth-of-type(3) {
    min-width: 10em;
}
.table-wrapper table.table th:nth-of-type(7) {
    min-width: 8em;
}
.table-wrapper table.table td:nth-of-type(3) {
    white-space: inherit;
}
.table-wrapper table.table th:nth-of-type(7),
.table-wrapper table.table td:nth-of-type(7) {
    white-space: inherit;
}
.table-wrapper table.table th,
.table-wrapper table.table td
.table-wrapper table.table thead th {
    position: sticky;
    top:0;
    z-index:2;
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
    z-index:3 !important;
}
.sticky-cell {
    position:sticky;
    top:0;
    background-color: #fff !important;
    border-left-width: 1;
    z-index:1;
}
.sticky-cell1 {
    left: 0px;
}
.sticky-header2 {
    position:sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell2 {
}
.sticky-header3 {
    position:sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell3 {
}
.sticky-header4 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell4 {
}
.sticky-header5 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell5 {
}
.sticky-header6 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell6 {
}
.sticky-header7 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell7 {
}
.sticky-header8 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell8 {
}
.sticky-header9 {
    position: sticky;
    top:0;
    z-index:3 !important;
}
.sticky-cell9 {
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
.bg-gray {
    background-color: #aaaaaa!important;
}
.paid {
    background-color: #e8e8e8!important;
}
.deficient {
    background-color: #ffd4d4!important;
}
.sticky-cell1 .form-check {
    text-align: center;
}
#context-menu {
    position: absolute;
    display: none;
    z-index: 100;
    background-color: #fff;
    border: 1px solid #ccc;
    padding: 10px;
    width: auto;
    font-size: 12px;
    line-height: 14px;
}
#context-menu div span {
    line-break: strict;
    white-space: nowrap;
}
#context-menu .label {
    margin: 0 .5em 0 0;
    display: block;
    width: 3em;
}
EOS;
$this->registerCss($style);
?>
<div id="context-menu">
    <div id="processed-date"><span></span></div>
    <div id="memo"><span></span></div>
</div>
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
                            <?= $form->field($searchModel, 'customer_code', [
                                'horizontalCssClasses' => [
                                    'label' => 'form-label col-first',
                                ],
                                'inputOptions' => [
                                    'placeholder' => '得意先コードを入力',
                                    'autocomplete' => 'off',
                                ],
                                'template' => '{label}<div class="col-auto"><div class="input-group has-validation">{input}<button type="button" class="btn btn-outline-secondary btn-clear-customer"><i class="ri-close-line"></i></button>{error}</div></div>'
                            ]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($searchModel, 'customer_name', [
                                'horizontalCssClasses' => [
                                    'label' => 'form-label col-auto',
                                ],
                                'inputOptions' => [
                                    'placeholder' => '得意先名入力',
                                    'autocomplete' => 'off'
                                ],
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
                        <div id="contract-pattern-wrapper">
                            <?php $patterns = ContractPattern::getContractNamePatterns($searchModel->client_corporation_id); ?>
                            <?= Html::activeCheckboxList($searchModel, 'contract_pattern_id', $patterns, ['inline' => true]) ?>
                        </div>
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
                    <button class="btn btn-log" onClick='javascript:test(<?php $form?>)'>afeaf</button>
                    <?= Html::submitButton('この内容で検索', ['class' => 'btn btn-primary', ]) ?>
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
                <div class="col-md-3">
                    <h5 class="card-title mb-0">回収実績登録一覧</h5>
                </div>
                <div class="col-md-9 text-end">
                    <?= Html::a('CSVエクスポート', ['/collection/export-schedules'], ['class' => 'btn btn-sm btn-success']) ?>
                    <?= Html::a('PDF出力', ['/publish/collection-schedules'], ['class' => 'btn btn-sm btn-secondary', 'target' => '_blank']) ?>
                </div>
            </div>
        </div>
        <?php \yii\widgets\Pjax::begin([
            'id' => 'pjax-grid-wrapper',
            'options' => [
                'class' => 'card-body',
            ],
            'linkSelector' => 'th a',
            'timeout' => 30000.
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
        $button1 = Html::button('一括更新選択切替', ['class' => 'btn btn-sm btn-outline-info btn-toggle-checker me-2']);
        $button2 = ModalAjax::widget([
            'id' => "modal-bulk-update-form",
            'title' => '期間指定一括更新:回収',
            'toggleButton' => [
                'label' => '期間指定一括更新:回収',
                'class' => 'btn btn-outline-info btn-modal-bulk-update btn-sm me-2',
            ],
            'url' => ['/update/bulk'],
            'ajaxSubmit' => true,
            'autoClose' => true,
            'events' => [
                ModalAjax::EVENT_BEFORE_SUBMIT => new \yii\web\JsExpression("
                    function(event, data, status, xhr, selector) {
                        $('#pjax-grid-wrapper .modal-body').html('<div class=\"modal-ajax-loader\"></div>')
                    }
                "),
                ModalAjax::EVENT_MODAL_SUBMIT_COMPLETE => new \yii\web\JsExpression("
                    function(event, xhr, textStatus) {
                        checker_toggle = false;
                        $.pjax.reload('#pjax-grid-wrapper', {timeout: false});
                    }
                "),
            ],
        ]);        $layout = <<<EOL
<div class="buttons">{$button1}{$button2}</div>
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
                <th class="sticky-header8">リース期間<br/>会社</th>
                <th class="sticky-header9">収支</th>
EOL;
        $current = (new \DateTime())->setDate(date('Y'), date('n'), 1);
        $lastMonth = $current->modify('-1 month');
        foreach($terms as $term) {
            $termText = $term->termDateTime->format('Y/m');
            if ($term->termDateTime < $lastMonth || \app\models\TargetTermMonthlyChargeStored::isMonthClosed($term->term, $searchModel->client_corporation_id) ) {
                $layout .= <<<EOL
                <th>回数</th>
                <th style="width:140px;">{$termText}</th>
EOL;
            }
            else {
                $layout .= <<<EOL
                <th class="current-term">回数</th>
                <th class="current-term" style="width:140px;">{$termText}</th>
EOL;
            }
        }
        $layout .= <<<EOL
                <th>今期合計</th>
                <th>リース開始年月<br />債務回収数</th>
                <th>前払回数</th>
                <th>前払リース料</th>
                <th>特記事項</th>
            </tr>
        </thead>
        <tbody>
            {items}
        </tbody>
        <tbody>
            <tr>
                <td class="sticky-cell sticky-cell1"></td>
                <td class="sticky-cell sticky-cell2"></td>
                <td class="sticky-cell sticky-cell3"></td>
                <td class="sticky-cell sticky-cell4"></td>
                <td class="sticky-cell sticky-cell5"></td>
                <td class="sticky-cell sticky-cell6"></td>
                <td class="sticky-cell sticky-cell7"></td>
                <td class="sticky-cell sticky-cell8 text-end" colspan="2">回収予定合計</td>
EOL;
        foreach($terms as $term) {
            $total = number_format(\app\models\ContractDetail::getTermTotalChargeAmountWithTax($dataProvider, $term), 0);
            $layout .= <<<EOL
                <td></td>
                <td class="text-end">{$total}</td>
EOL;
        }
        $total = number_format(\app\models\ContractDetail::getTermsDetailsTotalChargeAmountWithTax($dataProvider, $terms), 0);
        $wholeTotal = number_format(\app\models\ContractDetail::getWholeTotalChargeAmountWithTax($dataProvider),0);
        $layout .= <<<EOL
                <td class="text-end">{$total}</td>
                <td class="text-end">{$wholeTotal}</td>
            </tr>
            <tr>
                <td class="sticky-cell sticky-cell1"></td>
                <td class="sticky-cell sticky-cell2"></td>
                <td class="sticky-cell sticky-cell3"></td>
                <td class="sticky-cell sticky-cell4"></td>
                <td class="sticky-cell sticky-cell5"></td>
                <td class="sticky-cell sticky-cell6"></td>
                <td class="sticky-cell sticky-cell7"></td>
                <td class="sticky-cell sticky-cell8 text-end" colspan="2">回収実績合計</td>
EOL;
        foreach($terms as $the_term) {
            $total = number_format(\app\models\ContractDetail::getTermRepaymentTotal($dataProvider, $the_term), 0);
            $layout .= <<<EOL
                    <td></td>
                    <td class="text-end">{$total}</td>
EOL;
        }
        $total = number_format(\app\models\ContractDetail::getTermsDetailsTotalRepaymentAmountWithTax($dataProvider, $terms), 0);
        $wholeTotal = number_format(\app\models\ContractDetail::getWholeTotalRepaymentAmountWithTax($dataProvider),0);
        $layout .= <<<EOL
                <td class="text-end">{$total}</td>
                <td class="text-end">{$wholeTotal}</td>
            </tr>
            <tr>
                <td class="sticky-cell sticky-cell1"></td>
                <td class="sticky-cell sticky-cell2"></td>
                <td class="sticky-cell sticky-cell3"></td>
                <td class="sticky-cell sticky-cell4"></td>
                <td class="sticky-cell sticky-cell5"></td>
                <td class="sticky-cell sticky-cell6"></td>
                <td class="sticky-cell sticky-cell7"></td>
                <td class="sticky-cell sticky-cell8 text-end" colspan="2">支払合計</td>
EOL;
        foreach($terms as $the_term) {
            $total = number_format(\app\models\ContractDetail::getTermTotalPaymentAmountWithTax($searchModel, $dataProvider, $the_term), 0);
            $layout .= <<<EOL
                    <td></td>
                    <td class="text-end">{$total}</td>
EOL;
        }
        $total = number_format(\app\models\ContractDetail::getTermsDetailsTotalPaymentAmountWithTax($dataProvider, $terms), 0);
        $wholeTotal = number_format(\app\models\ContractDetail::getWholeTotalPaymentAmountWithTax($searchModel, $dataProvider),0);
        $layout .= <<<EOL
                <td class="text-end">{$total}</td>
                <td class="text-end">{$wholeTotal}</td>
            </tr>
        </tbody>
    </table>
</div>
{pager}
EOL;
        ?>
        <?= ListView::widget([
            'dataProvider' => $dataProvider,
            'itemView' => 'iv-collection-schedules-latest',
            'itemOptions' => ['tag' => false],
            'viewParams' => compact("targetTerm", "terms", "searchModel", "lastMonth"),
            'layout' => $layout,
        ]) ?>
        <?php \yii\widgets\Pjax::end(); ?>
    </div>
    <div id="updater"></div>
    <div id="updater-overlay"></div>
    <?php endif; ?>
</section>


