<?php

namespace Drupal\std\Entity;

use Drupal\rep\Vocabulary\REPGUI;
use Drupal\rep\Utils;

class StudyObjectCollection {

  public static function generateHeader() {

    return $header = [
      'soc_uri' => t('URI'),
      'soc_reference' => t('Reference'),
      'soc_label' => t('Label'),
      'soc_grounding_label' => t('Grounding Label'),
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
      $label = ' ';
      if ($element->label != NULL) {
        $label = $element->label;
      }
      $root_url = \Drupal::request()->getBaseUrl();
      $encodedUri = rawurlencode(rawurlencode($element->uri));
      $output[$element->uri] = [
          'soc_uri' => t('<a href="'.$root_url.REPGUI::DESCRIBE_PAGE.
                        base64_encode($element->uri).'">'.Utils::namespaceUri($element->uri).'</a>'),         
          'soc_reference' => $element->socreference,     
          'soc_label' => $element->label,     
          'soc_grounding_label' => $element->groundingLabel,
      ];
    }
    return $output;

  }

}