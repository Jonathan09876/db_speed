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
.text-end {
    text-align:right;
}
.paid {
    background-color: #e8e8e8!important;
}
.deficient {
    background-color: #ffd4d4!important;
}
EOS;
$this->registerCss($style);

$session = Yii::$app->session;
$params = $session['schedule_search_params'];
$searchModel = $self->searchModel;
$span = $searchModel->getTermsFromSpan();
$dataProvider = $searchModel->search($params, null);
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
$dataProvider->pagination = [
    'totalCount' => $dataProvider->totalCount,
    'pageSize' => 24
];//ここは暫定
$pageCount = $dataProvider->pagination->pageCount;
for($page = 0; $page < $pageCount; $page++) :
$dataProvider->pagination->page = $page;
$dataProvider->prepare(true);
$beginTag = '<div style="padding: 5mm 5mm; font-size: 8.5px;' . ($page != $pageCount ? 'page-break-after: always;' : '') . '">';
$paging = sprintf("%d / %d", $page+1, $pageCount);
$layout = <<<EOL
{$beginTag}
    <h4 class="text-center">回収実績登録一覧</h4>
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

foreach ($dataProvider->models as $model) :
    echo $this->render('iv-collection-schedules-latest', compact("model","targetTerm", "terms", "searchModel", "lastMonth"));
endforeach;

$layout = <<<EOL
            <tr>
                <td colspan="9" class="text-end">回収予定合計</td>
EOL;
foreach($terms as $term) {
    $total = number_format(\app\models\ContractDetail::getTermTotalChargeAmountWithTax($dataProvider, $term), 0);
    $layout .= <<<EOL
                <td></td>
                <td class="text-end">{$total}</td>
EOL;
}
$total = number_format(\app\models\ContractDetail::getTermsDetailsTotalChargeAmountWithTax($dataProvider, $terms), 0);
$wholeTotal = number_format(\app\models\ContractDetail::getWholeTotalChargeAmountWithTax($dataProvider),0);
$layout .= <<<EOL
                <td class="text-end">{$total}</td>
                <td class="text-end">{$wholeTotal}</td>
                <td colspan="3"></td>
            </tr>
            <tr>
                <td colspan="9" class="text-end">回収実績合計</td>
EOL;
foreach($terms as $the_term) {
    $total = number_format(\app\models\ContractDetail::getTermRepaymentTotal($dataProvider, $the_term), 0);
    $layout .= <<<EOL
                    <td></td>
                    <td class="text-end">{$total}</td>
EOL;
}
$total = number_format(\app\models\ContractDetail::getTermsDetailsTotalRepaymentAmountWithTax($dataProvider, $terms), 0);
$wholeTotal = number_format(\app\models\ContractDetail::getWholeTotalRepaymentAmountWithTax($dataProvider),0);
$layout .= <<<EOL
                <td class="text-end">{$total}</td>
                <td class="text-end">{$wholeTotal}</td>
                <td colspan="3"></td>

            </tr>
            <tr>
                <td colspan="9" class="text-end">支払合計</td>
EOL;
foreach($terms as $the_term) {
    $total = number_format(\app\models\ContractDetail::getTermTotalPaymentAmountWithTax($searchModel, $dataProvider, $the_term), 0);
    $layout .= <<<EOL
                    <td></td>
                    <td class="text-end">{$total}</td>
EOL;
}
$total = number_format(\app\models\ContractDetail::getTermsDetailsTotalPaymentAmountWithTax($dataProvider, $terms), 0);
$wholeTotal = number_format(\app\models\ContractDetail::getWholeTotalPaymentAmountWithTax($searchModel, $dataProvider),0);
$layout .= <<<EOL
                <td class="text-end">{$total}</td>
                <td class="text-end">{$wholeTotal}</td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>
</div>
EOL;
echo $layout;
endfor; ?>