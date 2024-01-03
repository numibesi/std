<?php

namespace Drupal\std\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rep\Utils;
use Drupal\rep\Vocabulary\HASCO;

class AddVirtualColumnForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_virtualcolumn_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['virtualcolumn_soc_reference'] = [
      '#type' => 'textfield',
      '#title' => $this->t("SOC Reference (must starts with '??')"),
    ];
    $form['virtualcolumn_study'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Study (required)'),
      '#autocomplete_route_name' => 'std.study_autocomplete',

    ];
    $form['virtualcolumn_groundinglabel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Grounding Label (optional)'),
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
      if(strlen($form_state->getValue('virtualcolumn_soc_reference')) < 1) {
        $form_state->setErrorByName('virtualcolumn_soc_reference', $this->t('Please enter a valid SOC Reference for Virtual Column'));
      }
      if(strlen($form_state->getValue('virtualcolumn_study')) < 1) {
        $form_state->setErrorByName('virtualcolumn_study', $this->t('Please enter a valid study for the Virtual Column'));
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
      $form_state->setRedirectUrl(Utils::selectBackUrl('virtualcolumn'));
      return;
    } 

    $useremail = \Drupal::currentUser()->getEmail();

    $studyUri = NULL;
    if ($form_state->getValue('virtualcolumn_study') != NULL && $form_state->getValue('virtualcolumn_study') != '') {
      $studyUri = Utils::uriFromAutocomplete($form_state->getValue('virtualcolumn_study'));
    } 

    $newVirtualColumnUri = Utils::uriGen('virtualcolumn');
    $virtualColumnJSON = '{"uri":"'. $newVirtualColumnUri .'",'.
        '"superUri":"'.HASCO::VIRTUAL_COLUMN.'",'.
        '"hascoTypeUri":"'.HASCO::VIRTUAL_COLUMN.'",'.
        '"label":"'.$form_state->getValue('virtualcolumn_soc_reference').'",'.
        '"socreference":"'.$form_state->getValue('virtualcolumn_soc_reference').'",'.
        '"isMemberOf":"' . $studyUri . '",' . 
        '"groundingLabel":"'.$form_state->getValue('virtualcolumn_groundinglabel').'",'.
        '"hasSIRManagerEmail":"'.$useremail.'"}';

    try {
      $api = \Drupal::service('rep.api_connector');
      $message = $api->parseObjectResponse($api->virtualColumnAdd($virtualColumnJSON),'virtualColumnAdd');
      if ($message != null) {
        \Drupal::messenger()->addMessage(t("Virtual column has been added successfully."));
      }
      $form_state->setRedirectUrl(Utils::selectBackUrl('virtualcolumn'));
      return;

    } catch(\Exception $e) {
      \Drupal::messenger()->addMessage(t("An error occurred while adding a virtual column: ".$e->getMessage()));
      $form_state->setRedirectUrl(Utils::selectBackUrl('virtualcolumn'));
      return;
    }

  }

}