<?php

namespace Drupal\like_and_dislike\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Entity\Comment;
use Drupal\comment\CommentInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;

/**
 * Provides a web test for like_and_dislike module.
 *
 * Tests that visibility of the like and dislike extra field can be properly
 * changed using the settings form and view mode configuration page. Also test
 * that voting works properly (that likes and dislikes are correctly considered
 * and displayed, for different users, and that vote cancellation can be
 * enabled or disabled).
 *
 * @group like_and_dislike
 */
class LikeAndDislikeTest extends WebTestBase {

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['like_and_dislike', 'node', 'field_ui', 'comment'];

  /**
   * A test user with administration permissions.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a node type.
    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_type->save();

    // Create a comment type.
    $comment_type = CommentType::create([
      'id' => 'comment',
      'label' => 'Default comments',
      'description' => 'Default comment field',
      'target_entity_type_id' => 'article',
    ]);
    $comment_type->save();

    $this->addDefaultCommentField('node', 'article', 'test_comment_field', CommentItemInterface::OPEN, 'test_comment_type');

    // Create a user with admin permissions and login as this user.
    $admin_permissions = [
      'administer like and dislike',
      'administer node display',
      'administer user display',
      'administer comment display',
      'administer display modes',
      'access comments',
      'administer permissions',
    ];
    $this->adminUser = $this->drupalCreateUser($admin_permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests likes visibility.
   *
   * Test that visibility of likes an dislikes can be correctly changed using
   * the settings form and the extra field visibility setting.
   */
  public function testVisibility() {
    // Create a node.
    $node = Node::create([
      'title' => 'Test node title',
      'type' => 'article',
    ]);
    $node->save();

    // Enable custom display settings for the teaser view mode.
    $edit = [
      'display_modes_custom[teaser]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/display', $edit, t('Save'));
    $this->assertText('Your settings have been saved.');

    // Enable like an dislike for our article node type, disable for our
    // comment type and enable vote cancellation.
    $edit = [
      'enabled_types[node][enabled]' => TRUE,
      'enabled_types[node][bundle_info][bundles][article]' => TRUE,
      'enabled_types[comment][enabled]' => TRUE,
      'enabled_types[comment][bundle_info][bundles][test_comment_type]' => TRUE,
      'allow_cancel_vote' => TRUE,
      'hide_vote_widget' => FALSE,
    ];
    $this->drupalPostForm('admin/config/search/votingapi/like_and_dislike', $edit, t('Save configuration'));
    $this->assertText('The configuration options have been saved.');
    $this->assertFieldChecked('edit-enabled-types-node-enabled');
    $this->assertFieldChecked('edit-enabled-types-comment-enabled');
    $this->assertFieldChecked('edit-enabled-types-node-bundle-info-bundles-article');
    $this->assertNoFieldChecked('edit-enabled-types-comment-bundle-info-bundles-comment');
    $this->assertFieldChecked('edit-enabled-types-comment-bundle-info-bundles-test-comment-type');
    $this->assertFieldChecked('edit-allow-cancel-vote');
    $this->assertNoFieldChecked('edit-hide-vote-widget');

    // Verify there are new like and dislike permissions.
    $this->drupalGet('admin/people/permissions');
    $this->assertText('Content (Article): add/remove Like vote');
    $this->assertText('Content (Article): add/remove Dislike vote');
    $this->assertNoText('Comment (Default comments): add/remove Like vote');
    $this->assertNoText('Comment (Default comments): add/remove Dislike vote');
    $this->assertText('Comment (Test_comment_type): add/remove Like vote');
    $this->assertText('Comment (Test_comment_type): add/remove Dislike vote');

    // Update the user with like and dislike permissions.
    $user_roles = $this->adminUser->getRoles();
    $user_role = end($user_roles);
    $edit = [
      $user_role . '[add or remove like votes on article of node]' => TRUE,
      $user_role . '[add or remove dislike votes on article of node]' => TRUE,
      $user_role . '[add or remove like votes on test_comment_type of comment]' => TRUE,
      $user_role . '[add or remove dislike votes on test_comment_type of comment]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save permissions');

    // Verify that like and dislike field is showing up as a field for default
    // view mode and that it is disabled by default.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertText('Like and dislike');
    $this->assertNoOptionSelected('edit-fields-like-and-dislike-type', 'visible');
    // Same for teaser view mode.
    $this->drupalGet('admin/structure/types/manage/article/display/teaser');
    $this->assertText('Like and dislike');
    $this->assertNoOptionSelected('edit-fields-like-and-dislike-type', 'visible');

    // Toggle on visibility of the extra field for default view mode.
    $this->drupalPostForm('admin/structure/types/manage/article/display', ['fields[like_and_dislike][region]' => 'content'], t('Save'));
    $this->assertText('Your settings have been saved.');
    $this->assertOptionSelected('edit-fields-like-and-dislike-type', 'visible');

    // Verify that like and dislike are properly displayed as links.
    $node_id = $node->id();
    $this->drupalGet('node/' . $node_id);
    $this->assertLikesAndDislikes('node', $node_id);

    // Verify that like and dislike aren't showing up on teaser view mode.
    $teaser_render_array = $this->drupalBuildEntityView($node, 'teaser');
    $this->assertFalse(isset($teaser_render_array['like_and_dislike']));

    // Toggle off visibility of like and dislike for default view mode and on
    // for teaser mode, for nodes.
    $this->drupalPostForm('admin/structure/types/manage/article/display', ['fields[like_and_dislike][region]' => 'hidden'], t('Save'));
    $this->assertText('Your settings have been saved.');
    $this->assertNoOptionSelected('edit-fields-like-and-dislike-type', 'visible');
    $this->drupalPostForm('admin/structure/types/manage/article/display/teaser', ['fields[like_and_dislike][region]' => 'content'], t('Save'));
    $this->assertText('Your settings have been saved.');
    $this->assertOptionSelected('edit-fields-like-and-dislike-type', 'visible');

    // Verify that like and dislike are no longer showing up on default view
    // mode.
    $this->drupalGet('node/' . $node_id);
    $this->assertNoText('Like');
    $this->assertNoText('Dislike');

    // Verify that like and dislike are now showing on teaser view mode.
    $teaser_render_array = $this->drupalBuildEntityView($node, 'teaser');
    $this->assertTrue(isset($teaser_render_array['like_and_dislike']));

    // Add a comment to this node.
    $comment = Comment::create([
      'subject' => 'Test subject',
      'comment_body' => 'Test body',
      'entity_id' => $node_id,
      'entity_type' => 'node',
      'node_type' => 'article',
      'field_name' => 'test_comment_field',
      'status' => CommentInterface::PUBLISHED,
      'uid' => $this->adminUser->id(),
    ]);
    $comment->save();
    $comment_id = $comment->id();

    // Verify that like and dislike are not showing up for the comment.
    $this->drupalGet('node/' . $node_id);
    $this->assertNoText('Like');
    $this->assertNoText('Dislike');

    // Disable like and dislike for nodes and enable for comments.
    $edit = [
      'enabled_types[node][enabled]' => FALSE,
      'enabled_types[comment][enabled]' => TRUE,
      'enabled_types[comment][bundle_info][bundles][test_comment_type]' => TRUE,
      'allow_cancel_vote' => TRUE,
    ];
    $this->drupalPostForm('admin/config/search/votingapi/like_and_dislike', $edit, t('Save configuration'));
    $this->assertText('The configuration options have been saved.');

    // Verify that like and dislike are no longer showing up for nodes.
    $teaser_render_array = $this->drupalBuildEntityView($node, 'teaser');
    $this->assertFalse(isset($teaser_render_array['like_and_dislike']));

    // Verify that like an dislike are not showing up for comments yet.
    $this->drupalGet('node/' . $node_id);
    $this->assertNoText('Like');
    $this->assertNoText('Dislike');

    // Toggle on visibility of like and dislike for the default view mode for
    // comments.
    $this->drupalPostForm('admin/structure/comment/manage/test_comment_type/display', ['fields[like_and_dislike][region]' => 'content'], t('Save'));
    $this->assertText('Your settings have been saved.');

    // Verify that like and dislike are now showing for the comment.
    $this->drupalGet('node/' . $node_id);
    $this->assertLikesAndDislikes('comment', $comment_id);

    // Enable and toggle on visibility of like and dislike for both nodes and
    // comments.
    $edit = [
      'enabled_types[node][enabled]' => TRUE,
      'enabled_types[node][bundle_info][bundles][article]' => TRUE,
      'allow_cancel_vote' => TRUE,
    ];
    $this->drupalPostForm('admin/config/search/votingapi/like_and_dislike', $edit, t('Save configuration'));
    $this->assertText('The configuration options have been saved.');
    $this->drupalPostForm('admin/structure/types/manage/article/display', ['fields[like_and_dislike][region]' => 'content'], t('Save'));
    $this->assertText('Your settings have been saved.');

    // Verify that both are showing up on the default view mode.
    $this->drupalGet('node/' . $node_id);
    $this->assertLikesAndDislikes('node', $node_id);
    $this->assertLikesAndDislikes('comment', $comment_id);

    // Turn on hide vote widget permission.
    $edit = [
      'hide_vote_widget' => TRUE,
    ];
    $this->drupalPostForm('admin/config/search/votingapi/like_and_dislike', $edit, t('Save configuration'));
    $this->assertText('The configuration options have been saved.');
    $this->assertFieldChecked('edit-hide-vote-widget');

    // Turn off dislike permission for node and comment.
    $this->drupalGet('admin/people/permissions');
    $edit = [
      $user_role . '[add or remove dislike votes on article of node]' => FALSE,
      $user_role . '[add or remove dislike votes on test_comment_type of comment]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save permissions');

    // Verify that dislike icon is not showed in default view mode.
    $this->drupalGet('node/' . $node_id);
    $this->assertVotingIconExistence('node', $node_id, 'like', TRUE);
    $this->assertVotingIconExistence('node', $node_id, 'dislike', FALSE);
    $this->assertVotingIconExistence('comment', $comment_id, 'like', TRUE);
    $this->assertVotingIconExistence('comment', $comment_id, 'dislike', FALSE);

    // Turn off like permission for node and comment.
    $this->drupalGet('admin/people/permissions');
    $edit = [
      $user_role . '[add or remove like votes on article of node]' => FALSE,
      $user_role . '[add or remove like votes on test_comment_type of comment]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save permissions');

    // Verify that both like and dislike icons are not showed in default view mode.
    $this->drupalGet('node/' . $node_id);
    $this->assertVotingIconExistence('node', $node_id, 'like', FALSE);
    $this->assertVotingIconExistence('node', $node_id, 'dislike', FALSE);
    $this->assertVotingIconExistence('node', $comment_id, 'dislike', FALSE);
    $this->assertVotingIconExistence('comment', $comment_id, 'dislike', FALSE);
  }

