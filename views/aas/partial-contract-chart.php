<?php
/**
 * @var $this \yii\web\View;
 * @var $model \app\models\ContractDetail;
 */

use yii\grid\GridView;
use app\models\MonthlyCharge;
use app\models\MonthlyPayment;
use app\models\Repayment;
use yii\widgets\Pjax;

?>
<?php
$query = MonthlyCharge::find()->alias('mc')
    ->where(['mc.contract_detail_id' => $model->contract_detail_id]);
$dataProvider = new \yii\data\ActiveDataProvider([
    'query' => $query,
    'pagination' => false,
])
?>
<?php Pjax::begin([
    'id' => "pjax-grid-{$model->contract_detail_id}-wrapper",
    'options' => [
        'class' => 'contract-grid-wrapper',
    ]
]) ?>
<?php
$type = $model->contract_type == 'ordinary' ? '物件リース' : 'メンテナンスリース';
$layout =<<<EOL
【{$type}】{summary}
<div class="table-wrapper">
{items}
</div>
{pager}
EOL; ?>
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'showFooter' => true,
    'layout' => $layout,
    'columns' => [
        [
            'header' => '回数',
            'class' => \yii\grid\SerialColumn::class,
        ],
        [
            'header' => '回収予定日',
            'attribute' => 'transfer_date',
            'contentOptions' => function($data){
                return ['data-id' => $data->monthly_charge_id, 'class' => 'editable cell-monthly_charge-transfer_date'];
            },
        ],
        /*
        [
            'header' => '回収予定額',
            'attribute' => 'amountWithTax',
            'content' => function($data){
                return number_format($data->getAmountWithTax('amount'), 0);
            },
            'contentOptions' => [
                'class' => 'text-end',
            ],
        ],
        */
        [
            'header' => '回収予定額',
            'contentOptions' => function($data){
                $options = [
                    'data-id' => $data->monthly_charge_id,
                    'class' => 'text-end editable cell-monthly_charge-temporary_charge_amount',
                ];
                $repayment = $data->repayments[0] ?? false;
                if ($repayment && $repayment->repaymentType->bg_color) {
                    $options['style'] = "background-color:{$repayment->repaymentType->bg_color};";
                }
                return $options;
            },
            'content' => function($data){
                $amount = $data->getAmountWithTax('temporary_charge_amount');
                return number_format($amount, 0);
            },
            'footerOptions' => [
                'class' => 'text-end',
            ],
            'footer' => "総額:".number_format(MonthlyCharge::getTotal($dataProvider->models, 'temporaryAmountWithTax'), 0),
        ],
        [
            'header' => '前払',
            'content' => function($data){
                $advanceRepayments = $data->getAdvanceRepayments()->all();
                $repayment = $advanceRepayments[0] ?? ($data->repayments[0] ?? false);
                return $repayment instanceof \app\models\AdvanceRepayment ? '◯' : '';
            },
        ],
        [
            'header' => '回収方法',
            'contentOptions' => function($data){
                $advanceRepayments = $data->getAdvanceRepayments()->all();
                $repayment = $advanceRepayments[0] ?? ($data->repayments[0] ?? false);
                return $repayment ? ($repayment instanceof Repayment ? ['data-id' => $repayment->repayment_id, 'class' => 'deletable editable cell-repayment-repayment_type_id'] : ['data-id' => $repayment->repayment_id]) :
                    ['data-mcid' => $data->monthly_charge_id, 'class' => 'registerable cell-repayment'];
            },
            'content' => function($data){
                $advanceRepayments = $data->getAdvanceRepayments()->all();
                $repayment = $advanceRepayments[0] ?? ($data->repayments[0] ?? false);
                return $repayment ? ($repayment instanceof Repayment ? '<span class="delete-this"></span>' . $repayment->repaymentType->type : '前払リース料'): '';
            },
        ],
        [
            'header' => '回収日',
            'contentOptions' => function($data){
                $advanceRepayments = $data->getAdvanceRepayments()->all();
                $repayment = $advanceRepayments[0] ?? ($data->repayments[0] ?? false);
                return $repayment ? ($repayment instanceof Repayment ? ['data-id' => $repayment->repayment_id, 'class' => 'editable cell-repayment-processed bg-gray'] : ['data-id' => $repayment->repayment_id]) :
                    ['data-mcid' => $data->monthly_charge_id, 'class' => 'registerable cell-repayment'];
            },
            'content' => function($data){
                $advanceRepayments = $data->getAdvanceRepayments()->all();
                $repayment = $advanceRepayments[0] ?? ($data->repayments[0] ?? false);
                return $repayment ? $repayment->processed : '';
            },
        ],
        [
            'header' => '回収額<sub>（税込）</sub>',
            'contentOptions' => function($data){
                $advanceRepayments = $data->getAdvanceRepayments()->all();
                $repayment = $advanceRepayments[0] ?? ($data->repayments[0] ?? false);
                return $repayment ? ($repayment instanceof Repayment ? ['data-id' => $repayment->repayment_id, 'class' => 'editable cell-repayment-repayment_amount bg-gray'] : ['data-id' => $repayment->repayment_id]) :
                    ['data-mcid' => $data->monthly_charge_id, 'class' => 'registerable cell-repayment'];
            },
            'content' => function($data){
                $advanceRepayments = $data->getAdvanceRepayments()->all();
                $repayment = $advanceRepayments[0] ?? ($data->repayments[0] ?? false);
                return $repayment ? number_format((int)$repayment->repayment_amount,0) : '';
            },
            'footerOptions' => [
                'class' => 'text-end',
            ],
            'footer' => "総額:".number_format(\app\models\AdvanceRepayment::getTotal($dataProvider->models, 'repayment_amount') + Repayment::getTotal($dataProvider->models, 'repayment_amount') - Repayment::getTotal($dataProvider->models, 'chargeback_amount'), 0)
        ],
        [
            'header' => '返金額',
            'contentOptions' => function($data){
                $repayment = $data->repayments[0] ?? false;
                return $repayment ? ['data-id' => $repayment->repayment_id, 'class' => 'text-end editable cell-repayment-chargeback_amount'] : [];
            },
            'content' => function($data){
                $repayment = $data->repayments[0] ?? false;
                return $repayment ? number_format((int)$repayment->chargeback_amount,0) : '';
            },
        ],
        [
            'header' => '回収残額',
            'contentOptions' => [
                'class' => 'text-end',
            ],
            'content' => function($data) use($dataProvider){
                $total = 0;
                foreach($dataProvider->models as $model) {
                    if ((new \DateTime($model->term)) <= (new \DateTime($data->term))) {
                        $repayment = $model->repayments[0] ?? false;
                        $total += $repayment ? ($repayment->repayment_amount - $repayment->chargeback_amount) : 0;
                    }
                }
                $remains = MonthlyCharge::getTotal($dataProvider->models, 'amountWithTax') - \app\models\AdvanceRepayment::getTotal($dataProvider->models, 'repayment_amount') - $total;
                return number_format($remains, 0);
            },
            'footerOptions' => [
                'class' => 'text-end',
            ],
            'footer' => "残額:".number_format(MonthlyCharge::getTotal($dataProvider->models, 'amountWithTax') - \app\models\AdvanceRepayment::getTotal($dataProvider->models, 'repayment_amount') - (Repayment::getTotal($dataProvider->models, 'repayment_amount') - Repayment::getTotal($dataProvider->models, 'chargeback_amount')), 0)
        ],
        [
            'header' => '支払月',
            'contentOptions' => function($data){
                $monthlyPayment = $data->monthlyPayment;
                return $monthlyPayment ? [
                    'data-id' => $monthlyPayment->monthly_payment_id,
                    'class' => 'editable cell-monthly_payment-payment_date'
                ] : [];
            },
            'content' => function($data){
                $monthlyPayment = $data->monthlyPayment;
                return $monthlyPayment ? $monthlyPayment->payment_date : '';
            },
            'visible' => $model->monthly_payment > 0,
        ],
        [
            'header' => '支払額<sub>(税込)</sub>',
            'contentOptions' => function($data){
                $monthlyPayment = $data->monthlyPayment;
                return $monthlyPayment ? [
                    'data-id' => $monthlyPayment->monthly_payment_id,
                    'class' => 'text-end editable cell-monthly_payment-payment_amount'
                ] : [];
            },
            'content' => function($data){
                $monthlyPayment = $data->monthlyPayment;
                return $monthlyPayment ? number_format($monthlyPayment->amountWithTax,0) : '';
            },
            'footerOptions' => ['class' => 'text-end'],
            'footer' => "総額:".number_format(MonthlyPayment::getTotal($dataProvider->models, 'amountWithTax'), 0) .
                "<br />支払残額:".number_format(MonthlyPayment::getTotal($dataProvider->models, 'amountWithTax') - \app\models\LeasePayment::getTotal($dataProvider->models, 'payment_amount'), 0),
            'visible' => $model->monthly_payment > 0,
        ],
        /*
        [
            'header' => '支払日',
            'contentOptions' => function($data){
                $payment = $data->payments[0] ?? false;
                return $payment ? ['data-id' => $payment->lease_payment_id, 'class' => 'text-end editable cell-lease_payment-processed'] : [];
            },
            'content' => function($data){
                $payment = $data->payments[0] ?? false;
                return $payment ? $payment->processed : '';
            }
        ],
        [
            'header' => '支払額<sub>（税込）</sub>',
            'contentOptions' => function($data){
                $payment = $data->payments[0] ?? false;
                return $payment ? ['data-id' => $payment->lease_payment_id, 'class' => 'text-end editable cell-lease_payment-payment_amount'] : [];
            },
            'content' => function($data){
                $payment = $data->payments[0] ?? false;
                return $payment ? number_format((int)$payment->payment_amount,0) : '';
            },
            'footerOptions' => [
                'class' => 'text-end',
            ],
            'footer' => "支払残額:".number_format(MonthlyPayment::getTotal($dataProvider->models, 'amountWithTax') - \app\models\LeasePayment::getTotal($dataProvider->models, 'payment_amount'), 0)
        ],
        [
            'header' => '消費税',
            'contentOptions' => [
                'class' => 'text-end',
            ],
            'content' => function($data){
                $payment = $data->payments[0] ?? false;
                if ($payment) {
                    $method = $data->contractDetail->fraction_processing_pattern;
                    $methods = [
                        'floor' => 'CEIL',
                        'ceil' => 'FLOOR',
                        'roundup' => 'ROUND'
                    ];
                    $sql = "SELECT :amount - {$methods[$method]}(:amount / (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE :term >= application_from AND :term <= IFNULL(application_to, '2099-12-31')) END)) FROM tax_application ta WHERE ta.tax_application_id=:id";
                    $value = Yii::$app->db->createCommand($sql)->bindValues([
                        ':amount' => $payment->payment_amount,
                        ':term' => $payment->processed,
                        ':id' => (int)$data->contractDetail->tax_application_id,
                    ])->queryScalar();
                    return number_format($value, 0);
                }
                return '';
            },
        ],
        */
        [
            'header' => '税率',
            'content' => function($data){
                $sql = "SELECT CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE :term >= application_from AND :term <= IFNULL(application_to, '2099-12-31')) END FROM tax_application ta WHERE ta.tax_application_id=:id";
                $value = Yii::$app->db->createCommand($sql)->bindValues([
                    ':term' => $data->monthlyPayment->term,
                    ':id' => (int)$data->contractDetail->tax_application_id,
                ])->queryScalar();
                return (string)($value * 100).'%';
            },
            'visible' => $model->monthly_payment > 0,
        ],
        [
            'header' => 'コメント',
            'contentOptions' => function($data){
                return [
                    'style' => 'min-width: 300px;',
                    'class' => 'editable cell-monthly_charge-memo',
                    'data-id' => $data->monthly_charge_id,
                ];
            },
            'content' => function($data){
                return $data->memo;
            },
        ],
    ],
]) ?>
<?php Pjax::end(); ?>

