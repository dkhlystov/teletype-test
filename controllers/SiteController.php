<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index', [
            'filenames' => glob(Yii::getAlias('@app') . '/log/*.log'),
        ]);
    }

    /**
     * Displays log.
     * @param string $name log file name
     *
     * @return string
     */
    public function actionLog($name)
    {
        $filename = Yii::getAlias('@app') . '/log/' . $name;
        if ((strpos($name, '/') !== false) || (strpos($name, '.log') === false) || !file_exists($filename)) {
            $content = null;
            Yii::$app->session->setFlash('error', 'File not found.');
        } else {
            $content = file_get_contents($filename);
        }

        return $this->render('log', [
            'name' => $name,
            'content' => $content,
        ]);
    }
}
