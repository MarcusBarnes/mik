<?php
// src/metadatamanipulators/AddCdmItemInfo.php

namespace mik\metadatamanipulators;
use GuzzleHttp\Client;
use \Monolog\Logger;

/**
 * AddCdmItemInfo - Adds the raw (JSON) metadata for an item from CONTENTdm
 * to an <extension> element in the MODS document. This manipulator is
 * probably specific to Simon Fraser University Library's use case.
 *
 * Note that your mappings file must contain a row to  add the following
 * element to your MODS: '<extension><cdmiteminfo></cdmiteminfo></extension>'.
 */
class AddCdmItemInfo extends MetadataManipulator
{
    /**
     * @var string $record_key - the unique identifier for the metadata
     *    record being manipulated.
     */
    private $record_key;

    /**
     * Create a new Metadata Instance
     */
    public function __construct($settings = null, $paramsArray, $record_key)
    {
        parent::__construct($settings, $paramsArray, $record_key);
        $this->record_key = $record_key;

        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_manipulator_log'];
        $this->log = new \Monolog\Logger('config');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler($this->pathToLog,
            Logger::INFO);
        $this->log->pushHandler($this->logStreamHandler);
    }

    /**
     * General manipulate wrapper method.
     *
     *  @param string $input XML fragment to be manipulated. We are only
     *     interested in <extension><cdmiteminfo> fragment added in the
     *     mappings file.
     *
     * @return string
     *     Manipulated XML fragment, or the original input XML if the
     *     input is not the fragment we are interested in. 
     */
    public function manipulate($input)
    {
        $dom = new \DomDocument();
        $dom->loadxml($input, LIBXML_NSCLEAN);

        // Test to see if the current fragment is <extension><cdmiteminfo>.
        $xpath = new \DOMXPath($dom);
        $cdmiteminfos = $xpath->query("//extension/cdmiteminfo");

        if ($cdmiteminfos->length === 1) {
          $cdmiteminfo = $cdmiteminfos->item(0);
          // Check to see if the <cdmiteminfo> element has a 'source'
          // attribute, and if so, just return the fragment.
          if ($cdmiteminfo->hasAttribute('source')) {
              // We return an empty string in order to remove the
              // empty <extension><cdmiteminfo> fragement from our MODS.
              return '';
          }
          // Add the 'source' attribute to the first cdmiteminfo element.
          $timestamp = date("Y-m-d H:i:s");
          $now = $dom->createAttribute('source');
          $now->value = 'Exported from CONTENTdm ' . $timestamp;
          $cdmiteminfo->appendChild($now);

          // Use Guzzle to fetch the output of the call to dmGetItemInfo
          // for the current object.
          $item_info_url = $this->settings['METADATA_PARSER']['ws_url'] .
              'dmGetItemInfo/' . $this->settings['METADATA_PARSER']['alias'] .
              '/' . $this->record_key . '/json';
          $client = new Client();
          try {
              $response = $client->get($item_info_url);
          } catch (TransferException $te) {
              $this->log->addInfo("AddCdmItemInfo",
                  array('Guzzle error' => $te->getMessage()));
              return '';
          }
          $item_info = $response->getBody();
          // If the CONTENTdm metadata contains the CDATA end delimiter, log and return.
          if (preg_match('/\]\]>/', $item_info)) {
              $message = "CONTENTdm metadata for object " . $this->settings['METADATA_PARSER']['alias'] .
                  '/' . $this->record_key . ' contains the CDATA end delimiter ]]>'; 
              $this->log->addInfo("AddCdmItemInfo", array('CONTENTdm metadata warning' => $message));
              return '';
          }
          // Add the output of dmGetItemInfo to <cdmiteminfo> as CDATA.
          $cdata = $dom->createCDATASection($item_info);
          $cdmiteminfo->appendChild($cdata);
          return $dom->saveXML($dom->documentElement);
        }
        else {
            // If current fragment is not <extension><cdmiteminfo>, return it.
            return $input;
        }
    }

}
