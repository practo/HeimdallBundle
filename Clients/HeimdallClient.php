<?php

namespace Practo\HeimdallBundle\Clients;

use \Buzz\Browser;
use \Memcached;

/**
 * Heimdall Client
 */
class HeimdallClient
{
    protected $productName;
    protected $apiUrl;
    protected $apiSecret;
    protected $buzz;
    protected $cache;

    /**
     * Constructor
     *
     * @param string    $productName  - Product Name
     * @param string    $apiUrl       - Heimdall API Base URL
     * @param string    $apiSecret    - Heimdall API Secret
     * @param Browser   $buzz         - Buzz
     * @param Memcached $cache        - Memcached cache instance
     * @param array     $cacheServers - Array of key-values pairs of host & port
     */
    public function __construct($productName, $apiUrl, $apiSecret,
        Browser $buzz,
        Memcached $cache,
        $cacheServers)
    {
        $this->productName = $productName;
        $this->apiUrl = $apiUrl;
        $this->apiSecret = $apiSecret;
        $this->buzz = $buzz;
        $this->cache = $cache;
        if (empty($this->cache->getServerList())) {
            foreach ($cacheServers as $host => $port) {
                $server = [$host, $port];
                $this->cache->addServers(array($server));
            }
        }
    }

    protected function generateNonce()
    {
        return time() . '|' . substr(str_shuffle(
            str_repeat('abcdefghijklmnopqrstuvwxyz0123456789', 3)), 0, 20);
    }

    /**
     * Searches the heimdall cache for the specified $primaryKey
     *
     * @param string $primaryKey - Cache Key
     *
     * @return string|false
     */
    public function getFromCache($primaryKey)
    {
        $cacheKey = 'heimdall_feature_set_' . $primaryKey;

        return $this->cache->get($cacheKey);
    }

     /**
     * Checks if a heimdall feature is enabled
     *
     * @param string  $featureName   - Name of the feature
     * @param string  $primaryKey    - Primary key of the cache
     * @param array   $requestParams - User Data parameters to send to Heimdall
     * @param boolean $resetCache    - Will fetch directly from Heimdall API if true
     *
     * @return boolean - Whether or not the feature is enabled
     */
    public function isFeatureEnabled($featureName, $primaryKey, $requestParams, $resetCache = false)
    {
        if (empty($featureName)) {
            throw new \Exception('Feature name was empty.');
        }
        if (empty($primaryKey)) {
            throw new \Exception('Primary key was empty.');
        }

        $featureSet = $this->getEnabledFeatureSet($primaryKey, $requestParams, $resetCache);

        return (isset($featureSet[$featureName]) && $featureSet[$featureName]);
    }


    /**
     * Returns the complete feature set for the app using $requestParameters
     *
     * @param string  $primaryKey    - Primary key of the cache
     * @param array   $requestParams - User Data parameters to send to Heimdall
     * @param boolean $resetCache    - Will fetch directly from Heimdall API if true
     *
     * @return array
     */
    public function getEnabledFeatureSet($primaryKey, $requestParams, $resetCache = false)
    {
        $cacheKey = 'heimdall_feature_set_' . $primaryKey;

        if (!$resetCache) {
            $featureSet = $this->getFromCache($cacheKey);

            if ($featureSet) {
                return json_decode($featureSet, true);
            }
        }

        $nonce = $this->generateNonce();

        $apiUrl = $this->apiUrl . '/get_feature_set_with_status';

        $headers = [
            'nonce'        => $nonce,
            'X-AUTH-TOKEN' => hash('sha256', $this->apiSecret . $nonce),
            'Content-type' => 'application/json'
        ];

        $queryParams = json_encode([
            'product_name' => $this->productName,
            'user_data'    => $requestParams
        ]);

        try {
            $response = $this->buzz->post($apiUrl, $headers, $queryParams);
            $content = $response->getContent();

            if (!$response->isOk()) {
                throw new \Exception("API Responded with - {$content}");
            }

            $featureSet = json_decode($content, true);

            if (!is_array($featureSet)) {
                throw new \Exception("API Responded with - {$content}");
            }

            $this->cache->set($cacheKey, $content, 3600);

            return $featureSet;
        } catch (\Exception $e) {
            throw new \Exception('Heimdall Failed: ' . $e->getMessage());
        }
    }
}
