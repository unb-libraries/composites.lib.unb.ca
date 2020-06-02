<?php

namespace Drupal\comp_core\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds additional citation information to a search api reference.
 *
 * @SearchApiProcessor(
 *   id = "subject_index_extension",
 *   label = @Translation("Additional Subject Fields"),
 *   description = @Translation("Add fulltext valedictorian, etc."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SubjectIndexExtension extends ProcessorPluginBase {

  /**
   * Only enabled for an index that indexes nodes.
   *
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource == 'node') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Fulltext Valedictorian'),
        'description' => $this->t('Adds "valedictorian" if the corresponding flag is checked.'),
        'type' => 'string',
        'is_list' => TRUE,
        'processor_id' => $this->getPluginId(),
      ];
      $properties['fulltext_valedictorian'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $entity = $item->getDatasource();
    if ($entity->getEntityTypeId() == 'node') {
      $subject_entity = $item->getOriginalObject()->getValue();

      if ($subject_entity->bundle() == 'subject') {

        // The fulltext valedictorian field.
        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($item->getFields(), NULL, 'fulltext_valedictorian');

        foreach ($fields as $field) {
          $valedictorian =
            $subject_entity->get('field_valedictorian')->getValue()[0]['value'];

          // If valedictorian flag is set, add 'valedictorian' string to field.
          if ($valedictorian) {
            $field->addValue(
              'valedictorian'
            );
          }
        }
      }
    }
  }

}
