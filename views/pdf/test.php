<?php
/**
 * Created by PhpStorm.
 * User: decama
 * Date: 2017/06/17
 * Time: 22:37
 */
/* @var $this \yii\web\View */
/* @var $context \yii2tech\html2pdf\Template */
/* @var $user \app\models\User */
$this->context->layout = 'layouts/main';
?>
<div class="wrapper">
    <h1>Invoice</h1>
    <p>日本語テキストも問題なく変換出来てます。</p>
    <table class="table table-bordered">
        <tr>
            <th>見出し</th>
            <td>内容</td>
            <th>見出し</th>
            <td>内容</td>
        </tr>
    </table>
</div>
