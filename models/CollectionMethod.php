<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "collection_method".
 *
 * @property int $collection_method_id
 * @property string $method
 * @property string|null $removed
 */
class CollectionMethod extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'collection_method';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['method'], 'required'],
            [['removed'], 'safe'],
            [['method'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'collection_method_id' => 'Collection Method ID',
            'method' => 'Method',
            'removed' => 'Removed',
        ];
    }
}
