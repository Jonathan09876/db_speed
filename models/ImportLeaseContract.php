<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "import_lease_contract".
 *
 * @property string $import_lease_contract_id
 * @property string $import_customer_id
 * @property string $contract_pattern
 * @property string $contract_number
 * @property string $contract_code
 * @property string $contract_sub_code
 * @property string $contract_date
 * @property string $target_name
 * @property string $registration_number
 * @property string|null $target_attributes
 * @property string|null $target_memo
 * @property string|null $contract_status
 */
class ImportLeaseContract extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'import_lease_contract';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['import_lease_contract_id', 'import_customer_id', 'contract_pattern', 'contract_number', 'contract_code', 'contract_date', 'target_name', 'registration_number'], 'required'],
            [['contract_date'], 'safe'],
            [['target_attributes', 'target_memo'], 'string'],
            [['import_lease_contract_id', 'import_customer_id', 'contract_pattern', 'contract_number', 'contract_code', 'contract_sub_code', 'registration_number'], 'string', 'max' => 64],
            [['contract_status'], 'string', 'max' => 64],
            [['target_name'], 'string', 'max' => 256],
            [['import_lease_contract_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'import_lease_contract_id' => 'Import Lease Contract ID',
            'import_customer_id' => 'Import Customer ID',
            'contract_pattern' => 'Contract Pattern',
            'contract_number' => 'Contract Number',
            'contract_code' => 'Contract Code',
            'contract_sub_code' => 'Contract Sub Code',
            'contract_date' => 'Contract Date',
            'target_name' => 'Target Name',
            'registration_number' => 'Registration Number',
            'target_attributes' => 'Target Attributes',
            'target_memo' => 'Target Memo',
        ];
    }
}
