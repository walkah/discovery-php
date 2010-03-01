<?php
/**
 * XRDS
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
class Discovery_XRDS
{
    const XRDS_NS = 'xri://$xrds';

    const XRD_NS  = 'xri://$XRD*($v*2.0)';

    const OPENID_NS = 'http://openid.net/xmlns/1.0';
    
    public $services = array();

    public static function loadXML($xml)
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xrds_element = $dom->getElementsByTagName('XRDS')->item(0);
        
        return self::fromDOM($xrds_element);
    }


    public static function fromDOM(DOMElement $dom)
    {
        $xrds = new Discovery_XRDS;

        $xrd_element = $dom->getElementsByTagName('XRD')->item(0);
        $service_elements = $dom->getElementsByTagName('Service');
        foreach ($service_elements as $element) {
            $service = array();
            $service['priority'] = $element->getAttribute('Priority');
            
            $elements = $element->getElementsByTagName('Type');
            foreach ($elements as $e) {
                $service['type'][] = $e->nodeValue;
            }

            if ($uri = $element->getElementsByTagName('URI')->item(0)) {
                $service['uri'] = $uri->nodeValue;
            }

            if ($localID = $element->getElementsByTagName('LocalID')->item(0)) {
                $service['local_id'] = $localID->nodeValue;
            }

            if ($delegate = $element->getElementsByTagNameNS(Discovery_XRDS::OPENID_NS, 'Delegate')->item(0)) {
                $service['delegate'] = $delegate->nodeValue;
            }
            
            $xrds->services[] = $service;
        }
        usort($xrds->services, array('Discovery_XRDS', 'sortPriority'));
        
        return $xrds;
    }

    public function getService($type)
    {
        foreach ($this->services as $service) {
            if (in_array($type, $service['type'])) {
                return $service;
            }
        }

    }
    
    protected static function sortPriority($a, $b)
    {
        if ($a['priority'] == $b['priority']) return 0;
        if ($a['priority'] > $b['priority']) return 1;
        if ($a['priority'] < $b['priority']) return -1;
    }
}
