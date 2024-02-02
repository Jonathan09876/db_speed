<?php
/**
 * @var $this \yii\web\View
 * @var $model \app\models\LeaseContract
 */

use yii\bootstrap5\Html;
use app\widgets\ActiveForm;

?>
<?php $form = ActiveForm::begin([
    'id' => 'update-lease-contract-memo-form'
]); ?>
<div style="width:400px;">
    <?= Html::activeTextarea($model, 'memo', ['id' => "lease-contract-memo-{$model->lease_contract_id}",'class' => 'form-control']) ?>
    <div class="text-end">
        <?= Html::button('更新', ['class' => 'btn btn-sm btn-primary btn-update-memo']) ?>
    </div>
</div>
<?php ActiveForm::end(); ?>
