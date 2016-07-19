<?php
namespace Practo\HeimdallBundle\Clients;
use Symfony\Component\HttpFoundation\Request;
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

	public function getHeimdallUri() {
		$domain = $this->_domain;
		$parts = parse_url($domain);
		$host_parts = explode('.', $parts['host']);
		$subdomain = $host_parts[0];
		$subdomain = explode('-', $subdomain);
		$uri = $parts['scheme'] . '://heimdall';
		if (isset($subdomain[1])) {
			$uri .= '-' . $subdomain[1];
		}
		$uri .= '.' . $host_parts[1] . '.' . $host_parts[2];
		return $uri;
	}
}

?>
