<?php
declare(strict_types=1);

namespace B2Binpay;

use B2Binpay\Exception\ConnectionErrorException;
use B2Binpay\Exception\EmptyResponseException;
use B2Binpay\Exception\ServerApiException;
use B2Binpay\Exception\UpdateTokenException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Send and validate requests through GuzzleHttp
 *
 * @package B2Binpay
 */
class Request
{
    const ERROR_UPDATE_TOKEN = ['-240', 'RESULT_TOKEN_ERROR_EXPIRED'];

    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client ?? new Client();
    }

    /**
     * @param string $token
     * @param string $method
     * @param string $url
     * @param array|null $params
     * @return mixed
     */
    public function send(string $token, string $method, string $url, array $params = null)
    {
        $header = [
            'Authorization' => 'Bearer ' . $token
        ];

        $request = [
            'headers' => $header,
            'form_params' => $params,
            'http_errors' => false
        ];

        return $this->execute($method, $url, $request)->data;
    }

    /**
     * @param string $authBasic
     * @param string $url
     * @return string
     */
    public function token(string $authBasic, string $url) : string
    {
        $header = [
            'Authorization' => 'Basic ' . $authBasic
        ];

        $request = [
            'headers' => $header,
            'http_errors' => false
        ];

        $method = 'get';

        return $this->execute($method, $url, $request)->access_token;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $request
     * @return mixed
     * @throws ConnectionErrorException
     * @throws EmptyResponseException
     * @throws ServerApiException
     * @throws UpdateTokenException
     */
    private function execute(string $method, string $url, array $request)
    {
        try {
            $response = $this->client->request($method, $url, $request);
        } catch (GuzzleException $e) {
            throw new ConnectionErrorException($e);
        }

        $status = $response->getStatusCode();
        $body = (string)$response->getBody();
        $responseDecode = json_decode($body, false);

        if (empty($responseDecode)) {
            throw new EmptyResponseException($url);
        }

        if (!empty($responseDecode->error)) {
            if ([$responseDecode->code, $responseDecode->error] == self::ERROR_UPDATE_TOKEN) {
                throw new UpdateTokenException();
            }

            throw new ServerApiException($responseDecode->error, $responseDecode->code, $status);
        }

        return $responseDecode;
    }
}
