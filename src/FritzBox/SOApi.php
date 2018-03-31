<?php

namespace Andig\FritzBox;


class SOApi

{

    private $ip;
    private $user;
    private $password;

    
    public function __construct($ip = 'fritz.box', $user = 'dslf-config', $password = false) {

        $this->ip       = $ip;
        $this->user     = $user;
        $this->password = $password;
    }

    
    public function getSOAPclient() {
    
            $client = new \SoapClient(
                null,
                array(
                    'location'   => "http://".$this->ip.":49000/upnp/control/x_contact",
                    'uri'        => "urn:dslforum-org:service:X_AVM-DE_OnTel:1",
                    'noroot'     => true,
                    'login'      => $this->user,
                    'password'   => $this->password,
                    'trace'      => true,
                    'exceptions' => true
                )
            );
        return $client;
    }

    /*
    GetPhonebook
    The following URL parameters are supported:
    Parameter name    Type          Remarks
    ---------------------------------------------------------------------------------------
    pbid              number        Phonebook ID
    max               number        maximum number of entries in call list, default 999
    sid               hex-string    Session ID for authentication
    timestamp         number        value from timestamp tag, to get the phonebook content
                                    only if last modification was made after this timestamp
    tr064sid          string        Session ID for authentication (obsolete)
    */
    public function getFBphonebook ($Phonebook = 0) {
    
        $client = $this->getSoapClient();
        $result = $client->GetPhonebook(new \SoapParam($Phonebook,"NewPhonebookID"));
        return simplexml_load_file($result['NewPhonebookURL']);
    }

/*  
    public function getFBphonebooklst () {       // delivers a string of phonebook ID (e.g. "0" or "0,1" or "0,1,2")
     
        $client = $this->getSoapClient();
        return $client->GetPhonebookList();
    }

    
    public function getFBDECThandsetlst () {       // delivers a string of DECT handset ID (e.g. "2,4,5")
     
        $client = $this->getSoapClient();
        return $client->GetDECTHandsetList();
    }
    
    
    public function getFBDECThandsetInfo ($DECTid) {       // delivers an array of DECT handset infos
     
        $client = $this->getSoapClient();
        return $client->GetDECTHandsetInfo(new \SoapParam($DECTid,"NewDectID"));
    }
*/  
    
}

?>