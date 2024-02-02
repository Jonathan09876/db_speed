<?php

namespace app\components;

use Yii;

class Helper extends \yii\base\Component
{
    public static function calcTaxIncluded($id, $amount, $method, $term = null)
    {
        if (!isset($term)) {
            $term = date('Y-m-d');
        }
        $methods = [
            'floor' => 'FLOOR',
            'ceil' => 'CEIL',
            'roundup' => 'ROUND'
        ];
        $sql = "SELECT {$methods[$method]}(:amount * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE :term >= application_from AND :term <= IFNULL(application_to, '2099-12-31')) END)) FROM tax_application ta WHERE ta.tax_application_id=:id";
        return Yii::$app->db->createCommand($sql)->bindValues([
            ':amount' => (int)$amount,
            ':id' => (int)$id,
            ':term' => $term,
        ])->queryScalar();
    }

}