<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "import_payment".
 *
 * @property int $import_payment_id
 * @property string $import_contract_detail_id
 * @property float $payment_amount
 * @property string $processed
 * @property string $registered
 */
class ImportPayment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'import_payment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['import_contract_detail_id', 'payment_amount', 'processed', 'registered'], 'required'],
            [['payment_amount'], 'number'],
            [['processed', 'registered'], 'safe'],
            [['import_contract_detail_id'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'import_payment_id' => 'Import Payment ID',
            'import_contract_detail_id' => 'Import Contract Detail ID',
            'payment_amount' => 'Payment Amount',
            'processed' => 'Processed',
            'registered' => 'Registered',
        ];
    }
}
