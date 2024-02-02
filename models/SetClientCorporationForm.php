<?php

namespace app\models;

use Yii;

class SetClientCorporationForm extends \yii\base\Model
{
    public $client_corporation_id;

    public function formName()
    {
        return '';
    }

    public function rules()
    {
        return [
            [['client_corporation_id'], 'each', 'rule' => ['exist', 'targetClass' => ClientCorporation::class, 'targetAttribute' => 'client_corporation_id']],
        ];
    }

    public function updateSession()
    {
        $session = Yii::$app->session;
        $session['customer-client-corporations'] = $this->client_corporation_id;
    }
}