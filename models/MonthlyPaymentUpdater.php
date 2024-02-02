<?php

namespace app\models;

use yii\base\Model;
use yii\bootstrap5\Html;
use app\widgets\datetimepicker\Datetimepicker;

class MonthlyPaymentUpdater extends Model
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
            [['attr'], 'in', 'range' => ['payment_date', 'payment_amount', 'payment_amount_with_tax'] ],
            [['id'], 'integer'],
            [['value'], 'safe'],
        ];
    }

    public function getInstance()
    {
        return MonthlyPayment::findOne($this->id);
    }

    public function getValue()
    {
        return $this->instance->{$this->attr};
    }

    public function getUpdater()
    {
        switch($this->attr) {
            case 'payment_date':
                $tag = Html::tag('div', Datetimepicker::widget([
                    'model' => $this->instance,
                    'attribute' => $this->attr,
                    'id' => "monthly_payment-{$this->attr}-{$this->id}",
                    'clientOptions' => [
                        'locale' => 'ja',
                        'format' => 'YYYY-MM-DD',
                    ],
                    'ajax' => true
                ]), ['style' => 'width:150px;']);
                break;
            case 'payment_amount_with_tax':
                $instance = $this->getInstance();
                $instance->payment_amount_with_tax = $instance->amountWithTax;
                $input = Html::activeTextInput($instance, $this->attr, ['id' => "monthly_payment-{$this->attr}-{$this->id}",'class' => 'form-control text-end formatted']);
                $tag = '<div class="hstack gap-2"><div style="width:150px;"><div class="input-group">{input}<span class="input-group-text">円<sub>(税込)</sub></span></div></div></div>';
                $tag = strtr($tag, ['{input}' => $input]);
                break;
            case 'payment_amount':
                $instance = $this->getInstance();
                $input = Html::activeTextInput($instance, $this->attr, ['id' => "monthly_payment-{$this->attr}-{$this->id}",'class' => 'form-control text-end formatted']);
                $tag = '<div class="hstack gap-2"><div style="width:150px;"><div class="input-group">{input}<span class="input-group-text">円<sub>(税抜)</sub></span></div></div>' .
                    '<div style="width:150px;"><div class="input-group"><input type="text" value="' . number_format($instance->getAmountWithTax('payment_amount'), 0) . '" class="form-control text-end" readonly><span class="input-group-text">円<sub>(税込)</sub></span></div></div>';
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
        $posted_attr = $this->attr;
        $posted_value = $this->value;
        $payment_amount = $instance->getAmountFromWithTax($posted_value);
        if ($posted_attr == 'payment_amount_with_tax') {
            $this->attr = 'payment_amount';
            $this->value = $payment_amount;
        }
        $oldValue = $instance->{$this->attr};
        $instance->{$this->attr} = $this->value;
        if ($instance->validate()) {
            $instance->save();
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