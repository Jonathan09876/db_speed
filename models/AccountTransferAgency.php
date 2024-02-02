<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "account_transfer_agency".
 *
 * @property int $account_transfer_agency_id
 * @property string|null $code
 * @property string|null $name
 * @property float|null $transfer_fee
 * @property float|null $basic_charge
 * @property float|null $transfer_charge
 * @property int|null $registration_date
 *
 * @property RepaymentPattern[] $repaymentPatterns
 */
class AccountTransferAgency extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'account_transfer_agency';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'name', 'transfer_fee', 'basic_charge', 'transfer_charge', 'registration_date'], 'required'],
            [['transfer_fee', 'basic_charge', 'transfer_charge'], 'number'],
            [['registration_date'], 'integer', 'min' => 1, 'max' => 31],
            [['code'], 'string', 'max' => 64],
            [['name'], 'string', 'max' => 256],
            [['code'], 'unique', 'targetClass' => AccountTransferAgency::class, 'targetAttribute' => 'code']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'account_transfer_agency_id' => 'Account Transfer Agency ID',
            'code' => '回収先コード',
            'name' => '回収先名',
            'transfer_fee' => '振替手数料',
            'basic_charge' => '基本料金',
            'transfer_charge' => '振込手数料',
            'registration_date' => '申請日',
        ];
    }

    /**
     * Gets query for [[RepaymentPatterns]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRepaymentPatterns()
    {
        return $this->hasMany(RepaymentPattern::class, ['account_transfer_agency_id' => 'account_transfer_agency_id']);
    }
}
