<?php
/**
 * @var $this \yii\web\View
 * @var $self \app\components\FormPublisher;
 * @var $searchModel \app\models\ScheduleSearch;
 */

use yii\widgets\ListView;

$style = <<<EOS
table.table th,
table.table td {
    border-left-width: 0;
    padding: 2px !important;
}
table.table th:nth-of-type(3) {
    min-width: 10em;
}
table.table th:nth-of-type(7) {
    min-width: 8em;
}
table.table td:nth-of-type(3) {
    white-space: inherit;
}
table.table th:nth-of-type(7),
table.table td:nth-of-type(7) {
    white-space: inherit;
}
table.table th.current-term {
    background-color: var(--vz-indigo);
}
.table-bordered,
.table-bordered>tbody>tr>td,
.table-bordered>tfoot>tr>td,
.table-bordered>thead>tr>td {
    border: 1px solid #ccc;
    border-left-color: #666;
    border-right-color: #666;
}
.border-bottom {
    border-bottom: 1px solid #000 !important;
}
.text-end {
    text-align:right;
}
.paid {
    background-color: #e8e8e8!important;
}
.deficient {
    background-color: #ffd4d4!important;
}
.payment-cell {
    background-color: #ffffe0!important;
}
.customer-last-contract .border-bottom {
    border-bottom-width: 3px!important;
}
EOS;
$this->registerCss($style);

$session = Yii::$app->session;
$params = $session['schedule_search_params'];
$searchModel = $self->searchModel;
$dataProvider = $searchModel->search($params, null);

$query = clone $dataProvider->query;
$query->select([
    '`c`.`customer_id`',
    'GROUP_CONCAT(DISTINCT lc.lease_contract_id ORDER BY lc.disp_order ASC) AS lcids',
])
    ->groupBy(['c.customer_id']);
$customers = \yii\helpers\ArrayHelper::index($query->asArray()->all(), 'customer_id');

$span = $searchModel->getTermsFromSpan();
$targetTerm = new \DateTime($searchModel->target_term_year . date('-m-01'));
$current = $span['from'];
$terms = [];
for ($i = 0; $i < 12; $i++) {
    $interval = $targetTerm->diff($current);
    $term = \app\models\Term::findOne(['term' => $current->format('Y-m-d')]);
    $term->relative_month = -((int)$targetTerm->format('n') - ((int)$current->format('n') - ((int)$targetTerm->format('Y') - (int)$current->format('Y')) * 12));
    $terms[] = $term;
    $current = $current->modify('+1 month');
}
$rows = [];
$offsets = [];
foreach((clone $dataProvider->query)->each() as $detail) {
    if ($searchModel->hide_collection || $detail->monthly_charge == 0) {
        $row = 1;
    }
    elseif ($searchModel->hide_payment || $detail->monthly_payment == 0) {
        $row = 2;
    }
    else {
        $row = 3;
    }
    $rows[] = $row;
}
$maxRows = 72; // 1ページあたりの行数上限（暫定）
$totalRows = 0;
for($i = 0; $i < count($rows); $i++) {
    $totalRows += $rows[$i];
    if ($totalRows >= $maxRows) {
        $offsets[] = $i - 1;
        $totalRows = $rows[$i];
    }
}
$pageCount = count($offsets) + 1;
for($page = 0; $page < $pageCount; $page++) :
$beginTag = '<div style="padding: 5mm 5mm; font-size: 8.5px;' . ($page != $pageCount ? 'page-break-after: always;' : '') . '">';
$paging = sprintf("%d / %d", $page+1, $pageCount);
$layout = <<<EOL
{$beginTag}
    <h4 class="text-center">回収予定表:{$searchModel->target_term_year}年度{$searchModel->clientCorporation->shorten_name}</h4>
    <p class="pull-right">Page: {$paging}</p>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>CF</th>
                <th>支払方法</th>
                <th>顧客名
                <th>契約No.</th>
                <th>税率</th>
                <th>リース開始<br>リース終了</th>
                <th>登録No.</th>
                <th>リース期間<br/>会社</th>
                <th style="width:3em;">収支</th>
