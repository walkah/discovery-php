<?php
/**
 * Discovery
 *
 * PHP Version 5.2.0+
 *
 * @category  Services
 * @package   Discovery
 * @author    James Walker <walkah@walkah.net>
 * @copyright 2010 James Walker
 * @license   http://opensource.org/licenses/gpl-2.0.php GPL
 * @license   http://opensource.org/licenses/mit-license.php MIT
 * @link      http://github.com/walkah/php-discovery
 *
 * @see http://yadis.org/wiki/Yadis_1.0_(HTML)
 */

require_once 'Discovery/XRDS.php';

class Discovery_Yadis extends Discovery
{

    const CONTENT_TYPE = 'application/xrds+xml';

    public function discover($yadis_url)
    {
        $headers = array('Accept' => Discovery_Yadis::CONTENT_TYPE);
        try {
            $response = $this->http->request($yadis_url, 'GET', $headers);
        } catch (Discovery_Exception $e) {
            throw $e;
        }

        if ($response['status'] == 200) {
            // 1) Do we have a XRDS document directly?
            // 2) Did we get an X-XRDS-Location ?
            // 3) Check the HTML for <meta> http-equiv
            $xrds = null;
            if ($response['headers']['content-type'] == Discovery_Yadis::CONTENT_TYPE) {
                $xrds = $this->parseXRDS($response['body']);
            } else if (isset($response['headers']['x-xrds-location'])) {
                $xrds = $this->fetchXRDS($response['headers']['x-xrds-location']);
            } else if ($xrds_location = $yadis->findHTML($response['body'])) {
                $xrds = $this->fetchXRDS($xrds_location);
            }
            
            if ($xrds) {
                return $xrds;
            }
        }

        throw new Discovery_Exception('Unable to find XRDS Document');
    }

    /**
     * Locate the <meta http-equiv> value from an HTML document.
     */
    protected function findHTML($html)
    {
        $dom = @DOMDocument::loadHTML($html);
        $head = $dom->getElementsByTagName('head')->item(0);

        if (!$head) {
            return false;
        }
        $meta_elements = $head->getElementsByTagName('meta');
        foreach ($meta_elements as $meta) {
            $http_equiv = $meta->getAttribute('http-equiv');
            if ($http_equiv == 'X-XRDS-Location') {
                return $meta->getAttribute('content');
            }
        }
    }

    /**
     * Parse an XRDS Document.
     */
    protected function parseXRDS($xrds)
    {
        return Discovery_XRDS::loadXML($xrds);
    }

    protected function fetchXRDS($xrds_url)
    {
        try {
            $response = $this->http->request($xrds_url);
        } catch (Discovery_Exception $e) {
            throw $e;
        }
        
        if ($response['status'] == 200) {
            return $this->parseXRDS($response['body']);
        }
    }
}
