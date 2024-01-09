<?php

namespace Drupal\std\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rep\Utils;
use Drupal\rep\Constant;
use Drupal\rep\Vocabulary\HASCO;

class AddSTDForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_std_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //$file_entity = \Drupal\file\Entity\File::load(24);
    //if ($file_entity != NULL) {
    //  dpm($file_entity);
    //  dpm($file_entity->getFilename());
    //}

    $form['std_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
    ];
    
    $form['std_filename'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('File Upload'),
      '#description' => $this->t('Upload a file.'),
      '#upload_location' => 'public://uploads/',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
    ];
    $form['std_comment'] = [
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
      if(strlen($form_state->getValue('std_name')) < 1) {
        $form_state->setErrorByName('std_name', $this->t('Please enter a valid name for the STD'));
      }
      #if(strlen($form_state->getValue('sdd_filename')) < 1) {
      #  $form_state->setErrorByName('sdd_filename', $this->t('Please enter a valid filename for the STD'));
      #}
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
      $form_state->setRedirectUrl(Utils::selectBackUrl('std'));
      return;
    } 

    try {
      $useremail = \Drupal::currentUser()->getEmail();

      $fileId = $form_state->getValue('std_filename');
      //dpm($fileId[0]);
      $file_entity = \Drupal\file\Entity\File::load($fileId[0]);
      $filename = $file_entity->getFilename();

      $newDataFileUri = Utils::uriGen('datafile');
      $datafileJSON = '{"uri":"'. $newDataFileUri .'",'.
          '"typeUri":"'.HASCO::DATAFILE.'",'.
          '"hascoTypeUri":"'.HASCO::DATAFILE.'",'.
          '"label":"'.$form_state->getValue('sdd_name').'",'.
          '"filename":"'.$filename.'",'.          
          '"id":"'.$fileId[0].'",'.          
          '"fileStatus":"'.Constant::FILE_STATUS_UNPROCESSED.'",'.          
          '"hasSIRManagerEmail":"'.$useremail.'"}';

      $newSTDUri = str_replace("DF","SD",$newDataFileUri);
      $stdJSON = '{"uri":"'. $newSTDUri .'",'.
          '"typeUri":"'.HASCO::STUDY.'",'.
          '"hascoTypeUri":"'.HASCO::STUDY.'",'.
          '"label":"'.$form_state->getValue('std_name').'",'.
          '"hasDataFile":"'.$newDataFileUri.'",'.          
          '"comment":"'.$form_state->getValue('std_description').'",'.
          '"hasSIRManagerEmail":"'.$useremail.'"}';

      // Check if a file was uploaded.
      if (!empty($fileId[0])) {
        // Load the file object.
        //$file_entity = \Drupal\file\Entity\File::load($file[0]);
        //dpm($file_entity);
        //dpm($file_entity->getFilename());
        \Drupal::messenger()->addMessage(t('File uploaded successfully.'));
        $api = \Drupal::service('rep.api_connector');

        // ADD DATAFILE
        $msg1 = $api->parseObjectResponse($api->datafileAdd($datafileJSON),'datafileAdd');

        // ADD STD
        $msg2 = $api->parseObjectResponse($api->studyAdd($stdJSON),'sddAdd');

        if ($msg1 != NULL && $msg2 != NULL) {
          \Drupal::messenger()->addMessage(t("STD has been added successfully."));      
        } else {
          \Drupal::messenger()->addError(t("Something went wrong while adding STD."));      
        }
        $form_state->setRedirectUrl(Utils::selectBackUrl('std'));
        return;
      }

    } catch(\Exception $e) {
      \Drupal::messenger()->addMessage(t("An error occurred while adding an STD: ".$e->getMessage()));
      $form_state->setRedirectUrl(Utils::selectBackUrl('std'));
      return;
    }

  }

}