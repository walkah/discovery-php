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
 */

require_once 'Discovery/HTTP.php';

class Discovery
{

    /**
     * HTTP_Adapter
     *
     * @var $http
     */
    public $http;
    
    /**
     * Constructor
     *
     * @param HTTP_Adapter $http    HTTP Adapter to use for requests
     */
    public function __construct($http = null)
    {
        $this->setHttpAdapter($http);
    }

    public static function factory($method, $http = null)
    {
        $class = 'Discovery_'. $method;
        
        @include_once 'Discovery' . DIRECTORY_SEPARATOR . $method .'.php';
        if (class_exists('Discovery_'. $method)) {
            return new $class($http);
        }
    }
    
    /**
     * Set the HTTP Adapter to user
     */
    public function setHttpAdapter($http = null)
    {
        if (is_null($http)) {
            $http = $this->determineHttpAdapter();
        }

        if ($http instanceof Discovery_HTTP) {
            $this->http = $http;
            return true;
        }
    }

    public function determineHttpAdapter()
    {
        include_once 'Discovery/HTTP/PEAR.php';

        return new Discovery_HTTP_PEAR;
    }
}

class Discovery_Exception extends Exception
{

}
