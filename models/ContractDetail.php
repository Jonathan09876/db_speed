<?php

namespace app\models;

use app\components\DateHelper;
use Yii;
use yii\db\Exception;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "contract_detail".
 *
 * @property int $contract_detail_id
 * @property int $lease_contract_id
 * @property int $lease_servicer_id
 * @property string $contract_type
 * @property int $tax_application_id
 * @property string $fraction_processing_pattern
 * @property string $term_start_at
 * @property string $term_end_at
 * @property string $lease_start_at
 * @property int $term_months_count
 * @property float $monthly_charge
 * @property float $monthly_payment
 * @property int|null $bonus_month_1
 * @property float|null $bonus_additional_charge_1
 * @property float|null $bonus_additional_payment_1
 * @property int|null $bonus_month_2
 * @property float|null $bonus_additional_charge_2
 * @property float|null $bonus_additional_payment_2
 * @property float $total_charge_amount
 * @property float $total_payment_amount
 * @property int $advance_repayment_count
 * @property int $advance_payment_count
 * @property int $collection_latency
 * @property int $payment_latency
 * @property int $registration_status
 *
 * @property LeaseContract $leaseContract
 * @property LeaseServicer $leaseServicer
 * @property MonthlyCharge[] $monthlyCharges
 * @property MonthlyPayment[] $monthlyPayments
 * @property Repayment[] $repayments
 * @property TaxApplication $taxApplication
 */
class ContractDetail extends \yii\db\ActiveRecord
{
    public $use_bonus_1;
    public $use_bonus_2;
    public $customer_client_corporation;
    public $customer_code;
    public $customer_name;

    public $delinquencies;

    public $regenerateMonthlyChargesPayments = 0;
    public $regenerateMonthlyCharges = 0;
    public $regenerateMonthlyPayments = 0;

    public $cdids = [];

    static $contractTypes = [
        'ordinary' => '通常リース',
        'meintenance' => 'メンテナンスリース',
        'delinquency' => '延滞金',
    ];

    CONST STATUS_COMPLETE = 0;
    CONST STATUS_TEMPORARY = 1;

