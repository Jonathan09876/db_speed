<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "lease_payment".
 *
 * @property int $lease_payment_id
 * @property int $contract_detail_id
 * @property int $monthly_payment_id
 * @property float $payment_amount
 * @property string $processed
 * @property string $registered
 * @property string|null $memo
 * @property string|null $removed
 * @property int|null $removed_by
 */
class LeasePayment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'lease_payment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contract_detail_id', 'payment_amount', 'processed', 'registered'], 'required'],
            [['contract_detail_id', 'removed_by'], 'integer'],
            [['payment_amount'], 'number'],
            [['processed', 'registered', 'removed'], 'safe'],
            [['memo'], 'string', 'max' => 256],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'lease_payment_id' => 'Lease Payment ID',
            'contract_detail_id' => 'Contract Detail ID',
            'payment_amount' => 'Payment Amount',
            'processed' => 'Processed',
            'registered' => 'Registered',
            'memo' => 'Memo',
            'removed' => 'Removed',
            'removed_by' => 'Removed By',
        ];
    }

    public static function getSibling($detail_id, $term, $relative_value)
    {
        $sign = $relative_value > 0 ? '-' : '+';
        $value = abs($relative_value);
        return self::find()->where(['and',
            ['contract_detail_id' => $detail_id],
            ["DATE_FORMAT(processed {$sign} INTERVAL {$value} MONTH, '%Y%m')" => $term]
        ])->limit(1)->one();
    }


    public static function getTotal($provider, $attribute)
    {
        $total = 0;
        foreach($provider as $model) {
            $payment_amount = LeasePayment::find()->where([
                'contract_detail_id' => $model->contract_detail_id,
                'DATE_FORMAT(processed, "%Y%m")' => (new \DateTime($model->term))->format('Ym')
            ])->sum($attribute);
            $total += $payment_amount;
        }
        return $total;
    }

    public function getOrderCount()
    {
        return self::find()->where(['and',
                ['contract_detail_id' => $this->contract_detail_id],
                ['<=', 'processed', $this->processed],
                ['<', 'lease_payment_id', $this->lease_payment_id],
            ])->count() + 1;
    }

}
