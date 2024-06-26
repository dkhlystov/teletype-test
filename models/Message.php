<?php

namespace app\models;

use yii\base\Model;

/**
 * Message model
 */
class Message extends Model
{
    /**
     * @var string message id
     */
    public string $id;

    /**
     * @var string dialig id
     */
    public string $dialogId;

    /**
     * @var string message text
     */
    public string $text;

    /**
     * @var bool client message flag
     */
    public bool $isItClient;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'dialogId', 'text', 'isItClient'], 'safe'],
        ];
    }
}
