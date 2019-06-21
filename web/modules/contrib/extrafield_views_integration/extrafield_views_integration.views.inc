<?php

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityTypeBundleInfo;

/**
 * Implements hook_views_data_alter().
 *
 * @param array $data
 */
function extrafield_views_integration_views_data_alter(array &$data)
{
    /** @var EntityFieldManager $fieldManager */
    $fieldManager = Drupal::getContainer()->get('entity_field.manager');
    /** @var EntityTypeBundleInfo $bundleInfo */
    $bundleInfo = Drupal::getContainer()->get('entity_type.bundle.info');
    $entityTypes = Drupal::entityTypeManager()->getDefinitions();
    /** @var EntityType $type */
    foreach ($entityTypes as $entityType) {
        if ($entityType->getGroup() == "content") {
            $bundles = $bundleInfo->getBundleInfo($entityType->id());
            foreach ($bundles as $bundleType => $bundle) {
                $extraFields = $fieldManager->getExtraFields($entityType->id(), $bundleType);
                if (!empty($extraFields['display'])) {
                    foreach ($extraFields['display'] as $extraFieldName => $extraField) {
                        if (isset($extraField['render_class'])) {
                            $name = 'extrafield_views_integration__' . $extraFieldName;
                            $data[$entityType->id()][$name] = array(
                                'title' => t('Extrafield @label', array('@label' => $extraField['label'])),
                                'help' => t('Integrate Extrafield: @label for entity type: @entityType', array(
                                    '@label' => $extraField['label'],
                                    '@entity_type' => $entityType->getLabel(),
                                )),
                                'field' => array(
                                    'title' => t('Extrafield @label', array('@label' => $extraField['label'])),
                                    'help' => $extraField['description'],
                                    'id' => 'extrafield_views_integration',
                                    'render_class' => $extraField['render_class'],
                                ),
                            );
                        }
                    }
                }
            }
        }
    }
}