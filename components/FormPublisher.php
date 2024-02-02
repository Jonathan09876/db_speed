<?php
/**
 * Created by PhpStorm.
 * User: decama
 * Date: 2018/07/31
 * Time: 17:05
 */

namespace app\components;

use Yii;
use yii\base\Widget;
use yii\bootstrap5\Html;
use yii\helpers\Url;
use yii\helpers\VarDumper;
use yii2tech\html2pdf\Manager;
use yii2tech\html2pdf\converters\Wkhtmltopdf;
use yii2tech\html2pdf\Template;

class FormPublisher extends Widget
{
    public $model;
    public $replaces;
    public $template = 'delinquencies';
    public $filename;

    public $publish_date;
    public $term;

    public $searchModel;

    static $templates = [
        'collection-schedules' => [
            'title' => ['回収実績登録一覧'],
            'orientation' => 'Landscape',
        ],
        'collection-schedules-new' => [
            'title' => ['回収実績登録一覧'],
            'orientation' => 'Landscape',
        ],
        'delinquencies' => [
            'title' => ['延滞管理一覧'],
            'orientation' => 'Landscape',
        ],
        'delinquencies-alternative' => [
            'title' => ['実績集計'],
            'orientation' => 'Landscape',
        ],
        'delinquencies-new' => [
            'title' => ['実績集計'],
            'orientation' => 'Landscape',
        ],
        'debt-collection-by-agency' => [
            'title' => ['売掛買掛一覧（リース会社別）'],
            'orientation' => 'Landscape',
        ],
        'debt-collection-by-customer' => [
            'title' => ['売掛買掛一覧（顧客別）'],
            'orientation' => 'Landscape',
        ],
        'collection-data' => [
            'title' => ['回収支払表'],
            'orientation' => 'Landscape',
        ],

    ];

    public function init()
    {
        parent::init();
        if (!isset($this->term)) {
            $this->term = (new \DateTime())->sub(new \DateInterval('P1M'))->format('Ym');
        }
        if (!isset($this->publish_date)) {
            $this->publish_date = date('Y-m-d');
        }
    }

    public function getTermText($format = 'bK.n.j')
    {
        if (isset($this->searchModel) && !empty($this->searchModel->date_from) && !empty($this->searchModel->date_to)) {
            $from = (new \DateTime($this->searchModel->date_from))->format('Y-m-d');
            $to = (new \DateTime($this->searchModel->date_to))->format('Y-m-d');
        }
        else if (preg_match('/(\d{4})(\d{2})/', $this->term, $matched)) {
            $date = mktime(0,0,0,$matched[2], 1, $matched[1]);
            $from = Date('Y-m-d', $date);
            $to = Date('Y-m-t', $date);
        }
        else {
            $from = Date('Y-m-01');
            $to = Date('Y-m-t');
        }
        return (new DateTimeJp($from))->format($format) . ' 〜 ' . (new DateTimeJp($to))->format($format);
    }

    /**
     * 発行文書htmlを表示
     */
    public function preview()
    {
        $view = Yii::$app->controller->view;
        Yii::$app->controller->viewPath = '@app/views/pdf';
        Yii::$app->controller->layout = '@app/views/pdf/layouts/main';

        return Yii::$app->controller->render($this->template, ['self' => $this]);
    }

    /**
     * html2pdfでpdfを生成
     */
    public function generatePDF()
    {
        $html2pdf = Yii::createObject([
            'class' => Manager::class,
            'viewPath' => '@app/views/pdf',
            'layout' => 'layouts/main',
            'converter' => [
                'class' => Wkhtmltopdf::class,
                'binPath' => Yii::getAlias('@runtime/wkhtmltox/bin/wkhtmltopdf'),
                'defaultOptions' => [
                    'pageSize' => 'A3',
                    'orientation' => self::$templates[$this->template]['orientation'],
                    'margin-top'    => 0,
                    'margin-right'  => 0,
                    'margin-bottom' => 0,
                    'margin-left'   => 0,
                ],
            ],
        ]);

        $basePath = Yii::getAlias('@webroot/assets/');
        if (!is_dir($basePath)) {
            mkdir($basePath);
        }
        $folder = $basePath . DIRECTORY_SEPARATOR . sprintf('form%s', $this->term);
        if (!is_dir($folder)) {
            mkdir($folder);
        }
        switch($this->template) {
            default:
                $this->filename = $folder . DIRECTORY_SEPARATOR . sprintf("%s-%s.pdf", $this->template, date('YmdHis'));
        }

        $template = new Template([
            'view' => $html2pdf->getView(),
            'viewPath' => $html2pdf->getViewPath(),
            'viewName' => $this->template,
            'layout' => $html2pdf->layout,
        ]);
        $htmlContent = $template->render(['self' => $this, 'searchModel' => $this->searchModel]);

        $html2pdf
            ->convert($htmlContent, $template->pdfOptions)
            ->saveAs($this->filename);

        return $this->filename;
    }

}