    static $registrationStatuses = [
        self::STATUS_COMPLETE => '正常登録',
        self::STATUS_TEMPORARY => '暫定登録',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contract_detail';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['use_bonus_1', 'use_bonus_2', 'regenerateMonthlyChargesPayments', 'regenerateMonthlyCharges', 'regenerateMonthlyPayments'], 'boolean'],
            [['monthly_charge', 'monthly_payment', 'bonus_additional_charge_1', 'bonus_additional_payment_1', 'bonus_additional_charge_2', 'bonus_additional_payment_2', 'total_charge_amount', 'total_payment_amount'], 'filter', 'filter' => [$this, 'zen2han']],
            [['monthly_charge', 'monthly_payment', 'bonus_additional_charge_1', 'bonus_additional_payment_1', 'bonus_additional_charge_2', 'bonus_additional_payment_2', 'total_charge_amount', 'total_payment_amount'], 'filter', 'filter' => [$this, 'digitOnly']],
            [['lease_contract_id', 'lease_servicer_id', 'contract_type', 'tax_application_id', 'fraction_processing_pattern', 'term_start_at', 'term_end_at', 'lease_start_at', 'term_months_count', 'monthly_charge', 'total_charge_amount', 'total_payment_amount', 'advance_repayment_count'], 'required', 'message' => '未入力です。'],
            [['customer_client_corporation'], 'in', 'range' => array_keys(ClientCorporation::getClientCorporations())],
            [['customer_code', 'customer_name'], 'safe'],
            [['monthly_payment'], 'required', 'when' => function($data){
                return $this->lease_contract_id ? !$data->leaseContract->registration_incomplete : false;
            }],
            [['advance_repayment_count'], 'in', 'range' => range(0,12)],
            [['contract_detail_id', 'lease_contract_id', 'lease_servicer_id', 'tax_application_id', 'term_months_count', 'bonus_month_1', 'bonus_month_2', 'advance_payment_count', 'collection_latency', 'payment_latency', 'first_collection_count', 'first_payment_count'], 'integer'],
            //[['term_months_count'], 'checkTermCount', 'skipOnError' => false],
            [['contract_type', 'fraction_processing_pattern'], 'string'],
            [['term_start_at', 'term_end_at'], 'date', 'format' => 'php:Y-m-d'],
            [['lease_start_at', 'collection_start_at', 'payment_start_at'], 'match', 'pattern' => '/\d+年\d+月/'],
            [['monthly_charge', 'monthly_payment', 'bonus_additional_charge_1', 'bonus_additional_payment_1', 'bonus_additional_charge_2', 'bonus_additional_payment_2', 'total_charge_amount', 'total_payment_amount'], 'match', 'pattern' => '/[0-9,]+/'],
            [['contract_detail_id'], 'unique'],
            [['lease_contract_id'], 'exist', 'skipOnError' => true, 'targetClass' => LeaseContract::class, 'targetAttribute' => ['lease_contract_id' => 'lease_contract_id']],
            [['lease_servicer_id'], 'exist', 'skipOnError' => true, 'targetClass' => LeaseServicer::class, 'targetAttribute' => ['lease_servicer_id' => 'lease_servicer_id']],
            [['tax_application_id'], 'exist', 'skipOnError' => true, 'targetClass' => TaxApplication::class, 'targetAttribute' => ['tax_application_id' => 'tax_application_id']],
            [['registration_status', 'monthly_payment_unfixed'], 'boolean'],
        ];
    }

    public function checkTermCount($attr, $params)
    {
        if (count($this->monthlyCharges) > $this->term_months_count) {
            $this->addError('term_months_count', '回数が回収予定月数より少ないです。');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'contract_detail_id' => 'Contract Detail ID',
            'lease_contract_id' => 'Lease Contract ID',
            'lease_servicer_id' => 'リース会社',
            'contract_type' => 'リース区分',
            'tax_application_id' => '税区分',
            'fraction_processing_pattern' => '端数処理',
            'term_start_at' => 'リース開始',
            'term_end_at' => 'リース終了',
            'term_months_count' => 'リース回数',
            'lease_start_at' => 'リース開始月',
            'monthly_charge' => '毎月回収額',
            'monthly_payment' => '毎月支払額',
            'use_bonus_1' => 'ボーナス加算1',
            'bonus_month_1' => 'ボーナス加算月1',
            'bonus_additional_charge_1' => 'ボーナス回収額1',
            'bonus_additional_payment_1' => 'ボーナス支払額1',
            'use_bonus_2' => 'ボーナス加算2',
            'bonus_month_2' => 'ボーナス加算月2',
            'bonus_additional_charge_2' => 'ボーナス回収額2',
            'bonus_additional_payment_2' => 'ボーナス支払額2',
            'total_charge_amount' => '総回収額',
            'total_payment_amount' => '総支払額',
            'advance_repayment_count' => 'リース回収開始',
            'advance_payment_count' => 'リース支払開始',
            'prepaid_repayment_amount' => '前払預金',
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->regenerateMonthlyChargesPayments = 1;
            $this->regenerateMonthlyCharges= 1;
            $this->regenerateMonthlyPayments = 1;
        }
        $this->lease_start_at = preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->lease_start_at);
        if (!!$this->collection_start_at) {
            $this->collection_start_at = preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->collection_start_at);
        }
        else {
            $this->collection_start_at = $this->lease_start_at;
        }
        if (!!$this->payment_start_at) {
            $this->payment_start_at = preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->payment_start_at);
        }
        else {
            if ($this->registration_status == self::STATUS_COMPLETE) {
                $this->payment_start_at = $this->lease_start_at;
            }
        }
        if (!$this->first_collection_count) {
            $this->first_collection_count = 1;
        }
        if (!$this->first_payment_count) {
            $this->first_payment_count = 1;
        }
        if ($this->monthly_charge == null) {
            $this->monthly_charge = 0;
        }
        if ($this->monthly_payment == null) {
            $this->monthly_payment = 0;
        }
        if ($this->advance_payment_count == null) {
            $this->advance_payment_count = 0;
        }
        return true;
    }

    public function afterFind()
    {
        $this->lease_start_at = (new \DateTime($this->lease_start_at))->format('Y年n月');
        $this->collection_start_at = (new \DateTime($this->collection_start_at))->format('Y年n月');
        $this->payment_start_at = $this->payment_start_at ? (new \DateTime($this->payment_start_at))->format('Y年n月') : null;
        $this->use_bonus_1 = !empty($this->bonus_month_1);
        $this->use_bonus_2 = !empty($this->bonus_month_2);
    }

    public function digitOnly($val)
    {
        return preg_replace('/[^0-9]+/', '', $val);
    }

    public function zen2han($value)
    {
        return mb_convert_kana($value, "as", 'UTF-8');
    }

    /**
     * Gets query for [[LeaseContract]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLeaseContract()
    {
        return $this->hasOne(LeaseContract::class, ['lease_contract_id' => 'lease_contract_id']);
    }

    /**
     * Gets query for [[Leaseservicer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLeaseServicer()
    {
        return $this->hasOne(LeaseServicer::class, ['lease_servicer_id' => 'lease_servicer_id']);
    }

    /**
     * Gets query for [[MonthlyCharges]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMonthlyCharges()
    {
        return $this->hasMany(MonthlyCharge::class, ['contract_detail_id' => 'contract_detail_id']);
    }

    /**
     * Gets query for [[MonthlyPayments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMonthlyPayments()
    {
        return $this->hasMany(MonthlyPayment::class, ['contract_detail_id' => 'contract_detail_id']);
    }

    /**
     * Gets query for [[Repayments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRepayments()
    {
        return $this->hasMany(Repayment::class, ['contract_detail_id' => 'contract_detail_id']);
    }

    public function getAdvanceRepayments()
    {
        return $this->hasMany(AdvanceRepayment::class, ['contract_detail_id' => 'contract_detail_id']);
    }

    /**
     * Gets query for [[Repayments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLeasePayments()
    {
        return $this->hasMany(LeasePayment::class, ['contract_detail_id' => 'contract_detail_id']);
    }

    /**
     * Gets query for [[TaxApplication]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTaxApplication()
    {
        return $this->hasOne(TaxApplication::class, ['tax_application_id' => 'tax_application_id']);
    }

    public function getMonthlyChargeSpan()
    {
        return $this->hasOne(MonthlyChargeSpan::class, ['contract_detail_id' => 'contract_detail_id']);
    }

    public function getMonthlyPaymentSpan()
    {
        return $this->hasOne(MonthlyPaymentSpan::class, ['contract_detail_id' => 'contract_detail_id']);
    }

    public function getCollectionCells()
    {
        return $this->hasMany(CollectionCell::class, ['contract_detail_id' => 'contract_detail_id']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        $session = Yii::$app->session;
        $session['ignore-update-content'] = 1;
        $this->generateMonthlyChargesPaymentsAlternativeRefactored($this->regenerateMonthlyCharges, $this->regenerateMonthlyPayments);
        unset($session['ignore-update-content']);
        $this->updateSpans();
        $this->updateContent();
    }

    public function generateMonthlyChargesPaymentsAlternativeRefactored($updateCharges = true, $updatePayments = true)
    {
        $repaymentPattern = $this->leaseContract->customer->clientContract->repaymentPattern;
        $transferDateFormat = $repaymentPattern->transfer_date == 31 ? 'Y-m-t' : "Y-m-{$repaymentPattern->transfer_date}";
        $termFormat = 'Y-m-01';
        if ($updateCharges) {
            $term = new \DateTime($this->collection_start_at);
            $count = 0;
            $charge_amount = 0;
            $first_collection_count = $this->first_collection_count ?? 1;
            $monthlyCharges = $this->monthlyCharges;
            do {
                $charge_amount += $this->monthly_charge;
                $term_date = $term->format($termFormat);
                $transfer_date = $term->format($transferDateFormat);
                if (!!$this->bonus_month_1 && $term->format('n') == $this->bonus_month_1 ) {
                    if (($count < $first_collection_count && $count == 0) || $count >= $first_collection_count) {
                        //ボーナス月支払額を加算額ではなく設定額そのものにするために通常支払額は減算しておく
                        $charge_amount -= $this->monthly_charge;
                        $charge_amount += $this->bonus_additional_charge_1;
                    }
                }
                if (!!$this->bonus_month_2 && $term->format('n') == $this->bonus_month_2 ) {
                    if (($count < $first_collection_count && $count == 0) || $count >= $first_collection_count) {
                        //ボーナス月支払額を加算額ではなく設定額そのものにするために通常支払額は減算しておく
                        $charge_amount -= $this->monthly_charge;
                        $charge_amount += $this->bonus_additional_charge_2;
                    }
                }
                if ($monthlyCharge = array_shift($monthlyCharges)) {
                    //解約・STOPは更新しない
                    if (!in_array($monthlyCharge->repaymentType->repayment_type_id, [11,12]) && !$monthlyCharge->isTermClosed) {
                        $monthlyCharge->term = $term_date;
                        $monthlyCharge->transfer_date = $transfer_date;
                        $monthlyCharge->charge_amount = $charge_amount;
                        $monthlyCharge->save();
                        Yii::$app->db->createCommand()->update('monthly_charge', ['temporary_charge_amount' => null], ['monthly_charge_id' => $monthlyCharge->monthly_charge_id])->execute();
                    }
                }
                else {
                    MonthlyCharge::register($this->contract_detail_id, $term_date, $transfer_date, $charge_amount);
                }
                $charge_amount = 0;

                if ($first_collection_count - 1 <= $count) {
                    $term->modify('+1 month');
                }
            } while(++$count < $this->term_months_count);
            //期間短縮になった場合の登録済み情報を削除
            while($monthlyCharge = array_shift($monthlyCharges)) {
                $monthlyCharge->delete();
            }

            //前払い登録がある場合は、登録済み情報を削除
            Yii::$app->db->createCommand()->delete('advance_repayment', ['contract_detail_id' => $this->contract_detail_id])->execute();
            //この時点では売掛は存在していないので、前受入金登録のみを行う
            if ($amount = $this->monthly_charge * $this->advance_repayment_count) {
                $target = $this->getMonthlyCharges()->orderBy(['term' => SORT_DESC])->limit($this->advance_repayment_count);
                foreach($target->each() as $monthlyCharge) {
                    $repaymentPattern = $monthlyCharge->contractDetail->leaseContract->customer->clientContract->repaymentPattern;
                    $term = new \DateTime($monthlyCharge->term);
                    if ($repaymentPattern->target_month == 'next') {
                        $term = $term->modify('+1 month');
                    }
                    $repaymentAmount = $monthlyCharge->temporaryAmountWithTax;
                    $advanceRepayment = new AdvanceRepayment([
                        'contract_detail_id' => $this->contract_detail_id,
                        'repayment_amount' => $repaymentAmount,
                        'processed' => $term->format('Y-m-d'),
                        'registered' => date('Y-m-d H:i:s')
                    ]);
                    $advanceRepayment->save();
                    $monthlyCharge->temporary_charge_amount = 0;
                    $monthlyCharge->save();
                }
            }
        }
        if ($updatePayments && $this->registration_status == self::STATUS_COMPLETE) {
            $paymentTerm = new \DateTime($this->payment_start_at);
            $count = 0;
            $payment_amount = 0;
            $first_payment_count = $this->first_payment_count ?? 1;
            $monthlyPayments = $this->monthlyPayments;
            do {
                $payment_amount += $this->monthly_payment;
                $payment_term_date = $paymentTerm->format($termFormat);
                $payment_transfer_date = $paymentTerm->format($transferDateFormat);
                if (!!$this->bonus_month_1 && $paymentTerm->format('n') == $this->bonus_month_1 ) {
                    if (($count < $first_collection_count && $count == 0) || $count >= $first_collection_count) {
                        //ボーナス月支払額を加算額ではなく設定額そのものにするために通常支払額は減算しておく
                        $payment_amount -= $this->monthly_payment;
                        $payment_amount += $this->bonus_additional_payment_1;
                    }
                }
                if (!!$this->use_bonus_2 && $paymentTerm->format('n') == $this->bonus_additional_charge_2 ) {
                    if (($count < $first_collection_count && $count == 0) || $count >= $first_collection_count) {
                        //ボーナス月支払額を加算額ではなく設定額そのものにするために通常支払額は減算しておく
                        $payment_amount -= $this->monthly_payment;
                        $payment_amount += $this->bonus_additional_payment_2;
                    }
                }
                if ($monthlyPayment = array_shift($monthlyPayments)) {
                    $monthlyPayment->term = $payment_term_date;
                    $monthlyPayment->payment_date = $payment_transfer_date;
                    $monthlyPayment->payment_amount = $payment_amount;
                    $monthlyPayment->save();
                }
                else {
                    MonthlyPayment::register($this->contract_detail_id, $payment_term_date, $payment_transfer_date, $payment_amount);
                }
                $payment_amount = 0;

                if ($first_payment_count - 1 <= $count) {
                    $paymentTerm->modify('+1 month');
                }
            } while(++$count < $this->term_months_count);
            //期間短縮になった場合の登録済み情報を削除
            while($monthlyPayment = array_shift($monthlyPayments)) {
                $monthlyPayment->delete();
            }
        }
    }

    public function updateSpans()
    {
        $isNext = $this->leaseContract->customer->clientContract->repaymentPattern->target_month == 'next';
        $mc_first = new \DateTime($this->getMonthlyCharges()->min('term'));
        $mc_last = new \DateTime($this->getMonthlyCharges()->max('term'));
        if ($isNext) {
            $mc_first = $mc_first->modify('+1 month');
            $mc_last = $mc_last->modify('+1 month');
        }
        Yii::$app->db->createCommand()->delete('monthly_charge_span',['contract_detail_id' => $this->contract_detail_id])->execute();
        Yii::$app->db->createCommand()->insert('monthly_charge_span', [
            'contract_detail_id' => $this->contract_detail_id,
            'first_term' => $mc_first->format('Y-m-d'),
            'last_term' => $mc_last->format('Y-m-d'),
        ], ['contract_detail_id' => $this->contract_detail_id])->execute();
        Yii::$app->db->createCommand()->delete('monthly_payment_span',['contract_detail_id' => $this->contract_detail_id])->execute();
        Yii::$app->db->createCommand()->insert('monthly_payment_span', [
            'contract_detail_id' => $this->contract_detail_id,
            'first_term' => $this->getMonthlyPayments()->min('term'),
            'last_term' => $this->getMonthlyPayments()->max('term'),
        ], ['contract_detail_id' => $this->contract_detail_id])->execute();
    }

    public function updateContent()
    {
        foreach($this->getCollectionCells()->each() as $collectionCell) {
            $collectionCell->updateContent();
        }
    }

    public function getRepaymentPattern()
    {
        return $this->leaseContract->customer->clientContract->repaymentPattern;
    }

    public function getMonthlyChargeWithTax()
    {
        $method = $this->fraction_processing_pattern ?? 'roundup';
        $methods = [
            'floor' => 'FLOOR',
            'ceil' => 'CEIL',
            'roundup' => 'ROUND'
        ];
        $sql = "SELECT {$methods[$method]}(:amount * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE NOW() >= application_from AND NOW() <= IFNULL(application_to, '2099-12-31')) END)) FROM tax_application ta WHERE ta.tax_application_id=:id";
        $value = Yii::$app->db->createCommand($sql)->bindValues([
            ':amount' => (int)$this->monthly_charge,
            ':id' => (int)$this->tax_application_id,
        ])->queryScalar();
        return $value;
    }

    public function getMonthlyPaymentWithTax()
    {
        $method = $this->fraction_processing_pattern ?? 'roundup';
        $methods = [
            'floor' => 'FLOOR',
            'ceil' => 'CEIL',
            'roundup' => 'ROUND'
        ];
        $sql = "SELECT {$methods[$method]}(:amount * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE NOW() >= application_from AND NOW() <= IFNULL(application_to, '2099-12-31')) END)) FROM tax_application ta WHERE ta.tax_application_id=:id";
        $value = Yii::$app->db->createCommand($sql)->bindValues([
            ':amount' => (int)$this->monthly_payment,
            ':id' => (int)$this->tax_application_id,
        ])->queryScalar();
        return $value;
    }

    public function getTotalChargeAmountWithTax()
    {
        $total = 0;
        foreach($this->monthlyCharges as $charge) {
            $total += $charge->temporaryAmountWithTax;
        }
        return $total;
    }

    /**
     * @param $searchModel ScheduleSearch
     * @return int
     */
    public function getTermsTotalChargeAmountWithTax($terms)
    {
        $total = 0;
        foreach($terms as $term) {
            $monthlyCharges = $term->getMonthlyCharges($this->contract_detail_id);
            if (count($monthlyCharges)) {
                foreach($monthlyCharges as $charge) {
                    $total += $charge->temporaryAmountWithTax;
                }
            }
        }
        return $total;
    }

    public function getTotalPaymentAmountWithTax()
    {
        $total = 0;
        foreach($this->monthlyPayments as $payment) {
            $total += $payment->amountWithTax;
        }
        return $total;
    }

    public function getTermsTotalPaymentAmountWithTax($terms)
    {
        $total = 0;
        foreach($terms as $term) {
            $monthlyPayments = $term->getMonthlyPayments($this->contract_detail_id);
            if (count($monthlyPayments) > 0) {
                foreach($monthlyPayments as $payment) {
                    $total += $payment->amountWithTax;
                }
            }
        }
        return $total;
    }

    public function getTermsRepaymentTotal($terms)
    {
        $total = 0;
        foreach($terms as $term) {
            $monthlyCharges = $term->getMonthlyCharges($this->contract_detail_id);
            if (count($monthlyCharges)) {
                foreach($monthlyCharges as $charge) {
                    $repayments = $charge->repayments;
                    if (count($repayments) > 0) {
                        foreach($repayments as $repayment) {
                            $total += ($repayment->repayment_amount - $repayment->chargeback_amount);
                        }
                    }
                }
            }
        }
        return $total;
    }

    public function getAdvanceRepaymentTotal()
    {
        return $this->getAdvanceRepayments()->sum('repayment_amount');
    }

    public static function getTermTotalChargeAmountWithTax($dataProvider, $term, $whole = false)
    {
        $total = 0;
        if ($whole) {
            $dataProvider->pagination->setPage(0);
            $dataProvider->pagination->setPageSize($dataProvider->pagination->totalCount);
            $dataProvider->prepare(true);
        }
        foreach($dataProvider->models as $detail) {
            $monthlyCharges = $term->getMonthlyCharges($detail->contract_detail_id);
            if (count($monthlyCharges)) {
                foreach($monthlyCharges as $charge) {
                    $total += $charge->temporaryAmountWithTax;
                }
            }
        }
        return $total;
    }

    public static function getTermTotalPaymentAmountWithTax($searchModel, $dataProvider, $term, $whole = false)
    {
        $targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $searchModel->target_term));
        $total = 0;
        if ($whole) {
            $dataProvider->pagination->setPage(0);
            $dataProvider->pagination->setPageSize($dataProvider->pagination->totalCount);
            $dataProvider->prepare(true);
        }
        foreach($dataProvider->models as $detail) {
            /*
            $monthlyPayments = \app\models\MonthlyPayment::getSiblings($detail->contract_detail_id, $targetTerm->format('Ym'), $term['relative_month']);
            if (count($monthlyPayments) > 0) {
                foreach($monthlyPayments as $payment) {
                    $total += $payment->amountWithTax;
                }
            }
            */
            $collectionCell = CollectionCell::getInstance($detail->contract_detail_id, $term->term_id, true);
            $total += $collectionCell->monthly_payment_amount_with_tax;
        }
        return $total;
    }

    public static function getTermRepaymentTotal($dataProvider, $term, $whole = false)
    {
        $total = 0;
        if ($whole) {
            $dataProvider->pagination->setPage(0);
            $dataProvider->pagination->setPageSize($dataProvider->pagination->totalCount);
            $dataProvider->prepare(true);
        }
        foreach($dataProvider->models as $detail) {
            $monthlyCharges = $term->getMonthlyCharges($detail->contract_detail_id);
            if (count($monthlyCharges)) {
                foreach($monthlyCharges as $charge) {
                    $repayments = $charge->repayments;
                    if (count($repayments) > 0) {
                        foreach($repayments as $repayment) {
                            $total += ($repayment->repayment_amount - $repayment->chargeback_amount);
                        }
                    }
                }
            }
        }
        return $total;
    }

    public static function getTermsDetailsTotalChargeAmountWithTax($dataProvider, $terms, $whole = false)
    {
        $total = 0;
        if ($whole) {
            $dataProvider->pagination->setPage(0);
            $dataProvider->pagination->setPageSize($dataProvider->pagination->totalCount);
            $dataProvider->prepare(true);
        }
        foreach($dataProvider->models as $detail) {
            $total += $detail->getTermsTotalChargeAmountWithTax($terms);
        }
        return $total;
    }

    public static function getTermsDetailsTotalPaymentAmountWithTax($dataProvider, $terms, $whole = false)
    {
        $total = 0;
        if ($whole) {
            $dataProvider->pagination->setPage(0);
            $dataProvider->pagination->setPageSize($dataProvider->pagination->totalCount);
            $dataProvider->prepare(true);
        }
        foreach($dataProvider->models as $detail) {
            $total += $detail->getTermsTotalPaymentAmountWithTax($terms);
        }
        return $total;
    }

    public static function getTermsDetailsTotalRepaymentAmountWithTax($dataProvider, $terms, $whole = false)
    {
        $total = 0;
        if ($whole) {
            $dataProvider->pagination->setPage(0);
            $dataProvider->pagination->setPageSize($dataProvider->pagination->totalCount);
            $dataProvider->prepare(true);
        }
        foreach($dataProvider->models as $detail) {
            $total += $detail->getTermsRepaymentTotal($terms);
        }
        return $total;
    }

    public static function getWholeTotalChargeAmountWithTax($dataProvider, $whole = false)
    {
        $total = 0;
        if ($whole) {
            $dataProvider->pagination->setPage(0);
            $dataProvider->pagination->setPageSize($dataProvider->pagination->totalCount);
            $dataProvider->prepare(true);
        }
        foreach($dataProvider->models as $detail) {
            $total += $detail->totalChargeAmountWithTax;
        }
        return $total;
    }

    public static function getWholeTotalPaymentAmountWithTax($searchModel, $dataProvider, $whole = false)
    {
        $targetTerm = new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $searchModel->target_term));
        $total = 0;
        if ($whole) {
            $dataProvider->pagination->setPage(0);
            $dataProvider->pagination->setPageSize($dataProvider->pagination->totalCount);
            $dataProvider->prepare(true);
        }
        foreach($dataProvider->models as $detail) {
            $total += \app\models\Repayment::getCurrentTotal($detail->contract_detail_id, $targetTerm->format('Ym'));
        }
        return $total;
    }

    public static function getWholeTotalRepaymentAmountWithTax($dataProvider, $whole = false)
    {
        $total = 0;
        if ($whole) {
            $dataProvider->pagination->setPage(0);
            $dataProvider->pagination->setPageSize($dataProvider->pagination->totalCount);
            $dataProvider->prepare(true);
        }
        foreach($dataProvider->models as $detail) {
            $total += $detail->totalPaymentAmountWithTax;
        }
        return $total;
    }

    public function getElapsedMonths($term)
    {
        $lease_start_at = preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $this->lease_start_at);
        $leaseStartAt = new \DateTime($lease_start_at);
        $term_end_at = preg_replace('/(\d+)-(\d+)-(\d+)/', '$1-$2-1', $this->term_end_at);
        $termEndAt = new \DateTime($term_end_at);
        $diff = $leaseStartAt->diff($term);
        return $term > $termEndAt ? '終' : (int)$diff->format('%Y') * 12 + (int)$diff->format('%m') + 1;
    }

    static $tax_rates = [];
    public function getTaxRate($term = null)
    {
        if (!isset($term)) {
            $term = date('Y-m-01');
        }
        if (!isset(static::$tax_rates[$this->contract_detail_id])) {
            static::$tax_rates[$this->contract_detail_id] = Yii::$app->db->createCommand("
            SELECT
                CASE ta.fixed 
                    WHEN 1 THEN ta.tax_rate * 100 
                    ELSE (SELECT rate * 100 FROM consumption_tax_rate WHERE :term >= application_from AND :term <= IFNULL(application_to, '2099-12-31'))
                END
            FROM tax_application ta WHERE ta.tax_application_id=:id
            ")
                ->bindValues([
                    ':term' => $term,
                    ':id' => $this->tax_application_id,
                ])
                ->queryScalar();
        }
        return static::$tax_rates[$this->contract_detail_id];
    }

    public function getReceivable($term, $rate)
    {
        $erapsed = $this->getErapsedMonths($term);
        $monthlyCharges = $term->getMonthlyCharges($this->contract_detail_id);
        $monthlyCharge = count($monthlyCharges) > 1 ? array_pop($monthlyCharges) : $monthlyCharges[0] ?? false;
        $chargeFinished = false;
        if (!$monthlyCharge) {
            $lastTerm = \app\models\Term::findOne(['term' => $this->monthlyChargeSpan->last_term]);
            $monthlyCharge = $lastTerm->getMonthlyCharges($this->contract_detail_id)[0];
            $chargeFinished = true;
        }
        if ($chargeFinished) {
            return 0;
        }
        else {
            if ($rate == $this->getTaxRate($term->termDateTime->format('Y-m-d'))) {
                return $erapsed == '終' ? 0 : ($erapsed > $monthlyCharge->orderCount ? ($erapsed - $monthlyCharge->orderCount) * $this->monthlyChargeWithTax : 0);
            }
        }
        return 0;
    }

    public function getAdvances($term, $rate)
    {
        $erapsed = $this->getErapsedMonths($term);
        $monthlyCharges = $term->getMonthlyCharges($this->contract_detail_id);
        $monthlyCharge = count($monthlyCharges) > 1 ? array_pop($monthlyCharges) : $monthlyCharges[0] ?? false;
        $chargeFinished = false;
        if (!$monthlyCharge) {
            $lastTerm = \app\models\Term::findOne(['term' => $this->monthlyChargeSpan->last_term]);
            $monthlyCharge = $lastTerm->getMonthlyCharges($this->contract_detail_id)[0];
            $chargeFinished = true;
        }
        if ($chargeFinished) {
            return 0;
        }
        else {
            if ($rate == $this->getTaxRate($term->termDateTime->format('Y-m-d'))) {
                return $erapsed == '終' ? 0 : ($erapsed < $monthlyCharge->orderCount ? ($monthlyCharge->orderCount - $erapsed) * (int)$this->monthlyChargeWithTax : 0);
            }
        }
        return 0;
    }

    public function getPayable($term, $rate)
    {
        if ($this->leaseServicer->for_internal) {
            return 0;
        }
        if ($this->registration_status == 1) {
            return 0;
        }
        $erapsed = $this->getErapsedMonths($term);
        $monthlyPayments = $term->getMonthlyPayments($this->contract_detail_id);
        $monthlyPayment = count($monthlyPayments) > 1 ? array_pop($monthlyPayments) : $monthlyPayments[0] ?? false;
        $paymentFinished = false;
        if (!$monthlyPayment) {
            $lastTerm = \app\models\Term::findOne(['term' => $this->monthlyPaymentSpan->last_term]);
            $monthlyPayment = $lastTerm->getMonthlyPayments($this->contract_detail_id)[0];
            $paymentFinished = true;
        }
        if ($paymentFinished) {
            return 0;
        }
        else {
            if ($rate == $this->getTaxRate($term->termDateTime->format('Y-m-d'))) {
                return $erapsed == '終' ? 0 : ($erapsed > $monthlyPayment->orderCount ? ($erapsed - $monthlyPayment->orderCount) * (int)$this->monthlyPaymentWithTax : 0);
            }
        }
        return 0;
    }

    /**
     * 期間最終月の支払回数と回収回数の差分が0以上の場合、差分と税込支払額の積を返す
     * @param $term
     * @param $rate
     * @return integer
     */
    public function getPayableAdvance($term, $rate)
    {
        if ($this->leaseServicer->for_internal) {
            return 0;
        }
        if ($this->registration_status == 1) {
            return 0;
        }
        $erapsed = $this->getErapsedMonths($term);
        $monthlyCharges = $term->getMonthlyCharges($this->contract_detail_id);
        $monthlyCharge = count($monthlyCharges) > 1 ? array_pop($monthlyCharges) : $monthlyCharges[0] ?? false;
        $chargeFinished = false;
        if (!$monthlyCharge) {
            $lastTerm = \app\models\Term::findOne(['term' => $this->monthlyChargeSpan->last_term]);
            $monthlyCharge = $lastTerm->getMonthlyCharges($this->contract_detail_id)[0] ?? false;
            $chargeFinished = true;
        }
        $monthlyPayments = $term->getMonthlyPayments($this->contract_detail_id);
        $monthlyPayment = count($monthlyPayments) > 1 ? array_pop($monthlyPayments) : $monthlyPayments[0] ?? false;
        $paymentFinished = false;
        if (!$monthlyPayment) {
            $lastTerm = \app\models\Term::findOne(['term' => $this->monthlyPaymentSpan->last_term]);
            $monthlyPayment = $lastTerm->getMonthlyPayments($this->contract_detail_id)[0] ?? false;
            $paymentFinished = true;
        }
        if (!$monthlyCharge || !$monthlyPayment) {
            return 0;
        }
        //$chargeCount = $chargeFinished ? $this->term_months_count : $monthlyCharge->orderCount;
        $chargeCount = $erapsed;
        if ($erapsed == '終') {
            return 0;
        }
        $paymentCount = $monthlyPayment->orderCount;
        $advanceCount = $paymentCount - $chargeCount;
        if ($rate == $this->getTaxRate($term->termDateTime->format('Y-m-d'))) {
            return $advanceCount > 0 ? $advanceCount * (int)$this->monthlyPaymentWithTax : 0;
        }
        return 0;
    }

    public function getChargeRemains($term)
    {
        $total = $this->totalChargeAmountWithTax;
        $terms = $this->monthlyChargeSpan->terms;
        if ($terms[0]->termDateTime > $term->termDateTime) {
            return $total;
        }
        $index = 0;
        while (isset($terms[$index]) && $terms[$index]->termDateTime <= $term->termDateTime) {
            $monthlyCharges = $terms[$index]->getMonthlyCharges($this->contract_detail_id);
            $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []);
            $total -= array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments));
            $index++;
        }
        return $total;
    }

    public function getDelinquencies($term)
    {
        $terms = term::find()->where(['between', 'term', $this->monthlyChargeSpan->first_term, $term->term])->all();
        $monthlyCharges = array_reduce(array_map(function($term){return $term->getMonthlyCharges($this->contract_detail_id);}, $terms), 'array_merge', []);
        $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []);
        return array_sum(array_map(function($monthlyCharge){return $monthlyCharge->amountWithTax;}, $monthlyCharges)) -
            array_sum(array_map(function($repayment){return $repayment->repayment_amount;}, $repayments));
    }

    public function getErapsedMonths($term)
    {
        if ($this->leaseContract->currentStatus && in_array($this->leaseContract->currentStatus->lease_contract_status_type_id, [5, 10])) {
            return '終';
        }
        $lastTerm = $this->monthlyChargeSpan->lastTerm;
        $lastTerm = Term::findOne(['term' => (new  \DateTime($this->term_end_at))->format('Y-m-01')]);
        $leaseStartAt = preg_replace('/(\d+)年(\d*)月/', '$1-$2-01', $this->lease_start_at);
        $startTerm = Term::findOne(['term' => (new \DateTime($leaseStartAt))->format('Y-m-01')]);
        return $term->termDateTime >= $lastTerm->termDateTime ? '終' : Yii::$app->db->createCommand("SELECT PERIOD_DIFF(:term, :start)+1")->bindValues([
            ':term' => $term->termDateTime->format('Ym'),
            ':start' => $startTerm->termDateTime->format('Ym'),
        ])->queryScalar();
    }
}
