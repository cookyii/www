<?php
/**
 * main.php
 * @author Revin Roman
 * @link https://rmrevin.com
 *
 * @var yii\web\View $this
 * @var string $content
 */

use rmrevin\yii\fontawesome\FA;
use yii\helpers\Html;

/** @var \resources\Account $User */
$User = User()->identity;

$this->beginContent('@app/views/layouts/_layout.php', ['content' => $content]);

echo $content;

$this->endContent();

/**
 * @param array|string $item
 * @return null|string
 */
function renderItem($item)
{
    $options = [];

    if ('|' === $item) {
        return Html::tag('li', null, ['class' => 'divider']);
    }

    if ($item['visible'] !== true) {
        return null;
    }

    if (true === $item['selected']) {
        Html::addCssClass($options, 'active');
    }

    $anchor_options = [];

    if (!isset($item['items']) || empty($item['items'])) {
        $label = '';
        $subitems = '';

        if (isset($item['icon'])) {
            $label .= FA::icon($item['icon']) . ' ';
        }

        $label .= $item['label'];
    } else {
        $label = '';
        $subitems = '';

        if (isset($item['icon'])) {
            $label .= FA::icon($item['icon']) . ' ';
        }

        $label .= $item['label']
            . Html::tag('span', null, ['class' => 'caret']);

        foreach ($item['items'] as $subitem) {
            $subitems .= renderItem($subitem);
        }

        $subitems = Html::tag('ul', $subitems, ['class' => 'dropdown-menu', 'role' => 'menu']);

        Html::addCssClass($options, 'dropdown');
        Html::addCssClass($anchor_options, 'dropdown-toggle');

        $anchor_options['data-toggle'] = 'dropdown';
        $anchor_options['role'] = 'button';
        $anchor_options['aria-expanded'] = 'false';
    }

    return Html::tag(
        'li',
        Html::a(
            $label,
            $item['url'],
            $anchor_options
        ) . $subitems,
        $options
    );
}