<?php
/**
 * @var $this \yii\web\View;
 * @var $model \app\models\MonthlyCharges;
 */

use app\widgets\ActiveForm;
use yii\bootstrap5\Html;
use app\widgets\datetimepicker\Datetimepicker;

$script = <<<EOS
$(document)
    .on('keydown', '#monthlycharges-charge_amount', function(){
        var that = this;
        setTimeout(function(){
            $(that).format();
        }, 150);
    })
EOS;
$this->registerJs($script);
if (count($model->contract_detail_ids) > 0) :
?>
<div class="card">
    <?php $form = ActiveForm::begin([
        'id' => 'monthlycharges-bulkupdate-form'
    ]); ?>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'term_from')->widget(DateTimePicker::class, [
                        'id' => 'monthlycharges-term_from',
                        'clientOptions' => [
                            'locale' => 'ja',
                            'format' => 'YYYY年M月',
                            'viewMode' => 'months',
                        ]
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'term_to')->widget(DateTimePicker::class, [
                        'id' => 'monthlycharges-term_to',
                        'clientOptions' => [
                            'locale' => 'ja',
                            'format' => 'YYYY年M月',
                            'viewMode' => 'months',
                        ]
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'repayment_type_id')->dropDownList(\app\models\RepaymentType::getTypes(), ['prompt' => '回収区分を選択']) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'charge_amount', ['template' => '{label}<div class="input-group">{input}<span class="input-group-text">円</span>{error}</div>', 'inputOptions' => ['class' => 'form-control text-end']]) ?>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <?= Html::submitButton('この内容で一括更新', ['class' => 'btn btn-primary']) ?>
        </div>
    <?php ActiveForm::end(); ?>
</div>
<?php else : ?>
<div class="card">
    <div class="card-body">
        <p>更新対象の契約が未設定です。</p>
    </div>
</div>
<?php endif; ?>
