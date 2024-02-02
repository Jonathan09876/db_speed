<?php
/**
 * @var $this \yii\web\View
 * @var $searchModel \app\models\MonthlyChargeSearch;
 * @var $dataProvider \yii\data\ActiveDataProvider;
 */

use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\widgets\ListView;
use yii\bootstrap5\Html;
use app\widgets\datetimepicker\Datetimepicker;
use app\models\RepaymentPattern;
use kartik\typeahead\Typeahead;
use yii\helpers\Url;

$this->title = '回収実績登録一覧';

$script = <<<EOS
function setCssStyle(selectorText, style, value) {
    var CSSstyle = undefined;
    for(var m in document.styleSheets) {
        rules = document.styleSheets[m][document.all ? 'rules' : 'cssRules'];
        for(var n in rules) {
            if(rules[n].selectorText == selectorText){
                CSSstyle = rules[n].style;
            }
        }
    }
    if (CSSstyle) {
        let current = CSSstyle.cssText;
        CSSstyle.cssText = current + ' ' + style + ': ' + value + ';';
    }
}
function numberFormat(num) {
    return num.toString().replace(/(\d+?)(?=(?:\d{3})+$)/g, '$1,');
}
$.fn.extend({
    format: function(){
        return $(this).each(function(){
            var v = $(this).val();
            $(this).val(numberFormat(v.toString().replace(/[^\d]/g,'')));
        });
    },
    unformat: function(){
        return $(this).each(function(){
            var v = $(this).val();
            $(this).val(v.toString().replace(/[^\d]/g,''));
        });
    },
    numVal: function(){
        return $(this).val().replace(/[^\d]/g,'')*1;
    },
});
$(document)
    .on('keyup', '.formatted', function(){
        $(this).format();
    })
    .on('click', '[name="MonthlyChargeSearch[client_corporation_id]"]', async function(){
        $('#monthlychargesearch-customer_code').val('');
        $('#monthlychargesearch-customer_name').val('');
        monthlychargesearch_customer_code_data_1.clear();
        monthlychargesearch_customer_code_data_1.clearRemoteCache();
        monthlychargesearch_customer_code_data_1.clearPrefetchCache();
        monthlychargesearch_customer_name_data_1.clear();
        monthlychargesearch_customer_name_data_1.clearRemoteCache();
        monthlychargesearch_customer_name_data_1.clearPrefetchCache();
        let response = await $.ajax({
            method: 'post',
            data: $('[name="MonthlyChargeSearch[client_corporation_id]"]:checked').serialize(),
            url: '/aas/set-client-corporations',
            dataType: 'json'
        });
    })
    .on('typeahead:select', '#monthlychargesearch-customer_code', function(evt, selected){
        $('#monthlychargesearch-customer_id').val(selected.customer_id);
        $('#monthlychargesearch-customer_name').val(selected.name);
    })
    .on('typeahead:select', '#monthlychargesearch-customer_name', function(evt, selected){
        $('#monthlychargesearch-customer_id').val(selected.customer_id);
        $('#monthlychargesearch-customer_code').val(selected.customer_code);
    })
$('.formatted').format();
let width1 = $('.sticky-header1').outerWidth();
let width2 = $('.sticky-header2').outerWidth();
let width3 = $('.sticky-header3').outerWidth();
setCssStyle('.sticky-header2', 'left', width1+'px;')
setCssStyle('.sticky-header3', 'left', (width1+width2)+'px;')
setCssStyle('.sticky-header4', 'left', (width1+width2+width3)+'px;')
setCssStyle('.sticky-cell2', 'left', width1+'px;')
setCssStyle('.sticky-cell3', 'left', (width1+width2)+'px;')
setCssStyle('.sticky-cell4', 'left', (width1+width2+width3)+'px;')

