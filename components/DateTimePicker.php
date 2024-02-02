<?php
/**
 * Created by PhpStorm.
 * User: decama
 * Date: 2017/06/06
 * Time: 23:15
 */

namespace app\components;


use app\assets\DateTimePickerAsset;
use yii\helpers\VarDumper;
use yii\bootstrap5\InputWidget;
use yii\bootstrap5\Html;

class DateTimePicker extends InputWidget
{
    static $registered = [
        'default' => false,
        'class-selected' => false,
    ];

    public $script =<<<EOS
$.extend($.fn.datetimepicker.defaults, {
    locale:'ja',
    format:'YYYY-MM-DD',
    dayViewHeaderFormat:'YYYY年MMMM',
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
        DateTimePickerAsset::register($this->getView());
        $this->getView()->registerJs($this->script);
    }

    public function run()
    {
        echo $this->renderWidget();
    }

    public function renderWidget()
    {
        $content = [];
        if ($this->hasModel()) {
            $id = Html::getInputId($this->model, $this->attribute);
            $name = !empty($this->name) ? $this->name : Html::getInputName($this->model, $this->attribute);
            $options = array_merge($this->options, ['id'=>$id, 'name' => $name, 'class'=>'form-control datepicker', 'autocomplete' => 'off']);
            if (!empty($this->placeholder)) {
                $options['data-placeholder'] = $this->placeholder;
            }
            if ($this->clientOptions) {
                $clientOptions = json_encode($this->clientOptions);
                $script = <<<EOS
$('#{$id}').datetimepicker({$clientOptions});
EOS;
                $this->getView()->registerJs($script);
            }
            else {
                $this->getView()->registerJs("$('#{$id}').datetimepicker();");
            }
            $content[] = Html::ActiveTextInput($this->model, $this->attribute, $options);
        }
        else {
            $options = array_merge(['id'=>$this->id, 'class'=>'form-control datepicker', 'autocomplete' => 'off'], $this->options);
            if (!empty($this->placeholder)) {
                $options['data-placeholder'] = $this->placeholder;
            }
            if ($this->clientOptions) {
                $clientOptions = json_encode($this->clientOptions);
                $script = <<<EOS
$('#{$this->id}').datetimepicker({$clientOptions});
EOS;
                $this->getView()->registerJs($script);
            }
            $this->getView()->registerJs('$(\'.datepicker\').datetimepicker();');
            $content[] = Html::textInput($this->name, $this->value, $options);
        }
        return join("\r\n", $content);
    }
}