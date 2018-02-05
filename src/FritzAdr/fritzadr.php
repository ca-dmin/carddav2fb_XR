<?php

namespace Andig\FritzAdr;

/*
Die Klasse stellt Funktionen zur Verfügung, um die Adressdatenbank FRITZ!adr
von AVM zu manipulieren. FRITZ!adr ist ein Adress- und Telefonbuch für die
Programme FRITZ!fon, FRITZ!fax, FRITZ!data, FRITZ!com sowie fax4box:
 
- Erstellen einer neuen Datenbank:          CreateFritzAdr
- Öffnen einer vorhandenen Daenbank:        OpenFritzAdr
- einen Datensatz anfügen:                  AddRecordFritzAdr
- eien Datensatz löschen:                   DelRecordFritzAdr
- einen Datensatz als assoziatives array:   GetRecordFritzAdr
- Anzahl der Datensätze:                    CountRecordFritzAdr
- Datenbank reorganiseren (endg. löschen):  PackFritzAdr
- Datensatz überschreiben:                  ReplaceRecordFritzAdr
- Datenbank schließen:                      CloseFritzAdr

Die DB-Analyse mehrere FritzAdr.dbf Dateien hat überraschender-
weise beide Varianten gezeigt.
Letztlich funktioniert hat bei mir die 21er.  

Author: Volker Püschel
*/

class fritzadr
{

    const FritzAdrDefinition_19 = [
            ['BEZCHNG',   'C',  40],    //  1
            ['FIRMA',     'C',  40],    //  2
            ['NAME',      'C',  40],    //  3
            ['VORNAME',   'C',  40],    //  4
            ['ABTEILUNG', 'C',  40],
            ['STRASSE',   'C',  40],
            ['PLZ',       'C',  10],
            ['ORT',       'C',  40],
            ['KOMMENT',   'C',  80],
            ['TELEFON',   'C',  64],
            ['MOBILFON',  'C',  64],
            ['TELEFAX',   'C',  64],    // 12
            ['TRANSFER',  'C',  64],
            ['BENUTZER',  'C', 128],
            ['PASSWORT',  'C', 128],
            ['TRANSPROT', 'C',   1],
            ['NOTIZEN',   'C', 254],
            ['EMAIL',     'C', 254],
            ['HOMEPAGE',  'C', 254],     // 19
        ];
    const FritzAdrDefinition_21 = [
            ['BEZCHNG',   'C',  40],   // Feld 1
            ['FIRMA',     'C',  40],   // Feld 2
            ['NAME',      'C',  40],   // Feld 3
            ['VORNAME',   'C',  40],   // Feld 4
            ['ABTEILUNG', 'C',  40],
            ['STRASSE',   'C',  40],
            ['PLZ',       'C',  10],
            ['ORT',       'C',  40],
            ['KOMMENT',   'C',  80],
            ['TELEFON',   'C',  64],
            ['TELEFAX',   'C',  64],   // Feld 11
            ['TRANSFER',  'C',  64],
            ['TERMINAL',  'C',  64],
            ['BENUTZER',  'C', 128],
            ['PASSWORT',  'C', 128],
            ['TRANSPROT', 'C',   1],
            ['TERMMODE',  'C',  40],
            ['NOTIZEN',   'C', 254],
            ['MOBILFON',  'C',  64],
            ['EMAIL',     'C', 254],
            ['HOMEPAGE',  'C', 254]   // Feld 21
        ];
    const FritzAdrDefinition = self::FritzAdrDefinition_21;    // ggf. andere Definition wählen 
        
    private $DataBasePath = '',
            $DataBaseID,
            $DataBaseHeader;
            
    public $NumAttributes = 0;
    
    
    public function __construct() {
        $this->NumAttributes = count(self::FritzAdrDefinition);
    }
    
            
    public function CreateFritzAdr ($db_path = '') {
        IF (!empty ($db_path)) {
            IF (dbase_create($db_path, self::FritzAdrDefinition)) {
                $this->DataBasePath = $db_path;
                return true;
            }
            ELSE {
                ECHO 'Error: Can´t create dBase file '.$db_path;
                return false;
            }
                
        }
        ELSE {
            ECHO 'Error: Can´t create dBase file without a location!';
            return false;
            }
    }
        
    public function OpenFritzAdr ($db_path = '') {
        IF (!empty ($db_path)) {
            $this->DataBasePath = $db_path;
        }
        $this->DataBaseID = dbase_open ($this->DataBasePath, 2);
        return $this->DataBaseID;
    }

    public function GetHeaderFritzAdr () {
        $this->DataBaseHeader = dbase_get_header_info ($this->DataBaseID);
        return $this->DataBaseHeader;
        }
    
    public function AddRecordFritzAdr ($db_data) {
        return dbase_add_record ($this->DataBaseID, $db_data);
        }

    public function DelRecordFritzAdr ($RecordNum) {
        return dbase_delete_record ($this->DataBaseID, $RecordNum);
        }
    
    public function GetRecordFritzAdr ($RecordNum) {
        return dbase_get_record_with_names ($this->DataBaseID, $RecordNum);
        }
    
    public function CountRecordFritzAdr () {
        return dbase_numrecords ($this->DataBaseID);
        }
    
    public function PackFritzAdr () {
        return dbase_pack ($this->DataBaseID);
        }

    public function ReplaceRecordFritzAdr ($db_data, $rec_num) {
        return dbase_replace_record ($this->DataBaseID, $db_data, $rec_num);
        }
    
    public function CloseFritzAdr () {
        dbase_close ($this->DataBaseID);
        }
}

?>