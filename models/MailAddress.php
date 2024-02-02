<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "mail_address".
 *
 * @property int $mail_address_id
 * @property int $customer_id
 * @property string $mail_address
 *
 * @property Customer $customer
 */
class MailAddress extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mail_address';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mail_address'], 'filter', 'filter' => [$this, 'zen2han']],
            [['customer_id', 'mail_address'], 'required'],
            [['customer_id'], 'integer'],
            [['mail_address'], 'string', 'max' => 256],
            [['mail_address'], 'email'],
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customer::class, 'targetAttribute' => ['customer_id' => 'customer_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'mail_address_id' => 'Mail Address ID',
            'customer_id' => 'Customer ID',
            'mail_address' => 'Mail Address',
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

    public static function register($customer_id, $mail_address)
    {
        $instance = new MailAddress([
            'customer_id' => $customer_id,
            'mail_address' => $mail_address,
        ]);
        return $instance->save();
    }
}