EOS;
$this->registerJs($script);
$style = <<<EOS
.table-wrapper table.table {
	border-collapse:separate;
	border-spacing:0;
}
.table-wrapper table.table th,
.table-wrapper table.table td {
    border-left-width: 0;
}
.sticky-header1 {
    position:sticky;
    top:0;
    left: 0px;
    border-left-width: 1;
    z-index:1;
}
.sticky-cell1 {
    position:sticky;
    top:0;
    left: 0px;
    background-color: #fff !important;
    border-left-width: 1;
    z-index:1;
}
.sticky-header2 {
    position:sticky;
    top:0;
    z-index:1;
}
.sticky-cell2 {
    position:sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
}
.sticky-header3 {
    position:sticky;
    top:0;
    z-index:1;
}
.sticky-cell3 {
    position:sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
}
.sticky-header4 {
    position: sticky;
    top:0;
    z-index:1;
}
.sticky-cell4 {
    position: sticky;
    top:0;
    background-color: #fff !important;
    z-index:1;
}
.col-first {
    -webkit-box-flex: 0;
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    width: 110px;
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
                                <h5 class="card-title mb-0">回収実績登録一覧検索</h5>
                            </div>
                        </div>
                    </div>
                    <?php $form = ActiveForm::begin([
                        'layout' => 'horizontal',
                    ]) ?>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <?= $form->field($searchModel, 'target_term', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-first',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-sm-5',
                                ],])->widget(Datetimepicker::class, ['clientOptions' => [
                                    'locale' => 'ja',
                                    'format' => 'YYYY年M月',
                                    'viewMode' => 'months',
                                ]]) ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <?php $children = Yii::$app->user->identity->clientCorporation->clientCorporationChildren; ?>
                                <?= $form->field($searchModel, 'client_corporation_id', ['inline' => true, 'horizontalCssClasses' => [
                                    'label' => 'form-label col-first',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-auto',
                                ],])->radioList(\yii\helpers\ArrayHelper::map($children, 'client_corporation_id', 'shorten_name')) ?>
                            </div>
                            <div class="col-auto">
                                <?= Html::activeHiddenInput($searchModel, 'customer_id') ?>
                                <?= $form->field($searchModel, 'customer_code', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-first',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-auto',
                                ],])->widget(Typeahead::class, [
                                    'options' => ['placeholder' => '得意先コードを入力'],
                                    'scrollable' => true,
                                    'pluginOptions' => ['hint' => false, 'highlight' => true],
                                    'dataset' => [
                                        [
                                            'datumTokenizer' => "Bloodhound.tokenizers.obj.whitespace('customer_code')",
                                            'display' => 'customer_code',
                                            'prefetch' => Url::to(['/aas/get-customer-info']),
                                            'remote' => [
                                                'url' => Url::to(['/aas/get-customer-info']) . '?q=%QUERY',
                                                'wildcard' => '%QUERY'
                                            ]
                                        ],
                                    ]
                                ]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($searchModel, 'customer_name', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-auto',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-md-6',
                                ],])->widget(Typeahead::class, [
                                    'options' => ['placeholder' => '得意先名入力'],
                                    'scrollable' => true,
                                    'pluginOptions' => ['hint' => false, 'highlight' => true],
                                    'dataset' => [
                                        [
                                            'datumTokenizer' => "Bloodhound.tokenizers.obj.whitespace('name')",
                                            'display' => 'name',
                                            'prefetch' => Url::to(['/aas/get-customer-info-by-name']),
                                            'remote' => [
                                                'url' => Url::to(['/aas/get-customer-info-by-name']) . '?q=%QUERY',
                                                'wildcard' => '%QUERY'
                                            ]
                                        ],
                                    ]
                                ]) ?>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3 row field-contract-codes">
                                    <label class="form-label col-first">契約番号</label>
                                    <div class="col-md-8 hstack gap-1">
                                        <div class="col-auto">
                                            <?= Html::activeDropDownList($searchModel, 'contract_pattern_id', \app\models\ContractPattern::getContractPatterns(), ['class' => 'form-control', 'prompt' => '契約マスタ選択']) ?>
                                        </div>
                                        <div class="col-auto">
                                            <?= Html::activeTextInput($searchModel, 'contract_number', ['class' => 'form-control']) ?>
                                        </div>
                                        -
                                        <div class="col-auto">
                                            <?= Html::activeTextInput($searchModel, 'contract_code', ['class' => 'form-control']) ?>
                                        </div>
                                        -
                                        <div class="col-1">
                                            <?= Html::activeTextInput($searchModel, 'contract_sub_code', ['class' => 'form-control']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($searchModel, 'target_word', ['horizontalCssClasses' => [
                                    'label' => 'col-first form-label',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-md-8',
                                ], 'inputOptions' => ['placeholder' => '登録ナンバー等']]) ?>
                            </div>
                            <div class="col-md-12">
                                <div class="row field-contract-codes">
                                    <label class="form-label col-first">リース期間</label>
                                    <div class="col-md-8 hstack gap-1">
                                        <div class="col-auto">
                                            <?= $form->field($searchModel, "term_from", ['horizontalCssClasses' => [
                                                'offset' => 'col-sm-offset-2',
                                                'wrapper' => 'col-auto',
                                            ],])->label(false)->widget(Datetimepicker::class, ['id' => "term-start-at", 'clientOptions' => [
                                                'locale' => 'ja',
                                                'format' => 'YYYY年M月',
                                                'viewMode' => 'months',
                                            ]]) ?>
                                        </div>
                                        <div class="col-autp">〜</div>
                                        <div class="col-6">
                                            <?= $form->field($searchModel, "term_to", ['horizontalCssClasses' => [
                                                'offset' => 'col-sm-offset-2',
                                                'wrapper' => 'col-auto',
                                            ],])->label(false)->widget(Datetimepicker::class, ['id' => "term-end-at", 'clientOptions' => [
                                                'locale' => 'ja',
                                                'format' => 'YYYY年M月',
                                                'viewMode' => 'months',
                                            ]]) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <?= $form->field($searchModel, 'repayment_pattern_id', ['horizontalCssClasses' => [
                                    'label' => 'form-label col-first',
                                    'offset' => 'col-sm-offset-2',
                                    'wrapper' => 'col-auto',
                                ],])->dropDownList(RepaymentPattern::getPatterns(), ['prompt' => '支払条件を選択']) ?>
                            </div>
                        </div>
                        <div class="hstack gap-2 mb-3">
                            <label class="form-label col-first">契約ステータス</label>
                            <?= Html::activeCheckboxList($searchModel, 'lease_contract_status_type_id', \app\models\LeaseContractStatusType::getTypes(), ['inline' => true]) ?>
                        </div>
                        <div class="hstack gap-2 mb-3">
                            <label class="form-label col-first">契約情報</label>
                            <?= Html::activeCheckboxList($searchModel, 'contract_pattern_id', \app\models\ContractPattern::getContractNamePatterns(), ['inline' => true]) ?>
                        </div>
                        <div class="hstack gap-2 mb-3">
                            <label class="form-label col-first">税区分</label>
                            <?= Html::activeCheckboxList($searchModel, 'tax_application_id', \app\models\TaxApplication::getTaxApplications(), ['inline' => true]) ?>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <?= Html::submitButton('この内容で検索', ['class' => 'btn btn-primary']) ?>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <h5 class="card-title mb-0">回収実績登録一覧</h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php
                $term = empty($searchModel->target_term) ? (new \DateTime())->format('Y年m月') : $searchModel->target_term;
                $targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $term));
                preg_match('/(\d+)年(\d+)月/', $term, $matched);
                $current = clone $targetTerm;
                $closing_month = $searchModel->getClientCorporation()->account_closing_month;
                $current->setDate((int)$matched[1] - ($closing_month == 12 ? 1 : 0), $closing_month == 12 ? 1 : $closing_month+1, 1);
                if ($current >= $targetTerm) {
                    $current = $current->modify('-1 year');
                }
                $terms = [];
                for ($i = 0; $i < 12; $i++) {
                    $interval = $targetTerm->diff($current);
                    $relative_month = -((int)$targetTerm->format('n') - ((int)$current->format('n') - ((int)$targetTerm->format('Y') - (int)$current->format('Y')) * 12));
                    $terms[] = [
                        'the_term' => clone $current,
                        'relative_month' => $relative_month,
                    ];
                    $current = $current->modify('+1 month');
                }
                $layout = <<<EOL
{summary}
<div class="table-wrapper">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th class="sticky-header1">CF</th>
                <th class="sticky-header2">支払方法</th>
                <th class="sticky-header3">顧客名
                <th class="sticky-header4">契約No.</th>
                <th>税率</th>
                <th>リース開始<br>リース終了</th>
                <th>登録No.</th>
                <th>リース期間<br/>会社</th>
                <th>収支</th>
