<?php

namespace app\components;

use Yii;
use app\models\Message;
use yii\base\BaseObject;
use yii\helpers\Json;

/**
 * Teletype component
 * Logging and DataAPI requests
 */
class Teletype extends BaseObject
{
    /**
     * @var string base url for data API requests
     */
    public string $baseUrl = 'https://api.teletype.app/public/api/v1';

    /**
     * @var string data API access token
     */
    public string $token;

    /**
     * @var int data API request timeout
     */
    public int $timeout = 300;

    /**
     * @var string clients log file
     */
    public string $logFileClients;

    /**
     * @var string operators log file
     */
    public string $logFileOperators;

    /**
     * @var int|null last response code from data API
     */
    protected ?int $lastResponseCode = null;

    /**
     * Logging message
     * 
     * @param Message $message message to add into log
     * @return void
     */
    public function logMessage(Message $message)
    {
        $filename = Yii::getAlias($message->isItClient ? $this->logFileClients : $this->logFileOperators);
        $data = gmdate('c') . "\nid: {$message->id}\ndialogId: {$message->dialogId}\n{$message->text}\n\n";
        file_put_contents($filename, $data, FILE_APPEND);
    }

    /**
     * Get message from data API by id
     * @see https://teletype.app/help/api/#tag/Messages/paths/~1messages/get
     * 
     * @param string $id message id
     * @param array $args additional args to search message
     * @return Message|null
     */
    public function getMessage(string $id, array $args = []): ?Message
    {
        $message = null;

        $page = 1;
        do {
            $args['page'] = $page;
            $body = $this->request('/messages', 'GET', $args);
            $totalPages = $body['data']['totalPages'] ?? 0;
            $items = $body['data']['items'] ?? [];
            foreach ($items as $item) {
                if ($item['id'] === $id) {
                    $message = new Message;
                    $message->setAttributes($item);
                    break;
                }
            }
            $page++;
        } while ($page <= $totalPages);

        return $message;
    }

    /**
     * Send message to client
     * 
     * @param string $dialogId dialog id
     * @param string $text message text
     * @return void
     */
    public function sendMessage(string $dialogId, string $text): void
    {
        $this->request('/message/send', 'POST', [
            'dialogId' => $dialogId,
            'text' => $text,
        ]);
    }

    /**
     * Send API request
     * 
     * @param string $endpoint endpoint
     * @param string $method http method
     * @param array $data request body data
     * @param int $attemptsCount attempts count
     * @return mixed
     */
    protected function request(string $endpoint, string $method = 'GET', array $data = [], int $attemptsCount = 1): mixed
    {
        $url = $this->baseUrl . $endpoint;
        if ($method === 'GET' && $data) {
            $parts = parse_url($url);
            parse_str($parts['query'] ?? '', $params);
            $params = array_merge($params, $data);
            $url = "{$parts['scheme']}://{$parts['host']}{$parts['path']}?" . http_build_query($params);
            $data = [];
        }

        $body = null;
        $lastError = '';
        do {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-Auth-Token: ' . $this->token,
                'Accept: application/json',
            ]);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
            }

            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }

            $body = Json::decode(curl_exec($ch), true);
            $this->lastResponseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $lastError = curl_error($ch);
            curl_close($ch);
            $attemptsCount--;
        } while ((int) $attemptsCount > 0);

        if ($lastError) {
            throw new \Exception($lastError);
        }

        $success = $body['success'] ?? false;
        if (!$success) {
            throw new \Exception($body['errors'][0]['message'] ?? 'Unknown error.');
        }

        return $body;
    }

}
