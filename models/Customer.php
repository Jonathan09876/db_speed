<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "customer".
 *
 * @property int $customer_id
 * @property string $customer_code
 * @property int $client_contract_id
 * @property string $name
 * @property string|null $position
 * @property string|null $transfer_name
 * @property int|null $use_transfer_name
 * @property int $bank_account_id
 * @property int $sales_person_id
 * @property int $location_id
 * @property string|null $memo
 * @property string|null $removed
 *
 * @property BankAccount $bankAccount
 * @property LeaseContract[] $leaseContracts
 * @property Location $location
 * @property MailAddress[] $mailAddresses
 * @property Phone[] $phones
 * @property SalesPerson $salesPerson
 */
class Customer extends \yii\db\ActiveRecord
{
    public $phoneModels = [];
    public $mailAddressModels = [];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'customer';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['customer_code', 'name', 'client_contract_id', 'bank_account_id', 'location_id'], 'required'],
            [['use_transfer_name', 'client_contract_id', 'bank_account_id', 'sales_person_id', 'location_id'], 'integer'],
            [['memo'], 'string'],
            [['removed'], 'safe'],
            [['customer_code'], 'string', 'max' => 64],
            [['customer_code'], 'checkUniqueInClient', 'skipOnError' => false],
            [['name', 'position', 'transfer_name'], 'string', 'max' => 256],
            [['client_contract_id'], 'exist', 'skipOnError' => true, 'targetClass' => ClientContract::class, 'targetAttribute' => ['client_contract_id' => 'client_contract_id']],
            [['bank_account_id'], 'exist', 'skipOnError' => true, 'targetClass' => BankAccount::class, 'targetAttribute' => ['bank_account_id' => 'bank_account_id']],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => Location::class, 'targetAttribute' => ['location_id' => 'location_id']],
            [['sales_person_id'], 'exist', 'skipOnError' => true, 'targetClass' => SalesPerson::class, 'targetAttribute' => ['sales_person_id' => 'sales_person_id']],
        ];
    }

    public function checkUniqueInClient($attr,$params)
    {
        $check = Customer::find()->alias('c')
            ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
            ->where(['and',
            ['cc.client_corporation_id' => $this->clientContract->client_corporation_id],
            ["LPAD(`customer_code`, 8, '0')" => str_pad($this->customer_code, 8, '0', STR_PAD_LEFT)],
            ['not', ['c.customer_id' => $this->customer_id]],
        ])->count();
        if ($check > 0) {
            $this->addError('customer_code', str_pad($this->customer_code, 8, '0', STR_PAD_LEFT) .'::他の登録情報と値が重複しています。');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'customer_id' => 'Customer ID',
            'customer_code' => '得意先コード',
            'client_contract_id' => 'クライアント契約ID',
            'name' => '得意先名',
            'position' => '部署',
            'transfer_name' => '個人名',
            'use_transfer_name' => '引落名',
            'bank_account_id' => 'Bank Account ID',
            'sales_person_id' => '営業担当者',
            'location_id' => 'Location ID',
            'memo' => '備考',
            'removed' => 'Removed',
        ];
    }

    public function beforeValidate()
    {
        if (empty($this->sales_person_id)) {
            $this->sales_person_id = 9; //担当者未設定
        }
        return true;
    }

    public function getClientContract()
    {
        return $this->hasOne(ClientContract::class, ['client_contract_id' => 'client_contract_id']);
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
        return $this->hasMany(LeaseContract::class, ['customer_id' => 'customer_id']);
    }

    /**
     * Gets query for [[Location]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLocation()
    {
        return $this->hasOne(Location::class, ['location_id' => 'location_id']);
    }

    /**
     * Gets query for [[MailAddresses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMailAddresses()
    {
        return $this->hasMany(MailAddress::class, ['customer_id' => 'customer_id']);
    }

    /**
     * Gets query for [[Phones]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPhones()
    {
        return $this->hasMany(Phone::class, ['customer_id' => 'customer_id']);
    }

    /**
     * Gets query for [[SalesPerson]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSalesPerson()
    {
        return $this->hasOne(SalesPerson::class, ['sales_person_id' => 'sales_person_id']);
    }

    public function getName()
    {
        return $this->use_transfer_name ? $this->transfer_name : $this->name;
    }
}
