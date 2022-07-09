<?php
namespace ACL\RH\Dependency\Cache;

use ipinfo\ipinfo\cache\CacheInterface;
use League\Flysystem\Adapter\Local;
use Psr\SimpleCache\InvalidArgumentException;

class IPinfoFilesystem implements CacheInterface {

    public $maxsize;
    public $ttl;
    private $path;
    private $cache;

    /**
     * IPinfoFilesystem constructor.
     * @param string $path
     * @param $maxsize
     * @param int $ttl
     */
    public function __construct(string $path, int $ttl)
    {
        $this->path = $path;
        $adapter = new Local($path);
        $filesystem = new \League\Flysystem\Filesystem($adapter);
        $this->cache = new Filesystem($filesystem);
        $this->ttl = $ttl;

    }

    private function sanitize( $filename ) {
        $special_chars = array("?", "[", "]", "/", "", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}");
        $filename = str_replace($special_chars, '', $filename);
        $filename = preg_replace('/[s-]+/', '-', $filename);
        $filename = trim($filename, '.-_');
        return $filename;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path){
        $this->path = $path;
        $this->cache->setFolder($path);
    }

    /**
     * Tests if the specified IP address is cached.
     * @param string $name
     * @return boolean Is the IP address data in the cache.
     */
    public function has(string $name)
    {
        $name = $this->sanitize($name);
        try {
            return $this->cache->has($name);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Set the IP address key to the specified value.
     * @param string $name
     * @param mixed $value Data for specified IP address.
     * @return null
     */
    public function set(string $name, $value)
    {
        $name = $this->sanitize($name);
        try {

            $this->cache->set($name, $value, $this->ttl);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Get data for the specified IP address.
     * @param string $name
     * @return mixed IP address data.
     */
    public function get(string $name)
    {
        $name = $this->sanitize($name);
        try {
            return $this->cache->get($name);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }
}
