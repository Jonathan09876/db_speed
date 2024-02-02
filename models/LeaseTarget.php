<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "lease_target".
 *
 * @property int $lease_target_id
 * @property string $name
 * @property string|null $registration_number
 * @property string|null $attributes
 * @property string|null $memo
 *
 * @property LeaseContract[] $leaseContracts
 */
class LeaseTarget extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'lease_target';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['name'], 'required'],
            [['attributes', 'memo'], 'string'],
            [['name'], 'string', 'max' => 256],
            [['registration_number'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'lease_target_id' => 'Lease Target ID',
            'name' => '物件名',
            'registration_number' => '登録ナンバー',
            'attributes' => '物件属性',
            'memo' => '物件備考',
        ];
    }

    /**
     * Gets query for [[LeaseContracts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLeaseContracts()
    {
        return $this->hasMany(LeaseContract::class, ['lease_target_id' => 'lease_target_id']);
    }
}
