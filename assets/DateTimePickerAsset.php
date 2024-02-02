<?php

namespace app\assets;

class DateTimePickerAsset extends \yii\web\AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        //'assets/css/tempus-dominus.min.css',
        'assets/css/bootstrap-datetimepicker.min.css',
    ];
    public $js = [
        //'https://unpkg.com/@popperjs/core@2',
        //'assets/js/tempus-dominus.min.js',
        //'assets/locales/ja.js',
        //'assets/js/jQuery-provider.min.js',
        'assets/js/bootstrap-datetimepicker.min.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapPluginAsset'
    ];

}