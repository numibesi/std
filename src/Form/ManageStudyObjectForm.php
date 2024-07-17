<?php

namespace Drupal\std\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\rep\Utils;
use Drupal\rep\Vocabulary\VSTOI;
use Drupal\rep\Vocabulary\REPGUI;
use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ManageStudyObjectForm extends FormBase {

  protected $studyObjectCollection;

  public function getStudyObjectCollection() {
    return $this->studyObjectCollection;
  }

  public function setStudyObjectCollection($studyObjectCollection) {
    return $this->studyObjectCollection = $studyObjectCollection; 
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manage_study_object_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $studyobjectcollectionuri = NULL) {

    # SET CONTEXT
    $uri=base64_decode($studyobjectcollectionuri);

    # ROOT URL
    $root_url = \Drupal::request()->getBaseUrl();

    $uemail = \Drupal::currentUser()->getEmail();
    $uid = \Drupal::currentUser()->id();
    $user = \Drupal\user\Entity\User::load($uid);
    $username = $user->name->value;

    // RETRIEVE STUDY OBJECT COLLECTION BY URI
    $api = \Drupal::service('rep.api_connector');
    $studyObjectCollection = $api->parseObjectResponse($api->getUri($uri),'getUri');
    $this->setStudyObjectCollection($studyObjectCollection);
    
    // RETRIEVE STUDY OBJECT BY STUDY OBJECT COLLECTION
    $sos = $api->parseObjectResponse($api->studyObjectsBySOCWithPage($this->getStudyObjectCollection()->uri,12,0),'studyObjectsBySOCWithPage');

    //dpm($sos);

    # BUILD HEADER

    $header = [
      'so_uri' => t('URI'),
      'so_original_id' => t('Original Id'),
      'so_entity' => t('Entity Type'),
    ];

    # POPULATE DATA

    $output = array();
    $uriType = array();
    if ($sos != NULL) {
      foreach ($sos as $so) {

        $nsUri = Utils::namespaceUri($uri);

        $originalId = ' ';
        if ($so->originalId != NULL) {
          $originalId = $so->originalId;
        }

        $typeLabel = ' ';
        if ($so->typeLabel != NULL) {
          $typeLabel = $so->typeLabel;
        }

        $output[$so->uri] = [
          'so_uri' => t('<a href="'.$root_url.REPGUI::DESCRIBE_PAGE.base64_encode($uri).'">'.$nsUri.'</a>'),     
          'so_original_id' => $so->originalId,     
          'so_entity' => $so->typeLabel,     
        ];
      }
    }

    # PUT FORM TOGETHER

    $form['scope'] = [
      '#type' => 'item',
      '#title' => t('<h3>Study: <font color="DarkGreen">' . $this->getStudyObjectCollection()->study->label . '</font></h3>'),
    ];
    $form['subscope'] = [
      '#type' => 'item',
      '#title' => t('<h4>Study Object Collection: <font color="DarkGreen">' . $this->getStudyObjectCollection()->label . '</font></h4>'),
    ];
    $form['subtitle'] = [
      '#type' => 'item',
      '#title' => t('<h4>Managed by: <font color="DarkGreen">' . $username . ' (' . $uemail . ')</font></h4>'),
    ];
    if ($this->getStudyObjectCollection() != NULL) {
      $form['add_so'] = [
        '#type' => 'submit',
        '#value' => $this->t("Add Study Object"),
        '#name' => 'add_so',  
      ];
    }
    $form['edit_so'] = [
      '#type' => 'submit',
      '#value' => $this->t("Edit Study Object"),
      '#name' => 'edit_so',
    ];
    $form['delete_so'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Selected'),
      '#name' => 'delete_sos',    
      '#attributes' => ['onclick' => 'if(!confirm("Really Delete?")){return false;}'],
    ];
    $form['so_table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $output,
      '#js_select' => FALSE,
      '#empty' => t('No response options found'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back to Study Object Collections'),
      '#name' => 'back',
    ];
    $form['bottom_space'] = [
      '#type' => 'item',
      '#title' => t('<br><br><br>'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $submitted_values = $form_state->cleanValues()->getValues();
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];
  }

  /**
   * {@inheritdoc}
   */   
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // RETRIEVE TRIGGERING BUTTON
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];

    // SET USER ID AND PREVIOUS URL FOR TRACKING STORE URLS
    $uid = \Drupal::currentUser()->id();
    $previousUrl = \Drupal::request()->getRequestUri();

    // RETRIEVE SELECTED ROWS, IF ANY
    $selected_rows = $form_state->getValue('so_table');
    $rows = [];
    foreach ($selected_rows as $index => $selected) {
      if ($selected) {
        $rows[$index] = $index;
      }
    }

    // ADD SOC
    if ($button_name === 'add_so') {
      $url = Url::fromRoute('std.add_studyobject');
      $url->setRouteParameter('studyobjectcollectionuri', base64_encode($this->getStudyObjectCollection()->uri));
      $form_state->setRedirectUrl($url);
    }

    // EDIT SOC
    if ($button_name === 'edit_so') {
      if (sizeof($rows) < 1) {
        \Drupal::messenger()->addWarning(t("Select a Study Object to be edited."));      
      } else if (sizeof($rows) > 1) {
        \Drupal::messenger()->addWarning(t("Select one Study Object to be edited. No more than one Study Object can be edited at once."));      
      } else {
        $first = array_shift($rows);
        $url = Url::fromRoute('std.edit_studyobject');
        $url->setRouteParameter('studyobjecturi', base64_encode($first));
        $form_state->setRedirectUrl($url);  
      } 
      return;
    }

    // DELETE SOC
    if ($button_name === 'delete_so') {
      if (sizeof($rows) < 1) {
        \Drupal::messenger()->addMessage(t("Select Study Objects to be deleted."));
        return;      
      } else {
        $api = \Drupal::service('rep.api_connector');
        //dpm($rows);
        foreach($rows as $shortUri) {
          $uri = Utils::plainUri($shortUri);
          try {
            $api->studyObjectDel($uri);
          } catch(\Exception $e) {
            \Drupal::messenger()->addError(t("An error occurred while deleting a Study Object: ".$e->getMessage()));
            $url = Url::fromRoute('std.manage_studyobjectcollection', ['studyuri' => base64_encode($this->getStudyObjectCollection()->study->uri)]);
            $form_state->setRedirectUrl($url);
            return;
          }    
        }
        \Drupal::messenger()->addMessage(t("SOC(s) has been deleted successfully."));
        $url = Url::fromRoute('std.manage_studyobjectcollection');
        $url->setRouteParameter('studyuri', base64_encode($this->getStudyObjectCollection()->study->uri));
        $form_state->setRedirectUrl($url);  
        return;
      } 
    }

    // BACK TO MAIN PAGE
    if ($button_name === 'back') {
      $url = Url::fromRoute('std.manage_studyobjectcollection');
      $url->setRouteParameter('studyuri', base64_encode($this->getStudyObjectCollection()->study->uri));
      $form_state->setRedirectUrl($url);  
      return;
    }  
  }
  
}