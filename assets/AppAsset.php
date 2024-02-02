<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Main application asset bundle.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/site.css',
        'assets/css/icons.min.css',
        'assets/css/app.min.css',
        'assets/css/custom.min.css'
    ];
    public $js = [
        'assets/js/layout.js',
        'assets/libs/simplebar/simplebar.min.js',
        'assets/libs/node-waves/waves.min.js',
        'assets/libs/feather-icons/feather.min.js',
        'assets/js/pages/plugins/lord-icon-2.1.0.js',
        'assets/js/plugins.js',
        'assets/js/app.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapPluginAsset'
    ];
}
