<?php

namespace app\components;

use Yii;

class View extends \yii\web\View
{
    private $_pageTitle;

    public function getPageTitle()
    {
        if (!isset($this->_pageTitle)) {
            return Yii::$app->name;
        }
        else return $this->_pageTitle;
    }

    public function setPageTitle($pageTitle)
    {
        $this->_pageTitle = $pageTitle;
    }
}