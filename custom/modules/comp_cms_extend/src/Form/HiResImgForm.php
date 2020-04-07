<?php

namespace Drupal\comp_cms_extend\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * EditSubjectsForm class.
 */
class HiResImgForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'comp_cms_extend_hi_res_img_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $form = [];

    // Generate dynamic title.
    $composite = Node::load($node);
    $comp_title = $composite->getTitle();
    $form['#title'] = "High Resolution Image for $comp_title";

    // OpenSeadragon viewer div.
    $form['sample_view']['zoom'] = [
      '#markup' => '<div id="seadragon-viewer"></div>',
    ];

    $form['#attached'] = [
      'library' => [
        'comp_cms_extend/openseadragon',
        'comp_cms_extend/openseadragon_viewer',
      ],
      'drupalSettings' => [
        'comp_cms_extend' => [
          'dzi_filepath' => "/sites/default/files/comp_images/dzi/composite_$node.dzi",
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
