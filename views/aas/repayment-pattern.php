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

$this->title = $model->isNewRecord ? '新規支払条件登録' : '支払条件編集';

$style = <<<EOS
.form-control-color {
    height: 1em !important
}
EOS;
$this->registerCss($style);
?>
<section id="repayment-pattern">
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <h5 class="card-title mb-0"><?= $model->isNewRecord ? '新規支払条件登録' : '支払条件編集' ?></h5>
                    </div>
                </div>
            </div>
            <?php $form = ActiveForm::begin([]) ?>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($model, 'account_transfer_agency_id')->dropDownList(ArrayHelper::map(AccountTransferAgency::find()->where(['removed' => null])->all(), 'account_transfer_agency_id', 'name'), ['prompt' =>'回収先を選択']) ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($model, 'name') ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($model, 'target_month')->dropDownList(RepaymentPattern::$target_months, ['prompt' => '対象月を選択']) ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($model, 'transfer_date', ['template' => '{label}<div class="input-group"><span class="input-group-text">毎月</span>{input}<span class="input-group-text">日</span>{error}</div>', 'inputOptions' => ['class' => 'form-control text-end']]) ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($model, 'repayment_type_id')->dropDownList(\app\models\RepaymentType::getTypes(), ['prompt' => '基本回収区分を選択']) ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($model, 'bg_color')->widget(ColorInput::class, ['options' => ['placeholder' => '色を選択'], 'useNative' => true, 'size' => 'md']) ?>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <label style="display:block;">
                            <?= Html::checkbox('RepaymentPattern[bg_color]', false, ['value' => '', 'class' => 'checkbox']) ?>
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
