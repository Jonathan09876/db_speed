<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "repayment".
 *
 * @property int $repayment_id
 * @property int $contract_detail_id
 * @properry int $monthly_charge_id
 * @property int $repayment_type_id
 * @property float $repayment_amount
 * @property float $chargeback_amount
 * @property string $processed
 * @property string $registered
 * @property string|null $memo
 * @property string|null $removed
 * @property int|null $removed_by
 *
 * @property ContractDetail $contractDetail
 * @property User $removedBy
 * @property RepaymentType $repaymentType
 */
class Repayment extends \yii\db\ActiveRecord
{
    const SCENARIO_IMPORT = 'import';

    public $collected;
    public $additional_repayment_amount;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'repayment';
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_DEFAULT => [
                'repayment_id',
                'contract_detail_id',
                'monthly_charge_id',
                'repayment_type_id',
                'repayment_amount',
                'additional_repayment_amount',
                'chargeback_amount',
                'processed',
                'registered',
                'memo',
                'removed',
                'removed_by',
                'collected',
            ],
            self::SCENARIO_IMPORT  => [
                'repayment_id',
                'contract_detail_id',
                'monthly_charge_id',
                'repayment_type_id',
                'repayment_amount',
                'chargeback_amount',
                'processed',
                'registered',
                'memo',
                'removed',
                'removed_by',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['repayment_amount', 'chargeback_amount'], 'filter', 'filter' => [$this, 'digitOnly']],
            [['contract_detail_id', 'monthly_charge_id', 'repayment_type_id', 'repayment_amount', 'processed', 'registered'], 'required'],
            [['contract_detail_id', 'monthly_charge_id', 'repayment_type_id', 'removed_by'], 'integer'],
            [['repayment_amount', 'chargeback_amount', 'additional_repayment_amount'], 'number'],
            [['processed', 'registered', 'removed'], 'safe'],
            [['memo'], 'string', 'max' => 256],
            [['contract_detail_id'], 'exist', 'skipOnError' => true, 'targetClass' => ContractDetail::class, 'targetAttribute' => ['contract_detail_id' => 'contract_detail_id']],
            [['monthly_charge_id'], 'exist', 'skipOnError' => true, 'targetClass' => MonthlyCharge::class, 'targetAttribute' => ['monthly_charge_id' => 'monthly_charge_id']],
            [['repayment_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => RepaymentType::class, 'targetAttribute' => ['repayment_type_id' => 'repayment_type_id']],
            [['removed_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['removed_by' => 'user_id']],
            [['collected'], 'boolean'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'repayment_id' => 'Repayment ID',
            'contract_detail_id' => 'Contract Detail ID',
            'repayment_type_id' => 'Repayment Type ID',
            'repayment_amount' => 'Repayment Amount',
            'chargeback_amount' => 'Chargeback Amount',
            'processed' => 'Processed',
            'registered' => 'Registered',
            'memo' => 'Memo',
            'removed' => 'Removed',
            'removed_by' => 'Removed By',
        ];
    }

    public function digitOnly($val)
    {
        return preg_replace('/[^0-9]+/', '', $val);
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

    /**
     * Gets query for [[RemovedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRemovedBy()
    {
        return $this->hasOne(User::class, ['user_id' => 'removed_by']);
    }

    static $repaymentTypes = [];
    /**
     * Gets query for [[RepaymentType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRepaymentType()
    {
        if (!isset(static::$repaymentTypes[$this->repayment_type_id])) {
            static::$repaymentTypes[$this->repayment_type_id] = RepaymentType::findOne($this->repayment_type_id);
        }
        return static::$repaymentTypes[$this->repayment_type_id];
    }

    public function getMonthlyCharge()
    {
        return $this->hasOne(MonthlyCharge::class, ['monthly_charge_id' => 'monthly_charge_id']);
    }

    public static function getSibling($detail_id, $term, $relative_value)
    {
        $contractDetail = ContractDetail::findOne($detail_id);
        $repaymentPattern = $contractDetail->leaseContract->customer->clientContract->repaymentPattern;
        $term = new \DateTime(preg_replace('/(\d{4})(\d{2})/', '$1-$2-01', $term));
        if ($repaymentPattern->target_month == 'next') {
            $term = $term->modify('+1 month');
        }
        $sign = $relative_value > 0 ? '-' : '+';
        $value = abs($relative_value);
        return self::find()->where(['and',
            ['contract_detail_id' => $detail_id],
            ["DATE_FORMAT(processed {$sign} INTERVAL {$value} MONTH, '%Y%m')" => $term->format('Ym')]
        ])->limit(1)->one();
    }

    public static function getSiblings($detail_id, $term, $relative_value)
    {
        $contractDetail = ContractDetail::findOne($detail_id);
        $repaymentPattern = $contractDetail->leaseContract->customer->clientContract->repaymentPattern;
        $term = new \DateTime(preg_replace('/(\d{4})(\d{2})/', '$1-$2-01', $term));
        if ($repaymentPattern->target_month == 'next') {
            $term = $term->modify('+1 month');
        }
        $sign = $relative_value > 0 ? '-' : '+';
        $value = abs($relative_value);
        return self::find()->where(['and',
            ['contract_detail_id' => $detail_id],
            ["DATE_FORMAT(processed {$sign} INTERVAL {$value} MONTH, '%Y%m')" => $term->format('Ym')]
        ])->all();
    }

    public static function getTotal($provider, $attribute)
    {
        $total = 0;
        foreach($provider as $model) {
            $repayment = $model->repayments[0] ?? false;
            $total += $repayment ? $repayment->{$attribute} : 0;
        }
        return $total;
    }

    public function getOrderCountRecent()
    {
        return self::find()->where(['and',
                ['contract_detail_id' => $this->contract_detail_id],
                ['or',
                    ['<', 'processed', $this->processed],
                    ['and',
                        ['processed' => $this->processed],
                        ['<', 'repayment_id', $this->repayment_id],
                    ]
                ]
            ])->count() + 1;
    }

    public function getOrderCount()
    {
        $term = $this->monthlyCharge->term;
        return self::find()->alias('r')
                ->innerJoin('monthly_charge mc', 'r.monthly_charge_id=mc.monthly_charge_id')
                ->where(['and',
                ['r.contract_detail_id' => $this->contract_detail_id],
                ['or',
                    ['<', 'mc.term', $term],
                    ['and',
                        ['mc.term' => $term],
                        ['<', 'r.repayment_id', $this->repayment_id],
                    ]
                ]
            ])->count() + 1;
    }


    public function getOrderCountText()
    {
        $count = $this->orderCount;
        return $this->contractDetail->term_months_count < $count ? '残' : $count;
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $contractDetail = ContractDetail::findOne($this->contract_detail_id);
            $repaymentPattern = $contractDetail->leaseContract->customer->clientContract->repaymentPattern;
            $term = (new \DateTime($this->processed))->format('Y-m-01');
            $mc_term = new \DateTime($term);
            if ($repaymentPattern->target_month == 'next') {
                //$mc_term = $mc_term->modify('-1 month');
            }
            /*
            $monthlyCharge = MonthlyCharge::find()->alias('mc')
                ->leftJoin('repayment r', 'mc.monthly_charge_id=r.monthly_charge_id')
                ->leftJoin('debt d', 'mc.monthly_charge_id=d.monthly_charge_id')
                ->where([
                    'mc.contract_detail_id' => $this->contract_detail_id,
                    'mc.term' => $mc_term->format('Y-m-01'),
                    'r.repayment_id' => null,
                    'd.debt_id' => null,
                ])->limit(1)->one();
            $monthlyPayment = MonthlyPayment::find()->where([
                'contract_detail_id' => $this->contract_detail_id,
                'term' => $mc_term->format('Y-m-01')
            ])->limit(1)->one();
            */
            $monthlyCharge = MonthlyCharge::findOne($this->monthly_charge_id);
            $monthlyPayment = MonthlyPayment::find()->where(['contract_detail_id' => $this->contract_detail_id])
                ->offset($monthlyCharge->orderCount-1)->limit(1)->one();

            switch($this->repayment_type_id) {
                case 11://STOP
                    LeaseContractStatus::register($contractDetail->lease_contract_id, 8);
                case 6://売掛金
                case 9://未回収
                case 10://返金
                    //売掛登録
                    $debt = new Debt([
                        'contract_detail_id' => $this->contract_detail_id,
                        'monthly_charge_id' => $this->monthly_charge_id,
                        'debt_type_id' => $this->repayment_type_id,
                        'term' => $term,
                        'debt_amount' => $this->repayment_amount,
                        'registered' => $this->registered ?? date('Y-m-d H:i:s'),
                    ]);
                    $debt->save();
                    if ($monthlyCharge->amountWithTax == $this->repayment_amount && $this->repayment_type_id == 6 || $this->repayment_type_id == 9) {
                        //支払側も
                        $clientDebt = new ClientDebt([
                            'contract_detail_id' => $this->contract_detail_id,
                            'term' => $term,
                            'debt_amount' => $monthlyPayment->amountWithTax,
                            'registered' => date('Y-m-d H:i:s')
                        ]);
                        $clientDebt->save();
                    }
                    //入金処理はしない
                    return false;
                case 1://口座振替
                case 2://振込入金
                case 3://遅延入金
                case 4://一括振込
                case 7://相殺
                case 8://手形
                case 14:
                    //入金処理はそのまま
                    return true;
                    break;
                case 12:
                    LeaseContractStatus::register($contractDetail->lease_contract_id, 7);
                    //売掛登録
                    $debt = new Debt([
                        'contract_detail_id' => $this->contract_detail_id,
                        'monthly_charge_id' => $this->monthly_charge_id,
                        'debt_type_id' => $this->repayment_type_id,
                        'term' => $term,
                        'debt_amount' => $this->repayment_amount,
                        'registered' => $this->registered ?? date('Y-m-d H:i:s'),
                    ]);
                    $debt->save();
                    //入金処理はしない
                    return false;
                    break;
                case 5://前払いリース料は契約登録時に処理するのでここでの登録はない
                    break;
            }
        }
        else {
            return true;
        }
    }

    public function beforeDelete()
    {
        switch($this->repayment_type_id) {
            case 1://口座振替
            case 2://振込入金
            case 3://遅延入金
            case 4://一括振込
            case 7://相殺
            case 8://手形
                //売掛減額分があったら削除しておく
                Yii::$app->db->createCommand()->delete('debt', ['repayment_id' => $this->repayment_id])->execute();
        }
        $this->on(self::EVENT_AFTER_DELETE, function(){
            foreach($this->contractDetail->getCollectionCells()->each() as $collectionCell) {
                $collectionCell->updateContent();
            }
        });
        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        if ($insert && $this->scenario != self::SCENARIO_IMPORT) {
            switch($this->repayment_type_id) {
                case 1://口座振替
                    $repaymentPattern = $this->contractDetail->leaseContract->customer->clientContract->repaymentPattern;
                    $term_date = (new \DateTime($this->processed))->format('Y-m-01');
                    $term = new \DateTime($term_date);
                    if ($repaymentPattern->target_month == 'next') {
                        $term = $term->modify('-1 month');
                    }
                    $monthlyPayment = MonthlyPayment::find()->where([
                        'contract_detail_id' => $this->contract_detail_id,
                        'term' => $term->format('Y-m-d'),
                    ])->limit(1)->one();
                    if ($monthlyPayment) {
                        $leasePayment = new LeasePayment([
                            'contract_detail_id' => $this->contract_detail_id,
                            'monthly_payment_id' => $monthlyPayment->monthly_payment_id,
                            'payment_amount' => $monthlyPayment->amountWithTax,
                            'processed' => $monthlyPayment->payment_date,
                            'registered' => date('Y-m-d H:i:s'),
                        ]);
                        $leasePayment->save();
                    }
                case 2://振込入金
                case 3://遅延入金
                case 4://一括振込
                case 7://相殺
                case 8://手形
                case 14://事前振込
                    //売掛金の償却はRepaymentの紐づく対象のMonthlyChargeに当てられている対象に限定する
                    $debts = $this->monthlyCharge->debts;
                    if ($debts && $this->repayment_amount * -1 < 0) {
                        $debt_sub = new Debt([
                            'contract_detail_id' => $this->contract_detail_id,
                            'monthly_charge_id' => $this->monthly_charge_id,
                            'repayment_id' => $this->repayment_id,
                            'term' => $term,
                            'debt_amount' => $this->repayment_amount * -1,
                            'registered' => date('Y-m-d H:i:s')
                        ]);
                        $debt_sub->save();
                    }
                    break;

            }
        }
        $this->updateContent();
    }

    public static function getCurrentTotal($detail_id, $term = null)
    {
        if (is_null($term)) {
            $term = (new \DateTime('now'))->format('Ym');
        }
        $query = MonthlyCharge::find()->alias('mc')
            ->innerJoin('contract_detail cd', 'mc.contract_detail_id=cd.contract_detail_id')
            ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
            ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
            ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
            ->innerJoin('repayment_pattern rp', 'cc.repayment_pattern_id=rp.repayment_pattern_id')
            ->where(['and',
                ['mc.contract_detail_id' => $detail_id],
                ['<=', 'DATE_FORMAT(CASE rp.target_month WHEN "NEXT" THEN mc.term + INTERVAL 1 MONTH ELSE mc.term END, "%Y%m")', $term]
            ]);
        $total = 0;
        foreach($query->each() as $model) {
            $repayments = $model->repayments;
            foreach($repayments as $repayment) {
                $total += ($repayment->repayment_amount - $repayment->chargeback_amount);
            }
        }
        return $total;
    }

    public function getCollectionCell()
    {
        $term = $this->termInstance;
        return CollectionCell::getInstance($this->contract_detail_id, $term->term_id);
    }

    public function updateContent()
    {
        foreach($this->contractDetail->getCollectionCells()->each() as $collectionCell) {
            $collectionCell->updateContent();
        }
    }
}
