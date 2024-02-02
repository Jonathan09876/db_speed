<?php

namespace app\widgets;

use Yii;

class PageSizeLimitChanger extends \yii\bootstrap5\Widget
{
    public $id = 'pagesizelimitchanger';
    public $pageSize;
    public $pjax_id;

    public $dataProvider;

    const DEFAULT_PAGE_SIZE = 10;

    static $sizes = [
        '10' => 10,
        '20' => 20,
        '30' => 30,
        '50' => 50,
        '100' => 100,
        false => '全件',
    ];

    public function init()
    {
        parent::init();
        $script = <<<EOS
$('.page-size-limit-changer>.input-group>[name="pageSizeLimitChanger"]').change(async function(){
    let response = await $.getJSON('/update/page-size-limit?size='+$(this).val());
    let wrapper = $('#{$this->pjax_id}.card-body');
    wrapper.addClass('position-relative');
    wrapper.append('<div style="background-color: rgba(0,0,0,.1);position:absolute;left:0;top:0;" class="w-100 h-100 d-flex align-items-center justify-content-center"><div style="width:100px;height:100px;" class="spinner-border text-secondary" role="status"><span class="sr-only">Loading...</span></div></div>')
    $.pjax.reload('#{$this->pjax_id}', {timeout: false});
});
EOS;
        $view = $this->getView();
        $view->registerJs($script);
        $session = Yii::$app->session;
        $this->pageSize = $session['page-size-limit'] ?? static::DEFAULT_PAGE_SIZE;
    }

    public function run()
    {
        $this->renderItem();
    }

    public function renderItem()
    {
        return $this->render('page-size-limit-changer', ['widget' => $this]);
    }

    public function getPagination()
    {
        return ['pageSize' => $this->pageSize];
    }

    public function getSummaryLayout()
    {
        $widget = $this->renderItem();
        return $layout = <<<EOL
<div class="d-flex justify-content-between">
    {summary}
    {$widget}
</div>
EOL;
    }

    public function getLayout()
    {
        $widget = $this->renderItem();
        return $layout = <<<EOL
<div class="d-flex justify-content-between">
    {summary}
    {$widget}
</div>
{items}
{pager}
EOL;
    }
}