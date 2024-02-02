<?php

namespace app\models;

use Yii;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "target_term_monthly_charge_stored".
 *
 * @property int $target_term_monthly_charge_stored_id
 * @property string $target_term
 * @property int $client_corporation_id
 * @property int $repayment_pattern_id
 * @property string|null $memo
 * @property int $is_closed
 */
class TargetTermMonthlyChargeStored extends \yii\db\ActiveRecord
{
    public $transfer_date;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'target_term_monthly_charge_stored';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['target_term', 'client_corporation_id', 'repayment_pattern_id'], 'required'],
            [['target_term', 'closed_at'], 'safe'],
            [['client_corporation_id', 'repayment_pattern_id'], 'integer'],
            [['memo'], 'string'],
            [['is_closed'], 'boolean'],
            [['transfer_date'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'target_term_monthly_charge_stored_id' => 'Target Term Monthly Charge Stored ID',
            'target_term' => '対象月',
            'client_corporation_id' => '対象会社',
            'repayment_pattern_id' => '回収条件',
            'memo' => 'Memo',
        ];
    }

    public function getClientCorporation()
    {
        return $this->hasOne(ClientCorporation::class, ['client_corporation_id' => 'client_corporation_id']);
    }

    public function getRepaymentPattern()
    {
        return $this->hasOne(RepaymentPattern::class, ['repayment_pattern_id' => 'repayment_pattern_id']);
    }

    public function getMonthlyCharges()
    {
        return $this->hasMany(MonthlyCharge::class, ['monthly_charge_id' => 'monthly_charge_id'])
            ->viaTable('stored_monthly_charge', ['target_term_monthly_charge_stored_id' => 'target_term_monthly_charge_stored_id']);
    }

    public static function register_recent($params)
    {
        $searchModel = new MonthlyChargeSearch2();
        $dataProvider = $searchModel->search($params);
        $targetTerm = new \DateTime(
            preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $searchModel->target_term)
        );
        $instance = TargetTermMonthlyChargeStored::findOne([
            'target_term' => $targetTerm->format('Y-m-d'),
            'client_corporation_id' => $searchModel->client_corporation_id,
            'repayment_pattern_id' => $searchModel->repayment_pattern_id,
        ]);
        if (!$instance) {
            $instance = new TargetTermMonthlyChargeStored([
                'target_term' => $targetTerm->format('Y-m-d'),
                'client_corporation_id' => $searchModel->client_corporation_id,
                'repayment_pattern_id' => $searchModel->repayment_pattern_id,
            ]);
            $instance->save();
        }
        $query = $dataProvider->query;
        if (!$instance->isNewRecord) {
            Yii::$app->db->createCommand()->delete('stored_monthly_charge', ['target_term_monthly_charge_stored_id' => $instance->target_term_monthly_charge_stored_id])->execute();
        }
        $rows = $query->select(["{$instance->target_term_monthly_charge_stored_id} as `target_term_monthly_charge_stored_id`",'mc.monthly_charge_id'])->distinct()->andWhere(['not', ['mc.monthly_charge_id'  => null]])->asArray()->all();
        Yii::$app->db->createCommand()->batchInsert('stored_monthly_charge', ['target_term_monthly_charge_stored_id', 'monthly_charge_id'], $rows)->execute();
    }

    public static function register($params)
    {
        $searchModel = new ScheduleSearch();
        $dataProvider = $searchModel->search($params, 'store_collection_data');
        $targetTerm = new \DateTime(
            preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $searchModel->target_term)
        );
        $instance = TargetTermMonthlyChargeStored::findOne([
            'target_term' => $targetTerm->format('Y-m-d'),
            'client_corporation_id' => $searchModel->client_corporation_id,
            'repayment_pattern_id' => $searchModel->repayment_pattern_id,
        ]);
        if (!$instance) {
            $instance = new TargetTermMonthlyChargeStored([
                'target_term' => $targetTerm->format('Y-m-d'),
                'client_corporation_id' => $searchModel->client_corporation_id,
                'repayment_pattern_id' => $searchModel->repayment_pattern_id,
            ]);
            $instance->save();
        }
        $query = $dataProvider->query;
        if (!$instance->isNewRecord) {
            Yii::$app->db->createCommand()->delete('stored_monthly_charge', ['target_term_monthly_charge_stored_id' => $instance->target_term_monthly_charge_stored_id])->execute();
            Yii::$app->db->createCommand()->delete('stored_monthly_charge_repayment_registered', ['target_term_monthly_charge_stored_id' => $instance->target_term_monthly_charge_stored_id])->execute();
        }
        $rows = $query->select(["{$instance->target_term_monthly_charge_stored_id} as `target_term_monthly_charge_stored_id`",'mc.monthly_charge_id'])->distinct()->andWhere(['not', ['mc.monthly_charge_id'  => null]])->asArray()->all();
        Yii::$app->db->createCommand()->batchInsert('stored_monthly_charge', ['target_term_monthly_charge_stored_id', 'monthly_charge_id'], $rows)->execute();
        foreach($instance->monthlyCharges as $monthlyCharge) {
            if ($monthlyCharge->getRepayments()->count() > 0) {
                Yii::$app->db->createCommand()->insert('stored_monthly_charge_repayment_registered', [
                    'target_term_monthly_charge_stored_id' => $instance->target_term_monthly_charge_stored_id,
                    'monthly_charge_id' => $monthlyCharge->monthly_charge_id,
                ])->execute();
            }
        }
    }

    public function hasRegisteredRepayment($monthlyCharge)
    {
        //leaseContract->customer->clientContract->
        return Yii::$app->db->createCommand('
            SELECT COUNT(*) FROM `stored_monthly_charge_repayment_registered` `t`
            JOIN `monthly_charge` `mc` ON `t`.`monthly_charge_id` = `mc`.`monthly_charge_id`
            JOIN `contract_detail` `cd` ON `mc`.`contract_detail_id` = `cd`.`contract_detail_id`
            JOIN `lease_contract` `lc` ON `cd`.`lease_contract_id` = `lc`.`lease_contract_id`
            JOIN `customer` `c` ON `lc`.`customer_id` = `c`.`customer_id`
            JOIN `client_contract` `cc` ON `c`.`client_contract_id` = `cc`.`client_contract_id`
            JOIN `repayment_pattern` `rp` ON `cc`.`repayment_pattern_id` = `rp`.`repayment_pattern_id`
            WHERE `t`.`target_term_monthly_charge_stored_id`=:sid AND IFNULL(`mc`.`repayment_type_id`, `rp`.`repayment_type_id`) IN (2,4,5,14) AND `t`.`monthly_charge_id`=:mid
            ')
            ->bindValues([
                ':sid' => $this->target_term_monthly_charge_stored_id,
                ':mid' => $monthlyCharge->monthly_charge_id
            ])->queryScalar();
    }

    public static function isMonthClosed($term, $corporation_id)
    {
        return self::find()->where([
            'target_term' => $term,
            'client_corporation_id' => $corporation_id,
            'is_closed' => 1
        ])->count() > 0;
    }

    public static function isUpdateEnable($term, $corporation_id, $pattern_id)
    {
        return self::find()->where([
                'target_term' => $term,
                'client_corporation_id' => $corporation_id,
                'repayment_pattern_id' => $pattern_id,
                'is_closed' => 1
            ])->count() == 0;
    }

    public static function isStored($term, $corporation_id, $pattern_id)
    {
        $stored = self::find()->where([
                'target_term' => $term,
                'client_corporation_id' => $corporation_id,
                'repayment_pattern_id' => $pattern_id,
                //'is_closed' => 0
            ])->one();
        return $stored ? $stored->target_term_monthly_charge_stored_id : false;
    }

    public function closeTerm()
    {
        $this->is_closed = 1;
        $this->closed_at = date('Y-m-d H:i:s');
        $this->save();
    }

    public function uncloseTerm()
    {
        $this->is_closed = 0;
        $this->closed_at = null;
        $this->save();
    }
}
