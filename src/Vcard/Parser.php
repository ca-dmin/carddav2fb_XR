<?php

namespace Andig\Vcard;


/**
 * VCard PHP Class to parse .vcard files.
 *
 * This class is heavily based on the Zendvcard project (seemingly abandoned),
 * which is licensed under the Apache 2.0 license.
 * More information can be found at https://code.google.com/archive/p/zendvcard/
 *
 * Extended from https://github.com/jeroendesloovere/vcard
 * MIT License
 */

 
class Parser implements \IteratorAggregate

{
    /**
     * The raw VCard content.
    *
     * @var string
     */
    protected $content;

    /**
     * The VCard data objects.
     *
     * @var array
     */
    protected $vcardObjects;

    /**
     * Helper function to parse a file directly.
     *
     * @param string $filename
     * @return self
     */
    public static function parseFromFile($filename)
    {
        if (file_exists($filename) && is_readable($filename)) {
            return new self(file_get_contents($filename));
        } else {
            throw new \RuntimeException(sprintf("File %s is not readable, or doesn't exist.", $filename));
        }
    }

    public function __construct($content)
    {
        $this->content = $content;
        $this->vcardObjects = [];
        $this->parse();
    }

    /**
     * IteratorAggregate
     */
    public function getIterator()
    {
        foreach ($this->vcardObjects as $vcard) {
            yield $vcard;
        }
    }

    /**
     * Fetch all the imported VCards.
     *
     * @return array
     *    A list of VCard card data objects.
     */
    public function getCards()
    {
        return $this->vcardObjects;
    }

    /**
     * Fetch the imported VCard at the specified index.
     *
     * @throws OutOfBoundsException
     *
     * @param int $i
     *
     * @return stdClass
     *    The card data object.
     */
    public function getCardAtIndex($i)
    {
        if (isset($this->vcardObjects[$i])) {
            return $this->vcardObjects[$i];
        }
        throw new \OutOfBoundsException();
    }

