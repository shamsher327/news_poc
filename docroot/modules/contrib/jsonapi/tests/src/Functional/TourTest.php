<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Url;
use Drupal\tour\Entity\Tour;

/**
 * JSON API integration test for the "Tour" config entity type.
 *
 * @group jsonapi
 */
class TourTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['tour'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'tour';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'tour--tour';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\tour\TourInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['access tour']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $tour = Tour::create([
      'id' => 'tour-llama',
      'label' => 'Llama tour',
      'langcode' => 'en',
      'module' => 'tour',
      'routes' => [
        [
          'route_name' => '<front>',
        ],
      ],
      'tips' => [
        'tour-llama-1' => [
          'id' => 'tour-llama-1',
          'plugin' => 'text',
          'label' => 'Llama',
          'body' => 'Who handle the awesomeness of llamas?',
          'weight' => 100,
          'attributes' => [
            'data-id' => 'tour-llama-1',
          ],
        ],
      ],
    ]);
    $tour->save();

    return $tour;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/tour/tour/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'tour--tour',
        'links' => [
          'self' => $self_url,
        ],
        'attributes' => [
          'dependencies' => [],
          'id' => 'tour-llama',
          'label' => 'Llama tour',
          'langcode' => 'en',
          'module' => 'tour',
          'routes' => [
            [
              'route_name' => '<front>',
            ],
          ],
          'status' => TRUE,
          'tips' => [
            'tour-llama-1' => [
              'id' => 'tour-llama-1',
              'plugin' => 'text',
              'label' => 'Llama',
              'body' => 'Who handle the awesomeness of llamas?',
              'weight' => 100,
              'attributes' => [
                'data-id' => 'tour-llama-1',
              ],
            ],
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

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return "The following permissions are required: 'access tour' OR 'administer site configuration'.";
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
