<?php

namespace app\models;

use Yii;
use yii\db\Exception;
use yii\web\UploadedFile;

/**
 * This is the model class for table "import_customer".
 *
 * @property int $import_customer_id
 * @property int $client_corporation_id
 * @property string $customer_code
 * @property string $name
 * @property string|null $position
 * @property string|null $transfer_name
 * @property int|null $use_transfer_name
 * @property string|null $zip_code
 * @property string|null $address
 * @property string|null $address_optional
 * @property string|null $phone_1
 * @property string|null $phone_2
 * @property string|null $phone_3
 * @property string|null $mail_address_1
 * @property string|null $mail_address_2
 * @property string|null $mail_address_3
 * @property int $repayment_pattern_id
 * @property string|null $account_transfer_code
 * @property string|null $bank_name
 * @property string|null $bank_code
 * @property string|null $branch_name
 * @property string|null $branch_code
 * @property int|null $account_type
 * @property string|null $account_number
 * @property string|null $account_name
 * @property string|null $account_name_kana
 * @property string|null $memo
 */
class ImportCustomer extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'import_customer';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['import_customer_id', 'client_corporation_id', 'customer_code', 'name', 'repayment_pattern_id'], 'required'],
            [['import_customer_id', 'client_corporation_id', 'use_transfer_name', 'repayment_pattern_id', 'account_type'], 'integer'],
            [['memo'], 'string'],
            [['customer_code', 'account_transfer_code'], 'string', 'max' => 64],
            [['name', 'position', 'transfer_name', 'address', 'address_optional', 'mail_address_1', 'mail_address_2', 'mail_address_3', 'account_name', 'account_name_kana'], 'string', 'max' => 256],
            [['zip_code', 'phone_1', 'phone_2', 'phone_3'], 'string', 'max' => 32],
            [['bank_name', 'branch_name'], 'string', 'max' => 128],
            [['bank_code', 'branch_code', 'account_number'], 'string', 'max' => 16],
            [['import_customer_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'import_customer_id' => 'Import Customer ID',
            'client_corporation_id' => 'Client Corporation ID',
            'customer_code' => 'Customer Code',
            'name' => 'Name',
            'position' => 'Position',
            'transfer_name' => 'Transfer Name',
            'use_transfer_name' => 'Use Transfer Name',
            'zip_code' => 'Zip Code',
            'address' => 'Address',
            'address_optional' => 'Address Optional',
            'phone_1' => 'Phone 1',
            'phone_2' => 'Phone 2',
            'phone_3' => 'Phone 3',
            'mail_address_1' => 'Mail Address 1',
            'mail_address_2' => 'Mail Address 2',
            'mail_address_3' => 'Mail Address 3',
            'repayment_pattern_id' => 'Repayment Pattern ID',
            'account_transfer_code' => 'Account Transfer Code',
            'bank_name' => 'Bank Name',
            'bank_code' => 'Bank Code',
            'branch_name' => 'Branch Name',
            'branch_code' => 'Branch Code',
            'account_type' => 'Account Type',
            'account_number' => 'Account Number',
            'account_name' => 'Account Name',
            'account_name_kana' => 'Account Name Kana',
            'memo' => 'Memo',
        ];
    }
}