  /**
   * Asserts likes and dislikes for users.
   */
  public function testUserEntity() {
    // Enable likes and dislikes for users.
    $edit = [
      'enabled_types[user][enabled]' => TRUE,
      'allow_cancel_vote' => TRUE,
    ];
    $this->drupalPostForm('admin/config/search/votingapi/like_and_dislike', $edit, t('Save configuration'));
    $this->assertText('The configuration options have been saved.');
    $this->assertFieldChecked('edit-enabled-types-user-enabled');

    // Make "like and dislike" component visible.
    $edit = ['fields[like_and_dislike][region]' => 'content'];
    $this->drupalPostForm('admin/config/people/accounts/display', $edit, 'Save');

    // Go to user profile.
    $user_id = $this->adminUser->id();
    $this->drupalGet('user/' . $user_id);
    // Likes and dislikes are displayed but the user has no permission to vote.
    $this->assertLikesAndDislikes('user', $user_id, '0', '0', TRUE);

    // Allow user "like" permission only and assert the links.
    $user_roles = $this->adminUser->getRoles();
    $user_role = end($user_roles);
    $edit = [
      $user_role . '[add or remove like votes on user]' => TRUE,
    ];
    $this->drupalPostForm('admin/people/permissions', $edit, 'Save permissions');
    $this->drupalGet('user/' . $user_id);

    // Assert user is able to like, but not to dislike.
    $xpath = $this->xpath('//*[@id="like-container-user-' . $user_id . '"]/a')[0];
    $this->assertTrue(!isset($xpath['@class']));
    $this->assertEqual((string) $this->xpath('//*[@id="dislike-container-user-' . $user_id . '"]/a/@class')[0], 'disable-status');

    // Assert that enabled_types is an empty Array.
    $enabled_types = \Drupal::config('like_and_dislike.settings')->get('enabled_types');
    $this->assertEqual($enabled_types['user'], array());
  }

