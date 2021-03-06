<?php

namespace Drupal\comp_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a custom block for site branding.
 *
 * @Block(
 *   id = "comp_branding",
 *   admin_label = @Translation("Custom Branding"),
 *   category = @Translation("Misc"),
 * )
 */
class CompBranding extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $text = '
      <div class="block-system-branding-block mt-2 mb-2">
        <h1 class="mb-0">UNB Class Composite & Group Photographs</h1>
      </div>
    ';

    return [
      '#markup' => $this->t($text),
    ];
  }

}
