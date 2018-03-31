<?php

namespace Andig;
//namespace BlackSenator;
  
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class RunCommand extends Command
{
    use ConfigTrait;
    
    
    protected function configure()
    {
        $this->setName('run')
            ->setDescription('Download, convert and upload - all in one')
            ->addOption('image', 'i', InputOption::VALUE_NONE, 'download images');

        $this->addConfig();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        $this->loadConfig($input);
        
        // compare timestamp of CardDAV against last update on Fritz!Box
        $lastupdate = 0;
        $latestmod  = 0;
        $pb_down = downloadPhonebook ($this->config);                    // is needed for forecedupload > 1 as well  
        
        IF ($this->config['phonebook']['forcedupload'] < 2) {
            error_log("Determine the last change of the Fritz!Box phonebook");
            /// date_default_timezone_set('CET');
            $lastupdate = $pb_down->phonebook->timestamp;                // get timestamp from phonebook
        
            error_log("Determine the last change(s) on the CardDAV server(s)");
            foreach ($this->config['server'] as $server) {               // determine the youngest modification date
                $backend = backendProvider($server);
                $dummy = getlastmodification ($backend);
                IF ($dummy > $latestmod) {
                    $latestmod = $dummy;
                }
            }
        }   
        IF ($lastupdate > $latestmod) {
            error_log("Your Fritz!Box phonebook is more up to date than your CardDAV server contacts");
        }
        ELSE {
            $vcards = array();
            $xcards = array();
            if ($input->getOption('image')) {
                $substitutes = $this->config['conversions']['substitutes'] ?? '';
            }
            ELSE {
                $substitutes = [];
            }
            foreach ($this->config['server'] as $server) {
                $progress = new ProgressBar($output);
                error_log("Downloading vCard(s) from account ".$server['user']);
                $backend = backendProvider($server);
                $progress->start();
                $xcards = download($backend, $substitutes, function () use ($progress) {
                    $progress->advance();
                });
                $progress->finish();
                $vcards = array_merge($vcards, $xcards);
                error_log(sprintf("\nDownloaded %d vCard(s)", count($vcards)));
            }

            // parse and convert
            error_log("Parsing vCards");
            $cards = parse($vcards);

            // conversion
            $filters = $this->config['filters'];
                    
            $filtered = filter($cards, $filters);

            error_log(sprintf("Converted and filtered %d vCard(s)", count($filtered)));

            // images
            if ($input->getOption('image')) {
                error_log("Detaching and storing image(s)");
                $new_files = storeImages($filtered, $this->config['script']['cache']);
                $pictures = count($new_files);
                error_log(sprintf("Temporarily stored %d image file(s)", $pictures));
                If ($pictures > 0) {
                    $pictures = uploadImages ($new_files, $this->config['fritzbox']);
                    error_log(sprintf("Uploaded %d image file(s)", $pictures));
                }
            }
            ELSE {
                unset($this->config['phonebook']['imagepath']);
            }
        
            // fritzbox format
            $xml = export($filtered, $this->config);
            
            // FRITZadr dBase Ausgabe
            IF (isset($this->config['fritzbox']['fritzadr'])) {
                $nc = exportfa($xml, $this->config['fritzbox']['fritzadr']);
                error_log(sprintf("Converted %d FAX number(s) in FritzAdr.dbf", $nc));
            }
     
            // check for newer contacts in phonebook
            IF ($this->config['phonebook']['forcedupload'] < 3) {
                error_log("Checking Fritz!Box for newer entries");
                $i = checkupdates ($pb_down, $xml, $this->config);
                IF ($i > 0) {
                    error_log(sprintf("Saved %d new contact(s) from Fritz!Box phonebook", $i));
                }
            }

            $xml->asXML($this->config['script']['cache'].'/phonebook.xml');
            
            // upload
            error_log("Uploading");
            $xmlStr = $xml->asXML();
            IF (upload($xmlStr, $this->config) === true) {;
                error_log("Successful uploaded new Fritz!Box phonebook");
            }
        }
    }
}
