<?php

namespace Drupal\simple_poll\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Provides a block with the list of polls.
 *
 * @Block(
 *   id = "simple_poll_list_block",
 *   admin_label = @Translation("Poll List Block")
 * )
 */
class PollListBlock extends BlockBase implements BlockPluginInterface
{

  public function build()
  {
    $polls = \Drupal::entityTypeManager()
      ->getStorage('simple_poll')
      ->loadMultiple();

    $renderable_polls = [];

    foreach ($polls as $poll) {
      $results = NULL;

      if ($poll->showResults()) {
        $results = \Drupal::service('simple_poll.poll_service')->getPollResults($poll->id());
      }

      $renderable_polls[] = [
        'poll' => $poll,
        'is_active' => $poll->isActive(),
        'show_results' => $poll->showResults(),
        'results' => $results,
      ];
    }

    return [
      '#theme' => 'simple_poll_list',
      '#polls' => $renderable_polls,
      '#cache' => [
        'tags' => ['simple_poll_list'],
        'contexts' => ['user'],
      ],
    ];
  }
}
