<?php
/**
 * @var $this \yii\web\View
 * @var $customer \app\models\Customer
 */

?>
<div class="card">
    <div class="card-header">
        <h2>一括入金登録</h2>
    </div>
    <div class="card-body">
        <h4>[CF: <?= $customer->customer_code ?>] [支払方法: <?= $customer->clientContract->repaymentPattern->name ?>] [顧客名: <?= $customer->name ?>]</h4>
        <p>一括入金登録可能な回収予定がありません。</p>
    </div>
</div>
