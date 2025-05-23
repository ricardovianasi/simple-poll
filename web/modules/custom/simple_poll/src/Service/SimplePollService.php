<?php

namespace Drupal\simple_poll\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Service for Simple Poll operations.
 */
class SimplePollService
{

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructor for SimplePollService.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(
    Connection                 $database,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface     $config_factory,
    StateInterface             $state
  )
  {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->state = $state;
  }

  /**
   * Check if the voting system is enabled.
   *
   * @return bool
   *   TRUE if the voting system is enabled, FALSE otherwise.
   */
  public function isVotingEnabled()
  {
    $config = $this->configFactory->get('simple_poll.settings');
    return (bool)$config->get('enabled');
  }

  /**
   * Get a poll by its identifier.
   *
   * @param string $identifier
   *   The poll identifier.
   *
   * @return \Drupal\simple_poll\Entity\Poll|null
   *   The poll entity, or NULL if not found.
   */
  public function getPollByIdentifier($identifier)
  {
    try {
      $poll_ids = $this->entityTypeManager->getStorage('simple_poll')
        ->getQuery()
        ->condition('identifier', $identifier)
        ->accessCheck(false)
        ->execute();

      if (!empty($poll_ids)) {
        return $this->entityTypeManager->getStorage('simple_poll')->load(reset($poll_ids));
      }
    } catch (\Exception $e) {
      // Log the exception.
    }

    return NULL;
  }

  /**
   * Get a poll option by ID.
   *
   * @param int $id
   *   The option ID.
   *
   * @return \Drupal\simple_poll\Entity\PollOption|null
   *   The poll option entity, or NULL if not found.
   */
  public function getPollOption($id)
  {
    try {
      return $this->entityTypeManager->getStorage('simple_poll_option')->load($id);
    } catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Check if a user has already voted in a poll.
   *
   * @param int $poll_id
   *   The poll ID.
   * @param int $uid
   *   The user ID.
   *
   * @return bool
   *   TRUE if the user has voted, FALSE otherwise.
   */
  public function hasUserVoted($poll_id, $uid)
  {
    $query = $this->database->select('simple_poll_vote', 'v')
      ->fields('v', ['id'])
      ->condition('poll_id', $poll_id)
      ->condition('uid', $uid)
      ->range(0, 1);

    $result = $query->execute()->fetchField();

    return (bool)$result;
  }

  /**
   * Record a vote for a poll option.
   *
   * @param int $poll_id
   *   The poll ID.
   * @param int $option_id
   *   The option ID.
   * @param int $uid
   *   The user ID.
   *
   * @return bool
   *   TRUE if the vote was recorded successfully, FALSE otherwise.
   */
  public function recordVote($poll_id, $option_id, $uid)
  {
    try {
      $this->database->insert('simple_poll_vote')
        ->fields([
          'poll_id' => $poll_id,
          'option_id' => $option_id,
          'uid' => $uid,
          'ip_address' => \Drupal::request()->getClientIp(),
          'timestamp' => \Drupal::time()->getRequestTime(),
        ])
        ->execute();

      return TRUE;
    } catch (\Exception $e) {
      // Log the exception.
      return FALSE;
    }
  }

  /**
   * Get poll results.
   *
   * @param int $poll_id
   *   The poll ID.
   *
   * @return array
   *   An array containing the results.
   */
  public function getPollResults($poll_id)
  {
    $poll = $this->getPoll($poll_id);
    if (!$poll) {
      return [];
    }

    $options = $this->getPollOptions($poll_id);
    $results = [];
    $total_votes = 0;

    // Count votes for each option.
    foreach ($options as $option) {
      $query = $this->database->select('simple_poll_vote', 'v')
        ->fields('v', ['id'])
        ->condition('poll_id', $poll_id)
        ->condition('option_id', $option->id());

      $count = $query->countQuery()->execute()->fetchField();
      $results[$option->id()] = [
        'option_id' => $option->id(),
        'title' => $option->getTitle(),
        'votes' => (int)$count,
        'percentage' => 0, // Will calculate after we have the total
      ];

      $total_votes += (int)$count;
    }

    // Calculate percentages.
    foreach ($results as &$result) {
      if ($total_votes > 0) {
        $result['percentage'] = round(($result['votes'] / $total_votes) * 100, 2);
      }
    }

    return [
      'options' => array_values($results),
      'total_votes' => $total_votes,
    ];
  }

  /**
   * Get a poll by its ID.
   *
   * @param int $id
   *   The poll ID.
   *
   * @return \Drupal\simple_poll\Entity\Poll|null
   *   The poll entity, or NULL if not found.
   */
  public function getPoll($id)
  {
    try {
      return $this->entityTypeManager->getStorage('simple_poll')->load($id);
    } catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Get all options for a poll.
   *
   * @param int $poll_id
   *   The poll ID.
   *
   * @return \Drupal\simple_poll\Entity\PollOption[]
   *   An array of poll option entities.
   */
  public function getPollOptions($poll_id)
  {
    try {
      $option_ids = $this->entityTypeManager->getStorage('simple_poll_option')
        ->getQuery()
        ->condition('poll_id', $poll_id)
        ->accessCheck(false)
        ->sort('weight')
        ->execute();

      if (!empty($option_ids)) {
        return $this->entityTypeManager->getStorage('simple_poll_option')->loadMultiple($option_ids);
      }
    } catch (\Exception $e) {
      // Log the exception.
    }

    return [];
  }

}
