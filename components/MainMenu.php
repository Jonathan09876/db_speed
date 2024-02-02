<?php

namespace app\components;

use Yii;
use yii\bootstrap5\Html;

/**
 * <li class="menu-title"><span data-key="t-menu">Menu</span></li>
 */
class MainMenu extends \yii\base\Component
{
    public static function getItems()
    {
        return [
            '<li class="menu-title"><span data-key="t-menu">Menu</span></li>',
            ['label' => '契約情報', 'url' => ['/aas/lease-contracts']],
            ['label' => '顧客管理', 'url' => ['/aas/customers']],
            ['label' => '回収予定', 'url' => ['/collection/schedules']],
            ['label' => 'データ作成', 'url' => ['/collection/calc-data']],
            ['label' => '実績集計', 'url' => ['/collection/delinquencies']],
            /*
            '<li class="nav-item">' .
                Html::beginForm(['/collection/schedules']) .
                    Html::hiddenInput('ScheduleSearch[skip_search]', 1) .
                    Html::submitInput('データ作成', ['class' => 'nav-link btn btn-link','name' => 'ScheduleSearch[calc_collection_data]']) .
                Html::endForm() .
            '</li>',
            '<li class="nav-item">' .
                Html::beginForm(['/collection/schedules']) .
                    Html::hiddenInput('ScheduleSearch[skip_search]', 1) .
                    Html::submitInput('実績集計', ['class' => 'nav-link btn btn-link', 'name' => 'ScheduleSearch[delinquencies]']) .
                Html::endForm() .
            '</li>',
            */
            ['label' => '回収実績', 'url' => ['/aas/stored-collection-data']],
            ['label' => 'マスタ管理', 'url' => ['/aas/masters'], 'visible' => PrivilegeManager::hasPrivilege('aas/masters')],
            Yii::$app->user->isGuest
                ? ['label' => 'ログイン', 'url' => ['/site/login']]
                : '<li class="nav-item">'
                . Html::beginForm(['/aas/logout'])
                . Html::submitButton(
                    'ログアウト (' . Yii::$app->user->identity->username . ')',
                    ['class' => 'nav-link btn btn-link logout']
                )
                . Html::endForm()
                . '</li>'
        ];
    }
}