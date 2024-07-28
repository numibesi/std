<?php

namespace Drupal\std\Entity;

use Drupal\rep\Vocabulary\REPGUI;
use Drupal\rep\Utils;

class VirtualColumn {

  public static function generateHeader() {

    return $header = [
      'element_uri' => t('URI'),
      'element_study' => t('Study'),
      'element_soc_reference' => t('SOC Reference'),
    ];
  
  }

  public static function generateOutput($list) {

    // ROOT URL
    $root_url = \Drupal::request()->getBaseUrl();

    $output = array();
    foreach ($list as $element) {
      $uri = ' ';
      if ($element->uri != NULL) {
        $uri = $element->uri;
      }
      $uri = Utils::namespaceUri($uri);
      $study = ' ';
      if ($element->isMemberOf != NULL && $element->isMemberOf->label != NULL) {
        $study = $element->isMemberOf->label;
      }
      $soc = ' ';
      if ($element->socreference != NULL) {
        $soc = $element->socreference;
      }
      $root_url = \Drupal::request()->getBaseUrl();
      $encodedUri = rawurlencode(rawurlencode($element->uri));
      $output[$element->uri] = [
        'element_uri' => t('<a href="'.$root_url.REPGUI::DESCRIBE_PAGE.base64_encode($uri).'">'.$uri.'</a>'),     
        'element_study' => t($study),     
        'element_soc_reference' => t($soc),     
      ];
    }
    return $output;

  }

}