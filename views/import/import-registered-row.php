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
                <h3>一括インポート完了しました。</h3>
                <div class="card">
                    <div class="card-header">
                        <h6>一括インポート登録内容</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th>対象レコード</th><th>件数</th>
                            </tr>
                            <tr>
                                <td>顧客情報</td><td class="text-end"><?= number_format($registered['customer'],0) ?>件</td>
                            </tr>
                            <tr>
                                <td>契約情報</td><td class="text-end"><?= number_format($registered['lease_contract'],0) ?>件</td>
                            </tr>
                            <tr>
                                <td>契約詳細情報</td><td class="text-end"><?= number_format($registered['contract_detail'],0) ?>件</td>
                            </tr>
                            <tr>
                                <td>回収情報</td><td class="text-end"><?= number_format($registered['repayment'],0) ?>件</td>
                            </tr>
                            <tr>
                                <td>支払情報</td><td class="text-end"><?= number_format($registered['payment'],0) ?>件</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
