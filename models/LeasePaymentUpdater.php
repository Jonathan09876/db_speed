<?php

namespace app\models;

use yii\base\Model;
use yii\bootstrap5\Html;
use app\widgets\datetimepicker\Datetimepicker;

class LeasePaymentUpdater extends Model
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
            [['attr'], 'in', 'range' => ['processed', 'payment_amount'] ],
            [['id'], 'integer'],
            [['value'], 'safe'],
        ];
    }

    public function getInstance()
    {
        return LeasePayment::findOne($this->id);
    }

    public function getValue()
    {
        return $this->instance->{$this->attr};
    }

    public function getUpdater()
    {
        switch($this->attr) {
            case 'processed':
                $tag = Html::tag('div', Datetimepicker::widget([
                    'model' => $this->instance,
                    'attribute' => $this->attr,
                    'id' => "lease_payment-{$this->attr}-{$this->id}",
                    'clientOptions' => [
                        'locale' => 'ja',
                        'format' => 'YYYY-MM-DD',
                    ],
                    'ajax' => true
                ]), ['style' => 'width:150px;']);
                break;
            case 'payment_amount':
                $instance = $this->getInstance();
                $input = Html::activeTextInput($instance, $this->attr, ['id' => "lease_payment-{$this->attr}-{$this->id}",'class' => 'form-control text-end formatted']);
                $tag = '<div class="hstack gap-2"><div style="width:150px;"><div class="input-group">{input}<span class="input-group-text">円<sub>(税込)</sub></span></div></div>';
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