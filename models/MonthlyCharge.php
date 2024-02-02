<?php

namespace app\models;

use app\components\PrivilegeManager;
use Yii;
use yii\console\Application;
use yii\db\Exception;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "monthly_charge".
 *
 * @property int $monthly_charge_id
 * @property int $contract_detail_id
 * @property string $term
 * @property string $transfer_date
 * @property float $charge_amount
 * @property float|null $temporary_charge_amount
 * @property float|null $charged_amount
 * @property string $registered
 * @property string|null $fixed
 *
 * @property ContractDetail $contractDetail
 */
class MonthlyCharge extends \yii\db\ActiveRecord
{
    public $temporary_charge_amount_with_tax;
    static $details = [];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'monthly_charge';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contract_detail_id', 'term', 'transfer_date', 'registered'], 'required'],
            [['charge_amount'], 'required', 'strict' => true],
            [['contract_detail_id'], 'integer'],
            [['transfer_date'], 'date', 'format' => 'php:Y-m-d'],
            [['term', 'registered', 'fixed'], 'safe'],
            [['memo'], 'string', 'max' => 256],
            [['charge_amount', 'temporary_charge_amount', 'charged_amount', 'temporary_charge_amount_with_tax'], 'number'],
            [['contract_detail_id'], 'exist', 'skipOnError' => true, 'targetClass' => ContractDetail::class, 'targetAttribute' => ['contract_detail_id' => 'contract_detail_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'monthly_charge_id' => 'Monthly Charge ID',
            'contract_detail_id' => 'Contract Detail ID',
            'term' => 'Term',
            'transfer_date' => '振替日',
            'charge_amount' => 'Charge Amount',
            'temporary_charge_amount' => 'Temporary Charge Amount',
            'charged_amount' => 'Charged Amount',
            'registered' => 'Registered',
            'fixed' => 'Fixed',
        ];
    }

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

    public static function register($detail_id, $term_date, $transfer_date, $amount)
    {
        $instance = new MonthlyCharge([
            'contract_detail_id' => $detail_id,
            'term' => $term_date,
            'transfer_date' => $transfer_date,
            'charge_amount' => $amount,
            'registered' => date('Y-m-d H:i:s'),
        ]);
        $instance->save();
    }

    public function getCalculated_charge_amount()
    {
        $first_collection_count = $this->first_collection_count ?? 1;
        $detail = $this->contractDetail;

        $charge_amount = $detail->monthly_charge;
        $term = new \DateTime($this->term);
        $count = $this->orderCount - 1;

        if (!!$detail->bonus_month_1 && $term->format('n') == $detail->bonus_month_1 ) {
            if (($count < $first_collection_count && $count == 0) || $count >= $first_collection_count) {
                //ボーナス月支払額を加算額ではなく設定額そのものにするために通常支払額は減算しておく
                $charge_amount -= $detail->monthly_charge;
                $charge_amount += $detail->bonus_additional_charge_1;
            }
        }
        if (!!$detail->bonus_month_2 && $term->format('n') == $detail->bonus_month_2 ) {
            if (($count < $first_collection_count && $count == 0) || $count >= $first_collection_count) {
                //ボーナス月支払額を加算額ではなく設定額そのものにするために通常支払額は減算しておく
                $charge_amount -= $detail->monthly_charge;
                $charge_amount += $detail->bonus_additional_charge_2;
            }
        }

        return $charge_amount;
    }

    public function getAmount()
    {
        return is_null($this->temporary_charge_amount) ? $this->charge_amount : $this->temporary_charge_amount;
    }

    public function getAmountWithTax($attr = 'charge_amount')
    {
        $method = $this->getContractDetail()->fraction_processing_pattern;
        $methods = [
            'floor' => 'FLOOR',
            'ceil' => 'CEIL',
            'roundup' => 'ROUND'
        ];
        $sql = "SELECT {$methods[$method]}(:amount * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE :term >= application_from AND :term <= IFNULL(application_to, '2099-12-31')) END)) FROM tax_application ta WHERE ta.tax_application_id=:id";
        $value = Yii::$app->db->createCommand($sql)->bindValues([
            ':amount' => $attr == 'charge_amount' ? (int)$this->$attr : (int)(is_null($this->$attr) ? $this->charge_amount : $this->$attr),
            ':id' => (int)$this->contractDetail->tax_application_id,
            ':term' => $this->term
        ])->queryScalar();
        return $value;
    }

