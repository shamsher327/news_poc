<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\rdf\Entity\RdfMapping;

/**
 * JSON API integration test for the "RdfMapping" config entity type.
 *
 * @group jsonapi
 */
class RdfMappingTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'rdf'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'rdf_mapping';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'rdf_mapping--rdf_mapping';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\rdf\RdfMappingInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer site configuration']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" node type.
    $camelids = NodeType::create([
      'name' => 'Camelids',
      'type' => 'camelids',
    ]);

    $camelids->save();

    // Create the RDF mapping.
    $llama = RdfMapping::create([
      'targetEntityType' => 'node',
      'bundle' => 'camelids',
    ]);
    $llama->setBundleMapping([
      'types' => ['sioc:Item', 'foaf:Document'],
    ])
      ->setFieldMapping('title', [
        'properties' => ['dc:title'],
      ])
      ->setFieldMapping('created', [
        'properties' => ['dc:date', 'dc:created'],
        'datatype' => 'xsd:dateTime',
        'datatype_callback' => ['callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value'],
      ])
      ->save();

    return $llama;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/rdf_mapping/rdf_mapping/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'rdf_mapping--rdf_mapping',
        'links' => [
          'self' => $self_url,
        ],
        'attributes' => [
          'bundle' => 'camelids',
          'dependencies' => [
            'config' => [
              'node.type.camelids',
            ],
            'module' => [
              'node',
            ],
          ],
          'fieldMappings' => [
            'title' => [
              'properties' => [
                'dc:title',
              ],
            ],
            'created' => [
              'properties' => [
                'dc:date',
                'dc:created',
              ],
              'datatype' => 'xsd:dateTime',
              'datatype_callback' => [
                'callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value',
              ],
            ],
          ],
          'id' => 'node.camelids',
          'langcode' => 'en',
          'status' => TRUE,
          'targetEntityType' => 'node',
          'types' => [
            'sioc:Item',
            'foaf:Document',
          ],
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

}
