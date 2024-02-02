<?php
/**
 * @var $this \yii\web\View
 * @var $model \app\models\RepaymentPattern
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\ArrayHelper;
use app\models\RepaymentPattern;
use app\models\AccountTransferAgency;
use kartik\color\ColorInput;

$style = <<<EOS
.form-control-color {
    height: 1em !important
}
EOS;
$this->registerCss($style);

$this->title = $model->isNewRecord ? '新規支払区分登録' : '支払区分編集';
?>
<section id="repayment-pattern">
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <h5 class="card-title mb-0"><?= $model->isNewRecord ? '新規支払区分登録' : '支払区分編集' ?></h5>
                    </div>
                </div>
            </div>
            <?php $form = ActiveForm::begin([]) ?>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <?= $form->field($model, 'type') ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($model, 'bg_color')->widget(ColorInput::class, ['options' => ['placeholder' => '色を選択'], 'useNative' => true, 'size' => 'md']) ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">&nbsp;</label>
                        <label style="display:block;">
                            <?= Html::checkbox('RepaymentType[bg_color]', false, ['value' => '', 'class' => 'checkbox']) ?>
                            背景色を設定しない
                        </label>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <?= Html::submitButton('この内容で' . ($model->isNewRecord ? '登録' : '更新'), ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end() ?>
        </div>
    </div>
</section>
