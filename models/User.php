<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "user".
 *
 * @property integer $user_id
 * @property string $username
 * @property string $password
 * @property string $name
 * @property string $role
 * @property integer $client_corporation_id
 * @property string $auth_key
 * @property string $access_token
 * @property integer $status
 */
class User extends ActiveRecord implements IdentityInterface
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['client_corporation_id', 'status'], 'integer'],
            [['username', 'role'], 'string', 'max' => 32],
            [['password', 'name', 'auth_key', 'access_token'], 'string', 'max' => 128],
            [['client_corporation_id'], 'exist', 'targetClass' => ClientCorporation::class],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'username' => 'ログインアカウント',
            'password' => 'パスワード',
            'name' => 'アカウント名称',
            'role' => 'Role',
            'client_corporation_id' => '所属会社',
            'auth_key' => 'Auth Key',
            'access_token' => 'Access Token',
            'status' => 'Status',
        ];
    }

    public static function findByUsername($username)
    {
        return static::findOne(['username'=>$username]);
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type=null)
    {
        return static::findOne(['access_token' => $token]);
    }

    public function getId()
    {
        return $this->user_id;
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($auth_key)
    {
        return $this->getAuthKey() === $auth_key;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->access_token = Yii::$app->db->createCommand('SELECT UUID()')->queryColumn()[0];
                $this->auth_key = Yii::$app->security->generateRandomString();
                $this->password = Yii::$app->security->generatePasswordHash($this->password);
            }
            else {
                if ($this->password != $this->getOldAttribute('password')) {
                    $this->password = Yii::$app->security->generatePasswordHash($this->password);
                }
            }
            return true;
        }
        return false;
    }

    public function hashPassword($password)
    {
        return Yii::$app->security->generatePasswordHash($password);
    }

    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    public static function getUserList()
    {
        return ArrayHelper::map(self::find()->all(), 'user_id', 'name');
    }

    public function getClientCorporation()
    {
        return $this->hasOne(ClientCorporation::class, ['client_corporation_id' => 'client_corporation_id']);
    }
}