EOL;
$current = (new \DateTime())->setDate(date('Y'), date('n'), 1);
$lastMonth = $current->modify('-1 month');
foreach($terms as $term) {
    $termText = $term->termDateTime->format('Y/m');
    if ($term->termDateTime < $lastMonth || \app\models\TargetTermMonthlyChargeStored::isMonthClosed($term->term, $searchModel->client_corporation_id) ) {
        $layout .= <<<EOL
                <th>回数</th>
                <th>{$termText}</th>
EOL;
    }
    else {
        $layout .= <<<EOL
                <th>回数</th>
                <th>{$termText}</th>
EOL;
    }
}
$layout .= <<<EOL
                <th>今期合計</th>
                <th>リース開始年月<br />債務回収数</th>
                <th>前払回数</th>
                <th>前払リース料</th>
                <th>特記事項</th>
            </tr>
        </thead>
        <tbody>
EOL;
echo $layout;

$query = $dataProvider->query;
$query->offset = $page == 0 ? 0 : $offsets[$page - 1];
$query->limit = $page == 0 ? $offsets[$page] : (isset($offsets[$page]) ? $offsets[$page] - $offsets[$page - 1] : count($rows) - $offsets[$page - 1]);
//echo "page:{$page}, offset:{$query->offset}, limit:{$query->limit}<br/>";
foreach ($query->each() as $model) :
    echo $this->render('iv-collection-schedules-latest', compact("model","targetTerm", "terms", "searchModel", "lastMonth", "customers"));
endforeach;

if ($page == count($offsets)) :
$layout = <<<EOL
            <tr>
                <td colspan="9" class="text-end">回収予定合計</td>
EOL;
foreach($terms as $term) {
    $total = number_format(\app\models\ContractDetail::getTermTotalChargeAmountWithTax($dataProvider, $term, true), 0);
    $layout .= <<<EOL
                <td></td>
                <td class="text-end">{$total}</td>
EOL;
}
$total = number_format(\app\models\ContractDetail::getTermsDetailsTotalChargeAmountWithTax($dataProvider, $terms, true), 0);
$wholeTotal = number_format(\app\models\ContractDetail::getWholeTotalChargeAmountWithTax($dataProvider, true),0);
$layout .= <<<EOL
                <td class="text-end">{$total}</td>
                <td class="text-end">{$wholeTotal}</td>
                <td colspan="3"></td>
            </tr>
            <tr>
                <td colspan="9" class="text-end">回収実績合計</td>
EOL;
foreach($terms as $the_term) {
    $total = number_format(\app\models\ContractDetail::getTermRepaymentTotal($dataProvider, $the_term, true), 0);
    $layout .= <<<EOL
                    <td></td>
                    <td class="text-end">{$total}</td>
EOL;
}
$total = number_format(\app\models\ContractDetail::getTermsDetailsTotalRepaymentAmountWithTax($dataProvider, $terms, true), 0);
$wholeTotal = number_format(\app\models\ContractDetail::getWholeTotalRepaymentAmountWithTax($dataProvider, true),0);
$layout .= <<<EOL
                <td class="text-end">{$total}</td>
                <td class="text-end">{$wholeTotal}</td>
                <td colspan="3"></td>

            </tr>
            <tr>
                <td colspan="9" class="text-end">支払合計</td>
EOL;
foreach($terms as $the_term) {
    $total = number_format(\app\models\ContractDetail::getTermTotalPaymentAmountWithTax($searchModel, $dataProvider, $the_term, true), 0);
    $layout .= <<<EOL
                    <td></td>
                    <td class="text-end">{$total}</td>
EOL;
}
$total = number_format(\app\models\ContractDetail::getTermsDetailsTotalPaymentAmountWithTax($dataProvider, $terms, true), 0);
$wholeTotal = number_format(\app\models\ContractDetail::getWholeTotalPaymentAmountWithTax($searchModel, $dataProvider, true),0);
$layout .= <<<EOL
                <td class="text-end">{$total}</td>
                <td class="text-end">{$wholeTotal}</td>
                <td colspan="3"></td>
            </tr>
EOL;
else :
    $layout = '';
endif;
$layout .= <<<EOL
        </tbody>
    </table>
</div>
EOL;

echo $layout;
endfor; ?>