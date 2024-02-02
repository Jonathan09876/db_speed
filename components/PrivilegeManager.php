<?php

namespace app\components;

use Yii;
use yii\helpers\VarDumper;

class PrivilegeManager extends \yii\base\Component
{
    static $privileges = [
        ['route' => 'aas/masters', 'roles' => ['system_administrator', 'administrator']],
        ['route' => 'aas/store-collection-data', 'roles' => ['system_administrator', 'administrator']],
        ['route' => 'aas/remove-stored-collection-data', 'roles' => ['system_administrator', 'administrator']],
        ['route' => 'update-recent-monthly-charge', 'roles' => ['system_administrator', 'administrator']],
        ['route' => 'aas/close-stored-collection-data', 'roles' => ['system_administrator', 'administrator']],
        ['route' => 'aas/unclose-stored-collection-data', 'roles' => ['system_administrator', 'administrator']],
        ['route' => 'aas/register-stored-collection-data', 'roles' => ['system_administrator', 'administrator']],
    ];

    static function hasPrivilege($route)
    {
        $privilege = array_filter(self::$privileges, function($pv)use($route){
            return $pv['route'] == $route;
        });
        if (count($privilege) > 0) {
            return in_array(Yii::$app->user->identity->role, current($privilege)['roles']);
        }
        else {
            return true;
        }
    }
}