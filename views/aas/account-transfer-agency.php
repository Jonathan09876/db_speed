<?php
/**
 * @var $this \yii\web\View;
 * @var $model \app\models\AccountTransferAgency;
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
?>
<section id="registration-form">
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?= $model->isNewRecord ? '新規回収先登録' : '回収先情報編集' ?></h5>
            </div>
            <?php $form = ActiveForm::begin([]) ?>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <?= $form->field($model, 'code') ?>
                    </div>
                    <div class="col-md-8">
                        <?= $form->field($model, 'name') ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($model, 'transfer_fee', ['template' => '{label}<div class="input-group">{input}<span class="input-group-text">円</span>{error}</div>', 'inputOptions' => ['class' => 'form-control text-end']]) ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($model, 'basic_charge', ['template' => '{label}<div class="input-group">{input}<span class="input-group-text">円</span>{error}</div>', 'inputOptions' => ['class' => 'form-control text-end']]) ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($model, 'transfer_charge', ['template' => '{label}<div class="input-group">{input}<span class="input-group-text">円</span>{error}</div>', 'inputOptions' => ['class' => 'form-control text-end']]) ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($model, 'registration_date', ['template' => '{label}<div class="input-group"><span class="input-group-text">毎月</span>{input}<span class="input-group-text">日</span>{error}</div>', 'inputOptions' => ['class' => 'form-control text-end']]) ?>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <?= Html::submitButton($model->isNewRecord ? 'この内容で登録' : 'この内容で更新', ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</section>
