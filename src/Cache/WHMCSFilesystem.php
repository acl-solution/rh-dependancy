<?php
namespace ACL\RH\Dependency\Cache;

use League\Flysystem\Adapter\Local;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class WHMCSFilesystem implements CacheInterface {

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
        $this->cache = new Filesystem($filesystem, 'cache/whmcs');
        $this->ttl = $ttl;

    }

    private function sanitize( $filename ) {
        $special_chars = array("?", "[", "]", "/", "", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}", "-");
        $filename = str_replace($special_chars, '', $filename);
        $filename = preg_replace('/[s-]+/', '-', $filename);
        $filename = trim($filename, '.-_');
        return $filename;
    }

    private function sanitizeMulty( $filenames ) {
        array_walk($filenames, function (&$filename) {
            $filename = $this->sanitize($filename);
        });

        return $filenames;
    }

    private function sanitizeAssocMulty( $pairs ) {
        $updated = [];
        foreach ($pairs as $key=>$value) {
            $updated[$this->sanitize($key)] = $value;
        }
        return $updated;
    }

    /**
     * @param string $name
     * @return bool|null
     */
    public function has($name)
    {
        $name = $this->sanitize($name);
        try {
            return $this->cache->has($name);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }


    /**
     * @param string $name
     * @param mixed $value
     * @param null $ttl
     * @return bool|null
     */
    public function set($name, $value, $ttl = null)
    {
        $name = $this->sanitize($name);
        try {

            $this->cache->set($name, $value, $this->ttl);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    public function get($name, $default = null)
    {
        $name = $this->sanitize($name);
        try {
            return $this->cache->get($name);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function delete($key)
    {
        $key = $this->sanitize($key);
        return $this->cache->delete($key);
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear()
    {
        return $this->cache->clear();
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys A list of keys that can obtained in a single operation.
     * @param mixed $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function getMultiple($keys, $default = null)
    {
        $keys = $this->sanitizeMulty($keys);
        return $this->cache->getMultiple($keys, $default);
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     */
    public function setMultiple($values, $ttl = null)
    {
        $values = $this->sanitizeAssocMulty($values);
        return $this->cache->setMultiple($values, $ttl);
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function deleteMultiple($keys)
    {
        $keys = $this->sanitizeMulty($keys);
        return $this->cache->deleteMultiple($keys);
    }
}
