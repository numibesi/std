<?php

namespace Drupal\std\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\rep\Utils;
use Drupal\rep\Vocabulary\HASCO;

class AddStudyForm extends FormBase {

  public $pi = [];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_study_form';
  }

  // Constructor
  //public function __construct() {
  //  $this->pi = ["value1" => "Option 1", "value2" => "Option 2", "value3" => "Option 3"];
  //}

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    //dpm($form_state->getValue('study_pi'));

    // Add custom CSS for the fieldset.
    $form['#attached']['library'][] = 'std/std_js_css';
  
    $form['study_short_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Short Name'),
    ];
    $form['study_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Long Name'),
    ];

    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('PIs'),
      '#prefix' => '<div class="my-fieldset-wrapper">', // Add a wrapper div with a custom class.
      '#suffix' => '</div>', // Close the wrapper div.
      '#attributes' => [
        'id' => 'fieldset-wrapper',
      ],
    ];

    $form['fieldset']['add_textfield'] = [
      '#type' => 'button',
      '#value' => t('Add PI'),
      '#ajax' => [
        'callback' => '::addPICallback',
        'wrapper' => 'fieldset-wrapper',
      ],
    ];
    $form['fieldset']['spacer_1'] = [
      '#type' => 'item',
      '#title' => t('<br>'),
    ];

    // Get the count of existing textfields.
    $counter = $form_state->get('textfield_counter') ?? 0;

    // Add existing textfields to the form.
    for ($i = 1; $i <= $counter; $i++) {
      $form['fieldset']['fieldgroup_' . $i] = [
        '#type' => 'container',
        '#attributes' => array('class' => array('inline-container')),
      ];
      $form['fieldset']['fieldgroup_' . $i]['textfield_' . $i] = [
        '#type' => 'textfield',
        //'#title' => t('Textfield @counter', ['@counter' => $i]),
      ];
      $form['fieldset']['fieldgroup_' . $i]['delete_' . $i] = [
        '#type' => 'button',
        '#value' => t('Delete'),
        //'#ajax' => [
        //  'callback' => '::addPICallback',
        //  'wrapper' => 'fieldset-wrapper',
        //],
      ];
    }

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
    //dpm($form['study_pi_fieldset']);

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

  public function addPICallback(array &$form, FormStateInterface $form_state) {
    $response = new \Drupal\Core\Ajax\AjaxResponse();
  
    // Increment the counter to create a unique ID for the new textfield.
    $counter = $form_state->get('textfield_counter') ?? 0;
    $counter++;
    $form_state->set('textfield_counter', $counter);
  
    // Build the form element for the new textfield.
    //$new_textfield = [
    //  '#type' => 'container',
    //  '#attributes' => ['class' => ['inline-container']],
    //];
    //$new_textfield['textfield_' . $counter] = [
    //  '#type' => 'textfield',
    //  '#title' => t('Textfield @counter', ['@counter' => $counter]),
    //];
    //$new_textfield['delete_' . $counter] = [
    //  '#type' => 'button',
    //  '#value' => t('Delete'),
    //];

    // Add a new textfield to the form state.
    $form['fieldset']['fieldgroup_' . $counter] = [
      '#type' => 'container',
      '#attributes' => array('class' => array('inline-container')),
    ];
    $form['fieldset']['fieldgroup_' . $counter]['textfield_' . $counter] = [
      '#type' => 'textfield',
      //'#title' => t('Textfield @counter', ['@counter' => $i]),
    ];
    $form['fieldset']['fieldgroup_' . $counter]['delete_' . $counter] = [
      '#type' => 'button',
      '#value' => t('Delete'),
      //'#ajax' => [
      //  'callback' => '::addPICallback',
      //  'wrapper' => 'fieldset-wrapper',
      //],
    ];
    //$form['fieldset']['textfield_' . $counter] = [
    //  '#type' => 'textfield',
      //'#title' => t('Textfield @counter', ['@counter' => $counter]),
    //];

    // Add an Ajax command to append the new textfield to the fieldset.
    //$response->addCommand(new AppendCommand('#fieldset-wrapper', render($form['fieldset']['fieldgroup_' . $counter]['textfield_' . $counter])));
    $response->addCommand(new AppendCommand('#fieldset-wrapper', render($form['fieldset']['fieldgroup_' . $counter])));
  
    return $response;
  }

  /**
   * Ajax callback function to delete PIs.
   */
  function delete_pi_callback(array &$form, FormStateInterface $form_state) {
    
    //dpm('in delete pi ajax');

    // Get the selected value from the form state.
    $selected_value = $form_state->getValue('study_pi');

    // Remove the selected options from the options array.
    if ($selected_value != NULL && is_array($selected_value)) {
      $keys = array_keys($selected_value);
      if ($keys != NULL && is_array($keys)) {
        foreach ($keys as $key) {
          unset($form['study_pi_fieldset']['study_pi']['#options'][$key]);
        }
      }
    }

    //dpm($selected_value);

    // Return the updated form element.
    return $form['study_pi_fieldset'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submitted_values = $form_state->cleanValues()->getValues();
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];

    if ($button_name === 'add_pi') {
      return;
    } 

    if ($button_name === 'delete_pi') {
      //$selected_value = $form_state->getValue('study_pi');
      //unset($this->pi['value2']);
      return;
    } 

    if ($button_name === 'back') {
      $form_state->setRedirectUrl(Utils::selectBackUrl('study'));
      return;
    } 

    if ($button_name === 'save') {
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

    return;

  }

}