<?php

namespace app\widgets\datetimepicker;

use yii\bootstrap5\Html;
use yii\web\JsExpression;

class Datetimepicker extends \yii\bootstrap5\InputWidget
{
    public $ajax = false;
    public $script =<<<EOS
$.extend($.fn.datetimepicker.defaults, {
    locale:'ja',
    format:'YYYY-MM-DD',
    dayViewHeaderFormat:'YYYY年MMMM',
    icons: {
        time: 'ri-time-line',
        date: 'ri-calendar-line',
        up: ' ri-arrow-up-s-line',
        down: 'ri-arrow-down-s-line',
        previous: 'ri-arrow-left-s-line',
        next: 'ri-arrow-right-s-line',
        today: 'ri-calendar-check-line',
        clear: 'ri-delete-bin-2-line',
        close: 'ri-close-line'
    },
    tooltips: {
        today: '今日の日付',
        clear: 'クリア',
        close: '閉じる',
        selectMonth: '月を選択',
        prevMonth: '前月',
        nextMonth: '翌月',
        selectYear: '年を選択',
        prevYear: '前年',
        nextYear: '翌年',
        selectDecade: '10年単位で選択',
        prevDecade: '前の10年',
        nextDecade: '次の10年',
        prevCentury: '前世紀',
        nextCentury: '次世紀',
        pickHour: '時間を選択',
        incrementHour: '時間を増加',
        decrementHour: '時間を減少',
        pickMinute: '分を選択',
        incrementMinute: '分を増加',
        decrementMinute: '分を減少',
        pickSecond: '秒を選択',
        incrementSecond: '秒を増加',
        decrementSecond: '秒を減少',
        togglePeriod: 'ピリオド切替',
        selectTime: '時刻を選択'
    },
});
EOS;

    public function init()
    {
        parent::init();
        $view = $this->getView();
        if ($this->ajax) {
            DatetimepickerAjaxAsset::register($view);
        }
        else {
            DatetimepickerAsset::register($view);
        }
        $view->registerJs($this->script);
    }

    public function run()
    {

        if ($this->hasModel()) {
            $id = isset($this->id) ? $this->id : Html::getInputId($this->model, $this->attribute);
            $group_id = "input-group-{$id}";
            $attribute = preg_match('/\[\d+\](\w+)/', $this->attribute, $matches) ? $matches[1] : $this->attribute;
            $options = array_merge($this->options, ['id'=>$id, 'class' => 'form-control datetimepicker-input'.($this->model->hasErrors($attribute) ? ' is-invalid' : ''), 'data' => ['target' => "#{$group_id}"]]);
            $clientOptions = json_encode($this->clientOptions);
            $input = Html::activeTextInput($this->model, $this->attribute, $options);
        }
        else {
            $id = $this->id;
            $group_id = "input-group-{$id}";
            $options = array_merge($this->options, ['id'=>$id, 'class' => 'form-control datetimepicker-input', 'data' => ['target' => "#{$group_id}"]]);
            $clientOptions = json_encode($this->clientOptions);
            $input = Html::textInput($this->name, $this->value, $options);
        }
        $script = <<<EOS
$('#{$id}').datetimepicker($clientOptions);
$('#{$group_id} .input-group-text').click(function(){
    $('#{$id}').focus();
});
EOS;
        $this->getView()->registerJs($script);
        $isInvalid = ($this->hasModel() && $this->model->hasErrors()) ? ' is-invalid' : '';
        $tag = <<<EOT
<div class="input-group date{$isInvalid}" id="{$group_id}" data-target-input="nearest">
    {$input}
    <div class="input-group-text" data-target="#{$group_id}" data-toggle="datetimepicker">
        <i class="fa fa-calendar"></i>
    </div>
</div>
EOT;
        return $tag;
    }
}