  /**
   * Asserts module voting.
   *
   * Test that voting (liking and disliking) properly works, including removing
   * a like or dislike (if enabled) and changing a vote.
   */
  public function testVoting() {
    // Create a node and add a comment to it.
    $node = Node::create([
      'title' => 'Test node title',
      'type' => 'article',
    ]);
    $node->save();
    $node_id = $node->id();

    $comment = Comment::create([
      'subject' => 'Test subject',
      'comment_body' => 'Test body',
      'entity_id' => $node_id,
      'entity_type' => 'node',
      'node_type' => 'article',
      'field_name' => 'test_comment_field',
      'status' => CommentInterface::PUBLISHED,
      'uid' => $this->adminUser->id(),
    ]);
    $comment->save();
    $comment_id = $comment->id();

    // Enable like and dislike for nodes and comments (test_comment_type)
    // and enable vote cancellation.
    $edit = [
      'enabled_types[node][enabled]' => TRUE,
      'enabled_types[node][bundle_info][bundles][article]' => TRUE,
      'enabled_types[comment][enabled]' => TRUE,
      'enabled_types[comment][bundle_info][bundles][test_comment_type]' => TRUE,
      'allow_cancel_vote' => TRUE,
    ];
    $this->drupalPostForm('admin/config/search/votingapi/like_and_dislike', $edit, t('Save configuration'));
    $this->assertText('The configuration options have been saved.');

    // Update user with voting permissions.
    $user_roles = $this->adminUser->getRoles();
    $user_role = end($user_roles);
    $edit = [
      $user_role . '[add or remove like votes on article of node]' => TRUE,
      $user_role . '[add or remove dislike votes on article of node]' => TRUE,
      $user_role . '[add or remove like votes on test_comment_type of comment]' => TRUE,
      $user_role . '[add or remove dislike votes on test_comment_type of comment]' => TRUE,
    ];
    $this->drupalPostForm('admin/people/permissions', $edit, 'Save permissions');

    // Toggle on visibility of the extra fields.
    $this->drupalPostForm('admin/structure/types/manage/article/display', ['fields[like_and_dislike][region]' => 'content'], t('Save'));
    $this->assertText('Your settings have been saved.');
    $this->drupalPostForm('admin/structure/comment/manage/test_comment_type/display', ['fields[like_and_dislike][region]' => 'content'], t('Save'));
    $this->assertText('Your settings have been saved.');

    // Verify that the node and comment don't have any like or dislike.
    $this->drupalGet('node/' . $node_id);
    $this->assertLikesAndDislikes('node', $node_id);
    $this->assertLikesAndDislikes('comment', $comment_id);

    // Add likes and dislikes and verify that the count increments.
    $this->drupalGet('node/' . $node_id);
    $this->vote('like', 'node', $node_id, 'Your like vote was added.');
    $xpath = $this->xpath('//*[@id="like-container-node-' . $node_id . '"]/a')[0];
    $this->assertTrue(!isset($xpath['@class']));
    $this->assertLikesAndDislikes('node', $node_id, '1');
    $this->assertLikesAndDislikes('comment', $comment_id);

    $this->vote('dislike', 'comment', $comment_id, 'Your dislike vote was added.');
    $xpath = $this->xpath('//*[@id="like-container-comment-' . $comment_id . '"]/a')[0];
    $this->assertTrue(!isset($xpath['@class']));
    $this->assertLikesAndDislikes('node', $node_id, '1');
    $this->assertLikesAndDislikes('comment', $comment_id, '0', '1');

    // Login as different users to further increment votes.
    $user2_permissions = [
      'access comments',
      'add or remove like votes on article of node',
      'add or remove dislike votes on article of node',
      'add or remove like votes on test_comment_type of comment',
      'add or remove dislike votes on test_comment_type of comment',
    ];
    $user2 = $this->drupalCreateUser($user2_permissions);
    $this->drupalLogin($user2);

    // Assert that icons are not marked as "voted".
    $this->drupalGet('node/' . $node_id);
    $xpath = $this->xpath('//*[@id="like-container-node-' . $node_id . '"]/a')[0];
    $this->assertTrue(!isset($xpath['@class']));
    $xpath = $this->xpath('//*[@id="dislike-container-comment-' . $comment_id . '"]/a')[0];
    $this->assertTrue(!isset($xpath['@class']));

    $this->vote('like', 'node', $node_id);
    $this->assertLikesAndDislikes('node', $node_id, '2');
    $this->assertLikesAndDislikes('comment', $comment_id, '0', '1');
    $this->vote('like', 'comment', $comment_id);
    $this->assertLikesAndDislikes('node', $node_id, '2');
    $this->assertLikesAndDislikes('comment', $comment_id, '1', '1');

    // Vote the opposite, to swap the votes.
    $this->vote('dislike', 'node', $node_id);
    $xpath = $this->xpath('//*[@id="like-container-node-' . $node_id . '"]/a')[0];
    $this->assertTrue(!isset($xpath['@class']));
    $this->assertLikesAndDislikes('node', $node_id, '1', '1');
    $this->assertLikesAndDislikes('comment', $comment_id, '1', '1');

    // Vote the same again to cancel the votes. At this point, user 2 voted to
    // dislike the article and like the comment.
    $this->vote('dislike', 'node', $node_id, NULL, TRUE);
    $xpath = $this->xpath('//*[@id="like-container-node-' . $node_id . '"]/a')[0];
    $this->assertTrue(!isset($xpath['@class']));
    $this->assertLikesAndDislikes('node', $node_id, '1');
    $this->assertLikesAndDislikes('comment', $comment_id, '1', '1');

    // Disable vote cancellation.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'enabled_types[node][enabled]' => TRUE,
      'enabled_types[comment][enabled]' => TRUE,
      'enabled_types[comment][bundle_info][bundles][test_comment_type]' => TRUE,
      'allow_cancel_vote' => FALSE,
    ];
    $this->drupalPostForm('admin/config/search/votingapi/like_and_dislike', $edit, t('Save configuration'));
    $this->assertText('The configuration options have been saved.');
    $this->drupalLogin($user2);

