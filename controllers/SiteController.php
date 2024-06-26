<?php

namespace app\controllers;

use Yii;
use app\models\Message;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\Controller;

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
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if ($action->id == 'teletype') {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
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

    /**
     * Teletype webhook.
     *
     * @return string
     */
    public function actionTeletype()
    {
        $name = Yii::$app->getRequest()->post('name');
        $data = Json::decode(Yii::$app->getRequest()->post('payload'));

        $teletype = Yii::$app->teletype;

        switch ($name) {
            case 'new message':
                if (!isset($data['message'])) {
                    throw new BadRequestHttpException('Message are required.');
                }
                $message = new Message;
                $message->setAttributes($data['message']);
                $teletype->logMessage($message);
                if ($message->text === 'ping?') {
                    $teletype->sendMessage($message->dialogId, 'pong!');
                }
                break;

            case 'success send':
                if (!isset($data['messageId']) || !isset($data['dialogId'])) {
                    throw new BadRequestHttpException('Params "messageId" and "dialogId" are required.');
                }
                if (isset($data['message'])) { // Нет в доках
                    $message = new Message;
                    $message->setAttributes($data['message']);
                } else {
                    $message = $teletype->getMessage($data['messageId'], ['dialogId' => $data['dialogId']]);
                    if (!$message) {
                        throw new BadRequestHttpException('Message not found.');
                    }
                }
                $teletype->logMessage($message);
                break;
        }
    }
}
