<?php

namespace Drupal\comp_cms_extend\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * EditSubjectsForm class.
 */
class EditSubjectsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'comp_cms_extend_edit_subjects_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $form = [];

    // Generate dynamic title.
    $composite = Node::load($node);
    $comp_title = $composite->getTitle();
    $form['#title'] = "Edit People in $comp_title";

    // List current subjects.
    $view = Views::getView('edit_composite_subjects');
    $view->setDisplay('block_1');
    $view->setArguments([$node]);
    $render = $view->render();
    $form['edit_composite_subjects_view'] = $render;

    // Add new subject button.
    $url = Url::fromRoute('comp_cms_extend.add_comp_subject', ['cid' => $node]);
    $url_str = $url->toString();

    $form['add_subject_button'] = [
      '#type' => 'item',
      '#markup' =>
        "<a class='btn btn-primary' href=$url_str>
            <i class='fa fa-plus mr-2'></i>
            <span>Add New Person</span>
          </a>",
      '#url' => Url::fromRoute('comp_cms_extend.add_comp_subject', ['cid' => $node]),
      '#attributes' => [
        'id' => ['add-subject-link'],
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
