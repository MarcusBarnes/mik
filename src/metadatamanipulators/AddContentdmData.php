<?php
// src/metadatamanipulators/AddContentdmData.php

namespace mik\metadatamanipulators;

use GuzzleHttp\Client;
use \Monolog\Logger;

/**
 * AddCdmItemInfo - Adds several types of data about objects being
 * migrated from CONTENTdm, specifically the output of the following
 * CONTENTdm web API requests for the current object: dmGetItemInfo
 * (JSON format), dmGetCompoundObjectInfo (if applicable) (XML format),
 * and GetParent (if applicable) (XML format).
 *
 * Note that this manipulator doesn't add the <extension> fragment, it
 * only populates it with data from CONTENTdm. The mappings file
 * must contain a row that adds the following element to your MODS:
 * '<extension><CONTENTdmData></CONTENTdmData></extension>', e.g.,
 * null5,<extension><CONTENTdmData></CONTENTdmData></extension>.
 *
 * This metadata manipulator takes no configuration parameters.
 */
class AddContentdmData extends MetadataManipulator
{
    /**
     * @var string $record_key - the unique identifier for the metadata
     *    record being manipulated.
     */
    private $record_key;

    /**
     * Create a new metadata manipulator Instance.
     */
    public function __construct($settings, $paramsArray, $record_key)
    {
        parent::__construct($settings, $paramsArray, $record_key);
        $this->record_key = $record_key;
        $this->alias = $this->settings['METADATA_PARSER']['alias'];

        // Default Mac PHP setups may use Apple's Secure Transport
        // rather than OpenSSL, causing issues with CA verification.
        // Allow configuration override of CA verification at users own risk.
        if (isset($this->settings['SYSTEM']['verify_ca'])) {
            if ($this->settings['SYSTEM']['verify_ca'] == false) {
                $this->verifyCA = false;
            }
        } else {
            $this->verifyCA = true;
        }

        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_manipulator_log'];
        $this->log = new \Monolog\Logger('config');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler(
            $this->pathToLog,
            Logger::INFO
        );
        $this->log->pushHandler($this->logStreamHandler);
    }

