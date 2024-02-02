<?php
/**
 * @var $this \yii\web\View;
 * @var $widget PageSizeLimitChanger;
 *
 */
use yii\bootstrap5\Html;
use app\widgets\PageSizeLimitChanger;
?>
<div class="page-size-limit-changer col-auto">
    <div class="input-group">
        <span class="input-group-text">表示件数</span>
        <?= Html::dropDownList('pageSizeLimitChanger', $widget->pageSize, PageSizeLimitChanger::$sizes, ['class' => 'form-select']) ?>
        <span class="input-group-text">件</span>
    </div>
</div>
