<?php

namespace Drupal\std\Entity;

use Drupal\Core\Url;
use Drupal\rep\Vocabulary\REPGUI;
use Drupal\rep\Utils;
use Drupal\Core\Render\Markup;

class Study {

  public static function generateHeader() {

    return $header = [
      'element_uri' => t('URI'),
      'element_short_name' => t('Short Name'),
      'element_name' => t('Name'),
      'element_n_roles' => t('# Roles'),
      'element_n_vcs' => t('# Virt.Columns'),
      'element_n_socs' => t('# SOCs'),
    ];

  }

  public static function generateOutput($list) {

    // ROOT URL
    $root_url = \Drupal::request()->getBaseUrl();

    $output = array();
    if ($list == NULL) {
      return $output;
    }
    foreach ($list as $element) {
      $uri = ' ';
      if ($element->uri != NULL) {
        $uri = $element->uri;
      }
      $uri = Utils::namespaceUri($uri);
      $label = ' ';
      if ($element->label != NULL) {
        $label = $element->label;
      }
      $title = ' ';
      if ($element->title != NULL) {
        $title = $element->title;
      }
      $root_url = \Drupal::request()->getBaseUrl();
      $encodedUri = rawurlencode(rawurlencode($element->uri));
      $output[$element->uri] = [
        'element_uri' => t('<a href="'.$root_url.REPGUI::DESCRIBE_PAGE.base64_encode($uri).'">'.$uri.'</a>'),
        'element_short_name' => t($label),
        'element_name' => t($title),
        'element_n_roles' => $element->totalStudyRoles,
        'element_n_vcs' => $element->totalVirtualColumns,
        'element_n_socs' => $element->totalStudyObjectCollections,
        ];
    }
    return $output;
  }

  public static function generateOutputAsCard($list) {
    $output = [];

    // ROOT URL
    $root_url = \Drupal::request()->getBaseUrl();

    if ($list == NULL) {
        return $output;
    }

    //foreach ($output as $uri => $card) {
    //    $form['element_' . $index] = $card;
    //}

    $index = 0;
    foreach ($list as $element) {
      $index++;
      $uri = $element->uri ?? '';
      $label = $element->label ?? '';
      $title = $element->title ?? '';

      $urlComponents = parse_url($uri);

      if (isset($urlComponents['scheme']) && isset($urlComponents['host'])) {
        $url = Url::fromUri($uri);
      } else {
        $url = '';
      }

      if ($element->uri != NULL && $element->uri != "") {
        $previousUrl = base64_encode(\Drupal::request()->getRequestUri());

        $manage_elements_str = base64_encode(Url::fromRoute('std.manage_study_elements', ['studyuri' => base64_encode($element->uri)])->toString());
        $manage_elements = Url::fromRoute('rep.back_url', [
          'previousurl' => $previousUrl,
          'currenturl' => $manage_elements_str,
          'currentroute' => 'std.manage_study_elements'
        ]);

        $view_study_str = base64_encode(Url::fromRoute('rep.describe_element', ['elementuri' => base64_encode($element->uri)])->toString());
        $view_study_route = 'rep.describe_element';
        $view_study = Url::fromRoute('rep.back_url', [
          'previousurl' => $previousUrl,
          'currenturl' => $view_study_str,
          'currentroute' => 'rep.describe_element'
        ]);

        $edit_study_str = base64_encode(Url::fromRoute('std.edit_study', ['studyuri' => base64_encode($element->uri)])->toString());
        $edit_study = Url::fromRoute('rep.back_url', [
          'previousurl' => $previousUrl,
          'currenturl' => $edit_study_str,
          'currentroute' => 'std.edit_study'
        ]);

        $delete_study = Url::fromRoute('rep.delete_element', [
          'elementtype' => 'study',
          'elementuri' => base64_encode($element->uri),
          'currenturl' => $previousUrl,
        ]);
      }

      $output[$index] = [
        '#type' => 'container', // Use container instead of html_tag for better semantics
        '#attributes' => [
            'class' => ['card', 'mb-3'],
        ],
        '#prefix' => '<div class="col-md-6">',
        '#suffix' => '</div>',
        'card_body_'.$index => [
            '#type' => 'container', // Use container for the card body
            '#attributes' => [
                'class' => ['card-body'],
            ],
            'title' => [
                '#markup' => '<h5 class="card-title">' . $label . '</h5>',
            ],
            'text' => [
                '#markup' => '<p class="card-text">'. $title . '<br>' . $uri . '</p>',
            ],
            'link1_'.$index   => [
              '#type' => 'link',
              '#title' => Markup::create('<i class="fa-solid fa-folder-tree"></i> Manage Elements'),
              '#url' => $manage_elements,
              '#attributes' => [
                  'class' => ['btn', 'btn-sm', 'btn-secondary'],
                  'style' => 'margin-right: 10px;',
              ],
            ],
            'link2_'.$index => [
              '#type' => 'link',
              '#title' => Markup::create('<i class="fa-solid fa-eye"></i> View'),
              '#url' => $view_study,
              '#attributes' => [
                  'class' => ['btn', 'btn-sm', 'btn-secondary'],
                  'style' => 'margin-right: 10px;',
              ],
            ],
            'link3_'.$index => [
              '#type' => 'link',
              '#title' => Markup::create('<i class="fa-solid fa-pen-to-square"></i> Edit'),
              '#url' => $edit_study,
              '#attributes' => [
                  'class' => ['btn', 'btn-sm', 'btn-secondary'],
                  'style' => 'margin-right: 10px;',
              ],
            ],
            'link4_'.$index => [
              '#type' => 'link',
              '#title' => Markup::create('<i class="fa-solid fa-trash-can"></i> Delete'),
              '#url' => $delete_study,
              '#attributes' => [
                'onclick' => 'if(!confirm("Really Delete?")){return false;}',
                'class' => ['btn', 'btn-sm', 'btn-secondary', 'btn-danger'],
              ],
            ],
        ],
      ];

    }

    return $output;
  }


}
