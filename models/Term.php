<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "term".
 *
 * @property int $term_id
 * @property string $term
 *
 */
class Term extends \yii\db\ActiveRecord
{
    public $relative_month;
    static $details = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'term';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['term'], 'required'],
            [['term'], 'safe'],
            [['relative_month'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'term_id' => 'Term ID',
            'term' => 'Term',
        ];
    }

    public function getContractDetail($contract_detail_id)
    {
        if (!isset(static::$details[$contract_detail_id])) {
            static::$details[$contract_detail_id] = ContractDetail::findOne($contract_detail_id);
        }
        return static::$details[$contract_detail_id];
    }

    public function getTermDateTime()
    {
        return new \DateTime($this->term);
    }

    public function getMonthlyCharges($contract_detail_id)
    {
        $contractDetail = $this->getContractDetail($contract_detail_id);
        $repaymentPattern = $contractDetail->repaymentPattern;
        $term = $this->termDateTime;
        if ($repaymentPattern->target_month == 'next') {
            $term->modify('-1 month');
        }
        $query = MonthlyCharge::find()
            ->where([
                'contract_detail_id' => $contract_detail_id,
                'term' => $term->format('Y-m-d')
            ]);
        $query->multiple = true;
        return $query->all();
    }

    public function getMonthlyPayments($contract_detail_id)
    {
        $query = MonthlyPayment::find()
            ->where(['contract_detail_id' => $contract_detail_id, 'term' => $this->term]);
        return $query->all();
    }

    public function getCurrentRepayments($contract_detail_id)
    {
        $query = Repayment::find()->where([
            'and',
            ['contract_detail_id' => $contract_detail_id],
            ['between', 'processed', $this->termDateTime->format('Y-m-01 00:00:00'), $this->termDateTime->format('Y-m-t 23:59:59')]
        ]);
        return $query->all();
    }
}
