<?php
/**
 * @var $this \yii\web\View
 * @var $model \app\models\TaxApplication;
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use kartik\color\ColorInput;

$this->title = $model->isNewRecord ? '新規契約ステータス登録' : '契約ステータス編集';
$style = <<<EOS
.form-control-color {
    height: 1em !important
}
EOS;
$this->registerCss($style);
?>
<section id="tax-application">
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <h5 class="card-title mb-0"><?= $model->isNewRecord ? '新規契約ステータス登録' : '契約ステータス編集' ?></h5>
                    </div>
                </div>
            </div>
            <?php $form = ActiveForm::begin([]) ?>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <?= $form->field($model, 'type') ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($model, 'bg_color')->widget(ColorInput::class, ['options' => ['placeholder' => '色を選択'], 'useNative' => true, 'size' => 'md']) ?>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">&nbsp;</label>
                        <label style="display:block;">
                            <?= Html::checkbox('LeaseContractStatusType[bg_color]', false, ['value' => '', 'class' => 'checkbox']) ?>
                            背景色を設定しない
                        </label>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <?= Html::submitButton($model->isNewRecord ? 'この内容で登録' : 'この内容で更新', ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end() ?>
        </div>
    </div>
</section>
