<?php

namespace app\models;

use Yii;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "lease_contract".
 *
 * @property int $lease_contract_id
 * @property int $customer_id
 * @property int $lease_target_id
 * @property int $contract_pattern_id
 * @property string $contract_number
 * @property string $contract_code
 * @property string $contract_sub_code
 * @property string $code_search
 * @property string $contract_date
 * @property boolean $registration_incomplete
 *
 * @property ContractDetail[] $contractDetails
 * @property ContractPattern $contractPattern
 * @property Customer $customer
 * @property LeaseContractStatus[] $leaseContractStatuses
 * @property LeaseTarget $leaseTarget
 */
class LeaseContract extends \yii\db\ActiveRecord
{
    const SCENARIO_REGISTER = 'register';

    public $customer_client_corporation;
    public $customer_code;
    public $customer_name;

    public $contract_number_check;
    public $current_status;
    public $regenerateMonthlyChargesPayments = 0;
    public $regenerateMonthlyCharges = 0;
    public $regenerateMonthlyPayments = 0;

    public function scenarios()
    {
        return [
            self::SCENARIO_DEFAULT => [
                'customer_client_corporation', 'customer_code', 'customer_name',
                'lease_contract_id', 'customer_id', 'lease_target_id',
                'contract_pattern_id', 'contract_number', 'contract_code', 'contract_sub_code', 'code_search',
                'contract_date',
                'registration_incomplete',
                'collection_application_complete',
                'current_status',
                'disp_order',
                'memo',
                'regenerateMonthlyChargesPayments',
                'regenerateMonthlyCharges',
                'regenerateMonthlyPayments'
            ],
            self::SCENARIO_REGISTER => [
                'customer_client_corporation', 'customer_code', 'customer_name',
                'lease_contract_id', 'customer_id', 'lease_target_id',
                'contract_pattern_id', 'contract_number', 'contract_code', 'contract_sub_code', 'code_search',
                'contract_date', 'contract_number_check',
                'registration_incomplete',
                'collection_application_complete',
                'disp_order',
                'memo',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'lease_contract';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['customer_id'], 'required', 'message' => '得意先が選択されていません。'],
            [['lease_target_id', 'contract_pattern_id', 'contract_number', 'contract_code', 'contract_date'], 'required', 'message' => '未入力です。'],
            [['customer_id', 'lease_target_id', 'contract_pattern_id', 'customer_client_corporation', 'disp_order'], 'integer'],
            [['contract_date'], 'safe'],
            [['contract_number', 'contract_code', 'contract_sub_code', 'code_search'], 'string', 'max' => 32],
            [['contract_pattern_id'], 'exist', 'skipOnError' => true, 'targetClass' => ContractPattern::class, 'targetAttribute' => ['contract_pattern_id' => 'contract_pattern_id']],
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customer::class, 'targetAttribute' => ['customer_id' => 'customer_id']],
            [['lease_target_id'], 'exist', 'skipOnError' => true, 'targetClass' => LeaseTarget::class, 'targetAttribute' => ['lease_target_id' => 'lease_target_id']],
            [['customer_name', 'customer_code', 'memo'], 'safe'],
            [['registration_incomplete', 'collection_application_complete', 'regenerateMonthlyChargesPayments', 'regenerateMonthlyCharges', 'regenerateMonthlyPayments'], 'boolean'],
            [['current_status'], 'in', 'range' => array_keys(LeaseContractStatusType::getTypes())],
            [['contract_number_check'], 'checkDuplicateNumber', 'skipOnError' => false, 'on' => [self::SCENARIO_REGISTER]],
        ];
    }

    public function beforeValidate()
    {
        if ($this->scenario == self::SCENARIO_REGISTER) {
            $this->contract_number_check = $this->getContractNumber();
        }
        return true;
    }

    public function checkDuplicateNumber($attr, $params)
    {
        $checker = LeaseContract::find()
            ->where(['and',
                ['contract_pattern_id' => $this->contract_pattern_id],
                ['contract_number' => $this->contract_number],
                ['contract_code' => $this->contract_code],
                ['contract_sub_code' => $this->contract_sub_code]
            ])->count();
        if ($checker) {
            $this->addError('contract_number_check', '契約番号が登録済みの情報と重複しています。');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'lease_contract_id' => 'Lease Contract ID',
            'customer_id' => 'Customer ID',
            'lease_target_id' => 'Lease Target ID',
            'contract_pattern_id' => 'Contract Pattern ID',
            'contract_number' => 'Contract Number',
            'contract_code' => 'Contract Code',
            'contract_sub_code' => 'Contract Sub Code',
            'contract_date' => '契約日',
            'customer_client_corporation' => '会社',
            'customer_code' => '得意先コード',
            'customer_name' => '得意先名',
            'registration_incomplete' => '契約情報完',
            'collection_application_complete' => '回収代行申請完',
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->regenerateMonthlyChargesPayments = 1;
            $this->regenerateMonthlyCharges = 1;
            $this->regenerateMonthlyPayments = 1;
        }
        if (!$this->disp_order) {
            $this->disp_order = self::find()->max('disp_order') + 1;
        }
        $this->code_search = $this->contractNumber;
        return true;
    }

    public function afterFind()
    {
        $this->customer_client_corporation = $this->customer->clientContract->client_corporation_id;
        $this->customer_code = $this->customer->customer_code;
        $this->customer_name = $this->customer->getName();
        $this->current_status = $this->currentStatus ? $this->currentStatus->lease_contract_status_type_id : null;
    }

    public function afterSave($insert, $changedAttributes)
    {
        if ($this->current_status) {
            if (!$this->currentStatus || ($this->currentStatus && $this->currentStatus->lease_contract_status_type_id != $this->current_status)) {
                LeaseContractStatus::register($this->lease_contract_id, $this->current_status);
            }
        }
    }

    /**
     * Gets query for [[ContractDetails]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getContractDetails()
    {
        return $this->hasMany(ContractDetail::class, ['lease_contract_id' => 'lease_contract_id']);
    }

    /**
     * Gets query for [[ContractPattern]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getContractPattern()
    {
        return $this->hasOne(ContractPattern::class, ['contract_pattern_id' => 'contract_pattern_id']);
    }

    /**
     * Gets query for [[Customer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::class, ['customer_id' => 'customer_id']);
    }

    /**
     * Gets query for [[LeaseContractStatuses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLeaseContractStatuses()
    {
        return $this->hasMany(LeaseContractStatus::class, ['lease_contract_id' => 'lease_contract_id']);
    }

    /**
     * Gets query for [[LeaseTarget]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLeaseTarget()
    {
        return $this->hasOne(LeaseTarget::class, ['lease_target_id' => 'lease_target_id']);
    }

    public function getStatuses()
    {
        return $this->hasMany(LeaseContractStatus::class, ['lease_contract_id' => 'lease_contract_id']);
    }

    public function getCurrentStatus()
    {
        return $this->getStatuses()->orderBy(['registered' => SORT_DESC])->limit(1)->one();
    }

    public function getIsStoppedOrCancelled()
    {
        $currentStatus = $this->currentStatus;
        return $currentStatus ? in_array($currentStatus->lease_contract_status_type_id, [5]) : false;
    }

    public function getContractNumber()
    {

        return !!$this->contract_pattern_id ? sprintf("{$this->contractPattern->code}{$this->contract_number}-%04d", (int)$this->contract_code) . ($this->contract_sub_code ? "-{$this->contract_sub_code}" : '') : '';
    }

    public function updateOrder()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand('SET @rank=0')->execute();
            Yii::$app->db->createCommand("
UPDATE lease_contract lc JOIN (SELECT t.lease_contract_id, @rank:=@rank+1 as ndo FROM (SELECT `lc`.* FROM `lease_contract` `lc` 
INNER JOIN `lease_target` `lt` ON lc.lease_target_id=lt.lease_target_id 
INNER JOIN `customer` `c` ON lc.customer_id=c.customer_id 
INNER JOIN `client_contract` `cc` ON c.client_contract_id=cc.client_contract_id 
LEFT JOIN `contract_detail` `cd` ON lc.lease_contract_id=cd.lease_contract_id 
ORDER BY `cc`.`client_corporation_id`, `c`.`customer_code`, `lc`.`disp_order`, `cd`.`term_start_at`) t) nt ON lc.lease_contract_id=nt.lease_contract_id
SET lc.disp_order=nt.ndo
WHERE 1")->execute();
            $transaction->commit();
        } catch(Exception $e) {
            $transaction->rollBack();
        }
    }

    public function remove()
    {
        foreach($this->contractDetails as $contractDetail) {
            if ($contractDetail->monthlyCharges) foreach($contractDetail->monthlyCharges as $monthlyCharge) {
                if ($monthlyCharge->repayments) foreach($monthlyCharge->repayments as $repayment) {
                    $repayment->delete();
                }
                if ($monthlyCharge->advanceRepayments) {
                    if ($monthlyCharge->advanceRepayments instanceof AdvanceRepayment) {
                        $monthlyCharge->advanceRepayments->delete();
                    }
                    else foreach($monthlyCharge->advanceRepayments as $advanceRepayment) {
                        $advanceRepayment->delete();
                    }
                }
                if ($monthlyCharge->debts) foreach($monthlyCharge->debts as $debt) {
                    $debt->delete();
                }
                $monthlyCharge->delete();
            }
            if ($contractDetail->monthlyPayments) foreach($contractDetail->monthlyPayments as $monthlyPayment) {
                if ($monthlyPayment->leasePayments) foreach($monthlyPayment->leasePayments as $leasePayment) {
                    $leasePayment->delete();
                }
                $monthlyPayment->delete();
            }
            $contractDetail->delete();
        }
        foreach($this->leaseContractStatuses as $leaseContractStatus) {
            $leaseContractStatus->delete();
        }
        $this->delete();
    }

    public static function getContractPatterns($id)
    {
        $patterns = ContractPattern::find()->where(['client_corporation_id' => $id, 'removed' => NULL])->all();
        return ArrayHelper::map($patterns, 'contract_pattern_id', 'code');
    }
}