    public function getTemporaryAmountWithTax()
    {
        return $this->getAmountWithTax('temporary_charge_amount');
    }

    public function getCalculatedAmountWithTax()
    {
        return $this->getAmountWithTax('calculated_charge_amount');
    }

    /**
     * @param $amountWithTax
     * @return false|int|string|\yii\db\DataReader|null
     * @throws Exception
     */
    public function getAmountFromWithTax($amountWithTax)
    {
        $method = $this->contractDetail->fraction_processing_pattern;
        $methods = [
            'floor' => 'CEIL',
            'ceil' => 'FLOOR',
            'roundup' => 'ROUND'
        ];
        $sql = "SELECT {$methods[$method]}(:amount / (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE :term >= application_from AND :term <= IFNULL(application_to, '2099-12-31')) END)) FROM tax_application ta WHERE ta.tax_application_id=:id";
        $value = Yii::$app->db->createCommand($sql)->bindValues([
            ':amount' => (int)$amountWithTax,
            ':id' => (int)$this->contractDetail->tax_application_id,
            ':term' => $this->term
        ])->queryScalar();
        return $value;
    }

    public function getMonthlyPayment()
    {
        $orderCount = $this->getOrderCount();
        return MonthlyPayment::find()
            ->where(['contract_detail_id' => $this->contract_detail_id,])
            ->offset($orderCount - 1)
            ->limit(1)
            ->one();
    }

    public static function getTotal($provider, $attribute)
    {
        $total = 0;
        foreach($provider as $data) {
            $total += $data->{$attribute};
        }
        return $total;
    }

    public static function getSibling($detail_id, $targetTerm, $relative_value)
    {
        $detail = ContractDetail::findOne($detail_id);
        $repayment_pattern = $detail->leaseContract->customer->clientContract->repaymentPattern;

        $sign = $relative_value > 0 ? '-' : '+';
        $value = abs($relative_value);
        return self::find()->where(['and',
            ['contract_detail_id' => $detail_id],
            ["DATE_FORMAT(term {$sign} INTERVAL {$value} MONTH, '%Y%m')" => $targetTerm]
        ])->limit(1)->one();
    }

    public static function getSiblings($detail_id, $targetTerm, $relative_value)
    {
        $detail = ContractDetail::findOne($detail_id);
        $repayment_pattern = $detail->leaseContract->customer->clientContract->repaymentPattern;

        $sign = $relative_value > 0 ? '-' : '+';
        $value = abs($relative_value);
        return self::find()->where(['and',
            ['contract_detail_id' => $detail_id],
            ["DATE_FORMAT(term {$sign} INTERVAL {$value} MONTH, '%Y%m')" => $targetTerm]
        ])->all();
    }

    public static function getRelativeShortage($detail_id, $term)
    {
        $term = preg_replace('/(\d+)年(\d+)月/', '$1$2', $term);
        $total = 0;
        $relativeCharges = MonthlyCharge::find()->where(['and',
            ['contract_detail_id' => $detail_id],
            ['<', 'DATE_FORMAT(term, "%Y%m")', $term]
        ]);
        foreach($relativeCharges->each() as $model) {
            $total += $model->getAmountWithTax();
        }
        $relativeRepaymentTotal = Repayment::find()->where(['and',
            ['contract_detail_id' => $detail_id],
            ['<', 'DATE_FORMAT(processed, "%Y%m")', $term]
        ])->sum('repayment_amount');
        return $total - $relativeRepaymentTotal;
    }

    public function getOrderCount()
    {
        $count = self::find()->where(['and',
            ['contract_detail_id' => $this->contract_detail_id],
            ['<=', 'term', $this->term],
            ['<', 'monthly_charge_id', $this->monthly_charge_id],
        ])->count() + 1;
        return $count;
    }

    public function getIsLast()
    {
        return $this->contractDetail->getMonthlyCharges()->count() == $this->orderCount;
    }

    public function getOrderCountText()
    {
        $count = $this->orderCount;
        return $this->contractDetail->term_months_count < $count ? '残' : $count;
    }

    public function getTargetTransferDate()
    {
        $repayment_pattern = $this->contractDetail->leaseContract->customer->clientContract->repaymentPattern;
        $term = new \DateTime($this->term);
        if ($repayment_pattern->target_month == 'next') {
            //$term = $term->modify('1 month');
        }
        $format = $repayment_pattern->transfer_date == 31 ? 'Y-m-t' : "Y-m-{$repayment_pattern->transfer_date}";
        return $term->format($format);
    }

