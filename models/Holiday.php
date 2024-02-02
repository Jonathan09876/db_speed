<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "holiday".
 *
 * @property integer $holiday_id
 * @property string $holiday
 * @property string $day_name
 */
class Holiday extends \yii\db\ActiveRecord
{
    static $cache;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'holiday';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['holiday', 'day_name'], 'required'],
            [['holiday'], 'safe'],
            [['day_name'], 'string', 'max' => 32],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'holiday_id' => 'Holiday ID',
            'holiday' => 'Holiday',
            'day_name' => 'Day Name',
        ];
    }

    public static function updateCalendar($year=null){
        if (!isset($year)) {
            $year = date('Y');
        }
        if (isset(static::$cache[$year])) return;
        if (self::find()->where('holiday BETWEEN :fd AND :ld')->addParams([
            ':fd' => "{$year}-01-01",
            ':ld' => "{$year}-12-31",
        ])->count() > 0) {
            return ArrayHelper::map(self::find()->where('holiday BETWEEN :fd AND :ld')->addParams([
                ':fd' => "{$year}-01-01",
                ':ld' => "{$year}-12-31",
            ])->all(), 'holiday', 'day_name');
        }
        // 初日
        $first_day = mktime(0, 0, 0, 1, 1, intval($year));

        // 末日
        $last_day = mktime(0, 0, 0, 12, 31, intval($year));

        $api_key = 'AIzaSyB5C8jrsTI-7Vu_cxTFZJusAsvHAWcwxwg';
        $holidays_id = 'japanese__ja@holiday.calendar.google.com';  // Google 公式版日本語
        $holidays_url = sprintf(
            'https://www.googleapis.com/calendar/v3/calendars/%s/events?'.
            'key=%s&timeMin=%s&timeMax=%s&maxResults=%d&orderBy=startTime&singleEvents=true',
            $holidays_id,
            $api_key,
            date('Y-m-d', $first_day).'T00:00:00Z' ,  // 取得開始日
            date('Y-m-d', $last_day).'T00:00:00Z' ,   // 取得終了日
            50            // 最大取得数
        );
        if ( $results = file_get_contents($holidays_url) ) {
            $results = json_decode($results);
            $holidays = array();
            foreach ($results->items as $item ) {
                $date  = strtotime((string) $item->start->date);
                $title = (string) $item->summary;
                $holidays[date('Y-m-d', $date)] = $title;
            }
            ksort($holidays);
            foreach($holidays as $holiday => $day_name) {
                Yii::$app->db->createCommand()->insert('holiday', compact("holiday", "day_name"))->execute();
            }
        }
        static::$cache[$year] = true;
        return $holidays;
    }
}
