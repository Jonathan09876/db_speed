<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "lease_contract_status".
 *
 * @property int $lease_contract_status_id
 * @property int $lease_contract_id
 * @property int $lease_contract_status_type_id
 * @property string $registered
 * @property int $registered_by
 *
 * @property LeaseContract $leaseContract
 * @property LeaseContractStatusType $leaseContractStatusType
 * @property User $registeredBy
 */
class LeaseContractStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'lease_contract_status';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['lease_contract_id', 'lease_contract_status_type_id', 'registered', 'registered_by'], 'required'],
            [['lease_contract_id', 'lease_contract_status_type_id', 'registered_by'], 'integer'],
            [['registered'], 'safe'],
            [['lease_contract_id'], 'exist', 'skipOnError' => true, 'targetClass' => LeaseContract::class, 'targetAttribute' => ['lease_contract_id' => 'lease_contract_id']],
            [['lease_contract_status_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => LeaseContractStatusType::class, 'targetAttribute' => ['lease_contract_status_type_id' => 'lease_contract_status_type_id']],
            [['registered_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['registered_by' => 'user_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'lease_contract_status_id' => 'Lease Contract Status ID',
            'lease_contract_id' => 'Lease Contract ID',
            'lease_contract_status_type_id' => 'Lease Contract Status Type ID',
            'registered' => 'Registered',
            'registered_by' => 'Registered By',
        ];
    }

    /**
     * Gets query for [[LeaseContract]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLeaseContract()
    {
        return $this->hasOne(LeaseContract::class, ['lease_contract_id' => 'lease_contract_id']);
    }

    /**
     * Gets query for [[LeaseContractStatusType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLeaseContractStatusType()
    {
        return $this->hasOne(LeaseContractStatusType::class, ['lease_contract_status_type_id' => 'lease_contract_status_type_id']);
    }

    /**
     * Gets query for [[RegisteredBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegisteredBy()
    {
        return $this->hasOne(User::class, ['user_id' => 'registered_by']);
    }

    static function register($lease_contract_id, $lease_contract_status_type_id)
    {
        $leaseContract = LeaseContract::findOne($lease_contract_id);
        if (!$leaseContract->currentStatus || $leaseContract->currentStatus->lease_contract_status_type_id != $lease_contract_status_type_id) {
            //最新以外のステータスは保持しない
            Yii::$app->db->createCommand()->delete('lease_contract_status', [
                'lease_contract_id' => $lease_contract_id,
            ])->execute();
            $instance = new LeaseContractStatus([
                'lease_contract_id' => $lease_contract_id,
                'lease_contract_status_type_id' => $lease_contract_status_type_id,
                'registered' => date('Y-m-d H:i:s'),
                'registered_by' => isset(Yii::$app->user) ? Yii::$app->user->id : 1,
            ]);
            $instance->save();
        }
    }
}