EOL;
                foreach($terms as $the_term) {
                    $termText = $the_term['the_term']->format('Y/m');
                    if ($term != $the_term['the_term']->format('Y年n月')) {
                        $layout .= <<<EOL
                <th>回数</th>
                <th style="width:140px;">{$termText}</th>
EOL;
                    }
                    else {
                        $layout .= <<<EOL
                <th>回数</th>
                <th style="width:140px;">{$termText}<br/>予定額<sub>(税抜)</sub></th>
                <th style="width:140px;">予定額<sub>（税込）</sub></th>
                <th style="width:140px;">修正額<sub>（税抜）</sub></th>
                <th style="width:140px;">修正額<sub>（税込）</sub></th>
EOL;
                    }
                }
                $layout .= <<<EOL
                <th>合計</th>
                <th>リース開始<br>残り回数</th>
                <th>債務残高</th>
                <th>月額リース料</th>
            </tr>
        </thead>
        <tbody>
            {items}
        </tbody>
    </table>
</div>
{pager}
EOL;
                ?>
                <?= ListView::widget([
                    'dataProvider' => $dataProvider,
                    'itemView' => 'iv-lease-contract-operation',
                    'itemOptions' => ['tag' => false],
                    'viewParams' => compact("targetTerm", "terms"),
                    'layout' => $layout,
                ]) ?>
            </div>
        </div>
</section>