    public function getRepaymentsRecent()
    {
        $repayment_pattern = $this->contractDetail->leaseContract->customer->clientContract->repaymentPattern;
        $term = new \DateTime($this->term);
        if ($repayment_pattern->target_month == 'next') {
            $term = $term->modify('1 month');
        }
        $repayments = Repayment::find()->where([
            'contract_detail_id' => $this->contract_detail_id,
            'DATE_FORMAT(processed, "%Y%m")' => $term->format('Ym'),
        ])->all();
        return $repayments;
    }

    public function getRepayments()
    {
        return $this->hasMany(Repayment::class, ['monthly_charge_id' => 'monthly_charge_id']);
    }

    static $repaymentTypes = [];
    public function getRepaymentType()
    {
        $type_id = $this->repayment_type_id ?? $this->contractDetail->repaymentPattern->repayment_type_id;
        if (!isset(static::$repaymentTypes[$type_id])) {
            static::$repaymentTypes[$type_id] = RepaymentType::findOne($type_id);
        }
        return static::$repaymentTypes[$type_id];
    }

    public function getDebts()
    {
        return $this->hasMany(Debt::class, ['monthly_charge_id' => 'monthly_charge_id']);
    }

    public function getAdvanceRepayments()
    {
        $repayment_pattern = $this->contractDetail->leaseContract->customer->clientContract->repaymentPattern;
        $term = new \DateTime($this->term);
        if ($repayment_pattern->target_month == 'next') {
            $term = $term->modify('1 month');
        }
        $advanceRepayments = AdvanceRepayment::find()->where([
            'contract_detail_id' => $this->contract_detail_id,
            'DATE_FORMAT(processed, "%Y%m")' => $term->format('Ym'),
        ]);
        return $advanceRepayments;
    }

    public function getPayments()
    {
        $repayment_pattern = $this->contractDetail->leaseContract->customer->clientContract->repaymentPattern;
        $term = new \DateTime($this->term);
        $payments = LeasePayment::find()->where([
            'contract_detail_id' => $this->contract_detail_id,
            'DATE_FORMAT(processed, "%Y%m")' => $term->format('Ym')
        ])->all();
        return $payments;
    }

    public function getIsSlidable()
    {
        return $this->getRepayments()->count() == 0 && $this->getDebts()->count() == 0;
    }

    public function getPrev()
    {
        return MonthlyCharge::find()->where(['and',
            ['contract_detail_id' => $this->contract_detail_id],
            ['<', 'monthly_charge_id', $this->monthly_charge_id]
        ])->orderBy(['monthly_charge_id' => SORT_DESC])->limit(1)->one();
    }

    public function getNext()
    {
        return MonthlyCharge::find()->where(['and',
            ['contract_detail_id' => $this->contract_detail_id],
            ['>', 'monthly_charge_id', $this->monthly_charge_id]
        ])->orderBy(['monthly_charge_id' => SORT_ASC])->limit(1)->one();
    }

