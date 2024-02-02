<?php
use yii\bootstrap5\Breadcrumbs;
?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between"><?php if (!empty($this->params['breadcrumbs'])): ?>
            <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs'], 'options' => ['class' => 'm-0']]) ?>
            <?php endif; ?></div>
    </div>
</div>
