<?php
// src/metadatamanipulators/AddCdmItemInfo.php

namespace mik\metadatamanipulators;
use GuzzleHttp\Client;
use \Monolog\Logger;

/**
 * AddCdmItemInfo - Adds the raw JSON metadata for an item from CONTENTdm
 * to an <extension> element in the MODS document. This manipulator is
 * probably specific to Simon Fraser University Library's use case although
 * it my serve as an example of a metadta manipulator that adds content to
 * MODS XML documents.
 *
 * Note that this manipulator doesn't add the <extension> fragment, it
 * only populates it with data from an external source. The mappings file
 * must contain a row that adds the following element to your MODS:
 * '<extension><cdmiteminfo></cdmiteminfo></extension>', e.g.,
 * null1,<extension><cdmiteminfo></cdmiteminfo></extension>.
 *
 *
 * This metadata manipulator takes no configuration parameters.
 */
class AddCdmItemInfo extends MetadataManipulator
{
    /**
     * @var string $record_key - the unique identifier for the metadata
     *    record being manipulated.
     */
    private $record_key;

    /**
     * Create a new metadata manipulator Instance.
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
     *  @param string $input The XML fragment to be manipulated. We are only
     *     interested in the <extension><cdmiteminfo> fragment added in the
     *     MIK mappings file.
     *
     * @return string
     *     One of the manipulated XML fragment, the original input XML if the
     *     input is not the fragment we are interested in, or an empty string,
     *     which as the effect of removing the empty <extension><cdmiteminfo>
     *     fragement from our MODS (if there was an error, for example, we don't
     *     want empty extension elements in our MODS documents).
     */
    public function manipulate($input)
    {
        $dom = new \DomDocument();
        $dom->loadxml($input, LIBXML_NSCLEAN);

        // Test to see if the current fragment is <extension><cdmiteminfo>.
        $xpath = new \DOMXPath($dom);
        $cdmiteminfos = $xpath->query("//extension/cdmiteminfo");

        // There should only be one <cdmiteminfo> fragment in the incoming
        // XML. If there is 0 or more than 1, return the original.
        if ($cdmiteminfos->length === 1) {
          $cdmiteminfo = $cdmiteminfos->item(0);
          // Check to see if the <cdmiteminfo> element has a 'source'
          // attribute, and if so, just return the fragment.
          if ($cdmiteminfo->hasAttribute('source')) {
              return $input;
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
          } catch (Exception $e) {
              $this->log->addInfo("AddCdmItemInfo",
                  array('HTTP request error' => $e->getMessage()));
              return '';
          }
          $item_info = $response->getBody();

          // CONTENTdm returns a 200 OK with its error messages, so we can't rely
          // on catching all 'errors' with the above try/catch block. Instead, we
          // check to see if the string 'dmcreated' (one of the metadata fields
          // returned for every object) is in the response body. If it's not,
          // assume CONTENTdm has returned an error of some sort, log it, and
          // return.
          if (!preg_match('/dmcreated/', $item_info)) {
              $this->log->addInfo("AddCdmItemInfo", array('CONTENTdm internal error' => $item_info));
              return '';
          }          
          // If the CONTENTdm metadata contains the CDATA end delimiter, log and return.
          if (preg_match('/\]\]>/', $item_info)) {
              $message = "CONTENTdm metadata for object " . $this->settings['METADATA_PARSER']['alias'] .
                  '/' . $this->record_key . ' contains the CDATA end delimiter ]]>'; 
              $this->log->addInfo("AddCdmItemInfo", array('CONTENTdm metadata warning' => $message));
              return '';
          }

          // If we've made it this far, add the output of dmGetItemInfo to <cdmiteminfo> as
          // CDATA and return the modified XML fragment.
          $cdata = $dom->createCDATASection($item_info);
          $cdmiteminfo->appendChild($cdata);
          return $dom->saveXML($dom->documentElement);
        }
        else {
            // If current fragment is not <extension><cdmiteminfo>, return it
            // unmodified.
            return $input;
        }
    }

}
