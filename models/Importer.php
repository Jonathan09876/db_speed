<?php

namespace app\models;

use yii\db\Exception;

trait Importer
{
    public function readCsv($filePath)
    {
        $file = new \SplFileObject($filePath);
        $file->setFlags(\SplFileObject::READ_CSV);
        $csvList = array();
        foreach ($file as $line) {
            if (!empty($line) && $line[0] != null) {
                $csvList[] = $line;
            }
        }
        if (empty($csvList)) {
            throw new Exception('CSVファイルの中身が空です。');
        }
        mb_convert_variables('UTF-8', 'sjis-win', $csvList);
        return $csvList;
    }

}