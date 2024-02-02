<?php

namespace app\widgets;

use yii\bootstrap5\Html;

/**
<button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger" id="topnav-hamburger-icon">
<span class="hamburger-icon">
<span></span>
<span></span>
<span></span>
</span>
</button>
 */
class HamburgerIcon extends \yii\bootstrap5\Widget
{
    public function run()
    {
        $hamburger = Html::tag('span', Html::tag('span') . Html::tag('span') . Html::tag('span'), ['class' => 'hamburger-icon']);
        return Html::button($hamburger, $this->options);
    }
}
