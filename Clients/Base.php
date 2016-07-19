<?php
namespace Practo\HeimdallBundle\Clients;

use Symfony\Component\HttpFoundation\Request;

/**
 * Base Class for Heimdall Client
 */
class Base
{
    protected $_domain;
    protected $request;

    /**
     * Set Request and Domain
     *
     * @param Request $request - Request
     */
    public function setRequestAndDomain($request)
    {
        $this->request = $request;
        $this->_domain = null;
        if ($request instanceof Request) {
            $this->_domain = $this->request->getSchemeAndHttpHost();
        }
    }
    
    /**
     * Get Heimdall Uri from domain of request
     *
     * @return string - Heimdall base uri
     */
    public function getHeimdallUri()
    {
        $domain = $this->_domain;
        $parts = parse_url($domain);
        $hostParts = explode('.', $parts['host']);
        $subdomain = $hostParts[0];
        $subdomain = explode('-', $subdomain);
        $uri = $parts['scheme'] . '://heimdall';
        if (isset($subdomain[1])) {
            $uri .= '-' . $subdomain[1];
        }
        $uri .= '.' . $hostParts[1] . '.' . $hostParts[2] . '/v2';

        return $uri;
    }
}
