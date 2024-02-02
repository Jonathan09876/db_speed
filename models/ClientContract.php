<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "client_contract".
 *
 * @property int $client_contract_id
 * @property int $client_corporation_id
 * @property int $repayment_pattern_id
 * @property string $account_transfer_code
 *
 * @property ClientCorporation $clientCorporation
 * @property RepaymentPattern $repaymentPattern
 */
class ClientContract extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'client_contract';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['client_corporation_id', 'repayment_pattern_id'], 'required'],
            [['client_corporation_id', 'repayment_pattern_id'], 'integer'],
            [['account_transfer_code'], 'string', 'max' => 128],
            [['client_corporation_id'], 'exist', 'skipOnError' => true, 'targetClass' => ClientCorporation::class, 'targetAttribute' => ['client_corporation_id' => 'client_corporation_id']],
            [['repayment_pattern_id'], 'exist', 'skipOnError' => true, 'targetClass' => RepaymentPattern::class, 'targetAttribute' => ['repayment_pattern_id' => 'repayment_pattern_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'client_contract_id' => 'Client Contract ID',
            'client_corporation_id' => '契約会社',
            'repayment_pattern_id' => '支払条件',
            'account_transfer_code' => '振替管理コード',
        ];
    }

    public function beforeValidate()
    {
        if (empty($this->account_transfer_code)) {
            $this->account_transfer_code = '-未登録-';
        }
        return true;
    }

    /**
     * Gets query for [[ClientCorporation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClientCorporation()
    {
        return $this->hasOne(ClientCorporation::class, ['client_corporation_id' => 'client_corporation_id']);
    }

    /**
     * Gets query for [[RepaymentPattern]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRepaymentPattern()
    {
        return $this->hasOne(RepaymentPattern::class, ['repayment_pattern_id' => 'repayment_pattern_id']);
    }
}
