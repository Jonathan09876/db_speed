<?php
/**
 * @var $this \yii\web\View
 * @var $model \app\models\TaxApplication;
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = $model->isNewRecord ? '新規アカウント録' : 'アカウント編集';
?>
<section id="tax-application">
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <h5 class="card-title mb-0"><?= $model->isNewRecord ? '新規アカウント登録' : 'アカウント編集' ?></h5>
                    </div>
                </div>
            </div>
            <?php $form = ActiveForm::begin([
            ]) ?>
            <div class="card-body">
                <?= $form->field($model, 'name') ?>
                <?= $form->field($model, 'username') ?>
                <?= $form->field($model, 'password')->passwordInput() ?>
                <?= $form->field($model, 'client_corporation_id', ['inline' => true])->radioList(\app\models\ClientCorporation::getClientCorporations()) ?>
            </div>

            <div class="card-footer text-end">
                <?= Html::submitButton($model->isNewRecord ? 'この内容で登録' : 'この内容で更新', ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end() ?>
        </div>
    </div>
</section>
