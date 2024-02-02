<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "import_contract_detail".
 *
 * @property string $import_contract_detail_id 契約情報詳細ID
 * @property string $import_lease_contract_id 契約内容ID
 * @property string $lease_servicer リース会社
 * @property string $contract_type 契約区分
 * @property string $tax_application 消費税区分
 * @property int $fraction_processing_pattern 端数処理
 * @property string $term_start_at  リース開始年月
 * @property string $term_end_at リース終了年月
 * @property int $term_months_count リース回数
 * @property float|null $monthly_charge 毎月回収額税抜
 * @property float|null $monthly_payment 毎月支払額税抜
 * @property int|null $bonus_month_1 ボーナス支払月1
 * @property float|null $bonus_additional_charge_1 ボーナス回収加算額1
 * @property float|null $bonus_additional_payment_1 ボーナス支払加算額1
 * @property int|null $bonus_month_2 ボーナス支払月2
 * @property float|null $bonus_additional_charge_2 ボーナス回収加算額2
 * @property float|null $bonus_additional_payment_2 ボーナス支払加算額2
 * @property float $total_charge_amount 総回収額
 * @property float $total_payment_amount 総支払額
 * @property int|null $advance_repayment_count 前払回収回数
 * @property int|null $advance_payment_count 前払支払回数
 * @property string|null $collection_start_at
 * @property int|null $collection_latency 回収開始月月数
 * @property string|null $payment_start_at
 * @property int|null $payment_latency 支払開始月月数
 * @property string|null $lease_start_at リース開始月
 */
class ImportContractDetail extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'import_contract_detail';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['total_charge_amount', 'total_payment_amount', 'monthly_charge', 'monthly_payment'], 'filter', 'filter' => function($val){
                return preg_replace('/[^0-9]+/', '', $val);
            }],
            [['import_contract_detail_id', 'import_lease_contract_id', 'lease_servicer', 'contract_type', 'tax_application', 'fraction_processing_pattern', 'term_start_at', 'term_end_at', 'term_months_count', 'total_charge_amount', 'total_payment_amount'], 'required'],
            [['fraction_processing_pattern', 'term_months_count', 'bonus_month_1', 'bonus_month_2', 'advance_repayment_count', 'advance_payment_count', 'collection_latency', 'payment_latency'], 'integer'],
            [['term_start_at', 'term_end_at', 'lease_start_at', 'collection_start_at', 'payment_start_at'], 'safe'],
            [['monthly_charge', 'monthly_payment', 'bonus_additional_charge_1', 'bonus_additional_payment_1', 'bonus_additional_charge_2', 'bonus_additional_payment_2', 'total_charge_amount', 'total_payment_amount'], 'number'],
            [['import_contract_detail_id', 'import_lease_contract_id', 'lease_servicer', 'contract_type', 'tax_application'], 'string', 'max' => 64],
            [['import_contract_detail_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'import_contract_detail_id' => 'Import Contract Detail ID',
            'import_lease_contract_id' => 'Import Lease Contract ID',
            'lease_servicer' => 'Lease Servicer',
            'contract_type' => 'Contract Type',
            'tax_application' => 'Tax Application',
            'fraction_processing_pattern' => 'Fraction Processing Pattern',
            'term_start_at' => 'Term Start At',
            'term_end_at' => 'Term End At',
            'term_months_count' => 'Term Months Count',
            'monthly_charge' => 'Monthly Charge',
            'monthly_payment' => 'Monthly Payment',
            'bonus_month_1' => 'Bonus Month 1',
            'bonus_additional_charge_1' => 'Bonus Additional Charge 1',
            'bonus_additional_payment_1' => 'Bonus Additional Payment 1',
            'bonus_month_2' => 'Bonus Month 2',
            'bonus_additional_charge_2' => 'Bonus Additional Charge 2',
            'bonus_additional_payment_2' => 'Bonus Additional Payment 2',
            'total_charge_amount' => 'Total Charge Amount',
            'total_payment_amount' => 'Total Payment Amount',
            'advance_repayment_count' => 'Advance Repayment Count',
            'advance_payment_count' => 'Advance Payment Count',
            'collection_latency' => 'Collection Latency',
            'payment_latency' => 'Payment Latency',
            'lease_start_at' => 'Lease Start at',
        ];
    }
}
