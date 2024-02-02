<?php
/**
 * Created by PhpStorm.
 * User: decama
 * Date: 2018/07/31
 * Time: 17:20
 *
 * @var $self   \app\components\FormPublisher
 * @var $model  \app\models\Client
 */

use app\components\FormPublisher;

$rows = $model->getInboundTotals($self->term);
$var_a = array_sum(array_column($rows, 'transaction_total'));
$var_b = array_sum(array_column($rows, 'reward'));
$var_c = floor($var_b * TAX_RATE);
$var_1 = $var_b + $var_c;
$var_2 = $model->getExpenseTotal($self->term);
$total = $var_a - $var_1 - $var_2;

?>
<div style="padding: 15mm 10mm; font-size: 16px;">
<div class="row">
    <div class="col-xs-12">
        <h3 class="text-center"><?= FormPublisher::$templates[$self->template]['title'][$total > 0 ? 0 : 1] ?></h3>
        <p class="text-right"><?= (new \DateTime($self->publish_date))->format('Y年n月j日') ?></p>
    </div>
</div>
<div class="row">
    <div class="col-xs-4">
        <p>貴社コード：　<?= $model->cdb_code ?></p>
        <h4 style="border-bottom: 1px solid" class="text-center"><?= $model->corporation->name ?>&emsp;様</h4>
        <p>対象期間：<?= $self->getTermText() ?></p>
        <p>&nbsp;</p>
        <p>弊所をご利用頂きまして、誠にありがとうございました。<br />下記のとおりご報告、ご請求申し上げます。<br />ご不明点等ございましたらお気軽にお問合せ下さい。</p>
    </div>
    <div class="col-xs-4 col-xs-offset-4">
        <p>&nbsp;</p>
        <p>弁護士法人コモンズ法律事務所<br />〒102-0083<br />東京都千代田区麹町4-2-2 麹町陸ビル3階<br />TEL:03-6261-4577&emsp;&emsp;FAX:03-6261-4566</p>
    </div>
</div>
<div class="row" style="margin-top:2em;">
    <div class="col-xs-10">
        <table class="table table-bordered">
            <tr>
                <th width="8%" class="text-center" style="font-size:12px;vertical-align: middle">貴社コード</th>
                <th width="12%" class="text-center">報酬率</th>
                <th width="20%" class="text-center">当事務所入金</th>
                <th width="20%" class="text-center">クライアント様入金額</th>
                <th width="20%" class="text-center">入金合計額</th>
                <th width="20%" class="text-center">報酬額</th>
            </tr>
            <?php for ($num = 0; $num < 5; $num++ ) : ?>
            <tr>
                <td class="text-center"><?= isset($rows[$num]) ? $rows[$num]['cdb_code'] : '&nbsp;' ?></td>
                <td class="text-center"><?= isset($rows[$num]) ? number_format($rows[$num]['rate'], 1) . '%' : '&nbsp;' ?></td>
                <td class="text-right"><?= isset($rows[$num]) ? '&yen;' . number_format($rows[$num]['transaction_total'], 0) : '&nbsp;' ?></td>
                <td class="text-right"><?= isset($rows[$num]) ? '&yen;' . number_format($rows[$num]['indirect_total'], 0) : '&nbsp;' ?></td>
                <td class="text-right"><?= isset($rows[$num]) ? '&yen;' . number_format($rows[$num]['repayment_total'], 0) : '&nbsp;' ?></td>
                <td class="text-right"><?= isset($rows[$num]) ? '&yen;' . number_format($rows[$num]['reward'], 0) : '&nbsp;' ?></td>
            </tr>
            <?php endfor; ?>
            <tr style="border-top-style:double;">
                <td colspan="2" class="text-center">合計</td>
                <td class="text-right"><span class="pull-left">(A)</span>&yen;<?php echo number_format($var_a, 0) ?></td>
                <td class="text-right">&yen;<?= number_format(array_sum(array_column($rows, 'indirect_total')), 0) ?></td>
                <td class="text-right">&yen;<?= number_format(array_sum(array_column($rows, 'repayment_total')), 0) ?></td>
                <td class="text-right"><span class="pull-left">(B)</span>&yen;<?php  echo number_format($var_b, 0) ?></td>
            </tr>
            <tr>
                <td colspan="5">
                    <div class="col-xs-8 text-right">消費税</div>
                    <div class="col-xs-4">(B)×<?= TAX_RATE * 100 ?>%</div>
                </td>
                <td class="text-right"><span class="pull-left">(C)</span>&yen;<?php  echo number_format($var_c, 0) ?></td>
            </tr>
            <tr>
                <td colspan="5">
                    <div class="col-xs-8 text-right">報酬額合計</div>
                    <div class="col-xs-4">(B)+(C)</div>
                </td>
                <td class="text-right"><span class="pull-left">①</span>&yen;<?php echo number_format($var_1, 0) ?></td>
            </tr>
            <tr>
                <td colspan="5">
                    <div class="col-xs-8 text-right">他精算金額</div>
                    <div class="col-xs-4" style="font-size:87.5%;">※別紙明細書をご参照下さい。</div>
                </td>
                <td class="text-right"><span class="pull-left">②</span>&yen;<?php  echo number_format($var_2, 0) ?></td>
            </tr>
            <tr style="border: 2px solid;">
                <td colspan="5">
                    <div class="col-xs-8 text-right">ご精算金額</div>
                    <div class="col-xs-4">(A)-①-②<span class="pull-right"><?= $total > 0 ? 'ご送金額' : 'ご請求金額' ?></span></div>
                </td>
                <td class="text-right">&yen;<?= number_format(abs($total), 0) ?></td>
            </tr>
        </table>
        <?php if ($total > 0) : //精算書 ?>
        <div style="padding: 1.5em 3em 0">
            <p>ご指定口座に送金させて頂きます。<br />送金予定日：当月15日以内<br /><?= $model->transaction_fee_charge ? '※振込手数料はお客様負担とさせて頂きます。' : '' ?></p>
        </div>
        <?php else : //請求書 ?>
            <div style="padding: 0 3em">
                <p>右記期日までにお振込み願います。　期日：当月15日<br />振込先口座</p>
                <div style="display: inline-block; padding: 1em 1.5em; border: 1px solid">
                    <?php $ba = $model->targetBankAccount; echo "{$ba->bank_name}銀行　{$ba->branch_name}支店"?><br />
                    <?php echo $ba->account_type == 1 ? '普通' : '当座'; ?>　<?= $ba->account_number ?><br />
                    <?= $ba->account_name ?>
                </div>
                <p><?= $model->transaction_fee_charge ? '※振込手数料はお客様ご負担にてお願い致します。' : '' ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>