    // Unsuccessfully try to cancel the comment like vote.
    $this->drupalGet('node/' . $node_id);
    $this->vote('like', 'comment', $comment_id, 'You are not allowed to vote the same way multiple times.');
    $this->assertLikesAndDislikes('node', $node_id, '1');
    $this->assertLikesAndDislikes('comment', $comment_id, '1', '1');

    // Login as a user without permission to add or remove votes.
    $user3_permissions = [
      'access comments',
    ];
    $user3 = $this->drupalCreateUser($user3_permissions);
    $this->drupalLogin($user3);

    // Verify that the votes are correctly displayed, but are not links.
    $this->drupalGet('node/' . $node_id);
    $this->assertLikesAndDislikes('node', $node_id, '1', '0', TRUE);
    $this->assertLikesAndDislikes('comment', $comment_id, '1', '1', TRUE);
  }

  /**
   * Triggers a voting action with given parameters.
   *
   * @param string $vote_type
   *   The vote type.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $entity_id
   *   The entity ID.
   * @param string|null $message
   *   (optional) The message to assert.
   * @param bool $cancel
   *   (optional) TRUE if the vote is cancelled. Otherwise, FALSE.
   */
  protected function vote($vote_type, $entity_type_id, $entity_id, $message = NULL, $cancel = FALSE) {
    // Get the current URL.
    $url = $this->url;
    // Trigger the voting.
    $this->drupalGet("like_and_dislike/$entity_type_id/$vote_type/$entity_id");
    if ($message) {
      // @todo: The message content is not recognized.
      // $this->assertRaw($message);
    }
    // Go back to the original page.
    $this->drupalGet($url);
    // Assert that voted icon was updated.
    if (!$cancel) {
      $this->assertEqual((string) $this->xpath("//*[@id=\"$vote_type-container-$entity_type_id-$entity_id\"]/a/@class")[0], "voted");
    }
  }

  /**
   * Asserts likes and dislikes markup and their number.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $entity_id
   *   The entity ID.
   * @param string $likes
   *   (optional) The number of likes. Default to zero.
   * @param string $dislikes
   *   (optional) The number of dislikes. Default to zero.
   * @param bool $disabled
   *   (optional) If TRUE disabled CSS class is checked.
   */
  protected function assertLikesAndDislikes($entity_type_id, $entity_id, $likes = '0', $dislikes = '0', $disabled = FALSE) {
    // Assert likes.
    $like_container_id = 'like-container-' . $entity_type_id . '-' . $entity_id;
    $this->assertEqual((string) $this->xpath('//*[@id="' . $like_container_id . '"]/a/@data-entity-type')[0], $entity_type_id);
    $this->assertEqual((string) $this->xpath('//*[@id="' . $like_container_id . '"]/a/@data-entity-id')[0], $entity_id);
    $this->assertEqual((string) $this->xpath('//*[@id="' . $like_container_id . '"]/span[@class="count"]')[0], $likes);

    // Assert dislikes.
    $dislike_container_id = 'dislike-container-' . $entity_type_id . '-' . $entity_id;
    $this->assertEqual((string) $this->xpath('//*[@id="' . $dislike_container_id . '"]/a/@data-entity-type')[0], $entity_type_id);
    $this->assertEqual((string) $this->xpath('//*[@id="' . $dislike_container_id . '"]/a/@data-entity-id')[0], $entity_id);
    $this->assertEqual((string) $this->xpath('//*[@id="' . $dislike_container_id . '"]/span[@class="count"]')[0], $dislikes);

    if ($disabled) {
      $this->assertEqual((string) $this->xpath('//*[@id="' . $like_container_id . '"]/a/@class')[0], 'disable-status');
      $this->assertEqual((string) $this->xpath('//*[@id="' . $dislike_container_id . '"]/a/@class')[0], 'disable-status');
    }
  }

  /**
   * Asserts voting icon existence on the page.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $entity_id
   *   The entity ID.
   * @param string $type
   *   Type of the icon, can be 'like' or 'dislike'.
   * @param boolean $exist
   *   TRUE if icon should exist, FALSE if not.
   */
  protected function assertVotingIconExistence($entity_type_id, $entity_id, $type, $exist) {
    $container_id = $type . '-container-' . $entity_type_id . '-' . $entity_id;
    $icon_exist = !empty($this->xpath('//*[@id="' . $container_id . '"]/a/@data-entity-type')[0]);
    $this->assertEqual($icon_exist, $exist);
  }

}
