<?php

namespace Drupal\std\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rep\Utils;
use Drupal\rep\Vocabulary\HASCO;

class EditStudyForm extends FormBase {

  protected $studyUri;

  protected $study;

  public function getStudyUri() {
    return $this->studyUri;
  }

  public function setStudyUri($uri) {
    return $this->studyUri = $uri; 
  }

  public function getStudy() {
    return $this->study;
  }

  public function setStudy($sem) {
    return $this->study = $sem; 
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_study_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $studyuri = NULL) {
    $uri=$studyuri ?? 'default';
    $uri_decode=base64_decode($uri);
    $this->setStudyUri($uri_decode);

    $api = \Drupal::service('rep.api_connector');
    $svar = $api->parseObjectResponse($api->getUri($this->getStudyUri()),'getUri');
    if ($svar == NULL) {
      \Drupal::messenger()->addMessage(t("Failed to retrieve Study."));
      $form_state->setRedirectUrl(Utils::selectBackUrl('study'));
    } else {
      $this->setStudy($svar);
    }

    $form['study_short_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Short Name'),
      '#default_value' => $this->getStudy()->label,
    ];
    $form['study_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Long Name'),
      '#default_value' => $this->getStudy()->title,
    ];
    $form['study_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->getStudy()->comment,
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
      if(strlen($form_state->getValue('study_short_name')) < 1) {
        $form_state->setErrorByName('study_short_name', $this->t('Please enter a short name for the Study'));
      }
      if(strlen($form_state->getValue('study_name')) < 1) {
        $form_state->setErrorByName('study_name', $this->t('Please enter a name for the Study'));
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
      $form_state->setRedirectUrl(Utils::selectBackUrl('study'));
      return;
    } 

    $useremail = \Drupal::currentUser()->getEmail();

    $studyJson = '{"uri":"'. $this->getStudy()->uri .'",'.
      '"typeUri":"'.HASCO::STUDY.'",'.
      '"hascoTypeUri":"'.HASCO::STUDY.'",'.
      '"label":"'.$form_state->getValue('study_short_name').'",'.
      '"title":"'.$form_state->getValue('study_name').'",'.
      '"comment":"'.$form_state->getValue('study_description').'",'.
      '"hasSIRManagerEmail":"'.$useremail.'"}';

    try {
      // UPDATE BY DELETING AND CREATING
      $api = \Drupal::service('rep.api_connector');
      $api->studyDel($this->getStudy()->uri);
      $api->studyAdd($studyJson);
    
      \Drupal::messenger()->addMessage(t("Study has been updated successfully."));
      $form_state->setRedirectUrl(Utils::selectBackUrl('study'));

    } catch(\Exception $e) {
      \Drupal::messenger()->addMessage(t("An error occurred while updating Study: ".$e->getMessage()));
      $form_state->setRedirectUrl(Utils::selectBackUrl('study'));
    }

  }

}