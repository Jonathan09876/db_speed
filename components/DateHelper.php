<?php
/**
 * Created by PhpStorm.
 * User: decama
 * Date: 2017/09/26
 * Time: 15:20
 */

namespace app\components;

use app\models\Holiday;
use Yii;

class DateHelper extends \yii\base\Component
{
    public static function updateHolidays($date)
    {
        $from = (int)(new \DateTime($date))->format('Y') - 1;
        $to = (int)date('Y') + 1;
        for($y = $from; $y <= $to; $y++) {
            Holiday::updateCalendar($y);
        }
    }

    /**
     * 指定日からn日後の営業日を返す
     * @param $date
     * @param $interval
     * @return mixed
     */
    public static function businessDay_add($date, $interval)
    {
        // $dateの含まれる年の前年から翌年までの休日情報を取得しておく
        self::updateHolidays($date);
        $interval += 1;
        $sql = <<<EOS
SELECT
  max(tmp.calendar_date)
FROM (
  SELECT thc.calendar_date
  FROM (
    SELECT
      tds.tmp_date AS calendar_date,
      IF(ABS(DAYOFWEEK(tds.tmp_date)-4)=3 or tds.tmp_date in (SELECT holiday FROM holiday), 1, 0) AS not_business_day
    FROM (
      SELECT
        DATE(:date + INTERVAL @num:=@num+1 DAY) AS tmp_date
      FROM
        seed, seed as seed1, seed as seed2,
       (SELECT @num:=-1) num
      LIMIT 366
    ) tds
  ) thc
  WHERE thc.not_business_day=0
  ORDER BY thc.calendar_date ASC
  LIMIT :interval
) tmp
EOS;
        $result = Yii::$app->db->createCommand($sql)->bindValues([
            ':date' => $date,
            ':interval' => $interval,
        ])->queryColumn();
        return $result[0];
    }

    /**
     * 指定日からn日前の営業日を返す
     * @param $date
     * @param $interval
     * @return mixed
     */
    public static function businessDay_sub($date, $interval)
    {
        // $dateの含まれる年の前年から翌年までの休日情報を取得しておく
        self::updateHolidays($date);
        $interval += 1;
        $sql = <<<EOS
SELECT
  min(tmp.calendar_date)
FROM (
  SELECT thc.calendar_date
  FROM (
    SELECT
      tds.tmp_date AS calendar_date,
      IF(ABS(DAYOFWEEK(tds.tmp_date)-4)=3 or tds.tmp_date in (SELECT holiday FROM holiday), 1, 0) AS not_business_day
    FROM (
      SELECT
        DATE(:date - INTERVAL @num:=@num+1 DAY) AS tmp_date
      FROM
        seed, seed as seed1, seed as seed2,
         (SELECT @num:=-1) num
      LIMIT 366
    ) tds
  ) thc
  WHERE thc.not_business_day=0
  ORDER BY thc.calendar_date DESC
  LIMIT :interval
) tmp
EOS;
        $result = Yii::$app->db->createCommand($sql)->bindValues([
            ':date' => $date,
            ':interval' => $interval,
        ])->queryColumn();
        return $result[0];
    }

    public static function getMonthsList()
    {
        $months = [];
        for($i = 1; $i <= 12; $i++) {
            $months[$i] = "{$i}月";
        }
        return $months;
    }

    public static function dateToMonths($dateStr)
    {
        $date = \DateTime::createFromFormat('Y-m-d', $dateStr);
        return (((int)$date->format('Y') - 2000) * 12 + (int)$date->format('n') - 1) * 100 + (int)$date->format('j');
    }

    /**
     * @param $months
     * @return \DateTime
     * @throws \Exception
     */
    public static function monthsToDate($months)
    {
        $date = new \DateTime();
        $date->setDate(intdiv($months,1200) + 2000, intdiv($months % 1200, 100) + 1, $months % 100);
        return $date;
    }
}