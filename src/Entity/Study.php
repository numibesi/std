<?php

namespace Drupal\std\Entity;

use Drupal\Core\Url;
use Drupal\rep\Vocabulary\REPGUI;
use Drupal\rep\Utils;


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

    //dpm($list);

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

    foreach ($list as $element) {
        $uri = $element->uri ?? '';
        $label = $element->label ?? '';
        $title = $element->title ?? '';

        $urlComponents = parse_url($uri);

        if (isset($urlComponents['scheme']) && isset($urlComponents['host'])) {
          $url = Url::fromUri($uri);
        } else { 
          $url = '';
        }

        $manage_elements = Url::fromRoute('std.manage_study', ['studyuri' => base64_encode($element->uri)]);
        $view_study = Url::fromRoute('rep.describe_element', ['elementuri' => base64_encode($element->uri)]);
        $edit_study = Url::fromRoute('std.edit_study', ['studyuri' => base64_encode($element->uri)]);
        $delete_study = $root_url.REPGUI::DATAFILE_LOG.base64_encode($element->uri);

        $output[] = [
          '#type' => 'container', // Use container instead of html_tag for better semantics
          '#attributes' => [
              'class' => ['card', 'mb-3'],
          ],
          'card_body' => [
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
              'link1' => [
                '#type' => 'link',
                '#title' => 'Manage Elements',
                '#url' => $manage_elements, 
                '#attributes' => [
                    'class' => ['btn', 'btn-secondary'],
                    'style' => 'margin-right: 10px;',
                ],
              ],
              'link2' => [
                '#type' => 'link',
                '#title' => 'View',
                '#url' => $view_study, 
                '#attributes' => [
                    'class' => ['btn', 'btn-secondary'],
                    'style' => 'margin-right: 10px;',
                  ],
              ],
              'link3' => [
                '#type' => 'link',
                '#title' => 'Edit',
                '#url' => $edit_study, 
                '#attributes' => [
                    'class' => ['btn', 'btn-secondary'],
                    'style' => 'margin-right: 10px;',
                  ],
              ],
              'link4' => [
                '#type' => 'link',
                '#title' => 'Delete',
                '#url' => $url, 
                '#attributes' => [
                    'class' => ['btn', 'btn-secondary'],
                  ],
              ],
        ],
      ];
    
    }

    return $output;
  }

}