<?php
/**
 * Discovery_LRDD
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

require_once 'Discovery/XRD.php';

class Discovery_LRDD extends Discovery
{
    const LRDD_REL = 'lrdd';

    /**
     * This implements the actual lookup procedure
     */
    public function discover($id)
    {
        // Normalize the incoming $id to make sure we have a uri
        $uri = $this->normalize($id);

        $links = array();
        
        // 1) Check host-meta
        $links = $this->getHostMeta($id);

        if (count($links) == 0) {
            try {
                $response = $this->http->request($uri);
            } catch (Discovery_Exception $e) {
                throw $e;
            }

            if ($response['status'] == 200) {
                // 2) Check for a Link: HTTP Header
                if (isset($response['headers']['link'])) {
                    $links = $this->parseHeader($response['headers']['link']);
                }

                if (count($links) == 0) {
                    $links = $this->parseHTML($response['body']);
                }
            }
            
        }
        
        if ($link = $this->getService($links, Discovery_LRDD::LRDD_REL)) {
            // Load the LRDD XRD
            if ($link['template']) {
                $xrd_uri = Discovery_LRDD::applyTemplate($link['template'], $uri);
            } else {
                $xrd_uri = $link['href'];
            }

            $xrd = $this->fetchXrd($xrd_uri);
            if ($xrd) {
                return $xrd;
            }
        }
        
        throw new Discovery_Exception('Unable to find services via LRDD.');
    }
    
    /**
     * Given a "user id" make sure it's normalized to either a webfinger
     * acct: uri or a profile HTTP URL.
     */
    public static function normalize($user_id)
    {
        if (substr($user_id, 0, 5) == 'http:' ||
            substr($user_id, 0, 6) == 'https:' ||
            substr($user_id, 0, 5) == 'acct:') {
            return $user_id;
        }

        if (strpos($user_id, '@') !== FALSE) {
            return 'acct:' . $user_id;
        }

        return 'http://' . $user_id;
    }

    public static function isWebfinger($user_id)
    {
        $uri = Discovery_LRDD::normalize($user_id);
        
        return (substr($uri, 0, 5) == 'acct:');
    }


    public static function getService($links, $service) {
        if (!is_array($links)) {
            return false;
        }
        
        foreach ($links as $link) {
            if ($link['rel'] == $service) {
                return $link;
            }
        }
    }
    

    public static function applyTemplate($template, $id)
    {
        $template = str_replace('{uri}', urlencode($id), $template);

        return $template;
    }

    
    public function fetchXrd($url)
    {
        try {
            $response = $this->http->request($url);
        } catch (Discovery_Exception $e) {
            throw $e;
        }

        if ($response['status'] == 200) {
            return Discovery_XRD::loadXML($response['body']);
        }
    }
    
    public function getHostMeta($acct)
    {
        if (Discovery_LRDD::isWebfinger($id)) {
            // We have a webfinger acct: - start with host-meta
            list($name, $domain) = explode('@', $acct);
        } else {
            $domain = parse_url($acct, PHP_URL_HOST);
        }
        $url = 'http://'. $domain .'/.well-known/host-meta';

        $xrd = $this->fetchXrd($url);
        if ($xrd) {
            if ($xrd->host != $domain) {
                return false;
            }
            
            return $xrd->links;
        }
    }

    protected static function parseHeader($header)
    {
        preg_match('/^<[^>]+>/', $header, $uri_reference);
        //if (empty($uri_reference)) return;

        $links = array();
        
        $link_uri = trim($uri_reference[0], '<>');
        $link_rel = array();
        $link_type = null;
        
        // remove uri-reference from header
        $header = substr($header, strlen($uri_reference[0]));
        
        // parse link-params
        $params = explode(';', $header);
        
        foreach ($params as $param) {
            if (empty($param)) continue;
            list($param_name, $param_value) = explode('=', $param, 2);
            $param_name = trim($param_name);
            $param_value = preg_replace('(^"|"$)', '', trim($param_value));
            
            // for now we only care about 'rel' and 'type' link params
            // TODO do something with the other links-params
            switch ($param_name) {
            case 'rel':
                $link_rel = trim($param_value);
                break;
                
            case 'type':
                $link_type = trim($param_value);
            }
        }

        $links[] =  array(
            'href' => $link_uri,
            'rel' => $link_rel,
            'type' => $link_type);

        return $links;
    }

    public function parseHTML($html)
    {
        $dom = @DOMDocument::loadHTML($html);
        $head = $dom->getElementsByTagName('head')->item(0);

        if (!$head) {
            return false;
        }

        $links = array();

        $link_elements = $head->getElementsByTagName('link');
        foreach ($link_elements as $link_element) {
            $links[] = Discovery_XRD::parseLink($link_element);
        }

        return $links;
    }
}
