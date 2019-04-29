<?php


namespace app\services;


use GuzzleHttp\Client;
use Yii;

class TwitterService
{
    private $key;
    private $secret;
    private $token;
    private $endpoint;

    /**
     * TwitterService constructor.
     */
    public function __construct()
    {
        $twitterParams = Yii::$app->params['twitter'];
        $this->key = $twitterParams['api_key'];
        $this->secret = $twitterParams['api_secret'];
        $this->endpoint = $twitterParams['endpoint'];
    }

    /**
     * @return $this
     */
    private function getBearerToken()
    {
        $cache = Yii::$app->cache;

        $this->token = $cache->getOrSet('twitter-bearer', function () use ($cache) {
            $client = new Client();
            $response = $client->request('POST', $this->endpoint . '/oauth2/token', [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ],
                'headers' => [
                    'Content-type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . base64_encode($this->key . ':' . $this->secret)
                ]
            ]);

            $response = json_decode($response->getBody()->getContents());
            $token = $response->access_token;
            $cache->set('twitter-bearer', $token, 60 * 60);

            return $token;
        });

        return $this;
    }

    /**
     * @param string $username
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getUserLastTweet(string $username)
    {
        if (!$this->token)
            $this->getBearerToken();

        try {
            $client = new Client;
            $response = $client->request(
                'GET',
                $this->endpoint . '/1.1/statuses/user_timeline.json',
                [
                    'query' => [
                        'screen_name' => $username,
                        'count' => 1
                    ],
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->token
                    ]
                ])
                ->getBody()
                ->getContents();

            $response = json_decode($response)[0];
        } catch (\Exception $exception) {
            return null;
        }

        return [
            'user' => $username,
            'tweet' => $response->text,
            'hashtag' => $response->entities->hashtags
        ];
    }

    /**
     * @param array $users
     * @param int $cacheTime
     * @return array
     */
    public function fetchFeed(array $users = [], $cacheTime = 60): array
    {
        $cache = Yii::$app->cache;
        $cacheKey = implode($users, '.');

        $feed = $cache->getOrSet($cacheKey, function () use ($users, $cacheTime, $cacheKey, $cache) {
            $feed = [];
            foreach ($users as $user) {
                if ($temp = $this->getUserLastTweet($user))
                    $feed[] = $temp;
            }
            $cache->set($cacheKey, $feed, $cacheTime);

            return $feed;
        });

        return $feed;
    }
}