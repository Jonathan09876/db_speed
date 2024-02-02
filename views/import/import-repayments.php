<?php
/**
 * @var $this \yii\web\View;
 * @var $model \app\models\ImportCustomerForm;
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

?>
<section id="import-customers">
    <div class="container">
        <div class="row g-3">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h6>回収情報インポート</h6>
                    </div>
                    <?php $form = ActiveForm::begin([]) ?>
                        <div class="card-body">
                            <?= $form->field($model, 'import_file')->fileInput() ?>
                            <?= \yii\helpers\VarDumper::dumpAsString($model->errors, 10, 1) ?>
                        </div>
                        <div class="card-footer">
                            <?= Html::submitButton('送信', ['class' => 'btn btn-primary']) ?>
                        </div>
                    <?php ActiveForm::end() ?>
                </div>
            </div>
        </div>
    </div>
</section>
