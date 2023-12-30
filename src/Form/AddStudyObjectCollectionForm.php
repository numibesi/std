<?php

namespace Drupal\std\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rep\Utils;
use Drupal\rep\Vocabulary\HASCO;

class AddStudyObjectCollectionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_studyobjectcollection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['soc_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
    ];
    $form['soc_study'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Study'),
      '#autocomplete_route_name' => 'sem.semanticvariable_entity_autocomplete',

    ];
    $form['soc_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time Restriction (optional)'),
    ];
    $form['soc_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Version'),
    ];
    $form['soc_description'] = [
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
      if(strlen($form_state->getValue('soc_name')) < 1) {
        $form_state->setErrorByName('soc_name', $this->t('Please enter a valid name for the Semantic Variable'));
      }
      if(strlen($form_state->getValue('soc_entity')) < 1) {
        $form_state->setErrorByName('soc_entity', $this->t('Please enter a valid entity for the Semantic Variable'));
      }
      if(strlen($form_state->getValue('soc_attribute')) < 1) {
        $form_state->setErrorByName('soc_attribute', $this->t('Please enter a valid attribute for the Semantic Variable'));
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
      $form_state->setRedirectUrl(Utils::selectBackUrl('semanticvariable'));
      return;
    } 

    try {
      $useremail = \Drupal::currentUser()->getEmail();

      $entityUri = 'null';
      if ($form_state->getValue('soc_entity') != NULL && $form_state->getValue('soc_entity') != '') {
        $entityUri = Utils::uriFromAutocomplete($form_state->getValue('soc_entity'));
      } 

      $attributeUri = 'null';
      if ($form_state->getValue('soc_attribute') != NULL && $form_state->getValue('soc_attribute') != '') {
        $attributeUri = Utils::uriFromAutocomplete($form_state->getValue('soc_attribute'));
      } 

      $inRelationToUri = 'null';
      if ($form_state->getValue('soc_in_relation_to') != NULL && $form_state->getValue('soc_in_relation_to') != '') {
        $inRelationToUri = Utils::uriFromAutocomplete($form_state->getValue('soc_in_relation_to'));
      } 

      $unitUri = 'null';
      if ($form_state->getValue('soc_unit') != NULL && $form_state->getValue('soc_unit') != '') {
        $unitUri = Utils::uriFromAutocomplete($form_state->getValue('soc_unit'));
      } 

      $timeUri = 'null';
      if ($form_state->getValue('soc_time') != NULL && $form_state->getValue('soc_time') != '') {
        $timeUri = Utils::uriFromAutocomplete($form_state->getValue('soc_time'));
      } 

      $newStudyUri = Utils::uriGen('semanticvariable');
      $semanticVariableJSON = '{"uri":"'. $newStudyUri .'",'.
          '"typeUri":"'.HASCO::SEMANTIC_VARIABLE.'",'.
          '"hascoTypeUri":"'.HASCO::SEMANTIC_VARIABLE.'",'.
          '"label":"'.$form_state->getValue('soc_name').'",'.
          '"entityUri":"' . $entityUri . '",' . 
          '"attributeUri":"' . $attributeUri . '",' .
          '"inRelationToUri":"' . $inRelationToUri . '",' . 
          '"unitUri":"' . $unitUri . '",' . 
          '"timeUri":"' . $timeUri . '",' . 
          '"hasVersion":"'.$form_state->getValue('soc_version').'",'.
          '"comment":"'.$form_state->getValue('soc_description').'",'.
          '"hasSIRManagerEmail":"'.$useremail.'"}';

      $api = \Drupal::service('rep.api_connector');
      $api->semanticVariableAdd($semanticVariableJSON);
      \Drupal::messenger()->addMessage(t("Semantic Variable has been added successfully."));      
      $form_state->setRedirectUrl(Utils::selectBackUrl('semanticvariable'));

    }catch(\Exception $e){
      \Drupal::messenger()->addMessage(t("An error occurred while adding a semantic variable: ".$e->getMessage()));
      $form_state->setRedirectUrl(Utils::selectBackUrl('semanticvariable'));
    }

  }

}