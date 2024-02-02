<?php
/**
 * @var $this \yii\web\View;
 * @var $model \app\models\MonthlyPayments;
 */

use app\widgets\ActiveForm;
use yii\bootstrap5\Html;
use app\widgets\datetimepicker\Datetimepicker;

$script = <<<EOS
$(document)
    .on('keydown', '#monthlypayments-payment_amount', function(){
        var that = this;
        setTimeout(function(){
            $(that).format();
        }, 150);
    })
EOS;
$this->registerJs($script);
?>
<div class="card">
    <?php $form = ActiveForm::begin([
        'id' => 'monthlypayments-bulkupdate-form'
    ]); ?>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'term_from')->widget(DateTimePicker::class, [
                        'id' => 'monthlypayments-term_from',
                        'clientOptions' => [
                            'locale' => 'ja',
                            'format' => 'YYYY年M月',
                            'viewMode' => 'months',
                        ]
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'term_to')->widget(DateTimePicker::class, [
                        'id' => 'monthlypayments-term_to',
                        'clientOptions' => [
                            'locale' => 'ja',
                            'format' => 'YYYY年M月',
                            'viewMode' => 'months',
                        ]
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'payment_amount', ['template' => '{label}<div class="input-group">{input}<span class="input-group-text">円</span>{error}</div>', 'inputOptions' => ['class' => 'form-control text-end']]) ?>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <?= Html::submitButton('この内容で一括更新', ['class' => 'btn btn-primary']) ?>
        </div>
    <?php ActiveForm::end(); ?>
</div>
