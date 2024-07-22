<?php

namespace Drupal\std\Entity;

use Drupal\rep\Vocabulary\REPGUI;
use Drupal\rep\Utils;

class StudyObjectCollection {

  public static function generateHeader() {

    return $header = [
      'soc_uri' => t('URI'),
      'soc_study' => t('Study'),
      'soc_reference' => t('Reference'),
      'soc_label' => t('Label'),
      'soc_grounding_label' => t('Grounding Label'),
      'soc_num_objects' => t('# Objects'),
    ];
  
  }

  public static function generateOutput($list) {

    //dpm($list);

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
      $study = ' ';
      if ($element->isMemberOf != NULL && $element->isMemberOf->label != NULL) {
        $study = $element->isMemberOf->label;
      }      
      $root_url = \Drupal::request()->getBaseUrl();
      $encodedUri = rawurlencode(rawurlencode($element->uri));
      $socreference = " ";
      if ($element->virtualColumn != NULL && $element->virtualColumn->socreference != NULL) {
        $socreference = $element->virtualColumn->socreference;
      }
      $groundingLabel = " ";
      if ($element->virtualColumn != NULL && $element->virtualColumn->groundingLabel != NULL) {
        $groundingLabel = $element->virtualColumn->groundingLabel;
      }
      $output[$element->uri] = [
          'soc_uri' => t('<a href="'.$root_url.REPGUI::DESCRIBE_PAGE.
                        base64_encode($element->uri).'">'.Utils::namespaceUri($element->uri).'</a>'),         
          'soc_study' => $study,     
          'soc_reference' => $socreference,     
          'soc_label' => $element->label,     
          'soc_grounding_label' => $groundingLabel,
          'soc_num_objects' => $element->numOfObjects,
      ];
    }
    return $output;

  }

}