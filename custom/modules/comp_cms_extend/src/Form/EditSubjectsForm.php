<?php

namespace Drupal\comp_cms_extend\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;
use Drupal\Core\Url;

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

    // List current subjects.
    $view = Views::getView('edit_composite_subjects');
    $view->setDisplay('block_1');
    $view->setArguments([$node]);
    $render = $view->render();
    $form['edit_composite_subjects_view'] = $render;

    // Add new subject.
    $form['add_subject_button'] = [
      '#type' => 'link',
      '#title' => t('Add New Subject'),
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
