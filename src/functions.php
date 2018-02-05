<?php

namespace Andig;

use Andig\CardDav\Backend;
use Andig\Vcard\Parser;
use Andig\Vcard\mk_vCard;
use Andig\FritzBox\Api;
use Andig\FritzBox\Converter;
use Andig\FritzAdr\converter2fa;
use Andig\FritzAdr\fritzadr;
use Andig\ReplyMail\replymail;
use \SimpleXMLElement;


function backendProvider(array $config): Backend
{
    $server = $config['server'] ?? $config;

    $backend = new Backend();
    $backend->setUrl($server['url']);
    $backend->setAuth($server['user'], $server['password']);

    return $backend;
}

function download(Backend $backend, callable $callback=null): array
{
    $backend->setProgress($callback);
    return $backend->getVcards();
}

function downloadImages(Backend $backend, array $cards, callable $callback=null): array
{
    foreach ($cards as $card) {
        if (isset($card->photo)) {
            $uri = $card->photo;
            $image = $backend->fetchImage($uri);
            $card->photo_data = utf8_encode($image);

            if (is_callable($callback)) {
                $callback();
            }
        }
    }

    return $cards;
}

function countImages(array $cards): int
{
    $images = 0;

    foreach ($cards as $card) {
        if (isset($card->photo_data)) {
            $images++;
        }
    }

    return $images;
}

function parse(array $cards): array
{
    $vcards = [];
    $groups = [];

    // parse all vcards
    foreach ($cards as $card) {
        $parser = new Parser($card);
        $vcard = $parser->getCardAtIndex(0);

        // separate iCloud groups
        if (isset($vcard->xabsmember)) {
            $groups[$vcard->fullname] = $vcard->xabsmember;
            continue;
        }

        $vcards[] = $vcard;
    }

    // assign group memberships
    foreach ($vcards as $vcard) {
        foreach ($groups as $group => $members) {
            if (in_array($vcard->uid, $members)) {
                if (!isset($vcard->group)) {
                    $vcard->group = array();
                }

                $vcard->group = $group;
                break;
            }
        }
    }

    return $vcards;
}

/**
 * Filter included/excluded vcards
 *
 * @param array $cards
 * @param array $filters
 * @return array
 */
function filter(array $cards, array $filters): array
{
    // include selected
    $includeFilter = $filters['include'] ?? [];
    if (count($includeFilter)) {
        $step1 = [];

        foreach ($cards as $card) {
            if (filtersMatch($card, $includeFilter)) {
                $step1[] = $card;
            }
        }
    }
    else {
        // include all by default
        $step1 = $cards;
    }

    $excludeFilter = $filters['exclude'] ?? [];
    if (!count($excludeFilter)) {
        return $step1;
    }

    $step2 = [];
    foreach ($step1 as $card) {
        if (!filtersMatch($card, $excludeFilter)) {
            $step2[] = $card;
        }
    }

    return $step2;
}

function filtersMatch($card, array $filters): bool
{
    foreach ($filters as $attribute => $values) {
        if (isset($card->$attribute)) {
            if (filterMatches($card->$attribute, $values)) {
                return true;
            }
        }
    }

    return false;
}

function filterMatches($attribute, $filterValues): bool
{
    if (!is_array($filterValues)) {
        $filterValues = array($filterMatches);
    }

    foreach ($filterValues as $filter) {
        if (is_array($attribute)) {
            // check if any attribute matches
            foreach ($attribute as $childAttribute) {
                if ($childAttribute === $filter) {
                    return true;
                }
            }
        } else {
            // check if simple attribute matches
            if ($attribute === $filter) {
                return true;
            }
        }
    }

    return false;
}

function export(string $name, array $cards, array $conversions): SimpleXMLElement
    {
    $xml = new SimpleXMLElement(
        <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<phonebooks>
<phonebook />
</phonebooks>
EOT
    );

    $root = $xml->xpath('//phonebook')[0];
    $root->addAttribute('name', $name);

    $converter = new Converter($conversions);

    foreach ($cards as $card) {
        $contact = $converter->convert($card);
        // $root->addChild('contact', $contact);
        xml_adopt($root, $contact);
    }

    return $xml;
}


