<?php

namespace Drupal\std\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'STDSearchBlock' block.
 *
 * @Block(
 *  id = "std_search_block",
 *  admin_label = @Translation("Search Studies Criteria"),
 *  category = @Translation("Search Studies Criteria")
 * )
 */
class STDSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\std\Form\STDSearchForm');

    return $form;
  }

}
