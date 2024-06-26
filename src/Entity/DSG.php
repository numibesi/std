<?php

namespace Drupal\std\Entity;

use Drupal\rep\Vocabulary\HASCO;
use Drupal\rep\Vocabulary\REPGUI;
use Drupal\rep\Entity\DataFile;
use Drupal\rep\Constant;
use Drupal\rep\Utils;

class DSG {

  protected $preservedDSG;

  protected $preservedDF;

  // Constructor
  public function __construct() {
  }

  public function getPreservedDSG() {
    return $this->preservedDSG;
  }

  public function setPreservedDSG($dsg) {
    if ($this->preservedDSG == NULL) {
      $this->preservedDSG = new DSG();
    }
    $this->preservedDSG->uri = $dsg->uri;
    $this->preservedDSG->label = $dsg->label;
    $this->preservedDSG->hasDataFile = $dsg->hasDataFile;
    $this->preservedDSG->comment = $dsg->comment;          
    $this->preservedDSG->hasSIRManagerEmail = $dsg->hasSIRManagerEmail;
  }

  public function getPreservedDF() {
    return $this->preservedDF;
  }

  public function setPreservedDF($df) {
    if ($this->preservedDF == NULL) {
      $this->preservedDF = new DataFile();
    }
    $this->preservedDF->uri = $df->uri;
    $this->preservedDF->label = $df->label;
    $this->preservedDF->filename = $df->filename;
    $this->preservedDF->id = $df->id;
    $this->preservedDF->fileStatus = $df->fileStatus;
    $this->preservedDF->hasSIRManagerEmail = $df->hasSIRManagerEmail;
  }

  public static function generateHeader() {

    return $header = [
      'element_uri' => t('URI'),
      'element_name' => t('Name'),
      'element_filename' => t('FileName'),
      'element_status' => t('Status'),
      'element_log' => t('Log'),
      'element_download' => t('Download'),
    ];
  
  }

  public static function generateOutput($list) {

    //dpm($list);

    // ROOT URL
    $root_url = \Drupal::request()->getBaseUrl();

    $output = array();
    if ($list == NULL) {
      return $output;
    }

    foreach ($list as $element) {
      $uri = ' ';
      if ($element->uri != NULL) {
        $uri = $element->uri;
      }
      $uri = Utils::namespaceUri($uri);
      $label = ' ';
      if ($element->label != NULL) {
        $label = $element->label;
      }
      $name = ' ';
      if ($element->label != NULL && $element->label != '') {
        $name = $element->label;
      }
      $filename = ' ';
      $filestatus = ' ';
      $log = ' ';
      $download = ' ';
      $root_url = \Drupal::request()->getBaseUrl();
      if ($element->dataFile != NULL) {

        // RETRIEVE DATAFILE BY URI
        //$api = \Drupal::service('rep.api_connector');
        //$dataFile = $api->parseObjectResponse($api->getUri($element->hasDataFile),'getUri');

        if ($element->dataFile->filename != NULL && 
            $element->dataFile->filename != '') {
          $filename = $element->dataFile->filename;
        }
        if ($element->dataFile->fileStatus != NULL && 
            $element->dataFile->fileStatus != '') {
          if ($element->dataFile->fileStatus == Constant::FILE_STATUS_UNPROCESSED) {
            $filestatus = '<b><font style="color:#ff0000;">'.Constant::FILE_STATUS_UNPROCESSED.'</font></b>';
          } else if ($element->dataFile->fileStatus == Constant::FILE_STATUS_PROCESSED) {
            $filestatus = '<b><font style="color:#008000;">'.Constant::FILE_STATUS_PROCESSED.'</font></b>';
          } else if ($element->dataFile->fileStatus == Constant::FILE_STATUS_WORKING) {
            $filestatus = '<b><font style="color:#ffA500;">'.Constant::FILE_STATUS_WORKING.'</font></b>';
          } else {
            $filestatus = ' ';
          }
        }
        if (isset($element->dataFile->log) && $element->dataFile->log != NULL) {
          $link = $root_url.REPGUI::DATAFILE_LOG.base64_encode($element->dataFile->uri);
          $log = '<a href="' . $link . '" class="use-ajax btn btn-primary btn-sm" '.
                 'data-dialog-type="modal" '.
                 'data-dialog-options=\'{"width": 700}\' role="button">Read</a>';
  
          //$log = '<a href="'.$link.'" class="btn btn-primary btn-sm" role="button">Read</a>';
        } 
        $downloadLink = '';
        if ($element->dataFile->id != NULL && $element->dataFile->id != '') {
          $file_entity = \Drupal\file\Entity\File::load($element->dataFile->id);
          if ($file_entity != NULL) {
            $downloadLink = $root_url.REPGUI::DATAFILE_DOWNLOAD.base64_encode($element->dataFile->uri);
            $download = '<a href="'.$downloadLink.'" class="btn btn-primary btn-sm" role="button" disabled>Get It</a>';
          } 
        }  
      }
      $encodedUri = rawurlencode(rawurlencode($element->uri));
      $output[$element->uri] = [
        'element_uri' => t('<a href="'.$root_url.REPGUI::DESCRIBE_PAGE.base64_encode($uri).'">'.$uri.'</a>'),     
        'element_name' => t($label),    
        'element_filename' => $filename,
        'element_status' => t($filestatus),
        'element_log' => t($log),
        'element_download' => t($download),
      ];
    }
    return $output;
  }

  public function savePreservedDSG() {

    if ($this->getPreservedDSG() == NULL || $this->getPreservedDF() == NULL) {
      return FALSE;
    }

    try {
      $datafileJSON = '{"uri":"'. $this->getPreservedDF()->uri .'",'.
          '"typeUri":"'.HASCO::DATAFILE.'",'.
          '"hascoTypeUri":"'.HASCO::DATAFILE.'",'.
          '"label":"'.$this->getPreservedDF()->label.'",'.
          '"filename":"'.$this->getPreservedDF()->filename.'",'.          
          '"id":"'.$this->getPreservedDF()->id.'",'.          
          '"fileStatus":"'.Constant::FILE_STATUS_UNPROCESSED.'",'.          
          '"hasSIRManagerEmail":"'.$this->getPreservedDF()->hasSIRManagerEmail.'"}';

      $kgrJSON = '{"uri":"'. $this->getPreservedDSG()->uri .'",'.
          '"typeUri":"'.HASCO::DSG.'",'.
          '"hascoTypeUri":"'.HASCO::DSG.'",'.
          '"label":"'.$this->getPreservedDSG()->label.'",'.
          '"hasDataFile":"'.$this->getPreservedDSG()->hasDataFile.'",'.          
          '"comment":"'.$this->getPreservedDSG()->comment.'",'.
          '"hasSIRManagerEmail":"'.$this->getPreservedDSG()->hasSIRManagerEmail.'"}';

      $api = \Drupal::service('rep.api_connector');

      // ADD DATAFILE
      $msg1 = NULL;
      $msg2 = NULL;
      $dfRaw = $api->datafileAdd($datafileJSON);
      if ($dfRaw != NULL) {
        $msg1 = $api->parseObjectResponse($dfRaw,'datafileAdd');

        // ADD DSG
        $dsgRaw = $api->dsgAdd($dsgJSON);
        if ($dsg != NULL) {
          $msg2 = $api->parseObjectResponse($dsgRaw,'dsgAdd');
        } 
      }

      if ($msg1 != NULL && $msg2 != NULL) {
        return TRUE;      
      } else {
        return FALSE;
      }

    } catch(\Exception $e) {}
  } 

}