<?php

namespace Drupal\std\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rep\Utils;
use Drupal\rep\Vocabulary\HASCO;

class EditVirtualColumnForm extends FormBase {

  protected $virtualColumnUri;

  protected $virtualColumn;

  public function getVirtualColumnUri() {
    return $this->virtualColumnUri;
  }

  public function setVirtualColumnUri($uri) {
    return $this->virtualColumnUri = $uri; 
  }

  public function getVirtualColumn() {
    return $this->virtualColumn;
  }

  public function setVirtualColumn($vc) {
    return $this->virtualColumn = $vc; 
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_virtualcolumn_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $virtualcolumnuri = NULL) {
    $uri=$virtualcolumnuri ?? 'default';
    $uri_decode=base64_decode($uri);
    $this->setVirtualColumnUri($uri_decode);

    $api = \Drupal::service('rep.api_connector');
    $virtualColumn = $api->parseObjectResponse($api->getUri($this->getVirtualColumnUri()),'getUri');
    if ($virtualColumn == NULL) {
      \Drupal::messenger()->addMessage(t("Failed to retrieve Virtual Column."));
      $form_state->setRedirectUrl(Utils::selectBackUrl('virtualcolumn'));
    } else {
      $this->setVirtualColumn($virtualColumn);
      //dpm($virtualColumn);
    }
    
    $study = ' ';
    if ($this->getVirtualColumn()->study != NULL &&
        $this->getVirtualColumn()->study->uri != NULL &&
        $this->getVirtualColumn()->study->label != NULL) {
      $study = Utils::fieldToAutocomplete($this->getVirtualColumn()->study->uri,$this->getVirtualColumn()->study->label);
    }

    $form['virtualcolumn_study'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Study (required)'),
      '#default_value' => $study,
      '#autocomplete_route_name' => 'std.study_autocomplete',
    ];
    $form['virtualcolumn_soc_reference'] = [
      '#type' => 'textfield',
      '#title' => $this->t("SOC Reference (must starts with '??')"),
      '#default_value' => $this->getVirtualColumn()->label,
    ];
    $form['virtualcolumn_groundinglabel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Grounding Label (optional)'),
      '#default_value' => $this->getVirtualColumn()->groundingLabel,
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
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];

    if ($button_name === 'back') {
      $form_state->setRedirectUrl(Utils::selectBackUrl('virtualcolumn'));
      return;
    } 

    $useremail = \Drupal::currentUser()->getEmail();

    $studyUri = 'null';
    if ($form_state->getValue('virtualcolumn_study') != NULL && $form_state->getValue('virtualcolumn_study') != '') {
      $studyUri = Utils::uriFromAutocomplete($form_state->getValue('virtualcolumn_study'));
    } 

    $virtualColumnJSON = '{"uri":"'. $this->getVirtualColumn()->uri .'",'.
      '"superUri":"'.HASCO::VIRTUAL_COLUMN.'",'.
      '"hascoTypeUri":"'.HASCO::VIRTUAL_COLUMN.'",'.
      '"label":"'.$form_state->getValue('virtualcolumn_soc_reference').'",'.
      '"socreference":"'.$form_state->getValue('virtualcolumn_soc_reference').'",'.
      '"isMemberOf":"' . $studyUri . '",' . 
      '"groundingLabel":"'.$form_state->getValue('virtualcolumn_groundinglabel').'",'.
      '"hasSIRManagerEmail":"'.$useremail.'"}';


    try {
      // UPDATE BY DELETING AND CREATING
      $api = \Drupal::service('rep.api_connector');
      $api->virtualColumnDel($this->getVirtualColumn()->uri);
      $api->virtualColumnAdd($virtualColumnJSON);
    
      \Drupal::messenger()->addMessage(t("Virtual column has been updated successfully."));
      $form_state->setRedirectUrl(Utils::selectBackUrl('virtualcolumn'));

    } catch(\Exception $e) {
      \Drupal::messenger()->addMessage(t("An error occurred while updating Virtual Column: ".$e->getMessage()));
      $form_state->setRedirectUrl(Utils::selectBackUrl('virtualcolumn'));
    }

  }

}