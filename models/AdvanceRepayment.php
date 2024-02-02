<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "advance_repayment".
 *
 * @property int $advance_repayment_id
 * @property int $contract_detail_id
 * @property float $repayment_amount
 * @property string $processed
 * @property string $registered
 * @property string|null $memo
 *
 * @property ContractDetail $contractDetail
 */
class AdvanceRepayment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'advance_repayment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contract_detail_id', 'repayment_amount', 'processed', 'registered'], 'required'],
            [['contract_detail_id'], 'integer'],
            [['repayment_amount'], 'number'],
            [['processed', 'registered'], 'safe'],
            [['memo'], 'string', 'max' => 256],
            [['contract_detail_id'], 'exist', 'skipOnError' => true, 'targetClass' => ContractDetail::class, 'targetAttribute' => ['contract_detail_id' => 'contract_detail_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'advance_repayment_id' => 'Advance Repayment ID',
            'contract_detail_id' => 'Contract Detail ID',
            'repayment_amount' => 'Repayment Amount',
            'processed' => 'Processed',
            'registered' => 'Registered',
            'memo' => 'Memo',
        ];
    }

    /**
     * Gets query for [[ContractDetail]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getContractDetail()
    {
        return $this->hasOne(ContractDetail::class, ['contract_detail_id' => 'contract_detail_id']);
    }

    public function getRepayment_id()
    {
        return $this->advance_repayment_id;
    }

    public static function getTotal($provider, $attribute)
    {
        $total = 0;
        foreach($provider as $model) {
            $repayments = $model->advanceRepayments ?? false;
            $total += $repayments ? $repayments->{$attribute} : 0;
        }
        return $total;
    }

    /**
     * @param $term string
     * @return integer
     */
    public static function getTotalAmount($contract_detail_id, $term = null)
    {
        if (!isset($term)) {
            $term = date('Y-m-d');
        }
        $term = new \DateTime($term);
        $query = AdvanceRepayment::find()->where(['and',
            ['contract_detail_id' => $contract_detail_id],
            ['<=', 'processed', $term->format('Y-m-t 23:59:59')],
        ]);
        return $query->sum('repayment_amount');
    }

    public static function getTotalAmountBySpan($contract_detail_id, $terms, $tax_rate)
    {
        $query =  self::find()->alias('ar')
            ->innerJoin('contract_detail cd', 'ar.contract_detail_id=cd.contract_detail_id')
            ->innerJoin('tax_application ta', 'cd.tax_application_id=ta.tax_application_id')
            ->where(['and',
                ['ar.contract_detail_id' => $contract_detail_id],
                ['>=', 'ar.processed', $terms['from']->format('Y-m-01 00:00:00')],
                ['<=', 'ar.processed', $terms['to']->format('Y-m-t 23:59:59')],
                'CASE ta.fixed WHEN 1 THEN ta.tax_rate * 100 ELSE (SELECT ctr.rate * 100 FROM consumption_tax_rate ctr WHERE ar.processed >= ctr.application_from AND ar.processed <= ctr.application_to) END = :rate',
            ])
            ->params([':rate' => $tax_rate]);
        return $query->sum('ar.repayment_amount');
    }
}
