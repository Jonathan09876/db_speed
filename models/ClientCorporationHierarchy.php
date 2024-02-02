<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "client_corporation_ hierarchy".
 *
 * @property int $parent_client_corporation_id
 * @property int $child_client_corporation_id
 */
class ClientCorporationHierarchy extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'client_corporation_hierarchy';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['parent_client_corporation_id', 'child_client_corporation_id'], 'required'],
            [['parent_client_corporation_id', 'child_client_corporation_id'], 'integer'],
            [['parent_client_corporation_id', 'child_client_corporation_id'], 'unique', 'targetAttribute' => ['parent_client_corporation_id', 'child_client_corporation_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'parent_client_corporation_id' => 'Parent Client Corporation ID',
            'child_client_corporation_id' => 'Child Client Corporation ID',
        ];
    }
}
