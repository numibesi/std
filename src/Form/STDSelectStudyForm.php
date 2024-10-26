<?php

namespace Drupal\std\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\rep\ListManagerEmailPage;
use Drupal\rep\Utils;
use Drupal\rep\Vocabulary\REPGUI;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\CssCommand;

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
    $this->list = $list;
  }

  public function getListSize() {
    return $this->list_size;
  }

  public function setListSize($list_size) {
    $this->list_size = $list_size;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $elementtype = NULL, $page = NULL, $pagesize = NULL) {

    // GET MANAGER EMAIL
    $this->manager_email = \Drupal::currentUser()->getEmail();
    $uid = \Drupal::currentUser()->id();
    $user = \Drupal\user\Entity\User::load($uid);
    $this->manager_name = $user->getDisplayName();

    // GET ELEMENT TYPE
    $this->element_type = $elementtype;

    // Default page size if not provided
    if ($pagesize === NULL) {
      $pagesize = 9; // Load 9 items at a time
    }

    // Retrieve or set default view type
    $session = \Drupal::request()->getSession();
    $view_type = $session->get('std_select_study_view_type', 'card');
    $form_state->set('view_type', $view_type);

    // Store page size in form state for use in AJAX callbacks
    $form_state->set('page_size', $pagesize);

    // Determine the class names based on the element type
    $this->single_class_name = "";
    $this->plural_class_name = "";
    switch ($this->element_type) {
      case "study":
        $this->single_class_name = "Study";
        $this->plural_class_name = "Studies";
        break;
      default:
        $this->single_class_name = "Object of Unknown Type";
        $this->plural_class_name = "Objects of Unknown Types";
    }

    // PUT FORM TOGETHER
    $form['page_title'] = [
      '#type' => 'item',
      '#markup' => '<h3 class="mt-5">Manage ' . $this->plural_class_name . '</h3>',
    ];
    $form['page_subtitle'] = [
      '#type' => 'item',
      '#markup' => $this->t('<h4>@plural_class_name maintained by <font color="DarkGreen">@manager_name (@manager_email)</font></h4>', [
        '@plural_class_name' => $this->plural_class_name,
        '@manager_name' => $this->manager_name,
        '@manager_email' => $this->manager_email,
      ]),
    ];

    // Add view toggle buttons
    $form['view_toggle'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['view-toggle', 'd-flex', 'justify-content-end']],
    ];

    $form['view_toggle']['table_view'] = [
      '#type' => 'submit',
      '#value' => '',
      '#name' => 'view_table',
      '#attributes' => [
        'style' => 'padding: 20px;',
        'class' => ['table-view-button', 'fa-xl', 'mx-1'],
        'title' => $this->t('Table View'),
      ],
      '#submit' => ['::viewTableSubmit'],
      '#limit_validation_errors' => [],
    ];

    $form['view_toggle']['card_view'] = [
      '#type' => 'submit',
      '#value' => '',
      '#name' => 'view_card',
      '#attributes' => [
        'style' => 'padding: 20px;',
        'class' => ['card-view-button', 'fa-xl'],
        'title' => $this->t('Card View'),
      ],
      '#submit' => ['::viewCardSubmit'],
      '#limit_validation_errors' => [],
    ];

    $form['add_element'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add New ' . $this->single_class_name),
      '#name' => 'add_element',
      '#attributes' => [
        'class' => ['btn', 'btn-primary', 'add-element-button'],
      ],
    ];

    if ($view_type == 'card') {

      $form['space1'] = [
        '#type' => 'item',
        '#markup' => '<br>',
      ];

      // Initialize current page if not set
      if ($form_state->get('page') === NULL) {
        $form_state->set('page', 1);
      }
      $page = $form_state->get('page');

      // Add the JavaScript library for infinite scroll (ensure you have this library in your module)
      $form['#attached']['library'][] = 'std/infinite_scroll';

      // Retrieve elements for the current page
      $this->setList(ListManagerEmailPage::exec($this->element_type, $this->manager_email, $page, $pagesize));

      // Get total number of items
      $this->setListSize(ListManagerEmailPage::total($this->element_type, $this->manager_email));
      $total_items = $this->getListSize();

      // Wrap cards in a container for AJAX
      $form['cards_wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'cards-wrapper'],
      ];

      // Build the card view into the 'cards_wrapper'
      $this->buildCardView($form['cards_wrapper'], $form_state);

      // Check if there are more items to load
      if ($total_items > $page * $pagesize) {
        // Add the Load More button
        $form['load_more'] = [
          '#type' => 'button',
          '#value' => $this->t('Load More'),
          '#ajax' => [
            'callback' => '::loadMoreCallback',
            'wrapper' => 'cards-wrapper',
            'method' => 'append',
          ],
          '#attributes' => [
            'class' => ['btn', 'btn-primary', 'load-more-button'],
          ],
          '#name' => 'load_more',
        ];
      }
    } else {
      // Initialize current page if not provided
      if ($page === NULL) {
        $page = 1;
      }

      // Build the table view
      $this->setList(ListManagerEmailPage::exec($this->element_type, $this->manager_email, $page, $pagesize));
      $this->buildTableView($form, $form_state);

      // GET TOTAL NUMBER OF ELEMENTS AND TOTAL NUMBER OF PAGES
      $this->setListSize(ListManagerEmailPage::total($this->element_type, $this->manager_email));
      $total_items = $this->getListSize();

      if ($total_items % $pagesize == 0) {
        $total_pages = $total_items / $pagesize;
      } else {
        $total_pages = floor($total_items / $pagesize) + 1;
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

      // Add pager for table view
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
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#name' => 'back',
      '#attributes' => [
        'class' => ['btn', 'btn-primary', 'back-button'],
      ],
    ];
    $form['space2'] = [
      '#type' => 'item',
      '#markup' => '<br><br><br>',
    ];

    return $form;
  }

  /**
   * Build the card view with header, content, footer, and working action links.
   */
  protected function buildCardView(array &$form, FormStateInterface $form_state) {
    // Get the list of items to display
    $items = $this->getList();

    $cards = [];

    // Process each item to create a card
    $index = 0;
    foreach ($items as $element) {
      $index++;
      $uri = $element->uri ?? '';
      $label = $element->label ?? '';
      $title = $element->title ?? '';
      $pi = $element->pi ?? '';
      $ins = $element->institution ?? '';
      $desc = $element->comment ?? '';

      // Build the card array
      $card = [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-md-4']],
      ];

      $card['card'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['card', 'mb-3']],
      ];

      // Card header with 'short-Name'
      $shortName = $label;
      $card['card']['header'] = [
        '#type' => 'container',
        '#attributes' => [
          'style' => 'margin-bottom:0!important;',
          'class' => ['card-header']],
        '#markup' => '<h5>' . $shortName . '</h5>',
      ];

      // Determine the image URI or use a placeholder
      if (!empty($element->image)) {
        $image_uri = $element->image;
      } else {
        // Use default placeholder image from the module
        $image_uri = base_path() . \Drupal::service('extension.list.module')->getPath('rep') . '/images/std_placeholder.png';
      }

      // Create a hyperlink for the URI if it's valid
      $uri = Utils::namespaceUri($uri);

      // Build the card body with a 60%-40% layout
      $card['card']['body'] = [
        '#type' => 'container',
        '#attributes' => [
          'style' => 'margin-bottom:0!important;',
          'class' => ['card-body', 'mb-0']],
        'row' => [
          '#type' => 'container',
          '#attributes' => [
            'style' => 'margin-bottom:0!important;',
            'class' => ['row']],
          'text_column' => [
            '#type' => 'container',
            '#attributes' => [
              'style' => 'margin-bottom:0!important;',
              'class' => ['col-md-7']],
            'text' => [
              '#markup' => '<p class="card-text"><strong>Name:</strong> ' . $title
                . '<br><strong>URI:</strong> ' . Link::fromTextAndUrl($uri, Url::fromUserInput(REPGUI::DESCRIBE_PAGE . base64_encode($uri)))->toString()
                . '<br><strong>PI: </strong>' . $pi
                . '<br><strong>Institution: </strong>' . $ins
                . '<br><strong>Description: </strong>' . $desc . '</p>',
            ],
          ],
          'image_column' => [
            '#type' => 'container',
            '#attributes' => [
              'style' => 'margin-bottom:0!important;',
              'class' => ['col-md-5', 'text-center', 'mb-0', 'align-middle']],
            'image' => [
              '#theme' => 'image',
              '#uri' => $image_uri,
              '#alt' => $this->t('Image for @name', ['@name' => $title]),
              '#attributes' => [
                'style' => 'width: 70%',
                'class' => ['img-fluid', 'mb-0'],
              ],
            ],
          ],
        ],
      ];

      // Build the action links
      $previousUrl = base64_encode(\Drupal::request()->getRequestUri());

      if ($element->uri != NULL && $element->uri != "") {
        // Encode the study URI
        $studyUriEncoded = base64_encode($element->uri);

        // Manage Elements link
        $manage_elements_str = base64_encode(Url::fromRoute('std.manage_study_elements', [
          'studyuri' => $studyUriEncoded,
        ])->toString());

        $manage_elements = Url::fromRoute('rep.back_url', [
          'previousurl' => $previousUrl,
          'currenturl' => $manage_elements_str,
          'currentroute' => 'std.manage_study_elements',
        ]);

        // View link
        $view_study_str = base64_encode(Url::fromRoute('rep.describe_element', [
          'elementuri' => $studyUriEncoded,
        ])->toString());

        $view_study = Url::fromRoute('rep.back_url', [
          'previousurl' => $previousUrl,
          'currenturl' => $view_study_str,
          'currentroute' => 'rep.describe_element',
        ]);

        // Edit link
        $edit_study_str = base64_encode(Url::fromRoute('std.edit_study', [
          'studyuri' => $studyUriEncoded,
        ])->toString());

        $edit_study = Url::fromRoute('rep.back_url', [
          'previousurl' => $previousUrl,
          'currenturl' => $edit_study_str,
          'currentroute' => 'std.edit_study',
        ]);

        // Delete link
        $delete_study = Url::fromRoute('rep.delete_element', [
          'elementtype' => 'study',
          'elementuri' => $studyUriEncoded,
          'currenturl' => $previousUrl,
        ]);
      }

      // Card footer with action links
      $card['card']['footer'] = [
        '#type' => 'container',
        '#attributes' => [
          'style' => 'margin-bottom:0!important;',
          'class' => ['card-footer', 'text-right', 'd-flex', 'justify-content-end'],
        ],
        'actions' => [
          'link1' => [
            '#type' => 'link',
            '#title' => Markup::create('<i class="fa-solid fa-folder-tree"></i> Manage Elements'),
            '#url' => $manage_elements,
            '#attributes' => [
              'class' => ['btn', 'btn-sm', 'btn-secondary', 'mx-1'],
            ],
          ],
          'link2' => [
            '#type' => 'link',
            '#title' => Markup::create('<i class="fa-solid fa-eye"></i> View'),
            '#url' => $view_study,
            '#attributes' => [
              'class' => ['btn', 'btn-sm', 'btn-secondary', 'mx-1'],
            ],
          ],
          'link3' => [
            '#type' => 'link',
            '#title' => Markup::create('<i class="fa-solid fa-pen-to-square"></i> Edit'),
            '#url' => $edit_study,
            '#attributes' => [
              'class' => ['btn', 'btn-sm', 'btn-secondary', 'mx-1'],
            ],
          ],
          'link4' => [
            '#type' => 'link',
            '#title' => Markup::create('<i class="fa-solid fa-trash-can"></i> Delete'),
            '#url' => $delete_study,
            '#attributes' => [
              'class' => ['btn', 'btn-sm', 'btn-danger', 'mx-1'],
              'onclick' => 'if(!confirm("Really Delete?")){return false;}',
            ],
          ],
        ],
      ];

      $cards[] = $card;
    }

    // Now build the cards into the form
    $index = 0;
    // Build cards in rows of 3
    foreach (array_chunk($cards, 3) as $row) {
      $index++;
      $form['row_' . $index] = [
        '#type' => 'container',
        '#attributes' => [
          'style' => 'margin-bottom:0!important;',
          'class' => ['row', 'mb-0'],
        ],
      ];
      $indexCard = 0;
      foreach ($row as $card) {
        $indexCard++;
        $form['row_' . $index]['element_' . $indexCard] = $card;
      }
    }
  }

  /**
   * Build the table view with the specified columns and action buttons.
   */
  protected function buildTableView(array &$form, FormStateInterface $form_state) {
    // Define the table header
    $header = [
      'uri' => ['data' => $this->t('URI')],
      'short_name' => ['data' => $this->t('Short Name')],
      'name' => ['data' => $this->t('Name')],
      'actions' => ['data' => $this->t('Actions')],
    ];

    // Build the table rows
    $rows = [];
    foreach ($this->getList() as $element) {
      $uri = $element->uri ?? '';
      $uri = Utils::namespaceUri($uri);
      $label = $element->label ?? '';
      $title = $element->title ?? '';

      // Selection checkbox
      // $row['select'] = [
      //   'data' => [
      //     '#type' => 'checkbox',
      //     '#name' => 'select[' . $uri . ']',
      //   ],
      // ];

      // URI as a link
      $encodedUri = base64_encode($uri);
      $uri_link = Link::fromTextAndUrl($uri, Url::fromUserInput(REPGUI::DESCRIBE_PAGE . $encodedUri))->toString();
      $row['uri'] = ['data' => ['#markup' => $uri_link]];

      // Short Name
      $row['short_name'] = $label;

      // Name
      $row['name'] = $title;

      // Actions
      $actions = [];

      // Build URLs for the links
      $previousUrl = base64_encode(\Drupal::request()->getRequestUri());
      $studyUriEncoded = base64_encode($element->uri);

      // Manage Elements link
      $manage_elements_str = base64_encode(Url::fromRoute('std.manage_study_elements', [
        'studyuri' => $studyUriEncoded,
      ])->toString());

      $manage_elements = Url::fromRoute('rep.back_url', [
        'previousurl' => $previousUrl,
        'currenturl' => $manage_elements_str,
        'currentroute' => 'std.manage_study_elements',
      ]);

      // View link
      $view_study_str = base64_encode(Url::fromRoute('rep.describe_element', [
        'elementuri' => $studyUriEncoded,
      ])->toString());

      $view_study = Url::fromRoute('rep.back_url', [
        'previousurl' => $previousUrl,
        'currenturl' => $view_study_str,
        'currentroute' => 'rep.describe_element',
      ]);

      // Edit link
      $edit_study_str = base64_encode(Url::fromRoute('std.edit_study', [
        'studyuri' => $studyUriEncoded,
      ])->toString());

      $edit_study = Url::fromRoute('rep.back_url', [
        'previousurl' => $previousUrl,
        'currenturl' => $edit_study_str,
        'currentroute' => 'std.edit_study',
      ]);

      // Delete link
      $delete_study = Url::fromRoute('rep.delete_element', [
        'elementtype' => 'study',
        'elementuri' => $studyUriEncoded,
        'currenturl' => $previousUrl,
      ]);

      // Actions
      $actions = [];

      // Manage Element link
      $actions['manage_element'] = [
        '#type' => 'link',
        '#title' => Markup::create('<i class="fa-solid fa-folder-tree"></i> Manage Elements'),
        '#url' => $manage_elements,
        '#attributes' => [
          'class' => ['btn', 'btn-primary', 'btn-sm'],
        ],
      ];

      // View link
      $actions['view'] = [
        '#type' => 'link',
        '#title' => Markup::create('<i class="fa-solid fa-eye"></i> View'),
        '#url' => $view_study,
        '#attributes' => [
          'class' => ['btn', 'btn-secondary', 'btn-sm', 'mx-1'],
        ],
      ];

      // Edit link
      $actions['edit'] = [
        '#type' => 'link',
        '#title' => Markup::create('<i class="fa-solid fa-pen-to-square"></i> Edit'),
        '#url' => $edit_study,
        '#attributes' => [
          'class' => ['btn', 'btn-warning', 'btn-sm'],
        ],
      ];

      // Delete link
      $actions['delete'] = [
        '#type' => 'link',
        '#title' => Markup::create('<i class="fa-solid fa-trash-can"></i> Delete'),
        '#url' => $delete_study,
        '#attributes' => [
          'class' => ['btn', 'btn-danger', 'btn-sm', 'delete-button', 'mx-1'],
          'onclick' => 'if(!confirm("Are you sure you want to delete this item?")){return false;}',
        ],
      ];

      $row['actions'] = [
        'data' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['action-buttons']],
          'buttons' => $actions,
        ],
      ];

      $rows[] = $row;
    }

    // Build the table
    $form['element_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No ' . $this->plural_class_name . ' found'),
    ];
  }

  /**
   * AJAX callback to load more cards.
   */
  public function loadMoreCallback(array &$form, FormStateInterface $form_state) {
    // Increment the page number
    $page = $form_state->get('page') + 1;
    $form_state->set('page', $page);

    // Load the next set of items
    $pagesize = $form_state->get('page_size') ?? 9;
    $new_items = ListManagerEmailPage::exec($this->element_type, $this->manager_email, $page, $pagesize);

    // Build the new cards
    $new_cards = [];
    $this->setList($new_items);
    $this->buildCardView($new_cards, $form_state);

    // Render the new cards
    $renderer = \Drupal::service('renderer');
    $rendered_cards = $renderer->renderRoot($new_cards);

    $response = new AjaxResponse();
    $response->addCommand(new AppendCommand('#cards-wrapper', $rendered_cards));

    // Get total number of items
    $total_items = $this->getListSize();

    // If there are no more items, hide the Load More button
    if ($total_items <= $page * $pagesize) {
      $response->addCommand(new CssCommand('.load-more-button', ['display' => 'none']));
    }

    return $response;
  }

  /**
   * Submit handler for switching to table view.
   */
  public function viewTableSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->set('view_type', 'table');
    $session = \Drupal::request()->getSession();
    $session->set('std_select_study_view_type', 'table');
    $form_state->setRebuild();
  }

  /**
   * Submit handler for switching to card view.
   */
  public function viewCardSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->set('view_type', 'card');
    $session = \Drupal::request()->getSession();
    $session->set('std_select_study_view_type', 'card');
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // RETRIEVE TRIGGERING BUTTON
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];

    // SET USER ID AND PREVIOUS URL FOR TRACKING STORE URLS
    $uid = \Drupal::currentUser()->id();
    $previousUrl = \Drupal::request()->getRequestUri();

    // Handle actions based on button name
    if ($button_name === 'add_element') {
      Utils::trackingStoreUrls($uid, $previousUrl, 'std.add_study');
      $url = Url::fromRoute('std.add_study');
      $form_state->setRedirectUrl($url);
    } elseif ($button_name === 'back') {
      $url = Url::fromRoute('std.search');
      $form_state->setRedirectUrl($url);
    } elseif ($button_name === 'edit_element') {
      // Handle editing selected elements in table view
      $this->handleEditSelected($form_state);
    } elseif ($button_name === 'delete_element') {
      // Handle deleting selected elements in table view
      $this->handleDeleteSelected($form_state);
    }
  }

  /**
   * Perform the edit action.
   */
  protected function performEdit($uri, FormStateInterface $form_state) {
    $uid = \Drupal::currentUser()->id();
    $previousUrl = \Drupal::request()->getRequestUri();
    Utils::trackingStoreUrls($uid, $previousUrl, 'std.edit_study');
    $url = Url::fromRoute('std.edit_study', [
      'studyuri' => base64_encode($uri),
    ]);
    $form_state->setRedirectUrl($url);
  }

  /**
   * Perform the delete action.
   */
  protected function performDelete(array $uris, FormStateInterface $form_state) {
    $api = \Drupal::service('rep.api_connector');
    foreach ($uris as $uri) {
      $study = $api->parseObjectResponse($api->getUri($uri), 'getUri');
      if ($study != NULL && $study->hasDataFile != NULL) {

        // DELETE FILE
        if (isset($study->hasDataFile->id)) {
          $file = \Drupal\file\Entity\File::load($study->hasDataFile->id);
          if ($file) {
            $file->delete();
            \Drupal::messenger()->addMessage($this->t('File with ID @id deleted.', ['@id' => $study->hasDataFile->id]));
          }
        }

        // DELETE DATAFILE
        if (isset($study->hasDataFile->uri)) {
          $api->dataFileDel($study->hasDataFile->uri);
          \Drupal::messenger()->addMessage($this->t('DataFile with URI @uri deleted.', ['@uri' => $study->hasDataFile->uri]));
        }
      }
    }
    \Drupal::messenger()->addMessage($this->t('Selected ' . $this->plural_class_name . ' have been successfully deleted.'));
    $form_state->setRebuild();
  }

  /**
   * Handle editing selected elements in table view.
   */
  protected function handleEditSelected(FormStateInterface $form_state) {
    $selected_rows = $form_state->getUserInput()['select'] ?? [];
    $selected_uris = array_keys(array_filter($selected_rows));
    if (count($selected_uris) < 1) {
      \Drupal::messenger()->addWarning($this->t('Select exactly one ' . $this->single_class_name . ' to be edited.'));
    } elseif (count($selected_uris) > 1) {
      \Drupal::messenger()->addWarning($this->t('No more than one ' . $this->single_class_name . ' can be edited at once.'));
    } else {
      $uri = array_shift($selected_uris);
      $this->performEdit($uri, $form_state);
    }
  }

  /**
   * Handle deleting selected elements in table view.
   */
  protected function handleDeleteSelected(FormStateInterface $form_state) {
    $selected_rows = $form_state->getUserInput()['select'] ?? [];
    $selected_uris = array_keys(array_filter($selected_rows));
    if (count($selected_uris) <= 0) {
      \Drupal::messenger()->addWarning($this->t('At least one ' . $this->single_class_name . ' needs to be selected to be deleted.'));
    } else {
      $this->performDelete($selected_uris, $form_state);
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
