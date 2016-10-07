<?php

namespace Practo\HeimdallBundle\Clients;

use Symfony\Component\HttpFoundation\Request;

use \Buzz\Browser;
use \Buzz\Client\Curl;
use \Memcached;

/**
 * Heimdall Client
 */
class HeimdallClient
{
    protected $apiUrl;
    protected $apiSecret;
    protected $buzz;
    protected $cache;
    protected $request;

    const CACHE_KEY_PREFIX = 'heimdall_feature_';

    /**
     * Constructor
     *
     * @param string $apiUrl       - Heimdall API Base URL
     * @param string $apiSecret    - Heimdall API Secret
     * @param array  $cacheServers - Array of key-values pairs of host & port
     */
    public function __construct($apiUrl, $apiSecret, $cacheServers)
    {
        $this->apiUrl = $apiUrl;
        $this->apiSecret = $apiSecret;
        $this->buzz = new Browser(new Curl());
        $this->cache = new Memcached();
        foreach ($cacheServers as $host => $port) {
            $server = [$host, $port];
            $this->cache->addServers(array($server));
        }
    }

    /**
     * Set Request
     *
     * @param Request $request - Request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    protected function generateNonce()
    {
        return time() . '|' . substr(str_shuffle(
            str_repeat('abcdefghijklmnopqrstuvwxyz0123456789', 3)), 0, 20);
    }

    /**
     * Searches the heimdall cache for the specified $primaryKey
     *
     * @param string $primaryKey     - Cache Key
     * @param array  $featureNameSet - List of feature names
     *
     * @return string|false
     */
    public function getFromCache($primaryKey, $featureNameSet)
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $primaryKey . '_' . json_encode($featureNameSet);

        return $this->cache->get(hash('sha256', $cacheKey) . sizeof($featureNameSet));
    }

     /**
     * Checks if a heimdall feature is enabled
     *
     * @param string  $productName   - Name of the product of the feature
     * @param string  $featureName   - Name of the feature
     * @param string  $primaryKey    - Primary key of the cache
     * @param array   $requestParams - User Data parameters to send to Heimdall
     * @param boolean $resetCache    - Will fetch directly from Heimdall API if true
     * @param int $ttl               - time to live for caching the data
     *
     * @return boolean - Whether or not the feature is enabled
     */
    public function isFeatureEnabled($productName, $featureName, $primaryKey, $requestParams, $resetCache = false, $ttl = 3600)
    {
        $featureNameSet = array($featureName);
        $featureSet = $this->getEnabledFeatureSet($productName, $featureNameSet, $primaryKey, $requestParams, $resetCache);

        return (isset($featureSet[$featureName]) && $featureSet[$featureName]);
    }


    /**
     * get a set of heimdall features with status
     *
     * @param string  $productName    - Name of the product of the feature
     * @param array   $featureNameSet - List of feature names
     * @param string  $primaryKey     - Primary key of the cache
     * @param array   $requestParams  - User Data parameters to send to Heimdall
     * @param boolean $resetCache     - Will fetch directly from Heimdall API if true
     * @param int $ttl                - time to live for caching the data
     *
     * @return array - list of features with status
     */
    public function areFeaturesEnabled($productName, $featureNameSet, $primaryKey, $requestParams, $resetCache = false, $ttl = 3600)
    {
        $featureSet = $this->getEnabledFeatureSet($productName, $featureNameSet, $primaryKey, $requestParams, $resetCache);

        foreach ($featureNameSet as $featureName) {
            if (!isset($featureSet[$featureName])) {
                $featureSet[$featureName] = false;
            }
        }

        return $featureSet;
    }


    /**
     * Returns the complete feature set for the app using $requestParameters
     *
     * @param string  $productName    - Name of the product of the feature
     * @param array   $featureNameSet - List of feature names
     * @param string  $primaryKey     - Primary key of the cache
     * @param array   $requestParams  - User Data parameters to send to Heimdall
     * @param boolean $resetCache     - Will fetch directly from Heimdall API if true
     * @param int $ttl                - time to live for caching the data
     *
     * @return array
     */
    private function getEnabledFeatureSet($productName, $featureNameSet, $primaryKey, $requestParams, $resetCache = false, $ttl = 3600)
    {
        if (empty($productName)) {
            throw new \Exception('Product name was empty.');
        }
        if (empty($featureNameSet)) {
            throw new \Exception('Feature names were empty.');
        }
        if (empty($primaryKey)) {
            throw new \Exception('Primary key was empty.');
        }
        if (empty($requestParams)) {
            throw new \Exception('Request params were empty.');
        }
        $cacheKey = self::CACHE_KEY_PREFIX . $primaryKey . '_' . json_encode($featureNameSet);

        if (!$resetCache) {
            $featureSet = $this->getFromCache($primaryKey, $featureNameSet);

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

        if ($this->request instanceof Request) {
            $headers['User-Agent'] = $this->request->headers->get('User-Agent');
        }

        $queryParams = json_encode([
            'product_name' => $productName,
            'user_data'    => $requestParams,
            'feature_set'  => $featureNameSet
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

            $this->cache->set(hash('sha256', $cacheKey) . sizeof($featureNameSet), $content, $ttl);

            return $featureSet;
        } catch (\Exception $e) {
            throw new \Exception('Heimdall Failed: ' . $e->getMessage());
        }
    }
}
