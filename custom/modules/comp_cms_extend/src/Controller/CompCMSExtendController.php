<?php

namespace Drupal\comp_cms_extend\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Routing controller for comp_cms_extend.
 */
class CompCMSExtendController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * For services dependency injection.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * For services dependency injection.
   *
   * @var Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Class constructor.
   *
   * @param Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   For services dependency injection.
   * @param Drupal\Core\Form\FormBuilder $form_builder
   *   For services dependency injection.
   */
  public function __construct(
    EntityTypeManager $entity_type_manager,
    FormBuilder $form_builder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * Object create method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container interface.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function addCompSubject($cid) {
    $node = $this->entityTypeManager
      ->getStorage('node')
      ->create([
        'type' => 'subject',
        'field_composite' => $cid,
      ]);

    $form = $this->entityTypeManager
      ->getFormObject('node', 'default')
      ->setEntity($node);

    return $this->formBuilder->getForm($form);
  }

  /**
   * {@inheritdoc}
   */
  public function onlyComposites($node) {
    // Grants custom access to composite nodes only.
    return AccessResult::allowedIf(
      $this->entityTypeManager->getStorage('node')->load($node)->bundle() == 'composite');
  }

}