    /**
     * General manipulate wrapper method.
     *
     *  @param string $input The XML fragment to be manipulated. We are only
     *     interested in the <extension><CONTENTdmData> fragment added in the
     *     MIK mappings file.
     *
     * @return string
     *     One of the manipulated XML fragment, the original input XML if the
     *     input is not the fragment we are interested in, or an empty string,
     *     which as the effect of removing the empty <extension><CONTENTdmData>
     *     fragement from our MODS (if there was an error, for example, we don't
     *     want empty extension elements in our MODS documents).
     */
    public function manipulate($input)
    {
        $dom = new \DomDocument();
        $dom->loadxml($input, LIBXML_NSCLEAN);

        // Test to see if the current fragment is <extension><CONTENTdmData>.
        $xpath = new \DOMXPath($dom);
        $cdmdatas = $xpath->query("//extension/CONTENTdmData");

        // There should only be one <CONTENTdmData> fragment in the incoming
        // XML. If there is 0 or more than 1, return the original.
        if ($cdmdatas->length === 1) {
            $contentdmdata = $cdmdatas->item(0);

            $alias = $dom->createElement('alias', $this->alias);
            $contentdmdata->appendChild($alias);
            $pointer = $dom->createElement('pointer', $this->record_key);
            $contentdmdata->appendChild($pointer);

            $timestamp = date("Y-m-d H:i:s");

          // Add the <dmGetItemInfo> element.
            $dmGetItemInfo = $dom->createElement('dmGetItemInfo');
            $now = $dom->createAttribute('timestamp');
            $now->value = $timestamp;
            $dmGetItemInfo->appendChild($now);
            $mimetype = $dom->createAttribute('mimetype');
            $mimetype->value = 'application/json';
            $dmGetItemInfo->appendChild($mimetype);
            $source_url = $this->settings['METADATA_PARSER']['ws_url'] .
              'dmGetItemInfo/' . $this->alias . '/' . $this->record_key . '/json';
            $source = $dom->createAttribute('source');
            $source->value = $source_url;
            $dmGetItemInfo->appendChild($source);
            $item_info = $this->getCdmData($this->alias, $this->record_key, 'dmGetItemInfo', 'json');
          // CONTENTdm returns a 200 OK with its error messages, so we can't rely
          // on catching all 'errors' with the above try/catch block. Instead, we
          // check to see if the string 'dmcreated' (one of the metadata fields
          // returned for every object) is in the response body. If it's not,
          // assume CONTENTdm has returned an error of some sort, log it, and
          // return.
            if (!preg_match('/dmcreated/', $item_info)) {
                $this->log->addInfo("AddContentdmData", array('CONTENTdm internal error' => $item_info));
                return '';
            }
          // If the CONTENTdm metadata contains the CDATA end delimiter, log and return.
            if (preg_match('/\]\]>/', $item_info)) {
                $message = "CONTENTdm metadata for object " . $this->settings['METADATA_PARSER']['alias'] .
                  '/' . $this->record_key . ' contains the CDATA end delimiter ]]>';
                $this->log->addInfo("AddContentdmData", array('CONTENTdm metadata warning' => $message));
                return '';
            }
          // If we've made it this far, add the output of dmGetItemInfo to <CONTENTdmData> as
          // CDATA and return the modified XML fragment.
            if (strlen($item_info)) {
                $cdata = $dom->createCDATASection($item_info);
                $dmGetItemInfo->appendChild($cdata);
                $contentdmdata->appendChild($dmGetItemInfo);
            }

          // Add the <dmCompoundObjectInfo> element.
            $dmGetCompoundObjectInfo = $dom->createElement('dmGetCompoundObjectInfo');
            $now = $dom->createAttribute('timestamp');
            $now->value = $timestamp;
            $dmGetCompoundObjectInfo->appendChild($now);
            $mimetype = $dom->createAttribute('mimetype');
            $mimetype->value = 'text/xml';
            $dmGetCompoundObjectInfo->appendChild($mimetype);
            $source = $dom->createAttribute('source');
            $source_url = $this->settings['METADATA_PARSER']['ws_url'] .
              'dmGetCompoundObjectInfo/' . $this->alias . '/' . $this->record_key . '/xml';
            $source->value = $source_url;
            $dmGetCompoundObjectInfo->appendChild($source);
            $compound_object_info = $this->getCdmData(
                $this->alias,
                $this->record_key,
                'dmGetCompoundObjectInfo',
                'xml'
            );
          // Only add the <dmGetCompoundObjectInfo> element if the object is compound.
            if (strlen($compound_object_info) && preg_match('/<cpd>/', $compound_object_info)) {
                $cdata = $dom->createCDATASection($compound_object_info);
                $dmGetCompoundObjectInfo->appendChild($cdata);
                $contentdmdata->appendChild($dmGetCompoundObjectInfo);
            }

          // Add the <GetParent> element.
            $GetParent = $dom->createElement('GetParent');
            $now = $dom->createAttribute('timestamp');
            $now->value = $timestamp;
            $GetParent->appendChild($now);
            $mimetype = $dom->createAttribute('mimetype');
            $mimetype->value = 'text/xml';
            $GetParent->appendChild($mimetype);
            $source = $dom->createAttribute('source');
            $source_url = $this->settings['METADATA_PARSER']['ws_url'] .
              'GetParent/' . $this->alias . '/' . $this->record_key . '/xml';
            $source->value = $source_url;
            $GetParent->appendChild($source);
            $parent_info = $this->getCdmData(
                $this->alias,
                $this->record_key,
                'GetParent',
                'xml'
            );
          // Only add the <GetParent> element if the object has a parent
          // pointer of not -1.
            if (strlen($parent_info) && !preg_match('/\-1/', $parent_info)) {
                $cdata = $dom->createCDATASection($parent_info);
                $GetParent->appendChild($cdata);
                $contentdmdata->appendChild($GetParent);
            }

            return $dom->saveXML($dom->documentElement);
        } else {
            // If current fragment is not <extension><CONTENTdmData>, return it
            // unmodified.
            return $input;
        }
    }

    /**
     * Fetch the output of the CONTENTdm web API for the current object.
     *
     * @param string $alias
     *   The CONTENTdm alias for the current object.
     * @param string $pointer
     *   The CONTENTdm pointer for the current object.
     * @param string $cdm_api_function
     *   The name of the CONTENTdm API function.
     * @param string $format
     *   Either 'json' or 'xml'.
     *
     * @return stting
     *   The output of the CONTENTdm API request, in the format specified.
     */
    private function getCdmData($alias, $pointer, $cdm_api_function, $format)
    {
          // Use Guzzle to fetch the output of the call to dmGetItemInfo
          // for the current object.
          $url = $this->settings['METADATA_PARSER']['ws_url'] .
              $cdm_api_function . '/' . $this->alias . '/' . $pointer . '/' . $format;
          $client = new Client();
        try {
            $response = $client->get($url, [$this->verifyCA]);
        } catch (Exception $e) {
            $this->log->addInfo(
                "AddContentdmData",
                array('HTTP request error' => $e->getMessage())
            );
            return '';
        }
          $output = $response->getBody();
          return $output;
    }
}
