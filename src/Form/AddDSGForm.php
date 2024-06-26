<?php

namespace Drupal\std\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rep\Utils;
use Drupal\rep\Constant;
use Drupal\rep\Vocabulary\HASCO;

class AddDSGForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_dsg_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['dsg_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
    ];
    
    $form['dsg_filename'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('File Upload'),
      '#description' => $this->t('Upload a file.'),
      '#upload_location' => 'public://uploads/',
      '#upload_validators' => [
        'file_validate_extensions' => ['xlsx'],
      ],
    ];
    $form['dsg_comment'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Comment'),
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
      if(strlen($form_state->getValue('dsg_name')) < 1) {
        $form_state->setErrorByName('dsg_name', $this->t('Please enter a valid name for the DSG'));
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
      $form_state->setRedirectUrl(STDSelectForm::backSelect('dsg'));
      return;
    } 

    try {
      $useremail = \Drupal::currentUser()->getEmail();

      $fileId = $form_state->getValue('dsg_filename');
      $file_entity = \Drupal\file\Entity\File::load($fileId[0]);
      $filename = $file_entity->getFilename();

      $newDataFileUri = Utils::uriGen('datafile');
      $datafileJSON = '{"uri":"'. $newDataFileUri .'",'.
          '"typeUri":"'.HASCO::DATAFILE.'",'.
          '"hascoTypeUri":"'.HASCO::DATAFILE.'",'.
          '"label":"'.$form_state->getValue('dsg_name').'",'.
          '"filename":"'.$filename.'",'.          
          '"id":"'.$fileId[0].'",'.          
          '"fileStatus":"'.Constant::FILE_STATUS_UNPROCESSED.'",'.          
          '"hasSIRManagerEmail":"'.$useremail.'"}';

      $newDSGUri = str_replace("DF","DG",$newDataFileUri);
      $dsgJSON = '{"uri":"'. $newDSGUri .'",'.
          '"typeUri":"'.HASCO::DSG.'",'.
          '"hascoTypeUri":"'.HASCO::DSG.'",'.
          '"label":"'.$form_state->getValue('dsg_name').'",'.
          '"hasDataFile":"'.$newDataFileUri.'",'.          
          '"comment":"'.$form_state->getValue('dsg_description').'",'.
          '"hasSIRManagerEmail":"'.$useremail.'"}';

      // Check if a file was uploaded.
      if ($file_entity) {
        // Set the status to FILE_STATUS_PERMANENT.
        $file_entity->set('status', FILE_STATUS_PERMANENT);
        $file_entity->save();
        dpm($file_entity);
        \Drupal::messenger()->addMessage(t('File uploaded successfully.'));

        $api = \Drupal::service('rep.api_connector');

        // ADD DATAFILE
        $msg1 = $api->parseObjectResponse($api->datafileAdd($datafileJSON),'datafileAdd');

        // ADD DSG
        $msg2 = $api->parseObjectResponse($api->dsgAdd($dsgJSON),'dsgAdd');

        if ($msg1 != NULL && $msg2 != NULL) {
          \Drupal::messenger()->addMessage(t("DSG has been added successfully."));      
        } else {
          \Drupal::messenger()->addError(t("Something went wrong while adding DSG."));      
        }
        $form_state->setRedirectUrl(STDSelectForm::backSelect('dsg'));
        return;
      }

    } catch(\Exception $e) {
      \Drupal::messenger()->addMessage(t("An error occurred while adding an DSG: ".$e->getMessage()));
      $form_state->setRedirectUrl(STDSelectForm::backSelect('dsg'));
      return;
    }

  }

}