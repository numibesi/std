<?php

namespace Drupal\std\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\rep\Utils;
use Drupal\rep\Constant;
use Drupal\rep\Vocabulary\HASCO;
use Drupal\std\Form\STDSelectForm;

class EditDSGForm extends FormBase {

  protected $dsgUri;

  protected $dsg;

  public function getDSGUri() {
    return $this->dsgUri;
  }

  public function setDSGUri($uri) {
    return $this->dsgUri = $uri; 
  }

  public function getDSG() {
    return $this->dsg;
  }

  public function setDSG($dsg) {
    return $this->dsg = $dsg; 
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_dsg_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $dsguri = NULL) {
    $uri=$dsguri ?? 'default';
    $uri_decode=base64_decode($uri);
    $this->setDSGUri($uri_decode);

    $api = \Drupal::service('rep.api_connector');
    $this->setDSG($api->parseObjectResponse($api->getUri($this->getDSGUri()),'getUri'));
    if ($this->getDSG() == NULL) {
      \Drupal::messenger()->addMessage(t("Failed to retrieve dsg."));
      $form_state->setRedirectUrl(STDSelectForm::backSelect('dsg'));
    }
    $form['dsg_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $this->getDSG()->label,
    ];
    $form['dsg_comment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Comment'),
      '#default_value' => $this->getDSG()->comment,
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
      if(strlen($form_state->getValue('dsg_name')) < 1) {
        $form_state->setErrorByName('dsg_name', $this->t('Please enter a name for the dsg'));
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
      $form_state->setRedirectUrl(STDSelectForm::backSelect('dsg'));
      return;
    } 

    $useremail = \Drupal::currentUser()->getEmail();

    //dpm($this->getDSG());

    $dsgJSON = '{"uri":"'. $this->getDSG()->uri .'",'.
      '"typeUri":"'.HASCO::DSG.'",'.
      '"hascoTypeUri":"'.HASCO::DSG.'",'.
      '"label":"'.$form_state->getValue('dsg_name').'",'.
      '"hasDataFileUri":"'.$this->getDSG()->hasDataFile->uri.'",'.          
      '"comment":"'.$form_state->getValue('dsg_comment').'",'.
      '"hasSIRManagerEmail":"'.$useremail.'"}';

    try {
      // UPDATE BY DELETING AND CREATING
      $api = \Drupal::service('rep.api_connector');
      $msg1 = $api->parseObjectResponse($api->dsgDel($this->getDSG()->uri),'dsgDel');
      if ($msg1 == NULL) {
        \Drupal::messenger()->addMessage(t("Failed to update dsg: deleting existing dsg"));
        $form_state->setRedirectUrl(STDSelectForm::backSelect('dsg'));
      } else {
        $msg2 = $api->parseObjectResponse($api->dsgAdd($dsgJSON),'dsgAdd');
        if ($msg2 == NULL) {
          \Drupal::messenger()->addMessage(t("Failed to update dsg: inserting new dsg"));
          $form_state->setRedirectUrl(STDSelectForm::backSelect('dsg'));
        } else {
          \Drupal::messenger()->addMessage(t("dsg has been updated successfully."));
          $form_state->setRedirectUrl(STDSelectForm::backSelect('dsg'));
        }
      }

    } catch(\Exception $e) {
      \Drupal::messenger()->addMessage(t("An error occurred while updating dsg: ".$e->getMessage()));
      $form_state->setRedirectUrl(STDSelectForm::backSelect('dsg'));
    }

  }

}