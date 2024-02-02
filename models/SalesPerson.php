<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "sales_person".
 *
 * @property int $sales_person_id
 * @property string $name
 * @property string|null $removed
 *
 * @property Customer[] $customers
 */
class SalesPerson extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sales_person';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['removed'], 'safe'],
            [['name'], 'string', 'max' => 256],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'sales_person_id' => 'Sales Person ID',
            'name' => '担当者名',
            'removed' => 'Removed',
        ];
    }

    /**
     * Gets query for [[Customers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomers()
    {
        return $this->hasMany(Customer::class, ['sales_person_id' => 'sales_person_id']);
    }

    public static function getPersons()
    {
        return ArrayHelper::map(self::find()->where(['removed' => null])->all(), 'sales_person_id', 'name');
    }
}
