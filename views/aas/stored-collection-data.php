<?php
/**
 * @var $this \yii\web\View
 * @var $searchModel \app\models\MonthlyChargeSearch;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

use app\models\MonthlyChargeSearch;
use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\widgets\ListView;
use yii\bootstrap5\Html;
use app\widgets\datetimepicker\Datetimepicker;
use app\models\RepaymentPattern;
use kartik\typeahead\Typeahead;
use yii\helpers\Url;
use app\components\PrivilegeManager;

$this->title = '保存回収データ検索';

$style = <<<EOS
.col-first {
    -webkit-box-flex: 0;
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    width: 110px;
}
.table-wrapper .table thead tr {
    position: sticky;
    top: 0;
    z-index:2;
}
EOS;
$this->registerCss($style);

?>
<section id="customers">
        <div class="row mb-2">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center g-3">
                            <div class="col-md-3">
                                <h5 class="card-title mb-0">保存回収データ検索</h5>
                            </div>
                        </div>
                    </div>
                    <?php $form = ActiveForm::begin([
                        'layout' => 'horizontal',
                    ]) ?>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <?php $children = Yii::$app->user->identity->clientCorporation->clientCorporationChildren; ?>
                                <?= $form->field($searchModel, 'client_corporation_id', ['inline' => true, 'horizontalCssClasses' => [
                                    'label' => 'form-label col-first',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-auto',
                                ],])->radioList(\yii\helpers\ArrayHelper::map($children, 'client_corporation_id', 'shorten_name')) ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <?= $form->field($searchModel, 'repayment_pattern_id', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-first',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-auto',
                                ],])->dropDownList(RepaymentPattern::getPatterns(), ['prompt' => '回収条件を選択']) ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <?= Html::submitButton('引落データ検索', ['class' => 'btn btn-primary']) ?>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <h5 class="card-title mb-0">回収データ一覧</h5>
                    </div>
                </div>
            </div>
            <?php \yii\widgets\Pjax::begin([
                'id' => 'pjax-grid-wrapper',
                'options' => [
                    'class' => 'card-body'
                ],
                'linkSelector' => '.pagination a',
                'timeout' => 3000,
            ]); ?>
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'rowOptions' => function($data){
                        if ($data->is_closed) {
                            return ['style' => 'background-color:#f0f0f0'];
                        }
                    },
                    'layout' => "{summary}\n<div class=\"table-wrapper\">\n{items}\n</div>\n{pager}",
                    'columns' => [
                        [
                            'attribute' => 'target_term',
                            'format' => ['date', 'php:Y年n月'],
                        ],
                        [
                            'header' => $dataProvider->sort->link('clientCorporation.name'),
                            'attribute' => 'clientCorporation.name'
                        ],
                        [
                            'header' => $dataProvider->sort->link('repaymentPattern.name'),
                            'attribute' => 'repaymentPattern.name'
                        ],
                        [
                            'header' => '口座振替<br />回収予定額',
                            'contentOptions' => [
                                'class' => 'text-end'
                            ],
                            'content' => function($data){
                                $monthlyCharges = $data->monthlyCharges;
                                $total = array_sum(array_map(function($mc){return $mc->repaymentType->repayment_type_id == 1 ? $mc->temporaryAmountWithTax : 0;}, $monthlyCharges));
                                return number_format($total, 0).'円';
                            },
                        ],
                        [
                            'header' => '口座振替<br />回収実績額',
                            'contentOptions' => [
                                'class' => 'text-end'
                            ],
                            'content' => function($data){
                                $monthlyCharges = $data->monthlyCharges;
                                $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []);
                                $total = array_sum(array_map(function($rp){return $rp->repayment_type_id == 1 ? $rp->repayment_amount : 0;}, $repayments));
                                return number_format($total, 0).'円';
                            },
                        ],
                        [
                            'header' => '<span style="color:#fcc!important;">口座振替<br />未回収額</span>',
                            'contentOptions' => [
                                'class' => 'text-end text-danger'
                            ],
                            'content' => function($data){
                                $monthlyCharges = $data->monthlyCharges;
                                $total = array_sum(array_map(function($mc){return $mc->repaymentType->repayment_type_id == 1 ? $mc->temporaryAmountWithTax : 0;}, $monthlyCharges));
                                $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []);
                                $furikomiTotal = array_sum(array_map(function($rp){return $rp->repayment_type_id == 1 ? $rp->repayment_amount : 0;}, $repayments));
                                return number_format($total - $furikomiTotal, 0).'円';
                            },
                        ],
                        [
                            'header' => 'その他<br />回収予定額',
                            'contentOptions' => [
                                'class' => 'text-end'
                            ],
                            'content' => function($data){
                                $monthlyCharges = $data->monthlyCharges;
                                $total = array_sum(array_map(function($mc){return $mc->repaymentType->repayment_type_id != 1 ? $mc->temporaryAmountWithTax : 0;}, $monthlyCharges));
                                return number_format($total, 0).'円';
                            },
                        ],
                        [
                            'header' => 'その他<br />回収実績額',
                            'contentOptions' => [
                                'class' => 'text-end'
                            ],
                            'content' => function($data){
                                $monthlyCharges = $data->monthlyCharges;
                                $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []);
                                $total = array_sum(array_map(function($rp){return $rp->repayment_type_id != 1 ? $rp->repayment_amount : 0;}, $repayments));
                                return number_format($total, 0).'円';
                            },
                        ],
                        [
                            'header' => '件数',
                            'contentOptions' => [
                                'class' => 'text-end'
                            ],
                            'content' => function($data){
                                return $data->getMonthlyCharges()->count().'件';
                            },
                        ],
                        [
                            'header' => '実績未登録件数',
                            'contentOptions' => [
                                'class' => 'text-end'
                            ],
                            'content' => function($data){
                                $query = $data->getMonthlyCharges()->alias('mc')
                                    ->leftJoin('repayment r', 'mc.monthly_charge_id=r.monthly_charge_id')
                                    ->leftJoin('debt d', 'mc.monthly_charge_id=d.monthly_charge_id')
                                    ->where(['r.repayment_id' => null, 'd.debt_id' => null]);
                                return $query->count().'件';
                            },
                        ],
                        [
                            'header' => '締め日時',
                            'content' => function($data){
                                return $data->is_closed ? (new DateTime($data->closed_at))->format('Y-m-d H:i') : '';
                            }
                        ],
                        [
                            'content' => function($data, $key){
                                return Html::a('実績登録', ['/aas/register-stored-collection-data', 'id' => $key], ['class' => 'btn btn-sm btn-info me-1' . ($data->is_closed ? (PrivilegeManager::hasPrivilege('aas/register-stored-collection-data') ? '' : ' disabled') : '')]) .
                                    Html::a('CSVエクスポート', ['/aas/export-stored-collection-data', 'id' => $key], ['class' => 'btn btn-sm btn-success me-1' . ($data->is_closed ? ' disabled' : '')]) .
                                    (PrivilegeManager::hasPrivilege('aas/remove-stored-collection-data') ?
                                        Html::a('保存回収データ削除', ['/aas/remove-stored-collection-data', 'id' => $key], ['data-confirm' => 'この回収データを削除します。よろしいですか？', 'class' => 'btn btn-sm btn-danger me-1' . ($data->is_closed ? ' disabled' : '')]) : '') .
                                    (PrivilegeManager::hasPrivilege('aas/close-stored-collection-data') ?
                                        Html::a('実績登録締め', ['/aas/close-stored-collection-data', 'id' => $key], ['class' => 'btn btn-sm btn-secondary me-1' . ($data->is_closed ? ' disabled' : '')]) : '') .
                                    ($data->is_closed && PrivilegeManager::hasPrivilege('aas/unclose-stored-collection-data') ?
                                        Html::a('実績登録締め解除', ['/aas/unclose-stored-collection-data', 'id' => $key], ['class' => 'btn btn-sm btn-primary me-1']) : '');
                            }
                        ],
                    ],
                ]) ?>
            <?php \yii\widgets\Pjax::end(); ?>
        </div>
</section>