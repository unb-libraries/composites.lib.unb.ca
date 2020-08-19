<?php

namespace Drupal\comp_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a custom block for site branding.
 *
 * @Block(
 *   id = "comp_branding_anchor",
 *   admin_label = @Translation("Custom Branding Anchor"),
 *   category = @Translation("Misc"),
 * )
 */
class CompBrandingAnchor extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $text = '
      <div class="block-system-branding-block mt-2 mb-2">
        <a href="/" class="pt-0 pb-0">
          UNB Class Composite & Group Photographs
        </a>
      </div>
    ';

    return [
      '#markup' => $this->t($text),
    ];
  }

}
