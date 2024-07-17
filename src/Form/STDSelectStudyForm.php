<?php

namespace Drupal\std\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\HtmlTag;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Serialization\Json;
use Drupal\file\Entity\File;
use Drupal\rep\ListManagerEmailPage;
use Drupal\rep\Utils;
//use Drupal\rep\Entity\StudyObject;
use Drupal\std\Entity\Study;
//use Drupal\std\Entity\StudyRole;
//use Drupal\std\Entity\VirtualColumn;
//use Drupal\std\Entity\StudyObjectCollection;

class STDSelectStudyForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'std_select_study_form';
  }

  public $element_type;

  public $manager_email;

  public $manager_name;

  public $single_class_name;

  public $plural_class_name;

  protected $list;

  protected $list_size;

  public function getList() {
    return $this->list;
  }

  public function setList($list) {
    return $this->list = $list; 
  }

  public function getListSize() {
    return $this->list_size;
  }

  public function setListSize($list_size) {
    return $this->list_size = $list_size; 
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $elementtype=NULL, $page=NULL, $pagesize=NULL) {

    // GET MANAGER EMAIL
    $this->manager_email = \Drupal::currentUser()->getEmail();
    $uid = \Drupal::currentUser()->id();
    $user = \Drupal\user\Entity\User::load($uid);
    $this->manager_name = $user->name->value;

    // GET TOTAL NUMBER OF ELEMENTS AND TOTAL NUMBER OF PAGES
    $this->element_type = $elementtype;
    $this->setListSize(-1);
    if ($this->element_type != NULL) {
        $this->setListSize(ListManagerEmailPage::total($this->element_type, $this->manager_email));
    }
    if (gettype($this->list_size) == 'string') {
        $total_pages = "0";
    } else { 
        if ($this->list_size % $pagesize == 0) {
            $total_pages = $this->list_size / $pagesize;
        } else {
            $total_pages = floor($this->list_size / $pagesize) + 1;
        }
    }

    // CREATE LINK FOR NEXT PAGE AND PREVIOUS PAGE
    if ($page < $total_pages) {
        $next_page = $page + 1;
        $next_page_link = ListManagerEmailPage::link($this->element_type, $next_page, $pagesize);
    } else {
        $next_page_link = '';
    }
    if ($page > 1) {
        $previous_page = $page - 1;
        $previous_page_link = ListManagerEmailPage::link($this->element_type, $previous_page, $pagesize);
    } else {
        $previous_page_link = '';
    }

    // RETRIEVE ELEMENTS
    $this->setList(ListManagerEmailPage::exec($this->element_type, $this->manager_email, $page, $pagesize));
    //dpm($this->getList());

    $this->single_class_name = "";
    $this->plural_class_name = "";
    switch ($this->element_type) {
        case "study":
            $this->single_class_name = "Study";
            $this->plural_class_name = "Studies";
            $header = Study::generateHeader();
            //$output = Study::generateOutput($this->getList()); 
            $output = Study::generateOutputAsCard($this->getList()); 
            break;
        /*
        case "studyrole":
            $this->single_class_name = "Study Role";
            $this->plural_class_name = "Study Roles";
            $header = StudyRole::generateHeader();
            $output = StudyRole::generateOutput($this->getList());    
            break;
        case "virtualcolumn":
            $this->single_class_name = "Virtual Column";
            $this->plural_class_name = "Virtual Columns";
            $header = VirtualColumn::generateHeader();
            $output = VirtualColumn::generateOutput($this->getList());    
            break;
        case "studyobjectcollection":
            $this->single_class_name = "Study Object Collection";
            $this->plural_class_name = "Study Object Collections";
            $header = StudyObjectCollection::generateHeader();
            $output = StudyObjectCollection::generateOutput($this->getList());    
            break;
        case "studyobject":
            $this->single_class_name = "Study Object";
            $this->plural_class_name = "Study Objects";
            $header = StudyObject::generateHeader();
            $output = StudyObject::generateOutput($this->getList());    
            break;
        */
        default:
            $this->single_class_name = "Object of Unknown Type";
            $this->plural_class_name = "Objects of Unknown Types";
    }

    // PUT FORM TOGETHER
    $form['page_title'] = [
        '#type' => 'item',
        '#title' => $this->t('<h3>Manage ' . $this->plural_class_name . '</h3>'),
    ];
    $form['page_subtitle'] = [
        '#type' => 'item',
        '#title' => $this->t('<h4>' . $this->plural_class_name . ' maintained by <font color="DarkGreen">' . $this->manager_name . ' (' . $this->manager_email . ')</font></h4>'),
    ];
    $form['add_element'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add New ' . $this->single_class_name),
        '#name' => 'add_element',
    ];
    /*
    $form['edit_selected_element'] = [
        '#type' => 'submit',
        '#value' => $this->t('Edit Selected ' . $this->single_class_name),
        '#name' => 'edit_element',
    ];
    $form['delete_selected_element'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete Selected ' . $this->plural_class_name),
        '#name' => 'delete_element',
    ];
    */
    $form['space1'] = [
      '#type' => 'item',
      '#value' => $this->t('<br>'),
    ];

    //if ($this->element_type == "study") {
      // Add elements as cards
      $index = 0;
      foreach ($output as $uri => $card) {
          $index++; 
          $form['element_' . $index] = $card;
      }
    /*
    } else {
      // Add elements as table
      $form['element_table'] = [
        '#type' => 'tableselect',
        '#header' => $header,
        '#options' => $output,
        '#js_select' => FALSE,
        '#empty' => t('No ' . $this->plural_class_name . ' found'),
      ];
    }
    */

    $form['pager'] = [
        '#theme' => 'list-page',
        '#items' => [
            'page' => strval($page),
            'first' => ListManagerEmailPage::link($this->element_type, 1, $pagesize),
            'last' => ListManagerEmailPage::link($this->element_type, $total_pages, $pagesize),
            'previous' => $previous_page_link,
            'next' => $next_page_link,
            'last_page' => strval($total_pages),
            'links' => null,
            'title' => ' ',
        ],
    ];
    $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#name' => 'back',
    ];
    $form['space2'] = [
        '#type' => 'item',
        '#value' => $this->t('<br><br><br>'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */   
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // RETRIEVE TRIGGERING BUTTON
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];
    $destination = $triggering_element['#destination'];
    $urivalue = $triggering_element['#urivalue'];
    $drupal_selector = $triggering_element['#attributes']['data-drupal-selector'];
  
    // SET USER ID AND PREVIOUS URL FOR TRACKING STORE URLS
    $uid = \Drupal::currentUser()->id();
    $previousUrl = \Drupal::request()->getRequestUri();
        
    // RETRIEVE SELECTED ROWS, IF ANY
    $selected_rows = $form_state->getValue('element_table');
    $rows = [];
    foreach ($selected_rows as $index => $selected) {
      if ($selected) {
        $rows[$index] = $index;
      }
    }

    // ADD ELEMENT
    if ($button_name === 'add_element') {
      //if ($this->element_type == 'study') {
        Utils::trackingStoreUrls($uid, $previousUrl, 'std.add_study');
        $url = Url::fromRoute('std.add_study');
      //} 
      /*
      if ($this->element_type == 'studyrole') {
        Utils::trackingStoreUrls($uid, $previousUrl, 'std.add_studyrole');
        $url = Url::fromRoute('std.add_studyrole', [
          'studyuri' => 'none', 
          'fixstd' => 'F',
        ]);
      } 
      if ($this->element_type == 'studyobjectcollection') {
        Utils::trackingStoreUrls($uid, $previousUrl, 'std.add_studyobjectcollection');
        $url = Url::fromRoute('std.add_studyobjectcollection', [
          'studyuri' => 'none', 
          'fixstd' => 'F',
        ]);
      } 
      if ($this->element_type == 'studyobject') {
        Utils::trackingStoreUrls($uid, $previousUrl, 'std.add_studyobject');
        $url = Url::fromRoute('std.add_studyobject');
      } 
      if ($this->element_type == 'virtualcolumn') {
        Utils::trackingStoreUrls($uid, $previousUrl, 'std.add_virtualcolumn');
        $url = Url::fromRoute('std.add_virtualcolumn', [
          'studyuri' => 'none',
          'fixstd' => 'F',
        ]);
      }
      */ 
      $form_state->setRedirectUrl($url);
    }  

    /*
    // EDIT ELEMENT
    if ($button_name === 'edit_element') {
      if (sizeof($rows) < 1) {
        \Drupal::messenger()->addWarning(t("Select the exact " . $this->single_class_name . " to be edited."));      
      } else if ((sizeof($rows) > 1)) {
        \Drupal::messenger()->addWarning(t("No more than one " . $this->single_class_name . " can be edited at once."));      
      } else {
        $first = array_shift($rows);
        //if ($this->element_type == 'study') {
        //  Utils::trackingStoreUrls($uid, $previousUrl, 'std.edit_study');
        //  $url = Url::fromRoute('std.edit_study', ['studyuri' => base64_encode($first)]);
        //} 
        if ($this->element_type == 'studyrole') {
          Utils::trackingStoreUrls($uid, $previousUrl, 'std.edit_studyrole');
          $url = Url::fromRoute('std.edit_studyrole', [
            'studyroleuri' => base64_encode($first),
            'fixstd' => 'F',
          ]);
        } 
        if ($this->element_type == 'studyobjectcollection') {
          Utils::trackingStoreUrls($uid, $previousUrl, 'std.edit_studyobjectcollection');
          $url = Url::fromRoute('std.edit_studyobjectcollection', ['studyobjectcollectionuri' => base64_encode($first)]);
        } 
        if ($this->element_type == 'studyobject') {
          Utils::trackingStoreUrls($uid, $previousUrl, 'std.edit_studyobject');
          $url = Url::fromRoute('std.edit_studyobject', ['studyobjecturi' => base64_encode($first)]);
        } 
        if ($this->element_type == 'virtualcolumn') {
          Utils::trackingStoreUrls($uid, $previousUrl, 'std.edit_virtualcolumn');
          $url = Url::fromRoute('std.edit_virtualcolumn', [
            'virtualcolumnuri' => base64_encode($first),
            'fixstd' => 'F',
          ]);
        } 
        $form_state->setRedirectUrl($url);
      } 
    }
    */

    /*
    // DELETE ELEMENT
    if ($button_name === 'delete_element') {
      if (sizeof($rows) <= 0) {
        \Drupal::messenger()->addWarning(t("At least one " . $this->single_class_name . " needs to be selected to be deleted."));      
      } else {
        $api = \Drupal::service('rep.api_connector');
        foreach($rows as $uri) {
          if ($this->element_type == 'study') {
            $api->studyDel($uri);
          } 
          //if ($this->element_type == 'studyrole') {
          //  $api->studyRoleDel($uri);
          //} 
          //if ($this->element_type == 'studyobjectcollection') {
          //  $api->studyObjectCollectionDel($uri);
          //} 
          //if ($this->element_type == 'studyobject') {
          //  $api->studyObjectDel($uri);
          //} 
          //if ($this->element_type == 'virtualcolumn') {
          //  $api->virtualColumnDel($uri);
          //} 
        }
        \Drupal::messenger()->addMessage(t("Selected " . $this->plural_class_name . " has/have been deleted successfully."));      
      }
    }
    */ 

    if ($button_name === 'back') {
      $url = Url::fromRoute('std.search');
      $form_state->setRedirectUrl($url);
    }  

  }

  /**
   * {@inheritdoc}
   */   
  public static function backSelect($elementType) {
    $url = Url::fromRoute('rep.home');
    return $url;
  }
  
}