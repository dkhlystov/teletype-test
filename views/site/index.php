<?php

/** @var yii\web\View $this */
/** @var array $filenames */

use yii\data\ArrayDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'My Yii Application';

?>
<?= GridView::widget([
    'dataProvider' => new ArrayDataProvider([
        'allModels' => $filenames,
    ]),
    'showHeader' => false,
    'columns' => [
        ['content' => function($item) {
            $name = basename($item);
            return Html::a(Html::encode($name), ['log', 'name' => $name]);
        }],
    ],
]) ?>
