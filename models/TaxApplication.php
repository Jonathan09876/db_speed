<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "tax_application".
 *
 * @property int $tax_application_id
 * @property string|null $application_name
 * @property float|null $tax_rate
 * @property int|null $fixed
 * @property int $disp_order
 * @property string|null $removed
 */
class TaxApplication extends \yii\db\ActiveRecord
{
    static $fixed_patterns = [
        '1' => '固定',
        '0' => '法令に基づき変動'
    ];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tax_application';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tax_rate'], 'number'],
            [['fixed'], 'integer'],
            [['application_name'], 'string', 'max' => 64],
            [['removed'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'tax_application_id' => 'Tax Application ID',
            'application_name' => '適用名',
            'tax_rate' => '税率',
            'fixed' => '税率区分',
        ];
    }

    public function beforeSave($insert)
    {
        parent::beforeSave($insert);
        if ($insert) {
            $this->disp_order = self::find()->max('disp_order') + 1;
        }
        return true;
    }

    public static function getTaxApplications()
    {
        return ArrayHelper::map(self::find()->where(['removed' => null])->orderBy(['disp_order' => SORT_ASC])->all(), 'tax_application_id', 'application_name');
    }
}
