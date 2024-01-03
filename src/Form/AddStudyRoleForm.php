<?php

namespace Drupal\std\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rep\Utils;
use Drupal\rep\Vocabulary\HASCO;

class AddStudyRoleForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_study_role_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['studyrole_study'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Study'),
      '#autocomplete_route_name' => 'std.study_autocomplete',
    ];
    $form['studyrole_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
    ];
    $form['studyrole_description'] = [
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
    $submitted_values = $form_state->cleanValues()->getValues();
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

    $newStudyRoleUri = Utils::uriGen('studyrole');
    $studyJSON = '{"uri":"'. $newStudyRoleUri .'",'.
        '"typeUri":"'.HASCO::STUDY_ROLE.'",'.
        '"hascoTypeUri":"'.HASCO::STUDY_ROLE.'",'.
        '"label":"'.$form_state->getValue('studyrole_name').'",'.
        '"isMemberOf":"'.$studyUri.'",'.
        '"comment":"'.$form_state->getValue('studyrole_description').'",'.
        '"hasSIRManagerEmail":"'.$useremail.'"}';

    try {
      $api = \Drupal::service('rep.api_connector');
      $message = $api->parseObjectResponse($api->studyRoleAdd($studyJSON),'studyRoleAdd');
      if ($message != null) {
        \Drupal::messenger()->addMessage(t("Study Role has been added successfully."));
      }
      $form_state->setRedirectUrl(Utils::selectBackUrl('studyrole'));
      return;

    } catch(\Exception $e) {
      \Drupal::messenger()->addMessage(t("An error occurred while adding a study role: ".$e->getMessage()));
      $form_state->setRedirectUrl(Utils::selectBackUrl('studyrole'));
      return;
    }

  }

}