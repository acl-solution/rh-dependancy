<?php
namespace ACL\RH\Dependency;

use ACL\RH\Dependency\Cache\WHMCSFilesystem;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\ClientException;
use ACL\RH\Dependency\Exceptions\RequestException;
use ACL\RH\Dependency\Exceptions\ResponseException;

class Provider
{
    /**
     * @var Guzzle
     */
    protected $http;

    /**
     * @var string The WHMCS API username
     */
    protected $username;

    /**
     * @var string The WHMCS API password
     */
    protected $password;

    /**
     * @var string The WHMCS installation url
     */
    protected $url;

    protected $cache;

    /**
     * Api constructor.
     * @param string $url The WHMCS installation URL
     * @param string $username The WHMCS API username
     * @param string $password The WHMCS API password
     * @param $cacheDir
     * @param int $cacheTtl
     */
    public function __construct($url, $username, $password, $cacheDir, $cacheTtl = 7)
    {
        $this->url = $url;
        $this->username = $username;
        $this->password = md5($password);
        $this->cache = new WHMCSFilesystem($cacheDir, 86400*$cacheTtl);
        $this->http = new Guzzle(['base_uri' => $url . 'includes/api.php']);
    }


    /**
     * @param $action
     * @param array $params
     * @return mixed
     * @throws RequestException
     * @throws ResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendRequest($cacheToken, $action, $params = [])
    {
        try {
            $response = $this->http->post('', [
                'form_params' => array_merge($params, [
                    'username' => $this->username,
                    'password' => $this->password,
                    'action' => $action,
                    'responsetype' => 'json',
                ]),
            ]);
        } catch (ClientException $e) {
            throw new RequestException($e->getResponse());
        }

        $data = json_decode($response->getBody(), true);

        if (isset($data['result']) && $data['result'] === 'success') {
            $this->cache->set($cacheToken, $data);
            return $data;
        } else {
            throw new ResponseException($response);
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws RequestException
     * @throws ResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function __call($name, $arguments)
    {
        $params = isset($arguments[0]) && is_array($arguments[0]) ? $arguments[0] : [];
        $cacheToken = $this->generateCacheToken($name, $params);
        return $this->cache->has($cacheToken)? $this->cache->get($cacheToken) : $this->sendRequest($cacheToken, ucfirst($name), $params);
    }

    private function generateCacheToken($method, $params) {
        return sprintf("%s-%s", strtolower($method), md5(serialize($params)));
    }
}