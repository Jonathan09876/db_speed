<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "bank_account".
 *
 * @property int $bank_account_id
 * @property string|null $bank_name
 * @property string|null $bank_code
 * @property string|null $branch_name
 * @property string|null $branch_code
 * @property int|null $account_type
 * @property string|null $account_number
 * @property string|null $account_name
 * @property string|null $account_name_kana
 *
 * @property Customer[] $customers
 * @property LeaseServicer[] $leaseservicers
 */
class BankAccount extends \yii\db\ActiveRecord
{
    static $types = [
        '1' => '普通',
        '2' => '当座',
        '3' => '貯蓄',
    ];

    static $fallback_values = [
        'bank_name' => '-未登録-',
        'bank_code' => '0000',
        'branch_name' => '-未登録-',
        'branch_code' => '000',
        'account_type' => '1',
        'account_number' => '0000000',
        'account_name' => '-未登録-',
        'account_name_kana' => '-未登録-',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'bank_account';
    }

    public function scenarios()
    {
        return [
            'default' => ['bank_name', 'bank_code', 'branch_name', 'branch_code', 'account_type', 'account_number', 'account_name', 'account_name_kana'],
            'lease-servicer' => ['bank_name', 'bank_code', 'branch_name', 'branch_code', 'account_type', 'account_number', 'account_name', 'account_name_kana'],
            'customer' => ['bank_name', 'bank_code', 'branch_name', 'branch_code', 'account_type', 'account_number', 'account_name', 'account_name_kana']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['bank_name', 'bank_code', 'branch_name', 'branch_code', 'account_type', 'account_number', 'account_name', 'account_name_kana'], 'required', 'on' => 'default'],
            [['bank_name', 'bank_code', 'branch_name', 'branch_code', 'account_type', 'account_number', 'account_name', 'account_name_kana'], 'safe', 'on' => ['lease-servicer', 'customer']],
            [['account_type'], 'integer'],
            [['bank_name', 'branch_name', 'account_name_kana'], 'string', 'max' => 256],
            [['bank_code'], 'string', 'max' => 4],
            [['branch_code'], 'string', 'max' => 3],
            [['account_number'], 'string', 'max' => 16],
            [['account_name'], 'string', 'max' => 128],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'bank_account_id' => 'Bank Account ID',
            'bank_name' => '金融機関名',
            'bank_code' => '金融機関コード',
            'branch_name' => '支店名',
            'branch_code' => '支店コード',
            'account_type' => '口座区分',
            'account_number' => '口座番号',
            'account_name' => '口座名義',
            'account_name_kana' => '口座名義（カナ）',
        ];
    }

    public function beforeValidate()
    {
        foreach(self::$fallback_values as $key => $value) {
            if (empty($this->{$key})) {
                $this->{$key} = $value;
            }
        }
        return true;
    }

    /**
     * Gets query for [[Customers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomers()
    {
        return $this->hasMany(Customer::class, ['bank_account_id' => 'bank_account_id']);
    }

    /**
     * Gets query for [[Leaseservicers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLeaseservicers()
    {
        return $this->hasMany(LeaseServicer::class, ['bank_account_id' => 'bank_account_id']);
    }

    public function __toString(): string
    {
        $type = static::$types[$this->account_type];
        return "<div class=\"hstack gap-2\">" .
            "<div class=\"col-3\"><div class=\"input-group\"><span class=\"input-group-text\">金融機関名</span><input type=\"text\" class=\"form-control\" value=\"{$this->bank_name}\" readonly></div></div>" .
            "<div class=\"col-2\"><div class=\"input-group\"><span class=\"input-group-text\">支店名</span><input type=\"text\" class=\"form-control\" value=\"{$this->branch_name}\" readonly></div></div>" .
            "<div class=\"col-2 col-2-narrow\"><div class=\"input-group\"><span class=\"input-group-text\">{$type}</span><input type=\"text\" class=\"form-control\" value=\"{$this->account_number}\" readonly></div></div>" .
            "<div class=\"col-4\"><div class=\"input-group\"><span class=\"input-group-text\">名義</span><input type=\"text\" class=\"form-control\" value=\"{$this->account_name_kana}\" readonly></div></div>" .
        "</div>";
    }
}
