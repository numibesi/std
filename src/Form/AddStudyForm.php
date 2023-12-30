<?php

namespace Drupal\std\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rep\Utils;
use Drupal\rep\Vocabulary\HASCO;

class AddStudyForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_study_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['study_short_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Short Name'),
    ];
    $form['study_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Long Name'),
    ];
    $form['study_description'] = [
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
      if(strlen($form_state->getValue('study_short_name')) < 1) {
        $form_state->setErrorByName('study_short_name', $this->t('Please enter a valid short name for the Study'));
      }
      if(strlen($form_state->getValue('study_name')) < 1) {
        $form_state->setErrorByName('study_name', $this->t('Please enter a valid name for the Study'));
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
      $form_state->setRedirectUrl(Utils::selectBackUrl('study'));
      return;
    } 

    $useremail = \Drupal::currentUser()->getEmail();

    $newStudyUri = Utils::uriGen('study');
    $studyJSON = '{"uri":"'. $newStudyUri .'",'.
        '"typeUri":"'.HASCO::STUDY.'",'.
        '"hascoTypeUri":"'.HASCO::STUDY.'",'.
        '"label":"'.$form_state->getValue('study_short_name').'",'.
        '"title":"'.$form_state->getValue('study_name').'",'.
        '"comment":"'.$form_state->getValue('study_description').'",'.
        '"hasSIRManagerEmail":"'.$useremail.'"}';

    try {
      $api = \Drupal::service('rep.api_connector');
      $message = $api->parseObjectResponse($api->studyAdd($studyJSON),'studyAdd');
      if ($message != null) {
        \Drupal::messenger()->addMessage(t("Study has been added successfully."));
      }
      $form_state->setRedirectUrl(Utils::selectBackUrl('study'));
      return;

    } catch(\Exception $e) {
      \Drupal::messenger()->addMessage(t("An error occurred while adding a study: ".$e->getMessage()));
      $form_state->setRedirectUrl(Utils::selectBackUrl('study'));
      return;
    }

  }

}