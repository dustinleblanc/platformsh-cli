<?php

namespace Platformsh\Cli\Service;

use Doctrine\Common\Cache\CacheProvider;

/**
 * A service for reading environment variables from the remote environment.
 */
class RemoteEnvVars
{

    protected $shellHelper;
    protected $config;
    protected $ssh;
    protected $cache;

    /**
     * @param Ssh             $ssh
     * @param CacheProvider   $cache
     * @param Shell           $shellHelper
     * @param Config          $config
     */
    public function __construct(
        Ssh $ssh,
        CacheProvider $cache,
        Shell $shellHelper,
        Config $config
    ) {
        $this->ssh = $ssh;
        $this->cache = $cache;
        $this->shellHelper = $shellHelper;
        $this->config = $config;
    }

    /**
     * Read an environment variable from a remote application.
     *
     * @param string $variable The unprefixed name of the variable.
     * @param string $sshUrl   The SSH URL to the application.
     * @param bool   $refresh  Whether to refresh the cache.
     * @param int    $ttl      The cache lifetime of the result.
     *
     * @return string
     */
    public function getEnvVar($variable, $sshUrl, $refresh = false, $ttl = 3600)
    {
        $varName = $this->config->get('service.env_prefix') . $variable;
        $cacheKey = 'env-' . $sshUrl . '-' . $varName;
        $cached = $this->cache->fetch($cacheKey);
        if ($refresh || $cached === false) {
            $args = ['ssh'];
            $args = array_merge($args, $this->ssh->getSshArgs());
            $args[] = $sshUrl;
            $args[] = 'echo $' . $varName;
            $cached = $this->shellHelper->execute($args, null, true);
            $this->cache->save($cacheKey, $cached, $ttl);
        }

        return $cached;
    }
}