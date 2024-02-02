<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "monthly_payment_span".
 *
 * @property int $contract_detail_id
 * @property string|null $first_term
 * @property string|null $last_term
 */
class MonthlyPaymentSpan extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'monthly_payment_span';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contract_detail_id'], 'required'],
            [['contract_detail_id'], 'integer'],
            [['first_term', 'last_term'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'contract_detail_id' => 'Contract Detail ID',
            'first_term' => 'First Term',
            'last_term' => 'Last Term',
        ];
    }
}
