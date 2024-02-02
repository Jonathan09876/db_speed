<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "client_corporation".
 *
 * @property int $client_corporation_id
 * @property string|null $code
 * @property string|null $name
 * @property string|null $shorten_name
 * @property int|null $account_closing_month
 * @property string|null $removed
 *
 * @property ContractPattern[] $contractPatterns
 * @property Customer[] $customers
 */
class ClientCorporation extends \yii\db\ActiveRecord
{
    public $client_corporation_children;
    static $months = [
        '1' => '1月',
        '2' => '2月',
        '3' => '3月',
        '4' => '4月',
        '5' => '5月',
        '6' => '6月',
        '7' => '7月',
        '8' => '8月',
        '9' => '9月',
        '10' => '10月',
        '11' => '11月',
        '12' => '12月',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'client_corporation';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['account_closing_month'], 'integer'],
            [['removed'], 'safe'],
            [['code'], 'string', 'max' => 16],
            [['name'], 'string', 'max' => 256],
            [['shorten_name'], 'string', 'max' => 64],
            [['client_corporation_children'], 'each', 'rule' => ['exist', 'targetClass' => ClientCorporation::class, 'targetAttribute' => 'client_corporation_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'client_corporation_id' => 'Client Corporation ID',
            'code' => '会社コード',
            'name' => '会社名',
            'shorten_name' => '略称',
            'account_closing_month' => '決算月',
            'removed' => 'Removed',
            'client_corporation_children' => 'アクセス対象会社'
        ];
    }

    /**
     * Gets query for [[ContractPatterns]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getContractPatterns()
    {
        return $this->hasMany(ContractPattern::class, ['client_corporation_id' => 'client_corporation_id']);
    }

    /**
     * Gets query for [[Customers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomers()
    {
        return $this->hasMany(Customer::class, ['client_corporation_id' => 'client_corporation_id']);
    }

    public static function getClientCorporations()
    {
        return ArrayHelper::map(self::find()->where(['removed' => null])->all(), 'client_corporation_id', 'name');
    }

    public function getClientCorporationHierarchy()
    {
        return $this->hasMany(ClientCorporationHierarchy::class, ['parent_client_corporation_id' => 'client_corporation_id']);
    }

    public function getClientCorporationChildren()
    {
        return $this->hasMany(ClientCorporation::class, ['client_corporation_id' => 'child_client_corporation_id'])
            ->viaTable('client_corporation_hierarchy', ['parent_client_corporation_id' => 'client_corporation_id']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        //client_corporation_hierarchyのレコードを更新
        ClientCorporationHierarchy::deleteAll(['parent_client_corporation_id' => $this->client_corporation_id]);
        $children = $this->client_corporation_children;
        if (empty($children)) {
            $children = [$this->client_corporation_id];
        }
        foreach($children as $child_client_corporation_id) {
            $instance = new ClientCorporationHierarchy([
                'parent_client_corporation_id' => $this->client_corporation_id,
                'child_client_corporation_id' => $child_client_corporation_id,
            ]);
            $instance->save();
        }
    }
}