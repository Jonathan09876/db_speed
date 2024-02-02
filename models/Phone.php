<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "phone".
 *
 * @property int $phone_id
 * @property int $customer_id
 * @property string $number
 * @property string $number_search
 *
 * @property Customer $customer
 */
class Phone extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'phone';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['number'], 'filter', 'filter' => [$this, 'zen2han']],
            [['customer_id', 'number', 'number_search'], 'required'],
            [['customer_id'], 'integer'],
            [['number', 'number_search'], 'string', 'max' => 64],
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customer::class, 'targetAttribute' => ['customer_id' => 'customer_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'phone_id' => 'Phone ID',
            'customer_id' => 'Customer ID',
            'number' => 'Number',
            'number_search' => 'Number Search',
        ];
    }

    /**
     * Gets query for [[Customer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::class, ['customer_id' => 'customer_id']);
    }

    public function zen2han($value)
    {
        return mb_convert_kana($value, "as", 'UTF-8');
    }

    public function beforeValidate()
    {
        $this->number_search = preg_replace('/[^\d]+/', '', $this->number);
        return true;
    }

    public static function register($customer_id, $number)
    {
        $instance = new Phone([
            'customer_id' => $customer_id,
            'number' => $number,
        ]);
        return $instance->save();
    }
}
