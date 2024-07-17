<?php

namespace Drupal\std\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\rep\Utils;

class DeleteStudyController extends ControllerBase {

  /**
   *   Delete Study with given studyurl and redirect to current URL 
   */
  public function exec($studyuri, $currenturl) {
    if ($studyuri == NULL || $currenturl == NULL) {
      $response = new RedirectResponse(Url::fromRoute('rep.about')->toString());
      $response->send();
      return;
    }    

    $uri = base64_decode($studyuri);
    $url = base64_decode($currenturl);

    // DELETE ELEMENT
    $api = \Drupal::service('rep.api_connector');
    $api->studyDel($uri);
    \Drupal::messenger()->addMessage("Selected study has/have been deleted successfully.");      

    // RETURN TO CURRENT URL
    $response = new RedirectResponse($url);
    $response->send();
    return;
  }

}