<?php
namespace Practo\HeimdallBundle\Clients;

class HeimdallClient
{
    protected $apiKey;
    protected $cache;
    protected $productName;

    public function __construct($productName, $apiKey, $cache)
    {
        $this->productName = $productName;
        $this->apiKey = $apiKey;
        $this->cache = $cache;
    }

    protected function generateNonce()
    {
        return time() . '|' . substr(str_shuffle(
            str_repeat('abcdefghijklmnopqrstuvwxyz0123456789', 3)), 0, 20);
    }

    public function post($feature_name, $primary_key, $request_params) {
        $cacheKey = 'heimdall_feature_set_' . $primary_key;
        $featureSet = $this->cache->get($cacheKey);
        $featureSet = json_decode($featureSet, true);

        if (!$featureSet) {
            try {
                $nonce = $this->generateNonce();
                $heimdallBaseUri = $this->_heimdall_base_uri;
                $api_secret_key = $this->_heimdall_api_key;
                $auth_token = hash('sha256', $api_secret_key.$nonce);
                $api_url = $heimdallBaseUri.'/get_feature_set_with_status';
                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt ($ch , CURLOPT_RETURNTRANSFER , 1 );
                $headers = array(
                    'nonce: '.$nonce,
                    'X-AUTH-TOKEN: '.$auth_token,
                    'Content-type: application/json'
                );
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $data = array();
                $data['product_name'] = $this->productName;
                $data['user_data'] = $request_params;
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                $featureSet = curl_exec($ch);
                $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $errorMessage = '';
                if (false !== $featureSet) {
                    $featureSet = json_decode($featureSet, True);
                    if($responseCode === 200){
                        $practoCache->set('heimdall_feature_set_'.$primary_key, json_encode($featureSet), 3600);
                    }
                    else{
                        $errorMessage = $responseCode.' '.$responseCode.': '. $featureSet['message'];
                    }
                }
                else{
                    $errorMessage = curl_error($ch);
                }
                curl_close($ch);
                if($errorMessage){
                    throw new \Exception($errorMessage);
                }

            } catch (\Exception $e) {
                throw new \Exception("Heimdall Failed: " . $e->getMessage());
            }
        }
        return (isset($featureSet[$featureName]) && $featureSet[$featureName]);
    }
}
