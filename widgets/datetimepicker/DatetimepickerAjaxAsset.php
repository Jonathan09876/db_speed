<?php

namespace app\widgets\datetimepicker;

class DatetimepickerAjaxAsset extends \yii\web\AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'assets/css/bootstrap-datetimepicker.min.css',
        'assets/font-awesome/css/font-awesome.css',

    ];
    public $js = [
        'assets/js/moment-with-locales.js',
        'assets/js/bootstrap-datetimepicker.min.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\web\JqueryAsset',
    ];
}