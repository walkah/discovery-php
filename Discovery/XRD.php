<?php
/**
 * Discovery_XRD
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
class Discovery_XRD
{
    const XML_NS = 'http://www.w3.org/2000/xmlns/';
    
    const XRD_NS = 'http://docs.oasis-open.org/ns/xri/xrd-1.0';

    const HOST_META_NS = 'http://host-meta.net/xrd/1.0';
    
    public $expires;

    public $subject;

    public $host;

    public $alias = array();
    
    public $types = array();
    
    public $links = array();

    public static function loadXML($xml)
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xrd_element = $dom->getElementsByTagName('XRD')->item(0);

        return self::fromDOM($xrd_element);
    }
    
    public static function fromDOM(DOMElement $dom)
    {
        $xrd = new Discovery_XRD();

        // Check for host-meta host
        $host = $dom->getElementsByTagName('Host')->item(0);
        if (isset($host->nodeValue)) {
            $xrd->host = $host->nodeValue;
        }

        // Loop through other elements
        foreach ($dom->childNodes as $node) {
            if (!isset($node->tagName)) {
                continue;
            }
            
            switch ($node->tagName) {
            case 'Expires':
                $xrd->expires = $node->nodeValue;
                break;
            case 'Subject':
                $xrd->subject = $node->nodeValue;
                break;
                
            case 'Alias':
                $xrd->alias[] = $node->nodeValue;
                break;

            case 'Link':
                $xrd->links[] = $xrd->parseLink($node);
                break;

            }
        }
        return $xrd;
    }

    public function toXML()
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        
        $xrd_dom = $dom->createElementNS(Discovery_XRD::XRD_NS, 'XRD');
        $dom->appendChild($xrd_dom);

        if ($this->host) {
            $host_dom = $dom->createElement('hm:Host', $this->host);
            $xrd_dom->setAttributeNS(Discovery_XRD::XML_NS, 'xmlns:hm', Discovery_XRD::HOST_META_NS);
            $xrd_dom->appendChild($host_dom);
        }
        
        if ($this->expires) {
            $expires_dom = $dom->createElement('Expires', $this->expires);
            $xrd_dom->appendChild($expires_dom);
        }
        
        if ($this->subject) {
            $subject_dom = $dom->createElement('Subject', $this->subject);
            $xrd_dom->appendChild($subject_dom);
        }
        
        foreach ($this->alias as $alias) {
            $alias_dom = $dom->createElement('Alias', $alias);
            $xrd_dom->appendChild($alias_dom);
        }
        
        foreach ($this->types as $type) {
            $type_dom = $dom->createElement('Type', $type);
            $xrd_dom->appendChild($type_dom);
        }
        
        foreach ($this->links as $link) {
            $link_dom = $this->saveLink($dom, $link);
            $xrd_dom->appendChild($link_dom);
        }
        
        return $dom->saveXML();
    }
    
    function parseLink($element)
    {
        $link = array();
        $link['rel'] = $element->getAttribute('rel');
        $link['type'] = $element->getAttribute('type');
        $link['href'] = $element->getAttribute('href');
        $link['template'] = $element->getAttribute('template');
        foreach ($element->childNodes as $node) {
            if (!isset($node->tagName)) {
                continue;
            }
            
            switch($node->tagName) {
            case 'Title':
                $link['title'][] = $node->nodeValue;
            }
        }

        return $link;
    }

    function saveLink($doc, $link)
    {
        $link_element = $doc->createElement('Link');
        if ($link['rel']) {
            $link_element->setAttribute('rel', $link['rel']);
        }
        if ($link['type']) {
            $link_element->setAttribute('type', $link['type']);
        }
        if ($link['href']) {
            $link_element->setAttribute('href', $link['href']);
        }
        if ($link['template']) {
            $link_element->setAttribute('template', $link['template']);
        }

        if (is_array($link['title'])) {
            foreach($link['title'] as $title) {
                $title = $doc->createElement('Title', $title);
                $link_element->appendChild($title);
            }
        }

        
        return $link_element;
    }
}

