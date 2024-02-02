<?php
/**
 * @var $this \yii\web\View
 * @var $self \app\components\FormPublisher;
 * @var $searchModel \app\models\ScheduleSearch;
 */

use app\models\ScheduleSearch;
use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\widgets\ListView;
use yii\bootstrap5\Html;
use app\widgets\datetimepicker\Datetimepicker;
use app\models\RepaymentPattern;
use kartik\typeahead\Typeahead;
use yii\helpers\Url;

$this->title = '売掛買掛集計（顧客別）';

$style = <<<EOS
.text-red {
    color: #ff0000;
}
.total-row td {
    background-color: #ffffdd !important;
}
table.table td.charge-side {
    background-color: #ffeeee !important;
}
table.table td.debt-side {
    background-color: #eeeeee !important;
}
EOS;
$this->registerCss($style);
$session = Yii::$app->session;
$params = $session['schedule_search_params'];
$searchModel = $self->searchModel;
$dataProvider = $searchModel->search($params, 'credit_debt_collection_by_customer');

$term = empty($searchModel->target_term) ? (new \DateTime())->format('Y年m月') : $searchModel->target_term;
$targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $term));
$span = $searchModel->getTermsFromSpan();
$query = clone $dataProvider->query;

$query->select([
    '`c`.`customer_id`',
    'GROUP_CONCAT(DISTINCT cd.contract_detail_id ORDER BY lc.disp_order DESC) as cdids',
])
    ->groupBy(['c.customer_id']);

$totals = \yii\helpers\ArrayHelper::index($query->asArray()->all(), 'customer_id');
$dataProvider->pagination = [
    'totalCount' => $dataProvider->totalCount,
    'pageSize' => 33
];//ここは暫定
$pageCount = $dataProvider->pagination->pageCount;
for($page = 0; $page < $pageCount; $page++) :
$dataProvider->pagination->page = $page;
$dataProvider->prepare(true); ?>
<div style="padding: 5mm 5mm; font-size: 8.5px;<?php if ($page != $pageCount) : ?>page-break-after: always;<?php endif; ?>">
    <h4 class="text-center">売掛買掛集計（顧客別）</h4>
    <p class="pull-right">Page: <?= $page + 1 ?> / <?= $pageCount ?></p>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>CF</th>
                <th>支払方法</th>
                <th>顧客名
                <th>契約No.</th>
                <th>リース開始</th>
                <th>リース終了</th>
                <th>登録No.</th>
                <th>リース期間</th>
                <th>リース料発生月</th>
                <th>回数</th>
                <th><?= $span['to']->format('Y/m') ?><br/>回数</th>
                <th>リース料</th>
                <th>非課税未収<br/>売掛金</th>
                <th>8%未収<br/>売掛金</th>
                <th>10%未収<br/>売掛金</th>
                <th>非課税<br/>前払い</th>
                <th>8%<br/>前払い</th>
                <th>10%<br/>前払い</th>
                <th>リース会社</th>
                <th><?= $span['to']->format('Y/m') ?><br/>回数</th>
                <th>支払リース料</th>
                <th>非課税未払<br/>買掛金</th>
                <th>8%未払<br/>買掛金</th>
                <th>10%未払<br/>買掛金</th>
                <th>非課税<br/>前払い</th>
                <th>8%<br/>前払い</th>
                <th>10%<br/>前払い</th>
            </tr>
        </thead>
        <tbody>
        <?php
        foreach ($dataProvider->models as $model) {
            echo $this->render('iv-credit-debt-collection-by-customer', ['model' => $model, 'targetTerm' => $targetTerm, 'dataProvider' => $dataProvider, 'totals' => $totals, 'searchModel' => $searchModel, 'span' => $span]);
        }
        ?>
        </tbody>
    </table>
</div>
<?php endfor;