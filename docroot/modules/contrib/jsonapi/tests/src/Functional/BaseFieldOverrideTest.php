<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;

/**
 * JSON API integration test for the "BaseFieldOverride" config entity type.
 *
 * @group jsonapi
 */
class BaseFieldOverrideTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'node'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'base_field_override';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'base_field_override--base_field_override';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\Core\Field\Entity\BaseFieldOverride
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer node fields']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $camelids = NodeType::create([
      'name' => 'Camelids',
      'type' => 'camelids',
    ]);
    $camelids->save();

    $entity = BaseFieldOverride::create([
      'field_name' => 'promote',
      'entity_type' => 'node',
      'bundle' => 'camelids',
    ]);
    $entity->save();

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/base_field_override/base_field_override/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => 'http://jsonapi.org/format/1.0/',
          ],
        ],
        'version' => '1.0',
      ],
      'links' => [
        'self' => $self_url,
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'base_field_override--base_field_override',
        'links' => [
          'self' => $self_url,
        ],
        'attributes' => [
          'bundle' => 'camelids',
          'default_value' => [],
          'default_value_callback' => '',
          'dependencies' => [
            'config' => [
              'node.type.camelids',
            ],
          ],
          'description' => '',
          'entity_type' => 'node',
          'field_name' => 'promote',
          'field_type' => 'boolean',
          'id' => 'node.camelids.promote',
          'label' => NULL,
          'langcode' => 'en',
          'required' => FALSE,
          'settings' => [
            'on_label' => 'On',
            'off_label' => 'Off',
          ],
          'status' => TRUE,
          'translatable' => TRUE,
          'uuid' => $this->entity->uuid(),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

  // @codingStandardsIgnoreStart
  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    // @todo Uncomment first line, remove second line in https://www.drupal.org/project/jsonapi/issues/2940342.
//    return ['user.permissions'];
    return parent::getExpectedCacheContexts();
  }
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return "The 'administer node fields' permission is required.";
  }

}
