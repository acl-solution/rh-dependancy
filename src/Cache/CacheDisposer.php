<?php
namespace ACL\RH\Dependency\Cache;

use League\Flysystem\Adapter\Local;

class CacheDisposer {

    private $path;
    private $whmcs;
    private $ipinfo;

    public function __construct(string $path)
    {
        $this->path = $path;
        $adapter = new Local($path);
        $filesystem = new \League\Flysystem\Filesystem($adapter);
        $this->whmcs = new Filesystem($filesystem, 'cache/whmcs');
        $this->ipinfo = new Filesystem($filesystem, 'cache/ipinfo');
    }

    private function generateCacheToken($method, $params) {
        return sprintf("%s.%s", strtolower($method), md5(serialize($params)));
    }

    /**
     * @param $method
     * @param $params
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function removeWhmcs($method, $params) {
        $token = $this->generateCacheToken($method, $params);
        $this->whmcs->delete($token);
    }

    public function removeAllWhmcs() {
        $this->whmcs->clear();
    }

    public function removeAllIpinfo() {
        $this->ipinfo->clear();
    }
}