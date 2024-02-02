<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "debt".
 *
 * @property int $debt_id
 * @property int $contract_detail_id
 * @property string $term
 * @property float $debt_amount
 * @property string $registered
 *
 * @property ContractDetail $contractDetail
 */
class Debt extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'debt';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contract_detail_id', 'term', 'debt_amount', 'registered'], 'required'],
            [['contract_detail_id'], 'integer'],
            [['term', 'registered'], 'safe'],
            [['debt_amount'], 'number'],
            [['contract_detail_id'], 'exist', 'skipOnError' => true, 'targetClass' => ContractDetail::class, 'targetAttribute' => ['contract_detail_id' => 'contract_detail_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'debt_id' => 'Debt ID',
            'contract_detail_id' => 'Contract Detail ID',
            'term' => 'Term',
            'debt_amount' => 'Debt Amount',
            'registered' => 'Registered',
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

    public static function getTotalDebt($contract_detail_id, $term = null)
    {
        if (!is_null($term)) {
            $term = (new \DateTime($term))->format('Y-m-t');
        }
        else {
            $term = (new \DateTime())->format('Y-m-t');
        }
        return self::find()
            ->where(['and',
                ['contract_detail_id' => $contract_detail_id],
                ['<=', 'term', $term],
            ])->sum('debt_amount');
    }

    public static function getTotalDebtBySpan($contract_detail_id, $terms, $tax_rate)
    {
        return self::find()->alias('d')
            ->innerJoin('contract_detail cd', 'd.contract_detail_id=cd.contract_detail_id')
            ->innerJoin('tax_application ta', 'cd.tax_application_id=ta.tax_application_id')
            ->where(['and',
                ['d.contract_detail_id' => $contract_detail_id],
                ['>=', 'd.term', $terms['from']->format('Y-m-d')],
                ['<=', 'd.term', $terms['to']->format('Y-m-d')],
                'CASE ta.fixed WHEN 1 THEN ta.tax_rate * 100 ELSE (SELECT ctr.rate * 100 FROM consumption_tax_rate ctr WHERE d.term >= ctr.application_from AND d.term <= ctr.application_to) END = :rate',
            ])
            ->params([':rate' => $tax_rate])
            ->sum('d.debt_amount');
    }

    public function getDebtType()
    {
        return $this->hasOne(RepaymentType::class, ['repayment_type_id' => 'debt_type_id']);
    }
}