function getSOAPclient($fb_ip = 'fritz.box', $user = 'dslf_config', $password = false) {
    
        $client = new \SoapClient(
            null,
            array(
                'location'   => "http://".$fb_ip.":49000/upnp/control/x_contact",
                'uri'        => "urn:dslforum-org:service:X_AVM-DE_OnTel:1",
                'noroot'     => true,
                'login'      => $user,
                'password'   => $password,
                'trace'      => true,
                'exceptions' => true
            )
        );
    return $client;
}


function exportFA($xml, string $dblocation) { 
    
    $convert2fa = new converter2fa();
    $DB3 = new fritzadr;                                            // Instanz von fritzadr erzeugen                                                // Achtung -> in config mit aufnehmen!
    
    IF ($DB3->CreateFritzAdr($dblocation)) {                        // Versuche die dBase-Datei zu erzeugen
        $DB3->OpenFritzAdr();                                       // wenn erfolgreich dann öffne die dBase-Datei
        $FritzAdrRecords = $convert2fa->convert($xml, $DB3->NumAttributes);
        $numconv = count($FritzAdrRecords);
        IF ($numconv > 0) {
            foreach ($FritzAdrRecords as $FritzAdrRecord) {         // zerlege  FritzAdr array
                $DB3->AddRecordFritzAdr($FritzAdrRecord);           // und schreibe ihn als Datensatz in die dBase-Datei
            }
        }
        $numupload = $DB3->CountRecordFritzAdr();
        IF ($numupload <> $numconv) {
            throw new \Exception('Upload to dBase File failed!');
        }
        $DB3->CloseFritzAdr();                                      // schließe die dBase-Datei
    return $numupload;
    }
}


function checkupdates ($xml_up, $config) {
    
    // values from config for recursiv vCard assembling
    $Fritzbox  = $config['fritzbox'];
    $Phonebook = $config['phonebook'];
    $Reply     = $config['reply'];
    
    // set instance    
    $vCard = new mk_vCard ();
    $email = new replymail ($Reply);
    
    // initialize return value
    $i = 0;
    
    // download phonebook from fritzbox
    $client = getSoapClient($Fritzbox['url'], $Fritzbox['user'], $Fritzbox['password']);
    $result = $client->GetPhonebook(new \SoapParam($Phonebook['id'],"NewPhonebookID"));
    $xml_down = simplexml_load_file($result['NewPhonebookURL']);
    
    // check if entries are not included in the intended upload
    foreach ($xml_down->phonebook->contact as $contact) {
        foreach ($contact->telephony->number as $number) {
            
            $querynumber = (string)$number;
            $name  = $contact->person->realName;
            $type  = (string)$number['type'];
            $email = (string)$contact->services->email;
            $vip   = $contact->category; 

            IF (strpos($querynumber, '**') === false) {                        // skip internal numbers
                $querystr = '//telephony[number = "' .  $querynumber . '"]';   // assemble search string
                IF (!$DataObjects = $xml_up->xpath($querystr)) {               // not found in upload = new entry! 
                    // assemble vCard from new entry
                    $newvCard = $vCard->createVCard ($querynumber, $name, $type, $email, $vip);  
                    $filename = $name . '.vcf';
                    // send new entry as vCard to designated reply adress
                    IF ($email->sendReply ($Phonebook['name'], $newvCard, $filename) == true) {    
                        $i++;
                    //    break 2;    // DEBUG - just send 1 email for tests purposes
                    }
                }
            }
        }
    }
    return $i;
}


// https://stackoverflow.com/questions/4778865/php-simplexml-addchild-with-another-simplexmlelement
function xml_adopt(SimpleXMLElement $to, SimpleXMLElement $from)
{
    $toDom = dom_import_simplexml($to);
    $fromDom = dom_import_simplexml($from);
    $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
}


function upload(string $xml, $config) {
    
    $fritzbox = $config['fritzbox'];
    
    $fritz = new Api($fritzbox['url'], $fritzbox['user'], $fritzbox['password']); //, 1);

    $formfields = array(
        'PhonebookId' => $config['phonebook']['id']
    );

    $filefields = array(
        'PhonebookImportFile' => array(
            'type' => 'text/xml',
            'filename' => 'updatepb.xml',
            'content' => $xml,
        )
    );

    $result = $fritz->doPostFile($formfields, $filefields); // send the command

    if (strpos($result, 'Das Telefonbuch der FRITZ!Box wurde wiederhergestellt') === false) {
        throw new \Exception('Upload failed');
    }
}