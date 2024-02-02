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
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6>CSV一括インポート</h6>
                    </div>
                    <?php $form = ActiveForm::begin([]) ?>
                        <div class="card-body">
                            <?= $form->field($model, 'file_customer')->fileInput() ?>
                            <?= $form->field($model, 'file_contract')->fileInput() ?>
                            <?= $form->field($model, 'file_contract_detail')->fileInput() ?>
                            <?= $form->field($model, 'file_repayment')->fileInput() ?>
                            <?= $form->field($model, 'file_payment')->fileInput() ?>
                        </div>
                        <div class="card-footer">
                            <?= Html::submitButton('送信', ['class' => 'btn btn-primary']) ?>
                        </div>
                    <?php ActiveForm::end() ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6>一括インポート対象レコード</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th>対象レコード</th><th>件数</th>
                            </tr>
                            <tr>
                                <td>顧客情報</td><td class="text-end"><?= number_format($registered_rows['customers'],0) ?>件</td>
                            </tr>
                            <tr>
                                <td>契約情報</td><td class="text-end"><?= number_format($registered_rows['lease_contracts'],0) ?>件</td>
                            </tr>
                            <tr>
                                <td>契約詳細情報</td><td class="text-end"><?= number_format($registered_rows['contract_details'],0) ?>件</td>
                            </tr>
                            <tr>
                                <td>回収情報</td><td class="text-end"><?= number_format($registered_rows['repayments'],0) ?>件</td>
                            </tr>
                            <tr>
                                <td>支払情報</td><td class="text-end"><?= number_format($registered_rows['payments'],0) ?>件</td>
                            </tr>
                        </table>
                    </div>
                    <div class="card-footer">
                        <?= Html::a('一括登録処理実行', ['/import/registered-rows'], ['class' => 'btn btn-primary']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
