<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "repayment_pattern".
 *
 * @property int $repayment_pattern_id
 * @property int $account_transfer_agency_id
 * @property string $name
 * @property string $target_month
 * @property int $transfer_date
 * @property int|null $repayment_type_id
 * @property string|null $bg_color
 *
 * @property AccountTransferAgency $accountTransferAgency
 * @property ClientContract[] $clientContracts
 * @property RepaymentType $repaymentType
 */
class RepaymentPattern extends \yii\db\ActiveRecord
{
    static $target_months = [
        'current' => '当月',
        'next' => '翌月',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'repayment_pattern';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['account_transfer_agency_id', 'name', 'target_month', 'transfer_date'], 'required'],
            [['account_transfer_agency_id', 'transfer_date', 'repayment_type_id'], 'integer'],
            [['target_month'], 'string'],
            [['bg_color'], 'string', 'max' => 16],
            [['name'], 'string', 'max' => 64],
            [['account_transfer_agency_id'], 'exist', 'skipOnError' => true, 'targetClass' => AccountTransferAgency::class, 'targetAttribute' => ['account_transfer_agency_id' => 'account_transfer_agency_id']],
            [['repayment_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => RepaymentType::class, 'targetAttribute' => ['repayment_type_id' => 'repayment_type_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'repayment_pattern_id' => 'Repayment Pattern ID',
            'account_transfer_agency_id' => '回収先',
            'name' => '回収条件名',
            'target_month' => '対象月',
            'transfer_date' => '口座振替日',
            'repayment_type_id' => '回収区分',
            'bg_color' => '背景色',
        ];
    }

    /**
     * Gets query for [[AccountTransferAgency]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAccountTransferAgency()
    {
        return $this->hasOne(AccountTransferAgency::class, ['account_transfer_agency_id' => 'account_transfer_agency_id']);
    }

    public function getRepaymentType()
    {
        return $this->hasOne(RepaymentType::class, ['repayment_type_id' => 'repayment_type_id']);
    }

    public static function getPatterns()
    {
        return ArrayHelper::map(self::find()->all(), 'repayment_pattern_id', 'name');
    }
}
