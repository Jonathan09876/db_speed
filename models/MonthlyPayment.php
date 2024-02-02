<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "monthly_payment".
 *
 * @property int $monthly_payment_id
 * @property int $contract_detail_id
 * @property string $term
 * @property string $payment_date
 * @property float $payment_amount
 * @property float $paid_amount
 * @property string $registered
 *
 * @property ContractDetail $contractDetail
 */
class MonthlyPayment extends \yii\db\ActiveRecord
{
    public $payment_amount_with_tax;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'monthly_payment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contract_detail_id', 'term', 'registered'], 'required'],
            [['payment_amount'], 'required', 'strict' => true],
            [['contract_detail_id'], 'integer'],
            [['term', 'payment_date', 'registered'], 'safe'],
            [['payment_amount', 'paid_amount', 'payment_amount_with_tax'], 'number'],
            [['contract_detail_id'], 'exist', 'skipOnError' => true, 'targetClass' => ContractDetail::class, 'targetAttribute' => ['contract_detail_id' => 'contract_detail_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'monthly_payment_id' => 'Monthly Payment ID',
            'contract_detail_id' => 'Contract Detail ID',
            'term' => 'Term',
            'payment_amount' => 'Payment Amount',
            'paid_amount' => 'Paid Amount',
            'registered' => 'Registered',
        ];
    }

    static $details = [];
    /**
     * Gets query for [[ContractDetail]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getContractDetail()
    {
        if (!isset(static::$details[$this->contract_detail_id])) {
            static::$details[$this->contract_detail_id] = ContractDetail::findOne($this->contract_detail_id);
        }
        return static::$details[$this->contract_detail_id];
    }

    public static function register($detail_id, $term, $payment_date, $amount)
    {
        $instance = new MonthlyPayment([
            'contract_detail_id' => $detail_id,
            'term' => $term,
            'payment_date' => $payment_date,
            'payment_amount' => $amount,
            'registered' => date('Y-m-d H:i:s'),
        ]);
        $instance->save();
    }

    public function getAmountWithTax()
    {
        $method = $this->getContractDetail()->fraction_processing_pattern;
        $methods = [
            'floor' => 'FLOOR',
            'ceil' => 'CEIL',
            'roundup' => 'ROUND'
        ];
        $sql = "SELECT {$methods[$method]}(:amount * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE :term >= application_from AND :term <= IFNULL(application_to, '2099-12-31')) END)) FROM tax_application ta WHERE ta.tax_application_id=:id";
        $value = Yii::$app->db->createCommand($sql)->bindValues([
            ':amount' => (int)$this->payment_amount,
            ':id' => (int)$this->contractDetail->tax_application_id,
            ':term' => $this->term,
        ])->queryScalar();
        return $value;
    }

    public function getAmountFromWithTax($amount)
    {
        $method = $this->contractDetail->fraction_processing_pattern;
        $methods = [
            'roundup' => 'FLOOR',
            'ceil' => 'CEIL',
            'floor' => 'ROUND'
        ];
        $sql = "SELECT {$methods[$method]}(:amount / (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE :term >= application_from AND :term <= IFNULL(application_to, '2099-12-31')) END)) FROM tax_application ta WHERE ta.tax_application_id=:id";
        $value = Yii::$app->db->createCommand($sql)->bindValues([
            ':amount' => (int)$amount,
            ':id' => (int)$this->contractDetail->tax_application_id,
            ':term' => $this->term,
        ])->queryScalar();
        return $value;
    }

    public static function getSibling($detail_id, $targetTerm, $relative_value)
    {
        $sign = $relative_value > 0 ? '-' : '+';
        $value = abs($relative_value);
        return self::find()->where(['and',
            ['contract_detail_id' => $detail_id],
            ["DATE_FORMAT(term {$sign} INTERVAL {$value} MONTH, '%Y%m')" => $targetTerm]
        ])->limit(1)->one();
    }

    public static function getSiblings($detail_id, $targetTerm, $relative_value)
    {
        $sign = $relative_value > 0 ? '-' : '+';
        $value = abs($relative_value);
        return self::find()->where(['and',
            ['contract_detail_id' => $detail_id],
            ["DATE_FORMAT(term {$sign} INTERVAL {$value} MONTH, '%Y%m')" => $targetTerm]
        ])->all();
    }

    public function getOrderCount()
    {
        return self::find()->where(['and',
                ['contract_detail_id' => $this->contract_detail_id],
                ['<=', 'term', $this->term],
                ['<', 'monthly_payment_id', $this->monthly_payment_id],
            ])->count() + 1;
    }

    public function getIsLast()
    {
        return $this->contractDetail->getMonthlyPayments()->count() == $this->orderCount;
    }

    public static function getRemainsCount($detail_id, $term = null)
    {
        if (is_null($term)) {
            $term = (new \DateTime('now'))->format('Ym');
        }
        $query = self::find()->where(['and',
            ['contract_detail_id' => $detail_id],
            ['>', 'DATE_FORMAT(term, "%Y%m")', $term]
        ]);
        return $query->count();
    }

    public static function getRemainsAmount($detail_id, $term = null)
    {
        if (is_null($term)) {
            $term = (new \DateTime('now'))->format('Ym');
        }
        $query = self::find()->where(['and',
            ['contract_detail_id' => $detail_id],
            ['>', 'DATE_FORMAT(term, "%Y%m")', $term]
        ]);
        $total = 0;
        foreach($query->each() as $model) {
            $total += $model->amountWithTax;
        }
        return $total;
    }

    public static function getTotal($provider, $attribute, $toCurrent = false)
    {
        $current = (new \DateTime())->format('Y-m-01');
        $total = 0;
        foreach($provider as $data) {
            if ($data instanceof MonthlyCharge) {
                if ($toCurrent) {
                    if ($data->term < $current) {
                        $monthlyPayment = $data->monthlyPayment;
                        $total += ($monthlyPayment ? $monthlyPayment->{$attribute} : 0);
                    }
                }
                else {
                    $monthlyPayment = $data->monthlyPayment;
                    $total += ($monthlyPayment ? $monthlyPayment->{$attribute} : 0);
                }
            }
            else {
                if ($toCurrent) {
                    if ($data->term < $current) {
                        $total += $data->{$attribute};
                    }
                }
                else {
                    $total += $data->{$attribute};
                }
            }
        }
        return $total;
    }

    public function getLeasePayments()
    {
        return $this->hasMany(LeasePayment::class, ['monthly_payment_id' => 'monthly_payment_id']);
    }

    public function getTermInstance()
    {
        return Term::findOne(['term' => $this->term]);
    }

    public function getCollectionCell()
    {
        $term = $this->termInstance;
        return CollectionCell::getInstance($this->contract_detail_id, $term->term_id);
    }

    public function afterSave($insert, $changedAttributes)
    {
        $session = Yii::$app->session;
        if (!isset($session['ignore-update-content']) || !$session['ignore-update-content']) {
            foreach ($this->contractDetail->getCollectionCells()->each() as $collectionCell) {
                $collectionCell->updateContent();
            }
        }
    }
}
