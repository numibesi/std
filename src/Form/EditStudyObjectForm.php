<?php

namespace Drupal\std\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\rep\Utils;
use Drupal\rep\Vocabulary\HASCO;

class EditStudyObjectForm extends FormBase {

  protected $study;

  protected $studyObject;

  protected $entity;

  public function getStudy() {
    return $this->study;
  }
  public function setStudy($study) {
    return $this->study = $study; 
  }

  public function getStudyObject() {
    return $this->studyObject;
  }
  public function setStudyObject($studyObject) {
    return $this->studyObject = $studyObject; 
  }

  public function getEntity() {
    return $this->entity;
  }
  public function setEntity($entity) {
    return $this->entity = $entity; 
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_studyobject_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $studyobjecturi = NULL) {

    # SET CONTEXT
    $uri=base64_decode($studyobjecturi);

    $uemail = \Drupal::currentUser()->getEmail();
    $uid = \Drupal::currentUser()->id();
    $user = \Drupal\user\Entity\User::load($uid);
    $username = $user->name->value;

    // RETRIEVE STUDY OBJECT BY URI
    $api = \Drupal::service('rep.api_connector');
    $studyObject = $api->parseObjectResponse($api->getUri($uri),'getUri');
    $this->setStudyObject($studyObject);

    // RETRIEVE STUDY BY URI
    if ($studyObject != NULL &&
        $studyObject->studyObjectCollection != NULL && 
        $studyObject->studyObjectCollection->isMemberOf != NULL) {
      $study = $api->parseObjectResponse($api->getUri($studyObject->studyObjectCollection->isMemberOf),'getUri');
      $this->setStudy($study);
    }

    // RETRIEVE ENTITY BY URI
    $entityContent = ' '; 
    if ($studyObject != NULL &&
        $studyObject->typeUri != NULL) {
          $entity = $api->parseObjectResponse($api->getUri($studyObject->typeUri),'getUri');
          $this->setEntity($entity);
          $entityContent = Utils::fieldToAutocomplete(
            $this->getEntity()->uri,
            $this->getEntity()->label);
        }

    $studyContent = ' ';
    if ($this->getStudy() != NULL &&
        $this->getStudy()->uri != NULL &&
        $this->getStudy()->label != NULL) {
      $studyContent = Utils::fieldToAutocomplete(
        $this->getStudy()->uri,
        $this->getStudy()->label);
    }

    $socContent = ' ';
    if ($this->getStudyObject() != NULL &&
        $this->getStudyObject()->studyObjectCollection != NULL &&
        $this->getStudyObject()->studyObjectCollection->uri != NULL &&
        $this->getStudyObject()->studyObjectCollection->label != NULL) {
      $socContent = Utils::fieldToAutocomplete(
        $this->getStudyObject()->studyObjectCollection->uri,
        $this->getStudyObject()->studyObjectCollection->label);
    }

    $form['studyobject_study'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Study'),
      '#default_value' => $studyContent,
      '#disabled' => TRUE,
    ];
    $form['studyobject_soc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Study Object Collection (SOC)'),
      '#default_value' => $socContent,
      '#disabled' => TRUE,
    ];
    $form['studyobject_original_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Original ID (required)'),
      '#default_value' => $this->getStudyObject()->originalId,
    ];
    $form['studyobject_entity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity (required)'),
      '#autocomplete_route_name' => 'sem.semanticvariable_entity_autocomplete',
      '#default_value' => $entityContent,
    ];
    $form['studyobject_domainscope_object'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Domain Scope's Object (if required)"),
    ];
    $form['studyobject_timescope_object'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Time Scope's Object (if required)"),
    ];
    $form['studyobject_spacescope_object'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Space Scope's Object (if required)"),
    ];
    $form['studyobject_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
    ];
    $form['save_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#name' => 'save',
    ];
    $form['cancel_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#name' => 'back',
    ];
    $form['bottom_space'] = [
      '#type' => 'item',
      '#title' => t('<br><br>'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $submitted_values = $form_state->cleanValues()->getValues();
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];

    if ($button_name === 'save') {
      if(strlen($form_state->getValue('studyobject_original_id')) < 1) {
        $form_state->setErrorByName('studyobject_original_id', $this->t('Please enter an original ID for the Study Object'));
      }
      if(strlen($form_state->getValue('studyobject_entity')) < 1) {
        $form_state->setErrorByName('studyobject_entity', $this->t('Please enter a valid entity for the Study Object'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submitted_values = $form_state->cleanValues()->getValues();
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];

    if ($button_name === 'back') {
      $url = Url::fromRoute('std.manage_studyobject', ['studyobjectcollectionuri' => base64_encode($this->getStudyObject()->uri)]);
      $form_state->setRedirectUrl($url);
      return;
    } 

    $useremail = \Drupal::currentUser()->getEmail();

    $entityUri = NULL;
    if ($form_state->getValue('studyobject_entity') != NULL && $form_state->getValue('studyobject_entity') != '') {
      $entityUri = Utils::uriFromAutocomplete($form_state->getValue('studyobject_entity'));
    } 

    $socUri = NULL;
    if ($this->getStudyObject() != NULL &&
        $this->getStudyObject()->studyObjectCollection != NULL &&
        $this->getStudyObject()->studyObjectCollection->uri != NULL) {
        $socUri = $this->getStudyObject()->studyObjectCollection->uri; 
    } 

    $studyObjectJSON = '{"uri":"'. $this->getStudyObject()->uri .'",'.
        '"typeUri":"'.$entityUri.'",'.
        '"hascoTypeUri":"'.HASCO::STUDY_OBJECT.'",'.
        '"isMemberOf":"'.$socUri.'",'.
        '"label":"'.$form_state->getValue('studyobject_original_id').'",'.
        '"originalId":"'.$form_state->getValue('studyobject_original_id').'",'.
        '"comment":"'.$form_state->getValue('studyobject_description').'",'.
        '"hasSIRManagerEmail":"'.$useremail.'"}';

    try {
      $api = \Drupal::service('rep.api_connector');
      $api->studyObjectDel($this->getStudyObject()->uri);
      $message = $api->parseObjectResponse($api->studyObjectAdd($studyObjectJSON),'studyObjectAdd');
      if ($message != null) {
        \Drupal::messenger()->addMessage(t("Study Object has been added successfully."));
      } else {
        \Drupal::messenger()->addError(t("Study Object failed to be added."));
      }
      $url = Url::fromRoute('std.manage_studyobject', ['studyobjectcollectionuri' => base64_encode($this->getStudyObjectCollection()->uri)]);
      $form_state->setRedirectUrl($url);
      return;
    } catch(\Exception $e) {
      \Drupal::messenger()->addError(t("An error occurred while adding a Study Object: ".$e->getMessage()));
      $url = Url::fromRoute('std.manage_studyobject', ['studyobjectcollectionuri' => base64_encode($this->getStudyObjectCollection()->uri)]);
      $form_state->setRedirectUrl($url);
      return;
    }
  }

}