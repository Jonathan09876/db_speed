<?php

namespace app\models;

use yii\db\Exception;
use yii\helpers\VarDumper;
use yii\web\UploadedFile;

class ImportLeaseContractForm extends \yii\base\Model
{
    use Importer;

    public $import_file;

    public function rules()
    {
        return [
            [['import_file'], 'file'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'import_file' => '顧客情報をインポートするCSVファイル',
        ];
    }

    public function beforeValidate()
    {
        $this->import_file = UploadedFile::getInstance($this, 'import_file');
        return true;
    }

    public function loadFile($path)
    {
        $rows = $this->readCsv($path);
        $model = new ImportLeaseContract();
        $count = 0;
        while($row = array_shift($rows)) {
            if ($count++ == 0) continue;
            if (count($model->attributes()) != count($row)) {
                throw new Exception('データ列数が仕様と異なります。'.VarDumper::dumpAsString([$model->attributes(), $row]));
            }
            $attributes = array_combine($model->attributes(), $row);
            if (empty($attributes['target_name'])) {
                $attributes['target_name'] = '-';
            }
            $instance = new ImportLeaseContract($attributes);
            if (!$instance->save()) {
                throw new Exception(print_r($instance->firstErrors, 1));
            }
        }
    }

}