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

    protected $identifier;

    protected $secret;

    protected $accesskey;

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
    public function __construct($url, $cacheDir, $cacheTtl = 7)
    {
        $this->url = $url;
        $this->username = null;
        $this->password = null;
        $this->cache = new WHMCSFilesystem($cacheDir, 86400*$cacheTtl);
        $this->http = new Guzzle(['base_uri' => $url . 'includes/api.php']);
    }

    public function setUser($username, $password) {
        $this->username = $username;
        $this->password = md5($password);
    }

    public function setIdentifier($identifier, $secret, $accesskey) {
        $this->identifier = $identifier;
        $this->secret = $secret;
        $this->accesskey = $accesskey;
    }

    public function isUser() {
        return !empty($this->username) && !empty($this->password);
    }

    public function isIdentifier() {
        return !empty($this->identifier) && !empty($this->secret) && !empty($this->accesskey);
    }


    /**
     * @param $cacheToken
     * @param $action
     * @param array $params
     * @return mixed
     * @throws RequestException
     * @throws ResponseException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendRequest($cacheToken, $action, $params = [])
    {
        $authorization = [];

        if($this->isUser()) {
            $authorization['username'] = $this->username;
            $authorization['password'] = $this->password;
        } elseif ($this->isIdentifier()) {
            $authorization['identifier'] = $this->identifier;
            $authorization['secret'] = $this->secret;
            $authorization['accesskey'] = $this->accesskey;
        }

        if(!empty($authorization)) {
            try {
                $response = $this->http->post('', [
                    'form_params' => array_merge($params, $authorization, [
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
        return null;
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