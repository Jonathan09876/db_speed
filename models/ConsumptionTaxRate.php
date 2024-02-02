<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "consumption_tax_rate".
 *
 * @property int $consumption_tax_rate_id
 * @property string $application_from
 * @property string|null $application_to
 * @property float $rate
 */
class ConsumptionTaxRate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'consumption_tax_rate';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['application_from', 'rate'], 'required'],
            [['application_from', 'application_to'], 'safe'],
            [['rate'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'consumption_tax_rate_id' => 'Consumption Tax Rate ID',
            'application_from' => 'Application From',
            'application_to' => 'Application To',
            'rate' => 'Rate',
        ];
    }

    public static function getRates()
    {
        return ArrayHelper::map(self::find()->all(), 'rate', function($data){
            return $data->rateString;
        });
    }

    public function getRateString()
    {
        return ($this->rate * 100) . '%';
    }

    public static function getCurrentRate()
    {
        $date = date('Y-m-d');
        $query = self::find()->where(['and', ['<=', 'application_from', $date], ['>=', 'IFNULL(application_to,\'2099-12-31\')', $date]]);
        $current = $query->limit(1)->one();
        return $current;
    }

    public static function getTheRate($date)
    {
        $query = self::find()->where(['and', ['<=', 'application_from', $date], ['>=', 'IFNULL(application_to,\'2099-12-31\')', $date]]);
        $rate = $query->limit(1)->one();
        return $rate;
    }
}
