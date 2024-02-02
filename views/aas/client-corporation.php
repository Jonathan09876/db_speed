<?php
/**
 * @var $this \yii\web\View
 * @var $model \app\models\ClientCorporation;
 */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\ArrayHelper;
use app\models\RepaymentPattern;
use app\models\AccountTransferAgency;
use app\models\ClientCorporation;

$this->title = $model->isNewRecord ? '新規会社登録' : '会社編集';
?>
<section id="repayment-pattern">
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <h5 class="card-title mb-0"><?= $model->isNewRecord ? '新規会社登録' : '会社編集' ?></h5>
                    </div>
                </div>
            </div>
            <?php $form = ActiveForm::begin([]) ?>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <?= $form->field($model, 'code') ?>
                    </div>
                    <div class="col-md-8">
                        <?= $form->field($model, 'name') ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'shorten_name') ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'account_closing_month')->dropDownList(\app\models\ClientCorporation::$months, ['prompt' => '決算月を選択']) ?>
                    </div>
                </div>
                <div class="row">
                    <?php $clientCorporations = ClientCorporation::getClientCorporations();
                    $clientCorporationChildren = ArrayHelper::getColumn($model->clientCorporationChildren, 'client_corporation_id'); ?>
                    <?= $form->field($model, 'client_corporation_children', ['inline' => true])->checkboxList($clientCorporations, [
                        'item' => function($index, $label, $name, $checked, $value) use($model, $clientCorporations, $clientCorporationChildren){
                            $options = [
                                'label' => $label,
                                'value' => $value,
                                'id' => Html::getInputId($model, 'client_corporation_children') . "-{$index}",
                            ];
                            $wrapperOptions = ['class' => ['widget' => 'form-check form-check-inline']];
                            $checked = in_array($value, $clientCorporationChildren);
                            if ($model->client_corporation_id == $value) {
                                $checked = true;
                                $options['style'] = "pointer-events:none;";
                                $options['labelOptions'] = ['style' => "pointer-events:none;"];
                             }

                            $html = Html::beginTag('div', $wrapperOptions) . "\n" .
                                Html::checkbox($name, $checked, $options) . "\n";
                            if (count($clientCorporations) === $index - 1) {
                                $html .= Html::error($model, 'client_corporation_children') . "\n";
                            }
                            $html .= Html::endTag('div') . "\n";

                            return $html;
                        }
                    ]) ?>
                </div>
            </div>
            <div class="card-footer text-end">
                <?= Html::submitButton('この内容で' . ($model->isNewRecord ? '登録' : '更新'), ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end() ?>
        </div>
    </div>
</section>
