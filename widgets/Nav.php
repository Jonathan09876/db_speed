<?php

namespace app\widgets;

use yii\base\InvalidConfigException;
use yii\bootstrap5\Html;
use yii\bootstrap5\Widget;
use yii\helpers\ArrayHelper;

class Nav extends \yii\bootstrap5\Nav
{
    /**
     * Renders the widget.
     * @return string
     * @throws InvalidConfigException|\Throwable
     */
    public function run(): string
    {
        return $this->renderItems();
    }

    /**
     * Renders a widget's item.
     * @param string|array $item the item to render.
     * @return string the rendering result.
     * @throws InvalidConfigException
     * @throws \Throwable
     */
    public function renderItem($item): string
    {
        if (is_string($item)) {
            return $item;
        }
        if (!isset($item['label'])) {
            throw new InvalidConfigException("The 'label' option is required.");
        }
        $encodeLabel = $item['encode'] ?? $this->encodeLabels;
        $label = $encodeLabel ? Html::encode($item['label']) : $item['label'];
        $icon = ArrayHelper::getValue($item, 'icon');
        if ($icon) {
            $label = Html::tag('i', null, ['class' => $icon]) . Html::tag('span', $label);
        }
        $options = ArrayHelper::getValue($item, 'options', []);
        $items = ArrayHelper::getValue($item, 'items');
        $url = ArrayHelper::getValue($item, 'url', '#');
        $linkOptions = ArrayHelper::getValue($item, 'linkOptions', []);
        $disabled = ArrayHelper::getValue($item, 'disabled', false);
        $active = $this->isItemActive($item);

        if (empty($items)) {
            $items = '';
            Html::addCssClass($options, ['widget' => 'nav-item']);
            Html::addCssClass($linkOptions, ['widget' => 'nav-link']);
        } else {
            $collapseKey = 'items-' . md5(serialize($item));
            $url = "#{$collapseKey}";
            $linkOptions['data']['bs-toggle'] = 'collapse';
            $linkOptions['role'] = 'button';
            $linkOptions['aria']['expanded'] = 'false';
            $linkOptions['aria']['controls'] = $collapseKey;
            Html::addCssClass($options, ['widget' => 'nav-item']);
            Html::addCssClass($linkOptions, ['widget' => 'nav-link menu-link']);
            if (is_array($items)) {
                $items = $this->isChildActive($items, $active);
                $items = $this->renderCollaplseItems($items, $item);
            }
        }

        if ($disabled) {
            ArrayHelper::setValue($linkOptions, 'tabindex', '-1');
            ArrayHelper::setValue($linkOptions, 'aria.disabled', 'true');
            Html::addCssClass($linkOptions, ['disable' => 'disabled']);
        } elseif ($this->activateItems && $active) {
            Html::addCssClass($linkOptions, ['activate' => 'active']);
        }

        return Html::tag('li', Html::a($label, $url, $linkOptions) . $items, $options);
    }

    /**
     * @param array $items
     * @param array $parentItem
     * @return string
     * @throws InvalidConfigException
     * @throws \Throwable
     */
    protected function renderCollaplseItems(array $items, array $parentItem): string
    {
        $id = 'items-' . md5(serialize($parentItem));
        $renderedItems = Html::tag('ul', "\n" . implode("\n", array_map(function($item){
            return $this->renderItem($item);
        }, $items)), ['class' => 'nav nav-sm flex-column']);
        return Html::tag('div', $renderedItems, ['id' => $id, 'class' => 'collapse menu-dropdown']);
    }

}