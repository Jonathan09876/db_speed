<?php

namespace app\models;

use yii\base\Model;
use yii\bootstrap5\Html;
use app\widgets\datetimepicker\Datetimepicker;

class RepaymentUpdater extends Model
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
            [['attr'], 'in', 'range' => ['repayment_type_id', 'processed', 'repayment_amount', 'chargeback_amount'] ],
            [['id'], 'integer'],
            [['value'], 'safe'],
        ];
    }

    public function getInstance()
    {
        return Repayment::findOne($this->id);
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
                    'id' => "repayment-{$this->attr}-{$this->id}",
                    'clientOptions' => [
                        'locale' => 'ja',
                        'format' => 'YYYY-MM-DD',
                    ],
                    'ajax' => true
                ]), ['style' => 'width:150px;']);
                break;
            case 'repayment_amount':
            case 'chargeback_amount':
                $instance = $this->getInstance();
                $input = Html::activeTextInput($instance, $this->attr, ['id' => "repayment-{$this->attr}-{$this->id}",'class' => 'form-control text-end formatted']);
                $tag = '<div class="hstack gap-2"><div style="width:150px;"><div class="input-group">{input}<span class="input-group-text">円<sub>(税込)</sub></span></div></div>';
                $tag = strtr($tag, ['{input}' => $input]);
                break;
            case 'repayment_type_id':
                $instance = $this->getInstance();
                $input = Html::activeDropDownList($instance, $this->attr, RepaymentType::getTypes(), ['id' => "repayment-{$this->attr}-{$this->id}",'class' => 'form-control form-select']);
                $tag = '<div style="width:150px;">{input}</div>';
                $tag = strtr($tag, ['{input}' => $input]);
                break;
            default:
                $tag = '<span>適用外です。</span>';
        }
        return $tag;
    }

    public function getMonthlyCharge()
    {
        $term = new \DateTime($this->instance->processed);
        $monthlyCharge = MonthlyCharge::find()->where([
            'contract_detail_id' => $this->instance->contract_detail_id,
            'DATE_FORMAT(term, "%Y%m")' => $term->format('Ym')
        ])->limit(1)->one();
        return $monthlyCharge;
    }


    public function updateValue()
    {
        $instance = $this->instance;
        $oldValue = $instance->{$this->attr};
        $instance->{$this->attr} = $this->value;
        if ($instance->validate()) {
            switch($instance->repayment_type_id) {
                //前払いリース料 -> 回収予定額を「0」に
                case 5:
                    $monthlyCharge = $this->monthlyCharge;
                    if ($monthlyCharge) {
                        $monthlyCharge->charge_amount = 0;
                        $monthlyCharge->temporary_charge_amount = 0;
                        $monthlyCharge->save();
                    }
                    break;
                //解約,STOP -> 以降の回収予定額を[0]に
                case 11:
                case 12:
                    break;
            }











            if (!$instance->save()) {
                return [
                    'success' => false,
                    'errors' => $instance->errors,
                    'oldValue' => $oldValue,
                    'value' => $this->value,
                ];
            }
            else {
                return [
                    'success' => true,
                    'oldValue' => $oldValue,
                    'value' => $this->value,
                ];
            }
        }
        else return [
            'success' => false,
            'errors' => $instance->errors,
        ];
    }
}