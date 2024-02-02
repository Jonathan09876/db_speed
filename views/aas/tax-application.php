<?php
/**
 * @var $this \yii\web\View
 * @var $model \app\models\TaxApplication;
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = $model->isNewRecord ? '新規税区分登録' : '税区分編集';
?>
<section id="tax-application">
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <h5 class="card-title mb-0"><?= $model->isNewRecord ? '新規税区分登録' : '税区分編集' ?></h5>
                    </div>
                </div>
            </div>
            <?php $form = ActiveForm::begin([]) ?>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($model, 'application_name') ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($model, 'tax_rate')->dropDownList(\app\models\ConsumptionTaxRate::getRates(), ['prompt' => '税率を選択']) ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($model, 'fixed')->dropDownList(\app\models\TaxApplication::$fixed_patterns, ['prompt' => '税率区分を選択']) ?>
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
