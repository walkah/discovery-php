<?php
/**
 * Discovery_HTTP_PEAR
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
 */

require_once 'HTTP/Request2.php';

class Discovery_HTTP_PEAR implements Discovery_HTTP
{

    public function request($url, $method = 'GET', $headers = array(), $data = null)
    {
        $request = new HTTP_Request2();

        $options['follow_redirects'] = true;
        $options['ssl_verify_host'] = false;
        $options['ssl_verify_peer'] = false;

        $request->setConfig($options);
        
        $request->setUrl($url);
        $request->setMethod($method);
        $request->setHeader($headers);
        $request->setBody($data);

        try {
            $response = $request->send();
        } catch (HTTP_Request2_Exception $e) {
            throw new Discovery_Exception($e->getMessage());
        }

        return array(
            'status'  => $response->getStatus(),
            'headers' => $response->getHeader(),
            'body'    => $response->getBody(),
        );
    }
}

