<?php

/** @var yii\web\View $this */
/** @var string $name */
/** @var string|null $content */

use yii\helpers\Html;

$this->title = $name;
$this->params['breadcrumbs'] = [
    $this->title,
];

?>
<h1><?= Html::encode($this->title) ?></h1>

<?php if ($content): ?>
    <pre><?= Html::encode($content) ?></pre>
<?php endif; ?>
