<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "zengin_master".
 *
 * @property string|null $bank_code
 * @property string|null $branch_code
 * @property string|null $name_kana
 * @property string|null $name
 * @property int|null $division
 */
class ZenginMaster extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'zengin_master';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['division'], 'integer'],
            [['bank_code'], 'string', 'max' => 4],
            [['branch_code'], 'string', 'max' => 3],
            [['name_kana', 'name'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'bank_code' => 'Bank Code',
            'branch_code' => 'Branch Code',
            'name_kana' => 'Name Kana',
            'name' => 'Name',
            'division' => 'Division',
        ];
    }
}
