<?php

namespace App\Services\Payment\Providers\ApplePay;

use App\Services\Payment\Providers\ApplePay\APIInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class API implements APIInterface
{
    protected $secret;

    protected $perPage;

    public function __construct()
    {
        $this->secret = config('payment.providers.apple.secret_key');
    }

    public function baseUrl()
    {
        return config('payment.providers.apple.api_url');
    }

    /**
     *
     * @return  int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    public function setPerPage($perPage)
    {
        $this->perPage = (int) $perPage;
        return $this;
    }
    public function _get($url = null, $parameter = [])
    {
        if ($perPage = $this->getPerPage()) {
            $parameter['limit'] = $perPage;
        }

        return $this->execute('get', $url, $parameter);
    }

    public function _head($url = null, array $parameter = [])
    {
        return $this->execute('head', $url, $parameter);
    }
    public function _delete($url = null, array $parameter = [])
    {
        return $this->execute('delete', $url, $parameter);
    }

    public function _put($url = null, array $parameter = [])
    {
        return $this->execute('put', $url, $parameter);
    }

    public function _patch($url = null, array $parameter = [])
    {
        return $this->execute('patch', $url, $parameter);
    }

    public function _post($url = null, array $parameter = [])
    {
        return $this->execute('post', $url, $parameter);
    }

    public function _options($url = null, array $parameter = [])
    {
        return $this->execute('options', $url, $parameter);
    }

    /**
     * @param string $httpMethod
     * @param $url
     * @param array $parameters
     * @return array
     */
    public function execute($httpMethod, $url, array $parameters = [])
    {
        try {
            $results = $this->getClient()->{$httpMethod}($url, ['json' => $parameters]);
            $res  = json_decode((string) $results->getBody(), true);
            return response()->json($res)->getData();
        } catch (ClientException $exception) {
            return response()->json([
               'status' => false,
               'status_code' => $exception->getCode(),
               'message' => $exception->getMessage(),
            ])->getData();
        }
    }

    /**
     * Returns a Http Client instance.
     *
     * @return Client
     */
    private function getClient()
    {
        return new Client([
            'base_uri' => $this->baseUrl(),
        'handler' => $this->createHandler()
        ]);
    }

    /**
     * Create the Client Handler
     *
     * @return HandlerStack
     */
    private function createHandler()
    {
        $stack  = HandlerStack::create();
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {

           // $request = $request->withHeader('Authorization', 'Bearer '. $this->secret);
            $request = $request->withHeader('Content-Type', 'application/json');
            return $request;
        }));

        $stack->push(Middleware::retry(function ($retries, RequestInterface $request, ResponseInterface $response = null, TransferException $exception = null) {
            return $retries < 3 && ($exception instanceof ConnectException || ($response && $response->getStatusCode() >= 500));
        }, function ($retries) {
            return (int) pow(2, $retries) * 1000;
        }));

        return $stack;
    }
}
