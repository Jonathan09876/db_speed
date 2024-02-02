<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "import_repayment".
 *
 * @property int $import_repayment_id
 * @property string $import_contract_detail_id
 * @property string $repayment_type
 * @property float $repayment_amount
 * @property string $processed
 * @property string $registered
 */
class ImportRepayment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'import_repayment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['repayment_amount'], 'filter', 'filter' => [$this, 'digitOnly']],
            [['import_contract_detail_id', 'repayment_type', 'repayment_amount', 'processed', 'registered'], 'required'],
            [['repayment_amount'], 'number'],
            [['processed', 'registered'], 'safe'],
            [['import_contract_detail_id', 'repayment_type'], 'string', 'max' => 64],
        ];
    }

    public function digitOnly($val)
    {
        return preg_replace('/[^0-9]+/', '', $val);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'import_repayment_id' => 'Import Repayment ID',
            'import_contract_detail_id' => 'Import Contract Detail ID',
            'repayment_type' => 'Repayment Type',
            'repayment_amount' => 'Repayment Amount',
            'processed' => 'Processed',
            'registered' => 'Registered',
        ];
    }
}
