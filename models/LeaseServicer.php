<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "lease_servicer".
 *
 * @property int $lease_servicer_id
 * @property string $name
 * @property string $shorten_name
 * @property int $for_internal
 * @property int|null $bank_account_id
 * @property int|null $transfer_date
 * @property string|null $removed
 *
 * @property BankAccount $bankAccount
 * @property LeaseContract[] $leaseContracts
 */
class LeaseServicer extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'lease_servicer';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'shorten_name'], 'required'],
            [['for_internal', 'bank_account_id', 'transfer_date'], 'integer'],
            [['removed'], 'safe'],
            [['name'], 'string', 'max' => 256],
            [['shorten_name'], 'string', 'max' => 16],
            [['bank_account_id'], 'exist', 'skipOnError' => true, 'targetClass' => BankAccount::class, 'targetAttribute' => ['bank_account_id' => 'bank_account_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'lease_servicer_id' => 'Lease Servicer ID',
            'name' => 'リース会社名',
            'shorten_name' => '省略名',
            'for_internal' => '自社向け',
            'bank_account_id' => '振込先銀行口座',
            'transfer_date' => '毎月振込日',
            'removed' => 'Removed',
        ];
    }

    /**
     * Gets query for [[BankAccount]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBankAccount()
    {
        return $this->hasOne(BankAccount::class, ['bank_account_id' => 'bank_account_id']);
    }

    /**
     * Gets query for [[LeaseContracts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLeaseContracts()
    {
        return $this->hasMany(LeaseContract::class, ['lease_servicer_id' => 'lease_servicer_id']);
    }

    public static function getServicers()
    {
        return ArrayHelper::map(self::find()->where(['removed' => null])->all(), 'lease_servicer_id', 'shorten_name');
    }
}
