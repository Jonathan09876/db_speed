<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "location".
 *
 * @property int $location_id
 * @property string|null $zip_code
 * @property string $address
 * @property string|null $address_optional
 *
 * @property Customer[] $customers
 */
class Location extends \yii\db\ActiveRecord
{
    static $fallback_values = [
        'zip_code' => '000-0000',
        'address' => '-未登録-',
        'address_optional' => '-未登録-',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'location';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['address'], 'required'],
            [['zip_code'], 'string', 'max' => 16],
            [['address', 'address_optional'], 'string', 'max' => 256],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'location_id' => 'Location ID',
            'zip_code' => '郵便番号',
            'address' => '住所',
            'address_optional' => '住所その他',
        ];
    }

    public function beforeValidate()
    {
        foreach(static::$fallback_values as $key => $val) {
            $this->{$key} = $val;
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
        return $this->hasMany(Customer::class, ['location_id' => 'location_id']);
    }
}
