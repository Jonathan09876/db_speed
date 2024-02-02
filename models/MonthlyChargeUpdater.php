<?php

namespace app\models;

use yii\base\Model;
use yii\bootstrap5\Html;
use app\widgets\datetimepicker\Datetimepicker;

class MonthlyChargeUpdater extends Model
{
    public $attr;
    public $id;
    public $value;

    public function formName()
    {
        return '';
    }

    public function rules()
    {
        return [
            [['attr', 'id'], 'required'],
            [['attr'], 'in', 'range' => ['transfer_date', 'temporary_charge_amount', 'memo', 'repayment_type_id', 'amount_with_tax', 'temporary_charge_amount_with_tax'] ],
            [['id'], 'integer'],
            [['value'], 'safe'],
        ];
    }

    public function getInstance()
    {
        return MonthlyCharge::findOne($this->id);
    }

    public function getValue()
    {
        return $this->instance->{$this->attr};
    }

    public function getUpdater()
    {
        switch($this->attr) {
            case 'transfer_date':
                $tag = Html::tag('div', Datetimepicker::widget([
                    'model' => $this->instance,
                    'attribute' => $this->attr,
                    'id' => "monthly_charge-{$this->attr}-{$this->id}",
                    'clientOptions' => [
                        'locale' => 'ja',
                        'format' => 'YYYY-MM-DD',
                    ],
                    'ajax' => true
                ]), ['style' => 'width:150px;']);
                break;
            case 'temporary_charge_amount':
                $instance = $this->getInstance();
                $instance->temporary_charge_amount_with_tax = $instance->temporaryAmountWithTax;
                $input = Html::activeTextInput($instance, 'temporary_charge_amount_with_tax', ['id' => "monthly_charge-{$this->attr}-{$this->id}",'class' => 'form-control text-end formatted']);
                $tag = '<div class="hstack gap-2"><div style="width:150px;"><div class="input-group">{input}<span class="input-group-text">円<sub>(税込)</sub></span></div></div></div>';
                $tag = strtr($tag, ['{input}' => $input]);
                break;
            case '_temporary_charge_amount':
                $instance = $this->getInstance();
                if (is_null($instance->temporary_charge_amount)) {
                    $instance->temporary_charge_amount = $instance->charge_amount;
                }
                $input = Html::activeTextInput($instance, $this->attr, ['id' => "monthly_charge-{$this->attr}-{$this->id}",'class' => 'form-control text-end formatted']);
                $tag = '<div class="hstack gap-2"><div style="width:150px;"><div class="input-group">{input}<span class="input-group-text">円<sub>(税抜)</sub></span></div></div>' .
                    '<div style="width:150px;"><div class="input-group"><input type="text" value="' . number_format($instance->getAmountWithTax('temporary_charge_amount'), 0) . '" class="form-control text-end" readonly><span class="input-group-text">円<sub>(税込)</sub></span></div></div>';
                $tag = strtr($tag, ['{input}' => $input]);
                break;
            case 'memo':
                $instance = $this->getInstance();
                $input = Html::activeTextInput($instance, $this->attr, ['id' => "monthly_charge-{$this->attr}-{$this->id}",'class' => 'form-control']);
                $tag = '<div style="width:200px;">{input}</div>';
                $tag = strtr($tag, ['{input}' => $input]);
                break;
            case 'repayment_type_id':
                $instance = $this->getInstance();
                $input = Html::activeDropDownList($instance, $this->attr, RepaymentType::getTypes(), ['id' => "monthly_charge-{$this->attr}-{$this->id}",'class' => 'form-control form-select']);
                $tag = '<div style="width:150px;">{input}</div>';
                $tag = strtr($tag, ['{input}' => $input]);
                break;
            default:
                $tag = '<span>適用外です。</span>';
        }
        return $tag;
    }

    public function updateValue()
    {
        $instance = $this->instance;
        if ($this->attr == 'amount_with_tax' || $this->attr == 'temporary_charge_amount_with_tax') {
            $this->attr = 'temporary_charge_amount';
            $this->value = $instance->getAmountFromWithTax($this->value);
        }
        $oldValue = $instance->{$this->attr};
        $instance->{$this->attr} = $this->value;
        if ($this->attr == 'transfer_date') {
            $term = new \DateTime($this->value);
            $instance->term = $term->format('Y-m-01');
        }
        if ($instance->validate()) {
            $instance->save();
            //中途解約の場合は以降のMonthlyChargeも一括変更する
            if ($this->attr == 'repayment_type_id' && $this->value == 12) {
                $query = MonthlyCharge::find()
                    ->where(['and',
                        ['contract_detail_id' => $instance->contract_detail_id],
                        ['>=', 'term', $instance->term]
                    ]);
                foreach($query->each() as $mc) {
                    if ($mc->getRepayments()->count() == 0 && $mc->getAdvanceRepayments()->count() == 0) {
                        $mc->repayment_type_id = $this->value;
                        $mc->charge_amount = 0;
                        $mc->temporary_charge_amount = 0;
                        $mc->save();
                    }
                }
                //契約ステータスも変更
                LeaseContractStatus::register($instance->contractDetail->lease_contract_id, 10);
            }
            //STOPの場合も以降のMonthlyChargeを一括変更する
            if ($this->attr == 'repayment_type_id' && $this->value == 11) {
                $query = MonthlyCharge::find()
                    ->where(['and',
                        ['contract_detail_id' => $instance->contract_detail_id],
                        ['>=', 'term', $instance->term]
                    ]);
                foreach($query->each() as $mc) {
                    if ($mc->getRepayments()->count() == 0 && $mc->getAdvanceRepayments()->count() == 0) {
                        $mc->repayment_type_id = $this->value;
                        $mc->charge_amount = 0;
                        $mc->temporary_charge_amount = 0;
                        $mc->save();
                    }
                }
                //契約ステータスも変更
                LeaseContractStatus::register($instance->contractDetail->lease_contract_id, 5);
            }
            return [
                'success' => true,
                'oldValue' => $oldValue,
                'value' => $this->value,
            ];
        }
        else return [
            'success' => false,
            'errors' => $instance->errors,
        ];
    }
}