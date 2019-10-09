<?php


namespace Drupal\llom_scoring\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Score entity.
 *
 * @ingroup llom_scoring
 *
 * @ContentEntityType(
 *   id = "llom_scoring",
 *   label = @Translation("Score"),
 *   base_table = "llom_scoring",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */

class Score extends ContentEntityBase implements ContentEntityInterface {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Score entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Score entity.'))
      ->setReadOnly(TRUE);

    //entity reference to the user
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the Score'))
      ->setSettings(array(
        'target_type' => 'user',
        'default_value' => 0,
      ));

    //entity reference to the parent node
    $fields['nid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent Node ID'))
      ->setDescription(t('The ID of the parent node'))
      ->setSettings(array(
        'target_type' => 'node',
        'default_value' => 0,
      ));

    // The obtained score
    $fields['score'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t("Score"))
      ->setDescription(t('The obtained score'))
      ->setSettings(array(
        'precision' => 10,
        'scale' => 1,
      ));

    // The max score of the question
    $fields['max_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t("Max Score"))
      ->setDescription(t('The Max Score for the assignment'));

    // timestamp of the score
    $fields['time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t("Timestamp"))
      ->setDescription(t('Timestamp of the score'));

    return $fields;
  }

}
