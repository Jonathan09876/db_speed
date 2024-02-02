<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "contract_pattern".
 *
 * @property int $contract_pattern_id
 * @property int $client_corporation_id
 * @property string|null $pattern_name
 * @property string|null $code
 * @property string|null $bg_color
 * @property string|null $removed
 *
 * @property ClientCorporation $clientCorporation
 */
class ContractPattern extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contract_pattern';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['client_corporation_id', 'pattern_name', 'code'], 'required'],
            [['client_corporation_id'], 'integer'],
            [['removed'], 'safe'],
            [['pattern_name'], 'string', 'max' => 64],
            [['code', 'bg_color'], 'string', 'max' => 16],
            [['client_corporation_id'], 'exist', 'skipOnError' => true, 'targetClass' => ClientCorporation::class, 'targetAttribute' => ['client_corporation_id' => 'client_corporation_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'contract_pattern_id' => 'Contract Pattern ID',
            'client_corporation_id' => '対象会社',
            'pattern_name' => '契約種別',
            'code' => 'コード',
            'bg_color' => '色指定',
            'removed' => 'Removed',
        ];
    }

    /**
     * Gets query for [[ClientCorporation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClientCorporation()
    {
        return $this->hasOne(ClientCorporation::class, ['client_corporation_id' => 'client_corporation_id']);
    }

    public static function getContractPatterns($id = null)
    {
        if (isset($id)) {
            return ArrayHelper::map(self::find()->where(['client_corporation_id' => $id, 'removed' => null])->all(), 'contract_pattern_id', 'code');
        }
        return ArrayHelper::map(self::find()->where(['removed' => null])->all(), 'contract_pattern_id', 'code');
    }

    public static function getContractNamePatterns($client_corporation_id = null)
    {
        if (is_null($client_corporation_id)) {
            $client_corporation_id = Yii::$app->user->identity->client_corporation_id;
        }
        return ArrayHelper::map(self::find()->where(['client_corporation_id' => $client_corporation_id, 'removed' => null])->all(), 'contract_pattern_id', 'pattern_name');
    }

    public static function getFilteredPatterns($client_corporation_id, $filter)
    {
        $patterns = self::find()
            ->select(['contract_pattern_id'])
            ->where(['and',
                ['client_corporation_id' => $client_corporation_id],
                ['like', 'pattern_name', $filter]
            ])
            ->column();
        if ($filter == '割賦') {
            $subPatterns = self::getFilteredPatterns($client_corporation_id, '転割賦');
            return array_diff($patterns, $subPatterns);
        }
        return $patterns;
    }
}
