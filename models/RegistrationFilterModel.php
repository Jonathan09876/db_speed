<?php

namespace app\models;

use Yii;

class RegistrationFilterModel extends \yii\base\Model
{
    public $attr;
    public $word;

    public function rules()
    {
        return [
            [['attr'], 'required'],
            [['attr'], 'in', 'range' => ['code', 'name', 'repayment_type_id', 'contract_code']],
            [['word'], 'safe'],
        ];
    }

    public function formName()
    {
        return '';
    }

    public function updateFilter()
    {
        $session = Yii::$app->session;
        $session["filter-{$this->attr}"] = $this->word;
        return [
            'success' => true,
            'code' => $session['filter-code'] ?? '',
            'name' => $session['filter-name'] ?? '',
            'repayment_type_id' => $session['filter-repayment_type_id'] ?? '',
            'contract_code' => $session['filter-contract_code'] ?? '',
        ];
    }
}