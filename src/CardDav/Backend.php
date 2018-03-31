<?php

namespace Andig\CardDav;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Ringcentral\Psr7;

/**
 * @author Christian Putzke <christian.putzke@graviox.de>
 * @copyright Christian Putzke
 * @link http://www.graviox.de/
 * @link https://twitter.com/cputzke/
 * @since 24.05.2015
 * @version 0.7
 * @license http://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class Backend
{
    /**
     * CardDAV server url
     *
     * @var     string
     */
    private $url;

    /**
     * VCard File URL Extension
     *
     * @var string
     */
    private $url_vcard_extension = '.vcf';

    /**
    * Authentication: username
    *
    * @var  string
    */
    private $username;

    /**
    * Authentication: password
    *
    * @var  string
    */
    private $password;

    /**
     * Progress callback
     */
    private $callback;
    
    
    private $substitutes = [];
    
    /**
     * Constructor
     * Sets the CardDAV server url
     *
     * @param   string  $url    CardDAV server url
     */
    public function __construct(string $url=null) {
        if ($url) {
            $this->setUrl($url);
        }
    }

    
    public function setSubstitutes ($elements) {
            
        foreach ($elements as $element) {
            $this->substitutes [] = strtoupper($element);
        }
    }
    
    
    public function setUrl(string $url)
    {
        $this->url = $url;

        if (substr($this->url, -1, 1) !== '/') {
            $this->url = $this->url . '/';
        }

        // workaround for providers that don't use the default .vcf extension
        if (strpos($this->url, "google.com")) {
            $this->url_vcard_extension = '';
        }
    }

    /**
     * Set progress callback
     */
    public function setProgress($callback = null)
    {
        $this->callback = $callback;
    }

    /**
     * Set credentials
     */
    public function setAuth(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Gets all vCards including additional information from the CardDAV server
     *
     * @param   boolean $include_vcards     Include vCards within the response (simplified only)
     * @return  string                      Raw or simplified XML response
     */
    public function getVcards($include_vcards = true)
    {
        $response = $this->query($this->url, 'PROPFIND');
        
        // DEBUG: print_r ($response);
        
        if (in_array($response->getStatusCode(), [200,207])) {
            $body = (string)$response->getBody();
            // DEBUG: echo $body;
            return $this->simplify($body, $include_vcards);
        }

        throw new \Exception('Received HTTP ' . $response->getStatusCode());
    }
    
    
    /*
    Delivers the UNIX timestamp of last modification
    */
    public function getModDate ()
    {            
        $response = $this->query($this->url, 'PROPFIND');
        if (in_array($response->getStatusCode(), [200,207])) {
            $body = new \SimpleXMLElement((string)$response->getBody());
            // DEBUG: $body->asXML('getModDate.xml');;
            foreach ($body->response->propstat->prop as $prop) {
                IF ($prop->resourcetype->collection) {
                    return strtotime($prop->getlastmodified);
                    break;
                }
            }
        }
        throw new \Exception('Received HTTP ' . $response->getStatusCode());
    }

        
    // get the Base64 decode data from an external URL and embedding it instead of the link
    private function embeddingBase64 ($vcard, $substituteID) 
    {
        $search_card = strtoupper($vcard);                             // $equivalent to substituteID in 'CAPITALS'
        
        IF (!preg_match ("/$substituteID/", $search_card)) {           // rough check if element is ever included in vCard
            return $vcard;
        }
        ELSE {                                                         // if so, we have to dismantle the vCard in lines
            $vcard = str_replace(["\r\n", "\r"], "\n", $vcard);
            $vcard = preg_replace("/\n(?:[ \t])/", "", $vcard);
            $lines = explode("\n", $vcard);
            
            // in versions 3.0 and 4.0, this must come right after the BEGIN property.
            // keep in mind: CardDAV MUST support VERSION 3 as a media type (RFC 2426)
            $version = $this->getVersion (trim($lines[1]));
            
            // lets find the exact line we are looking for: e.g. PHOTO, LOGO, KEY or SOUND
            $key = -1;
            foreach ($lines as $line)  {
                $key++;
                IF (preg_match ("/$substituteID/", $line)) {
                    break;
                }
            }
            @list($type, $value) = explode(':', $lines[$key], 2);      // dismantle the designated line
            IF (!preg_match("/http/", $value)) {                       // no external URL -> must be allready base64 or local
                return $vcard;
            }
            ELSE {                                                     // get the data from the external URL
                $embedded = $this->getlinkedData($value);
                switch ($version) {
                    case 3:                                            // assamble the new line
                        $newline = $substituteID.';TYPE='.strtoupper($embedded['sub_type']).';ENCODING=b:'.$embedded['base64_data'];
                        break;
                    case 4:                                            // assamble the new line
                        $newline = $substituteID.':data:'.$embedded['mime_type'].';base64,'.$embedded['base64_data'];
                        break;
                }
                $lines[$key] = $newline ?? $lines[$key];               // reassembel the lines to a consitent vCard
                $vcard = implode(PHP_EOL,$lines);
                // DEBUG: echo $vcard . PHP_EOL;
            }
            return $vcard;
        }
    }
    
    
    /* returns 0 if its not the line containing the VERSION property or
       returns 99 if the property value could not converted to an integer (contains whatever)
    */
    private function getVersion ($vCardline) {
        
        $type = '';
        $value = '';
        @list($type, $value) = explode(':', $vCardline, 2);

        IF (preg_match('/VERSION/',strtoupper($type))) {
            $version = 0+$value ?? 99;
        }
        ELSE {
            $version = 0;
        }
        return $version;
    }
        
    /*
    * delivers an array including the previously linked data and its mime type details
    * a mime type  is composed of a type, a subtype, and optional parameters (e.g. "; charset=UTF-8")
    * ['mime_type']  : e.g. "image/jpeg" 
    * ['type']       : e.g. "audio"  
    * ['sub_type']   : e.g. "mpeg"
    * ['parameters'] : whatever
    * ['base64_data']: the base64 encoded data
    */
    public function getlinkedData($uri)
    {
        $ExternalData = array();
        
        $this->client = $this->client ?? new Client();
        $request = new Request('GET', $uri);

        if ($this->username) {
            $credentials = base64_encode($this->username.':'.$this->password);
            $request = $request->withHeader('Authorization', 'Basic '.$credentials);
        }
        $response = $this->client->send($request);
        // DEBUG: print_r ($response);
        
        if (200 !== $response->getStatusCode()) {
            throw new \Exception('Received HTTP ' . $response->getStatusCode());
        }
        ELSE {
            $content_type = $response->getHeader('Content-Type');
            
            @list($mime_type,$parameters) = explode(';', $content_type[0], 2);
            @list($type, $subtype) = explode('/', $mime_type);
                        
            $ExternalData['mime_type']   = $mime_type ?? '';
            $ExternalData['type']        = $type ?? '';
            $ExternalData['sub_type']    = $subtype ?? '';
            $ExternalData['parameters']  = $parameters ?? '';
            $ExternalData['base64_data'] = base64_encode((string)$response->getBody());    
        }
        return $ExternalData;
    }

    
    /**
    * Gets a clean vCard from the CardDAV server
    *
    * @param    string  $vcard_id   vCard id on the CardDAV server
    * @return   string              vCard (text/vcard)
    */
    public function getVcard($vcard_id)
    {
        $vcard_id = str_replace($this->url_vcard_extension, null, $vcard_id);
        $response = $this->query($this->url . $vcard_id . $this->url_vcard_extension, 'GET');

        if (in_array($response->getStatusCode(), [200,207])) {
            $body = (string)$response->getBody();

            if (isset($this->substitutes)) {
                foreach ($this->substitutes as $substitute) {
                    $body = $this->embeddingBase64 ($body, $substitute);
                }
            }
            if (is_callable($this->callback)) {
                ($this->callback)();
            }
            // DEBUG: echo $body;    // >getvCard_body.txt
            return $body;
        }
        throw new \Exception('Received HTTP ' . $response->getStatusCode());
    }

    /**
     * Simplify CardDAV XML response
     *
     * @param   string  $response           CardDAV XML response
     * @return  string                      Simplified CardDAV XML response
     */
    private function simplify(string $response): array
    {
        $response = $this->cleanResponse($response);
        $xml = new \SimpleXMLElement($response);

        $cards = [];

        foreach ($xml->response as $response) {
            if ((preg_match('/vcard/', $response->propstat->prop->getcontenttype) || preg_match('/vcf/', $response->href)) &&
              !$response->propstat->prop->resourcetype->collection) {
                $id = basename($response->href);
                $id = str_replace($this->url_vcard_extension, null, $id);

                $cards[] = $this->getVcard($id);
            }
        }
        // DEBUG: print_r ($cards);    // >getvCard_simplified.txt
        return $cards;
    }

    /**
     * Cleans CardDAV XML response
     *
     * @param   string  $response   CardDAV XML response
     * @return  string  $response   Cleaned CardDAV XML response
     */
    private function cleanResponse($response)
    {
        $response = utf8_encode($response);
        $response = str_replace('D:', null, $response);
        $response = str_replace('d:', null, $response);
        $response = str_replace('C:', null, $response);
        $response = str_replace('c:', null, $response);

        return $response;
    }

    /**
     * Query the CardDAV server via curl and returns the response
     *
     * @param   string  $url                CardDAV server URL
     * @param   string  $method             HTTP method like (OPTIONS, GET, HEAD, POST, PUT, DELETE, TRACE, COPY, MOVE)
     * @param   string  $content            Content for CardDAV queries
     * @param   string  $content_type       Set content type
     * @return  array                       Raw CardDAV Response and http status code
     */
    private function query($url, $method, $content = null, $content_type = null)
    {
        $this->client = $this->client ?? new Client();
        $request = new Request($method, $url, [
            'Depth' => '1'
        ]);

        if ($content_type) {
            $request = $request->withHeader('Content-type', $content_type);
        }

        if ($content) {
            $request = $request->withBody($content);
        }

        if ($this->username) {
            $credentials = base64_encode($this->username.':'.$this->password);
            $request = $request->withHeader('Authorization', 'Basic '.$credentials);
        }

        $response = $this->client->send($request);
        return $response;
    }
}