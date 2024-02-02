<?php
/**
 * @var $this \yii\web\View;
 * @var $model \app\models\Repayment;
 * @var $monthlyCharge \app\models\MonthlyCharge;
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$model->contract_detail_id = $monthlyCharge->contract_detail_id;
?>
<div class="card" style="max-width: 800px;">
    <div class="card-body">
        <?php $form = ActiveForm::begin([
            'id' => 'register-repayment-form',
            'action' => '/register/repayment',
        ]) ?>
        <div class="hstack gap-2">
            <?= Html::activeHiddenInput($model, 'contract_detail_id') ?>
            <label class="form-label col-auto">回収方法</label>
            <div class="col-2">
                <?= Html::activeDropDownList($model, 'repayment_type_id', \app\models\RepaymentType::getTypes(), [
                    'class' => 'form-control form-select'
                ]) ?>
            </div>
            <label class="form-label col-auto">回収額<sub>(税込)</sub></label>
            <div class="col-2">
                <div class="input-group">
                    <?= Html::activeTextInput($model, 'repayment_amount', [
                        'class' => 'form-control formatted text-end'
                    ]) ?>
                    <span class="input-group-text">円</span>
                </div>
            </div>
            <label class="form-label col-auto">回収日</sub></label>
            <div class="col-2">
                <div class="input-group">
                    <input type="date" class="form-control datepicker-input dateupdate" name="date" min="2000-01-01"></br></input>
                </div>
            </div>
            <?= Html::button('登録', ['class' => 'btn btn-sm btn-submit btn-primary']) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