    public function slideTerm($relative_month = 1, $with_after_sibling = true)
    {
        $session = Yii::$app->session;
        $session['ignore-update-content'] = true;
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $targets = $with_after_sibling ? MonthlyCharge::find()->distinct()->where(['and',
                ['contract_detail_id' => $this->contract_detail_id],
                ['>=', 'term', $this->term],
                ['>=', 'monthly_charge_id', $this->monthly_charge_id]
            ])->orderby(['monthly_charge_id' => ($relative_month > 0 ? SORT_ASC : SORT_DESC)])->all() : [$this];
            $transfer_date = $this->contractDetail->leaseContract->customer->clientContract->repaymentPattern->transfer_date;
            $format = $transfer_date == 31 ? 'Y-m-t' : "Y-m-{$transfer_date}";
            foreach($targets as $monthlyCharge) {
                $term = new \DateTime($monthlyCharge->term);
                $modifier = ($relative_month > 0 ? '+' : '-') . abs($relative_month) . ' month';
                $term->modify($modifier);
                //回収データはスライドさせない
                if ($monthlyCharge->getRepayments()->count() > 0) {
                    $dest = $relative_month > 0 ? $monthlyCharge->prev : $monthlyCharge->next;
                    if (!Yii::$app instanceof Application) {
                        if (!$dest || $dest->getRepayments()->count() > 0) {
                            throw new Exception("[{$monthlyCharge->monthly_charge_id}]回収データが移動出来ません。" . VarDumper::dumpAsString($dest->attributes()));
                        }
                        $repayment = $monthlyCharge->repayments[0];
                        $repayment->monthly_charge_id = $dest->monthly_charge_id;
                        $repayment->save();
                    }
                }
                //前払金が登録されていたら、そちらもスライドさせる
                $advanceRepayments = $monthlyCharge->advanceRepayments;
                if ($advanceRepayments) {
                    $advanceRepayments->processed = $term->format('Y-m-d');
                    $advanceRepayments->save();
                }
                $monthlyCharge->term = $term->format('Y-m-d');
                //ボーナス払いがある場合は再計算が必要なので
                $monthlyCharge->charge_amount = $monthlyCharge->calculated_charge_amount;
                $monthlyCharge->transfer_date = $term->format($format);
                $monthlyCharge->save();
            }
            $transaction->commit();
            unset($session['ignore-update-content']);
            return true;
        } catch(Exception $e) {
            $transaction->rollBack();
            unset($session['ignore-update-content']);
            return false;
        }
    }

    public static function getRemainsAmount($contract_detail_id, $term = null)
    {
        if (is_null($term)) {
            $term = (new \DateTime('now'))->format('Ym');
        }
        $query = self::find()->alias('mc')
            ->innerJoin('contract_detail cd', 'mc.contract_detail_id=cd.contract_detail_id')
            ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
            ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
            ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
            ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id')
            ->where(['and',
                ['mc.contract_detail_id' => $contract_detail_id],
                ['>', 'DATE_FORMAT(CASE rp.target_month WHEN "NEXT" THEN mc.term + INTERVAL 1 MONTH ELSE mc.term END, "%Y%m")', $term]
            ]);
        $total = 0;
        foreach($query->each() as $model) {
            $total += $model->amountWithTax;
        }
        return $total;
    }

    public function getTargetTerm()
    {
        $repaymentPattern = $this->contractDetail->leaseContract->customer->clientContract->repaymentPattern;
        $targetTerm = new \DateTime($this->term);
        if ($repaymentPattern->target_month == 'next') {
            $targetTerm->modify('+1 month');
        }
        return $targetTerm;
    }

    public function getIsUpdatable()
    {
        $clientContract = $this->contractDetail->leaseContract->customer->clientContract;
        return TargetTermMonthlyChargeStored::find()
            ->where([
                'target_term' => $this->targetTerm->format('Y-m-d'),
                'client_corporation_id' => $clientContract->client_corporation_id,
                'repayment_pattern_id' => $clientContract->repayment_pattern_id,
            ])->count() == 0 && (PrivilegeManager::hasPrivilege('update-recent-monthly-charge') || $this->targetTerm->format('Ym') >= (new \DateTime())->format('Ym'));
    }

    public function getTermInstance()
    {
        $contractDetail = $this->contractDetail;
        $repaymentPattern = $contractDetail->repaymentPattern;
        $term = new \DateTime($this->term);
        if ($repaymentPattern->target_month == 'next') {
            $term->modify('+1 month');
        }
        return Term::findOne(['term' => $term->format('Y-m-d')]);
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
            foreach($this->contractDetail->getCollectionCells()->each() as $collectionCell) {
                $collectionCell->updateContent();
            }
        }
    }

    public function getIsFirstIntermCharges()
    {
        $siblings = self::find()
            ->where(['contract_detail_id' => $this->contract_detail_id, 'term' => $this->term])
            ->orderBy(['monthly_charge_id' => SORT_ASC])
            ->all();
        return count($siblings) > 1 ? $siblings[0]->monthly_charge_id == $this->monthly_charge_id : true;
    }

    public function getIsTermClosed()
    {
        return Yii::$app->db->createCommand("
            SELECT COUNT(smc.monthly_charge_id) FROM `stored_monthly_charge` smc
            INNER JOIN `target_term_monthly_charge_stored` ttmcs ON smc.target_term_monthly_charge_stored_id=ttmcs.target_term_monthly_charge_stored_id
            WHERE ttmcs.target_term = :term AND ttmcs.is_closed=1 AND smc.monthly_charge_id=:id
        ")->bindValues([
            ':term' => $this->termInstance->term,
            ':id' => $this->monthly_charge_id
        ])->queryScalar() > 0;
    }
}
