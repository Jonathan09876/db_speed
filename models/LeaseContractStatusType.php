<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "lease_contract_status_type".
 *
 * @property int $lease_contract_status_type_id
 * @property string $type
 * @property string|null $bg_color
 * @property int $disp_order
 * @property string|null $removed
 *
 * @property LeaseContractStatus[] $leaseContractStatuses
 */
class LeaseContractStatusType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'lease_contract_status_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type'], 'required'],
            [['disp_order'], 'integer'],
            [['bg_color', 'removed'], 'safe'],
            [['type'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'lease_contract_status_type_id' => 'Lease Contract Status Type ID',
            'type' => '契約ステータス',
            'bg_color' => '背景色',
            'disp_order' => '表示順',
            'removed' => 'Removed',
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->disp_order = Yii::$app->db->createCommand('SELECT MAX(`disp_order`)+1 FROM `lease_contract_status_type` WHERE `removed` IS NULL')
                ->queryScalar();
        }
        return true;
    }

    /**
     * Gets query for [[LeaseContractStatuses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLeaseContractStatuses()
    {
        return $this->hasMany(LeaseContractStatus::class, ['lease_contract_status_type_id' => 'lease_contract_status_type_id']);
    }

    public function getLeaseContracts()
    {
        return LeaseContract::find()->alias('lc')
            ->leftJoin('lease_contract_status lcs', 'lc.lease_contract_id=lcs.lease_contract_id')
            ->leftJoin('lease_contract_status lcs2', 'lcs.lease_contract_id=lcs2.lease_contract_id AND lcs.lease_contract_status_id < lcs.lease_contract_status_id')
            ->where(['lcs.lease_contract_status_type_id' => $this->lease_contract_status_type_id, 'lcs2.lease_contract_status_id' => null]);
    }

    public static function getTypes()
    {
        return ArrayHelper::map(self::find()->where(['removed' => null])->all(), 'lease_contract_status_type_id', 'type');
    }
}
