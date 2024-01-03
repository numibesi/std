<?php

namespace Drupal\std\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rep\Utils;
use Drupal\rep\Vocabulary\HASCO;

class EditStudyRoleForm extends FormBase {

  protected $studyRoleUri;

  protected $studyRole;

  public function getStudyRoleUri() {
    return $this->studyRoleUri;
  }

  public function setStudyRoleUri($uri) {
    return $this->studyRoleUri = $uri; 
  }

  public function getStudyRole() {
    return $this->studyRole;
  }

  public function setStudyRole($role) {
    return $this->studyRole = $role; 
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_studyrole_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $studyroleuri = NULL) {
    $uri=$studyroleuri ?? 'default';
    $uri_decode=base64_decode($uri);
    $this->setStudyRoleUri($uri_decode);

    $api = \Drupal::service('rep.api_connector');
    $studyRole = $api->parseObjectResponse($api->getUri($this->getStudyRoleUri()),'getUri');
    if ($studyRole == NULL) {
      \Drupal::messenger()->addMessage(t("Failed to retrieve Study Role."));
      $form_state->setRedirectUrl(Utils::selectBackUrl('studyrole'));
    } else {
      $this->setStudyRole($studyRole);
    }
    
    $study = ' ';
    if ($this->getStudyRole()->study != NULL &&
        $this->getStudyRole()->study->uri != NULL &&
        $this->getStudyRole()->study->label != NULL) {
      $study = Utils::fieldToAutocomplete($this->getStudyRole()->study->uri,$this->getStudyRole()->study->label);
    }

    $form['studyrole_study'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Study'),
      '#default_value' => $study,
    ];
    $form['studyrole_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Long Name'),
      '#default_value' => $this->getStudyRole()->label,
    ];
    $form['studyrole_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->getStudyRole()->comment,
    ];
    $form['update_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
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
      if(strlen($form_state->getValue('studyrole_study')) < 1) {
        $form_state->setErrorByName('studyrole_study', $this->t('Please enter a valid study for the Study Role'));
      }
      if(strlen($form_state->getValue('studyrole_name')) < 1) {
        $form_state->setErrorByName('studyrole_name', $this->t('Please enter a valid name for the Study Role'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];

    if ($button_name === 'back') {
      $form_state->setRedirectUrl(Utils::selectBackUrl('studyrole'));
      return;
    } 

    $useremail = \Drupal::currentUser()->getEmail();

    $studyUri = 'null';
    if ($form_state->getValue('studyrole_study') != NULL && $form_state->getValue('studyrole_study') != '') {
      $studyUri = Utils::uriFromAutocomplete($form_state->getValue('studyrole_study'));
    } 

    $studyRoleJSON = '{"uri":"'. $this->getStudyRole()->uri .'",'.
        '"typeUri":"'.HASCO::STUDY_ROLE.'",'.
        '"hascoTypeUri":"'.HASCO::STUDY_ROLE.'",'.
        '"label":"'.$form_state->getValue('studyrole_name').'",'.
        '"isMemberOf":"'.$studyUri.'",'.
        '"comment":"'.$form_state->getValue('studyrole_description').'",'.
        '"hasSIRManagerEmail":"'.$useremail.'"}';

    try {
      // UPDATE BY DELETING AND CREATING
      $api = \Drupal::service('rep.api_connector');
      $api->studyRoleDel($this->getStudyRole()->uri);
      $api->studyRoleAdd($studyRoleJSON);
    
      \Drupal::messenger()->addMessage(t("Study Role has been updated successfully."));
      $form_state->setRedirectUrl(Utils::selectBackUrl('studyrole'));

    } catch(\Exception $e) {
      \Drupal::messenger()->addMessage(t("An error occurred while updating Study Role: ".$e->getMessage()));
      $form_state->setRedirectUrl(Utils::selectBackUrl('studyrole'));
    }

  }

}