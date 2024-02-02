<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "repayment_type".
 *
 * @property int $repayment_type_id
 * @property string $type
 * @property string|null $bg_color
 * @property int $disp_order
 * @property string|null $removed
 *
 * @property Repayment[] $repayments
 */
class RepaymentType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'repayment_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type'], 'required'],
            [['disp_order'], 'integer'],
            [['removed'], 'safe'],
            [['type'], 'string', 'max' => 64],
            [['bg_color'], 'string', 'max' => 16],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'repayment_type_id' => 'Repayment Type ID',
            'type' => '区分',
            'bg_color' => '背景色',
            'disp_order' => '表示順',
            'removed' => 'Removed',
        ];
    }

    /**
     * Gets query for [[Repayments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRepayments()
    {
        return $this->hasMany(Repayment::class, ['repayment_type_id' => 'repayment_type_id']);
    }

    public static function getTypes()
    {
        return ArrayHelper::map(RepaymentType::find()->where(['removed' => null])->orderBy(['disp_order'=>SORT_ASC])->all(), 'repayment_type_id', 'type');
    }

    public static function getDefaultTypes()
    {
        $query = RepaymentType::find()->alias('rt')
            ->leftJoin('repayment_pattern rp', 'rt.repayment_type_id=rp.repayment_type_id')
            ->orderBy(['disp_order'=>SORT_ASC])
            ->where(['not', ['rp.repayment_pattern_id' => null]]);
        return ArrayHelper::map($query->all(), 'repayment_type_id', 'type');
    }
}
