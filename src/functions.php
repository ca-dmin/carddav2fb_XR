<?php

namespace Andig;

use Andig\CardDav\Backend;
use Andig\Vcard\Parser;
use Andig\FritzBox\Api;
use Andig\FritzBox\Converter;
use Andig\FritzBox\Download;
use Andig\FritzAdr\converter2fa;
use Andig\FritzAdr\fritzadr;
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

function exportFA(array $cards, array $conversions,string $dblocation) {
    
    $converter2fa = new converter2fa($conversions);
    $DB3 = new fritzadr;                                            // Instanz von fritzadr erzeugen                                                // Achtung -> in config mit aufnehmen!
    $FritzAdrRecord = array ();
    
    IF ($DB3->CreateFritzAdr($dblocation)) {                        // Versuche die dBase-Datei zu erzeugen
        $converter2fa->NumDataFields = $DB3->NumAttributes;         // Anzahl der Datenfelder übergeben
        $DB3->OpenFritzAdr();                                       // wenn erfolgreich dann öffne die dBase-Datei
        foreach ($cards as $card) {    
            $converter2fa->convertfa($card);                        // extrahiere FAX-Daten in den public array der class
        }
        IF (count ($converter2fa->FritzAdrRecords)) {               // wenn der public array der class gefüllt ist

            foreach ($converter2fa->FritzAdrRecords as $key => $row) {    // Sortierung Aufsteigend nach Name und Nummer
                $BEZCHN[$key]  = $row[0];
                IF ($DB3->NumAttributes == 19) {
                    $TELEFAX[$key] = $row[11];
                }
                IF ($DB3->NumAttributes == 21) {
                    $TELEFAX[$key] = $row[10];
                }
            }
            array_multisort($BEZCHN, SORT_ASC, $TELEFAX, SORT_ASC, $converter2fa->FritzAdrRecords);

            foreach ($converter2fa->FritzAdrRecords as $FritzAdrRecord) {    // zerlege ihn in der FritzAdr array
                $DB3->AddRecordFritzAdr($FritzAdrRecord);             // und schreibe ihn als Datensatz in die dBase-Datei
            }
        }
        $DB3->CloseFritzAdr();                                        // schließe die dBase-Datei
        return $converter2fa->FritzAdrRecords;                        // mgl. Ausgabe für command convert
    }   
}


// https://stackoverflow.com/questions/4778865/php-simplexml-addchild-with-another-simplexmlelement
function xml_adopt(SimpleXMLElement $to, SimpleXMLElement $from)
{
    $toDom = dom_import_simplexml($to);
    $fromDom = dom_import_simplexml($from);
    $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
}


function upload(string $xml, string $url, string $user, string $password, int $phonebook=0)
{
    $fritz = new Api($url, $user, $password, 1);

    $formfields = array(
        'PhonebookId' => $phonebook
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