    /**
     * Start the parsing process.
     *
     * This method will populate the data object.
     */
    protected function parse()
    {
        // Normalize new lines.
        $this->content = str_replace(["\r\n", "\r"], "\n", $this->content);

        // RFC2425 5.8.1. Line delimiting and folding
        // Unfolding is accomplished by regarding CRLF immediately followed by
        // a white space character (namely HTAB ASCII decimal 9 or. SPACE ASCII
        // decimal 32) as equivalent to no characters at all (i.e., the CRLF
        // and single white space character are removed).
        $this->content = preg_replace("/\n(?:[ \t])/", "", $this->content);
        $lines = explode("\n", $this->content);

        // Parse the VCard, line by line.
        foreach ($lines as $line) {
            $line = trim($line);

            if (strtoupper($line) == "BEGIN:VCARD") {
                $cardData = new \stdClass();
            } elseif (strtoupper($line) == "END:VCARD") {
                $this->vcardObjects[] = $cardData;
            } elseif (!empty($line)) {
                // Strip grouping information. We don't use the group names. We
                // simply use a list for entries that have multiple values.
                // As per RFC, group names are alphanumerical, and end with a
                // period (.).
                $line = preg_replace('/^\w+\./', '', $line);

                $type = '';
                $value = '';
                @list($type, $value) = explode(':', $line, 2);

                $types = explode(';', $type);
                $element = strtoupper($types[0]);

                array_shift($types);

                // Normalize types. A type can either be a type-param directly,
                // or can be prefixed with "type=". E.g.: "INTERNET" or
                // "type=INTERNET".
                if (!empty($types)) {
                    $types = array_map(function ($type) {
                        return preg_replace('/^type=/i', '', $type);
                    }, $types);
                }

                $i = 0;
                $rawValue = false;
                foreach ($types as $type) {
                    if (preg_match('/base64/', strtolower($type))) {
                        $value = base64_decode($value);
                        unset($types[$i]);
                        $rawValue = true;
                    } elseif (preg_match('/encoding=b/', strtolower($type))) {
                        $value = base64_decode($value);
                        unset($types[$i]);
                        $rawValue = true;
                    } elseif (preg_match('/quoted-printable/', strtolower($type))) {
                        $value = quoted_printable_decode($value);
                        unset($types[$i]);
                        $rawValue = true;
                    } elseif (strpos(strtolower($type), 'charset=') === 0) {
                        try {
                            $value = mb_convert_encoding($value, "UTF-8", substr($type, 8));
                        } catch (\Exception $e) {
                        }
                        unset($types[$i]);
                    }
                    $i++;
                }

                switch (strtoupper($element)) {
                    case 'ADR':
                        if (!isset($cardData->address)) {
                            $cardData->address = [];
                        }
                        $key = !empty($types) ? implode(';', $types) : 'WORK;POSTAL';
                        $cardData->address[$key][] = $this->parseAddress($value);
                        break;
                    case 'AGENT':          // up to version 3.0 
                        $cardData->agent = $value;
                        break;
                    case 'ANNIVERSARY':    // from version 4.0
                        $cardData->anniversary = $value;
                        break;
                    case 'BDAY':
                        $cardData->birthday = $this->parseBirthday($value);
                        break;
                    case 'CALADRURI':      // from version 4.0
                        $cardData->caladruri = $value;
                        break;
                    case 'CALURI':         // from version 4.0
                        $cardData->caluri = $value;
                        break;
                    case 'CATEGORIES':
                        $cardData->categories = array_map('trim', explode(',', $value));
                        break;
                    case 'CLASS':          // only version 3.0
                        $cardData->class = $value;
                        break;
                    case 'CLIENTPIDMAP':   // from version 4.0
                        $cardData->clientpidmap = $value;
                        break;
                    case 'EMAIL':
                        if (!isset($cardData->email)) {
                            $cardData->email = [];
                        }
                        $key = !empty($types) ? implode(';', $types) : 'default';
                        $cardData->email[$key][] = $value;
                        break;
                    case 'FBURL':          // from version 4.0
                        $cardData->fburl = $value;
                        break;
                    case 'FN':             // mandatory from version 3.0! Formatted string with the full name
                        $cardData->fullname = $value;
                        break;
                    case 'GENDER':         // from version 4.0
                        $cardData->gender = $value;
                        break;
                    case 'GEO':
                        $cardData->geo = $value;
                        break;
                    case 'IMPP':           // from version 3.0
                        $cardData->impp = $value;
                        break;
                    case 'KEY':
                        $cardData->key = $value;
                        break;
                    case 'KIND':           // from version 4.0
                        $cardData->kind = $value;
                        break;
                    case 'LABEL':          // up to version 3.0
                        $cardData->label = $this->unescape($value);;
                        break;
                    case 'LANG':           // from version 4.0
                        $cardData->lang = $value;
                        break;
                    case 'LOGO':
                        if ($rawValue) {
                            $cardData->rawLogo = $value;
                            $cardData->logoData = implode(';',$types);
                        } else {
                            $cardData->logo = $value;
                        }
                        break;
                    case 'MAILER':         // up to version 3.0
                        $cardData->mailer = $value;
                        break;
                    case 'MEMBER':         // from version 4.0
                        $cardData->member = $value;
                        break;
                    case 'N':              // mandtory up to version 3.0! Textured components of the personal name
                        foreach ($this->parseName($value) as $key => $val) {
                            $cardData->{$key} = $val;
                        }
                        break;
                    case 'NAME':           // nur version 3.0! Textual representation of the 'SOURCE' property
                        $cardData->name = $value;
                        break;
                    case 'NICKNAME':       // from version 3.0
                        $cardData->nickname = $value;
                        break;
                    case 'NOTE':
                        $cardData->note = $this->unescape($value);
                        break;
                    case 'ORG':
                        $cardData->organization = $value;
                        break;
                    case 'PHOTO':
                        if ($rawValue) {
                            $cardData->rawPhoto = $value;
                            $cardData->photoData = implode(';',$types);
                        } else {
                            $cardData->photo = $value;
                        }
                        break;
                    case 'PRODID':         // from version 3.0! Identifier for the product that created the vCard object
                        $cardData->prodid = $value;
                        break;
                    case 'PROFILE':        // up to version 3.0! Specifies that the vCard is a vCard: "PROFILE: VCARD"
                        $cardData->profile = $value;
                        break;
                    case 'RELATED':        // from version 4.0! Other unit to which the person has connection
                        $cardData->related = $value;
                        break;
                    case 'REV':            // timestamp of the last update of the vCard
                        $cardData->revision = $value;
                        break;
                    case 'ROLE':
                        $cardData->role = $value;
                        break;
                    case 'SORT-STRING':        // up to version 3.0
                        $cardData->sortstring = $value;
                        break;
                    case 'SOUND':
                        if ($rawValue) {
                            $cardData->rawSound = $value;
                            $cardData->soundData = implode(';',$types);
                        } else {
                            $cardData->sound = $value;
                        }
                        break;
                    case 'SOURCE':
                        $cardData->source = $value;
                        break;
                    case 'TEL':
                        if (!isset($cardData->phone)) {
                            $cardData->phone = [];
                        }
                        $key = !empty($types) ? implode(';', $types) : 'default';
                        $cardData->phone[$key][] = $value;
                        break;
                    case 'TITLE':
                        $cardData->title = $value;
                        break;
                    case 'TZ':
                        $cardData->tz = $value;
                        break;
                    case 'UID':
                        $cardData->uid = $value;
                        break;
                    case 'URL':
                        if (!isset($cardData->url)) {
                            $cardData->url = [];
                        }
                        $key = !empty($types) ? implode(';', $types) : 'default';
                        $cardData->url[$key][] = $value;
                        break;
                    case 'VERSION':        // mandatoryd! From version 3.0 has to follow this to the BEGIN property
                        $cardData->version = $value;
                        break;
                    case 'XML':            // from version 4.0
                        $cardData->xml = $value;
                        break;
                    /* extended attributes references to RFC 6474 and RFC 6715 */
                    case 'BIRTHPLACE':
                        $cardData->birthplace = $value;
                        break;
                    case 'DEATHDATE':
                        $cardData->deathdate = $value;
                        break;
                    case 'DEATHPLACE':
                        $cardData->deathplace = $value;
                        break;
                    case 'EXPERTISE':
                        if (!isset($cardData->expertise)) {
                            $cardData->expertise = [];
                        }
                        $key = !empty($types) ? implode(';', $types) : 'default';
                        $cardData->expertise[$key][] = $value;
                        break;
                    case 'HOBBY':
                        if (!isset($cardData->hobby)) {
                            $cardData->hobby = [];
                        }
                        $key = !empty($types) ? implode(';', $types) : 'default';
                        $cardData->hobby[$key][] = $value;
                        break;
                    case 'INTEREST':
                        if (!isset($cardData->interest)) {
                            $cardData->interest = [];
                        }
                        $key = !empty($types) ? implode(';', $types) : 'default';
                        $cardData->interest[$key][] = $value;
                        break;
                    case 'ORG-DIRECTORY':
                        $cardData->orgdirectory = $value;
                        break;
                    /* iCloud extended attributes */
                    case 'X-MAIDENNAME':
                        $cardData->xmaidenname = $value;
                        break;
                    case 'X-ADDRESSBOOKSERVER-KIND':
                        $cardData->xabskind = $value;
                        break;
                    case 'X-ADDRESSBOOKSERVER-MEMBER':
                        if (!isset($cardData->xabsmember)) {
                            $cardData->xabsmember = [];
                        }
                        $cardData->xabsmember[] = preg_replace('/^urn:uuid:/', '', $value);
                        break;
                    /* User extended attributes for FritzBox*/
                    case 'X-FB_QUICKDIAL':
                        if ($value >= 0 && $value <= 99) {
                            $cardData->xquickdial =  $value;
                        }
                        break;
                    case 'X-FB_VANITY':
                        if (ctype_alpha($value)) {
                            $cardData->xvanity = substr (strtoupper($value), 0, 8);
                        }
                        break;
                }
            }
        }
    }

    protected function parseName($value)
    {
        @list(
            $lastname,
            $firstname,
            $additional,
            $prefix,
            $suffix
        ) = explode(';', $value);
        return (object) [
            'lastname' => $lastname,
            'firstname' => $firstname,
            'additional' => $additional,
            'prefix' => $prefix,
            'suffix' => $suffix
        ];
    }

    protected function parseBirthday($value)
    {
        return new \DateTime($value);
    }

    protected function parseAddress($value)
    {
        @list(
            $name,
            $extended,
            $street,
            $city,
            $region,
            $zip,
            $country,
        ) = explode(';', $value);
        return (object) [
            'name' => $name,
            'extended' => $extended,
            'street' => $street,
            'city' => $city,
            'region' => $region,
            'zip' => $zip,
            'country' => $country,
        ];
    }

    /**
     * Unescape newline characters according to RFC2425 section 5.8.4.
     * This function will replace escaped line breaks with PHP_EOL.
     *
     * @link http://tools.ietf.org/html/rfc2425#section-5.8.4
     * @param  string $text
     * @return string
     */
    protected function unescape($text)
    {
        return str_replace("\\n", PHP_EOL, $text);
    }
    
}
