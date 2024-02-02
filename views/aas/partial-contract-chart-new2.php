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
use app\models\TermTicker;
use yii\data\ArrayDataProvider;
use app\widgets\modal\src\ModalAjax;

// if ($model->registration_status == \app\models\ContractDetail::STATUS_COMPLETE) :

$query = MonthlyCharge::find()->alias('mc')
    ->where(['mc.contract_detail_id' => $model->contract_detail_id]);
$firstChargeTerm = new \DateTime((clone $query)->orderBy(['monthly_charge_id' => SORT_ASC])->limit(1)->one()->term);
$lastChargeTerm = new \DateTime((clone $query)->orderBy(['monthly_charge_id' => SORT_DESC])->limit(1)->one()->term);
$query = MonthlyPayment::find()->alias('mp')
    ->where(['mp.contract_detail_id' => $model->contract_detail_id]);
$firstPaymentTerm = new \DateTime((clone $query)->orderBy(['monthly_payment_id' => SORT_ASC])->limit(1)->one()->term);
$lastPaymentTerm = new \DateTime((clone $query)->orderBy(['monthly_payment_id' => SORT_DESC])->limit(1)->one()->term);
$incrementChargeTerm = (clone $lastChargeTerm)->modify('+1 month');
$transfer_date = $model->leaseContract->customer->clientContract->repaymentPattern->transfer_date;
$format = $transfer_date == 31 ? 'Y-m-t' : "Y-m-{$transfer_date}";
$incrementTransferDate = $incrementChargeTerm->format($format);
if ($model->monthly_charge == 0) {
    $term = clone $firstPaymentTerm;
    $lastTerm = clone $lastPaymentTerm;
}
else if ($model->monthly_payment == 0) {
    $term = clone $firstChargeTerm;
    $lastTerm = clone $lastChargeTerm;
}
else {
    $term = clone min($firstChargeTerm, $firstPaymentTerm);
    $lastTerm = clone max($lastChargeTerm, $lastPaymentTerm);
}
$terms = [];
do {
    $monthlyCharges = MonthlyCharge::getSiblings($model->contract_detail_id, $term->format('Ym'), 0);
    $monthlyPayments = MonthlyPayment::getSiblings($model->contract_detail_id, $term->format('Ym'), 0);
    $length = max(count($monthlyCharges), count($monthlyPayments));
    if ($length > 0) {
        for($i = 0; $i < $length; $i++) {
            $terms[] = new TermTicker([
                'contract_detail_id' => $model->contract_detail_id,
                'monthly_charge_id' => isset($monthlyCharges[$i]) ? $monthlyCharges[$i]->monthly_charge_id : null,
                'monthly_payment_id' => isset($monthlyPayments[$i]) ? $monthlyPayments[$i]->monthly_payment_id : null,
                'term' => clone $term,
            ]);
        }
    }
    else {
        $terms[] = new TermTicker([
            'contract_detail_id' => $model->contract_detail_id,
            'term' => clone $term,
        ]);
    }
    $term->modify('+1 month');
} while($term <= $lastTerm);
$dataProvider = new ArrayDataProvider([
    'allModels' => $terms,
    'pagination' => false,
]);
?>
<?php Pjax::begin([
    'id' => "pjax-grid-{$model->contract_detail_id}-wrapper",
    'options' => [
        'class' => 'contract-grid-wrapper',
    ],
]) ?>
<?php
$type = $model->contract_type == 'ordinary' ? '物件リース' : 'メンテナンスリース';
$button = ModalAjax::widget([
    'id' => "modal-bulk-update-form-{$model->contract_detail_id}",
    'title' => '期間指定一括更新:回収',
    'toggleButton' => [
        'label' => '期間指定一括更新:回収',
        'class' => 'btn btn-outline-info btn-sm me-2',
    ],
    'url' => ['/update/bulk', 'cdid' => $model->contract_detail_id],
    'ajaxSubmit' => true,
    'autoClose' => true,
    'pjaxContainer' => "#pjax-grid-{$model->contract_detail_id}-wrapper",
    'events' => [
        ModalAjax::EVENT_BEFORE_SUBMIT => new \yii\web\JsExpression("
            function(event, data, status, xhr, selector) {
                $('#modal-bulk-update-form-{$model->contract_detail_id} .modal-body').html('<div class=\"modal-ajax-loader\"></div>')
            }
        "),
    ],
]);
$button2 = ModalAjax::widget([
    'id' => "modal-payment-bulk-update-form-{$model->contract_detail_id}",
    'title' => '期間指定一括更新:支払',
    'toggleButton' => [
        'label' => '期間指定一括更新:支払',
        'class' => 'btn btn-outline-info btn-sm',
    ],
    'url' => ['/update/payment-bulk', 'cdid' => $model->contract_detail_id],
    'ajaxSubmit' => true,
    'autoClose' => true,
    'pjaxContainer' => "#pjax-grid-{$model->contract_detail_id}-wrapper",
    'events' => [
        ModalAjax::EVENT_BEFORE_SUBMIT => new \yii\web\JsExpression("
            function(event, data, status, xhr, selector) {
                $('#modal-payment-bulk-update-form-{$model->contract_detail_id} .modal-body').html('<div class=\"modal-ajax-loader\"></div>')
            }
        "),
    ],
]);
$layout =<<<EOL
【{$type}】{$button}{$button2}{summary}
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
            'content' => function($data){
                return $data->monthly_charge_id ? $data->monthlyCharge->orderCountText : '&nbsp;';
            },
        ],
        [
            'header' => '回収予定日',
            'contentOptions' => function($data){
                $isTermClosed = $data->monthly_charge_id ? $data->monthlyCharge->isTermClosed : false;
                $lastMonthlyCharges = MonthlyCharge::getSiblings($data->contract_detail_id, $data->term->format('Ym'), -1);
                $repayment = $data->monthly_charge_id ? ($data->monthlyCharge->repayments[0] ?? false) : false;
                return $data->monthly_charge_id ? ['data-id' => $data->monthly_charge_id, 'class' => ($lastMonthlyCharges ? '' : 'unskippable ') . ($repayment ? '' : ($isTermClosed ? '' : 'skippable ')) . ($isTermClosed ? '' : 'editable cell-monthly_charge-transfer_date')] : [];
            },
            'content' => function($data){
                if ($monthlyCharge = $data->monthlyCharge) {
                    $isTermClosed = $monthlyCharge->isTermClosed;
                    $repayment = $monthlyCharge->repayments[0] ?? false;
                    return ($repayment ? '' : ($isTermClosed ? '' : '<span class="skip-this"></span>')) . $monthlyCharge->transfer_date;
                }
                $transfer_date = $data->contractDetail->leaseContract->customer->clientContract->repaymentPattern->transfer_date;
                $format = $transfer_date == 31 ? 'Y-m-t' : "Y-m-{$transfer_date}";
                return $data->term->format($format);
            },
            'footer' => \yii\bootstrap5\Html::button('<i class="ri-add-box-line"></i>回収追加', ['class' => 'btn btn-sm btn-light btn-increment-monthly-charge', 'data-cdid' => $model->contract_detail_id, 'data-transfer_date' => $incrementTransferDate]),
        ],
        [
            'header' => '回収予定額',
            'contentOptions' => function($data){
                $isTermClosed = $data->monthly_charge_id ? $data->monthlyCharge->isTermClosed : false;
                $options = $data->monthly_charge_id ? [
                    'data-id' => $data->monthly_charge_id,
                    'class' => 'text-end ' . ($isTermClosed ? '' : 'editable') . ' cell-monthly_charge-temporary_charge_amount',
                ] : [];
                /*
                $repayment = $data->monthly_charge_id ? ($data->monthlyCharge->repayments[0] ?? false) : false;
                if ($repayment && $repayment->repaymentType->bg_color) {
                    $options['style'] = "background-color:{$repayment->repaymentType->bg_color};";
                }
                */
                return $options;
            },
            'content' => function($data){
                if ($data->monthly_charge_id) {
                    $amount = $data->monthlyCharge->getAmountWithTax('temporary_charge_amount');
                    return number_format($amount, 0);
                }
                return '&nbsp;';
            },
            'footerOptions' => [
                'class' => 'text-end',
            ],
            'footer' => "総額:".number_format(MonthlyCharge::getTotal($model->monthlyCharges, 'calculatedAmountWithTax'), 0),
        ],
        [
            'header' => '前払',
            'content' => function($data){
                if ($data->monthly_charge_id) {
                    $advanceRepayments = $data->monthlyCharge->getAdvanceRepayments()->all();
                    $repayment = $advanceRepayments[0] ?? ($data->repayments[0] ?? false);
                    return $repayment instanceof \app\models\AdvanceRepayment ? '◯' : '';
                }
                return '&nbsp;';
            },
        ],
        [
            'header' => '回収方法',
            'contentOptions' => function($data){
                if ($data->monthly_charge_id) {
                    $isTermClosed = $data->monthlyCharge->isTermClosed;
                    $advanceRepayments = $data->monthlyCharge->getAdvanceRepayments()->all();
                    $repayment = $advanceRepayments[0] ?? ($data->monthlyCharge->repayments[0] ?? false);
                    $debt = $data->monthlyCharge->debts[0] ?? false;
                    return $repayment ? ($repayment instanceof Repayment ? ['data-id' => $repayment->repayment_id, 'class' => ($isTermClosed ? 'term-closed ' : 'deletable editable ') . 'cell-repayment-repayment_type_id'] : ['data-id' => $repayment->repayment_id]) :
                        ['data-mcid' => $data->monthly_charge_id, 'data-id' => $data->monthly_charge_id, 'class' => ($isTermClosed ? '' : 'editable ') . 'cell-monthly_charge-repayment_type_id'];
                }
                return [];
            },
            'content' => function($data){
                if ($data->monthly_charge_id) {
                    $isTermClosed = $data->monthlyCharge->isTermClosed;
                    $advanceRepayments = $data->monthlyCharge->getAdvanceRepayments()->all();
                    $repayment = $advanceRepayments[0] ?? ($data->monthlyCharge->repayments[0] ?? false);
                    $debt = $data->monthlyCharge->debts[0] ?? false;
                    return $repayment ? ($repayment instanceof Repayment ? ($isTermClosed ? '' : '<span class="delete-this"></span>')  . $repayment->repaymentType->type : '前払リース料') : ($debt ? $debt->debtType->type : $data->monthlyCharge->repaymentType->type);
                }
                return '&nbsp;';
            },
        ],
        [
            'header' => '回収日',
            'contentOptions' => function($data){
                if ($data->monthly_charge_id) {
                    $isTermClosed = $data->monthlyCharge->isTermClosed;
                    $advanceRepayments = $data->monthlyCharge->getAdvanceRepayments()->all();
                    $repayment = $advanceRepayments[0] ?? ($data->monthlyCharge->repayments[0] ?? false);
                    $debt = $data->monthlyCharge->debts[0] ?? false;
                    $bgColor = $repayment && ($repayment->repayment_amount < $data->monthlyCharge->amountWithTax) ? 'bg-red' : 'bg-gray';
                    return $repayment ? ($repayment instanceof Repayment ? ['data-id' => $repayment->repayment_id, 'class' => ($isTermClosed ? '' : 'editable ') . 'cell-repayment-processed '.$bgColor] : ['class' => $bgColor, 'data-id' => $repayment->repayment_id]) :
                        ['data-mcid' => $data->monthly_charge_id, 'class' => ($isTermClosed ? '' : 'registerable ') . 'cell-repayment'];
                }
                return [];
            },
            'content' => function($data){
                if ($data->monthly_charge_id) {
                    $advanceRepayments = $data->monthlyCharge->getAdvanceRepayments()->all();
                    $repayment = $advanceRepayments[0] ?? ($data->monthlyCharge->repayments[0] ?? false);
                    $debt = $data->monthlyCharge->debts[0] ?? false;
                    return $repayment ? $repayment->processed : ($debt ? (new \DateTime($debt->registered))->format('Y-m-d') : '&nbsp;');
                }
                return '&nbsp;';
            },
        ],
        [
            'header' => '回収額<sub>（税込）</sub>',
            'contentOptions' => function($data){
                if ($data->monthly_charge_id) {
                    $isTermClosed = $data->monthlyCharge->isTermClosed;
                    $advanceRepayments = $data->monthlyCharge->getAdvanceRepayments()->all();
                    $repayment = $advanceRepayments[0] ?? ($data->monthlyCharge->repayments[0] ?? false);
                    $debt = $data->monthlyCharge->debts[0] ?? false;
                    $bgColor = $repayment && ($repayment->repayment_amount < $data->monthlyCharge->amountWithTax) ? 'bg-red' : 'bg-gray';
                    $style = $repayment ? ($repayment instanceof Repayment ? $repayment->repaymentType->style : \app\models\RepaymentType::findOne(5)->style) : null;
                    if ($repayment && $repayment instanceof Repayment && $repayment->repaymentType->bg_color) {
                        $bgColorCode = $repayment->repayment_type_id == 3 ? '#ced4da' :  $repayment->repaymentType->bg_color;
                        $style .= "background-color:{$bgColorCode}!important;";
                    }
                    return $repayment ?

                        ($repayment instanceof Repayment ?
                            ($repayment->repayment_amount == 0 ? ['data-mcid' => $data->monthly_charge_id, 'class' => 'text-end registerable cell-repayment ' . $bgColor, 'style' => $style ?? ""] :
                                ['data-id' => $repayment->repayment_id, 'class' => ($isTermClosed ? '' : 'editable ') . 'text-end cell-repayment-repayment_amount '.$bgColor, 'style' => $style ?? ""]) :
                            ['data-id' => $repayment->repayment_id, 'class' => 'text-end bg-gray', 'style' => $style ?? ""]) :

                        ($debt ?
                            ['data-mcid' => $data->monthly_charge_id, 'class' => ($isTermClosed ? '' : 'registerable ') . 'cell-repayment'] :
                            ['data-mcid' => $data->monthly_charge_id, 'class' => ($isTermClosed ? '' : 'registerable ') . 'cell-repayment']);
                }
                return [];
            },
            'content' => function($data){
                if ($data->monthly_charge_id) {
                    $advanceRepayments = $data->monthlyCharge->getAdvanceRepayments()->all();
                    $repayment = $advanceRepayments[0] ?? ($data->monthlyCharge->repayments[0] ?? false);
                    $debt = $data->monthlyCharge->debts[0] ?? false;
                    return $repayment ? number_format((int)$repayment->repayment_amount, 0) : ($debt ? number_format((int)$debt->debt_amount, 0) : '&nbsp;');
                }
                return '&nbsp;';
            },
            'footerOptions' => [
                'class' => 'text-end',
            ],
            'footer' => "総額:".number_format(\app\models\AdvanceRepayment::getTotal($model->monthlyCharges, 'repayment_amount') + Repayment::getTotal($model->monthlyCharges, 'repayment_amount') - Repayment::getTotal($model->monthlyCharges, 'chargeback_amount'), 0)
        ],
        [
            'header' => '返金額',
            'contentOptions' => function($data){
                if ($data->monthly_charge_id) {
                    $isTermClosed = $data->monthlyCharge->isTermClosed;
                    $repayment = $data->monthlyCharge->repayments[0] ?? false;
                    return $repayment ? ['data-id' => $repayment->repayment_id, 'class' => ($isTermClosed ? '' : 'editable ') . 'text-end cell-repayment-chargeback_amount'] : [];
                }
                return [];
            },
            'content' => function($data){
                if ($data->monthly_charge_id) {
                    $repayment = $data->monthlyCharge->repayments[0] ?? false;
                    return $repayment ? number_format((int)$repayment->chargeback_amount, 0) : '';
                }
                return '&nbsp;';
            },
        ],
        [
            'header' => '回収残額',
            'contentOptions' => [
                'class' => 'text-end',
            ],
            'content' => function($data) use($model){
                if ($data->monthly_charge_id) {
                    $total = 0;
                    foreach($model->monthlyCharges as $mc) {
                        if ((new \DateTime($mc->term)) <= $data->term) {
                            if ($data->monthly_charge_id && $mc->orderCount <= $data->monthlyCharge->orderCount) {
                                $repayment = $mc->repayments[0] ?? false;
                                $total += $repayment ? ($repayment->repayment_amount - $repayment->chargeback_amount) : 0;
                            }
                        }
                    }
                    $remains = MonthlyCharge::getTotal($model->monthlyCharges, 'calculatedAmountWithTax') - \app\models\AdvanceRepayment::getTotal($model->monthlyCharges, 'repayment_amount') - $total;
                    return number_format($remains, 0);
                }
                return '&nbsp;';
            },
            'footerOptions' => [
                'class' => 'text-end',
            ],
            'footer' => "残額:".number_format(MonthlyCharge::getTotal($model->monthlyCharges, 'calculatedAmountWithTax') - \app\models\AdvanceRepayment::getTotal($model->monthlyCharges, 'repayment_amount') - (Repayment::getTotal($model->monthlyCharges, 'repayment_amount') - Repayment::getTotal($model->monthlyCharges, 'chargeback_amount')), 0)
        ],
        [
            'header' => '回数',
            'content' => function($data){
                return $data->monthly_payment_id ? $data->monthlyPayment->orderCount : '&nbsp;';
            },
            'visible' => $model->monthly_payment > 0,
        ],
        [
            'header' => '支払月',
            'contentOptions' => function($data){
                return $data->monthly_payment_id ? [
                    'data-id' => $data->monthly_payment_id,
                    'class' => 'editable cell-monthly_payment-payment_date'
                ] : [];
            },
            'content' => function($data){
                $monthlyPayment = $data->monthlyPayment;
                return $monthlyPayment ? (new \DateTime($monthlyPayment->payment_date))->format('Y-m') : '';
            },
            'visible' => $model->monthly_payment > 0,
        ],
        [
            'header' => '支払額<sub>(税込)</sub>',
            'contentOptions' => function($data){
                $monthlyPayment = $data->monthlyPayment;
                return $monthlyPayment ? [
                    'data-id' => $monthlyPayment->monthly_payment_id,
                    'class' => 'text-end editable cell-monthly_payment-payment_amount_with_tax'
                ] : [];
            },
            'content' => function($data){
                $monthlyPayment = $data->monthlyPayment;
                return $monthlyPayment ? number_format($monthlyPayment->amountWithTax,0) : '';
            },
            'footerOptions' => ['class' => 'text-end'],
            'footer' => "総額:".number_format(MonthlyPayment::getTotal($model->monthlyCharges, 'amountWithTax'), 0) .
                "<br />支払残額:".number_format(MonthlyPayment::getTotal($model->monthlyCharges, 'amountWithTax') - MonthlyPayment::getTotal($model->monthlyCharges, 'amountWithTax', true)),
            'visible' => $model->monthly_payment > 0,
        ],
        [
            'header' => '税率',
            'content' => function($data){
                $sql = "SELECT CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE :term >= application_from AND :term <= IFNULL(application_to, '2099-12-31')) END FROM tax_application ta WHERE ta.tax_application_id=:id";
                $value = Yii::$app->db->createCommand($sql)->bindValues([
                    ':term' => $data->term->format('Y-m-d'),
                    ':id' => (int)$data->contractDetail->tax_application_id,
                ])->queryScalar();
                return (string)($value * 100).'%';
            },
            'visible' => $model->monthly_payment > 0,
        ],
        [
            'header' => 'コメント',
            'contentOptions' => function($data){
                return $data->monthly_charge_id ? [
                    'style' => 'min-width: 300px;',
                    'class' => 'editable cell-monthly_charge-memo',
                    'data-id' => $data->monthly_charge_id,
                ] : [
                    'style' => 'min-width: 300px;',
                ];
            },
            'content' => function($data){
                return $data->monthly_charge_id ? $data->monthlyCharge->memo : '&nbsp;';
            },
        ],
    ],
]) ?>
<?php Pjax::end(); ?>
