<?php

/*
Adaptierter Clone der Converter.php von andig.
Eine Variante mit "class converter2fa extends Converter"
hat nicht funktioniert.
Diese Klasse stellt eine weitere Funktionen zur Verfügung,
um Fax-Nummern zu extrahieren und in einem simplen Array
mit 19 bzw. 21 Feldern* - passend zu FritzAdr - zu übergeben:
 
* Die DB-Analyse mehrere FritzAdr.dbf Dateien hat überraschender-
  weise beide Varianten gezeigt.
  Letztlich funktioniert hat bei mir die 21er.  
 
 
- Konvertierung in das FritzAdr-Format:            convertfa

Author: Volker Püschel
*/

namespace andig\FritzAdr;

class converter2fa

{
    
	public function convert($xml, $NumDataFields = 21) : array {
		
		$i = -1;
		$FritzAdrRecords = array ();
		
		foreach ($xml->phonebook->contact as $contact) {
			foreach ($contact->telephony->number as $number) {
				if ((string)$number['type'] == "fax_work") {
					$i++;
					
				    $name = $contact->person->realName;
					$faxnumber = (string)$number;
                    
					// FritzAdr (dBase) verwendet den DOS-Zeichensatz (Codepage 850)
					// htmlspecialchars macht aus '&' ein '&amp;' muss hier zurückgesetzt werden 
                    $name = str_replace( '&amp;', '&', iconv('UTF-8', 'CP850//TRANSLIT', $name));
								
					$FritzAdrRecords[$i] = array_fill (0,$NumDataFields, '');  // baue einen neuen leeren FRITZadr-Datensatz auf  
                    $FritzAdrRecords[$i][0] = $name;             // FullName in Feld 1 ('BEZCHNG')
                    $parts = explode (', ', $name);
                    IF (count($parts) !== 2) {                   // wenn der Name nicht mit Komma getrennt war ( kein Vor- & Nachname) 
                        $FritzAdrRecords[$i][1] = $name;         // FullName in Feld 2 ('FIRMA')
                    }
                    ELSE {
						$FritzAdrRecords[$i][2] = $parts[0];     // Nachname in Feld 3 ('NAME')
						$FritzAdrRecords[$i][3] = $parts[1];     // Vorname in Feld 4 ('VORNAME')
					}
					IF ($NumDataFields == 19) {
						$FritzAdrRecords[$i][11] = $faxnumber;   // FAX-Nummer in Feld 12 ('TELEFAX')
					}
					IF ($NumDataFields == 21) {
						$FritzAdrRecords[$i][10] = $faxnumber;   // FAX-Nummer in Feld 11 ('TELEFAX')
					}
				}
			}
		}
	return $FritzAdrRecords;	
    } 
}

?>