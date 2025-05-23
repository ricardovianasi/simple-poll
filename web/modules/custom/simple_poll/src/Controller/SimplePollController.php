<?php

namespace Drupal\simple_poll\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\simple_poll\Entity\Poll;
use Drupal\simple_poll\Service\SimplePollService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for the Simple Poll module.
 */
class SimplePollController extends ControllerBase {

  /**
   * The Simple Poll service.
   *
   * @var \Drupal\simple_poll\Service\SimplePollService
   */
  protected $pollService;

  /**
   * Constructs a SimplePollController object.
   *
   * @param \Drupal\simple_poll\Service\SimplePollService $poll_service
   *   The Simple Poll service.
   */
  public function __construct(SimplePollService $poll_service) {
    $this->pollService = $poll_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_poll.poll_service')
    );
  }

  /**
   * Displays a list of polls.
   *
   * @return array
   *   A render array for the poll list page.
   */
  public function pollList() {
    $build = [];

    $header = [
      'title' => $this->t('Title'),
      'identifier' => $this->t('Identifier'),
      'status' => $this->t('Status'),
      'show_results' => $this->t('Show Results'),
      'operations' => $this->t('Operations'),
    ];

    $rows = [];

    $polls = $this->entityTypeManager()->getStorage('simple_poll')->loadMultiple();

    foreach ($polls as $poll) {
      $row = [];
      $row['title'] = $poll->getTitle();
      $row['identifier'] = $poll->getIdentifier();
      $row['status'] = $poll->isActive() ? $this->t('Active') : $this->t('Inactive');
      $row['show_results'] = $poll->showResults() ? $this->t('Yes') : $this->t('No');

      $operations = [];
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('simple_poll.edit', ['poll' => $poll->id()]),
      ];
      $operations['options'] = [
        'title' => $this->t('Options'),
        'url' => Url::fromRoute('simple_poll.option_list', ['poll' => $poll->id()]),
      ];
      $operations['results'] = [
        'title' => $this->t('Results'),
        'url' => Url::fromRoute('simple_poll.results', ['poll' => $poll->id()]),
      ];
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('simple_poll.delete', ['poll' => $poll->id()]),
      ];

      $row['operations'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $operations,
        ],
      ];

      $rows[] = $row;
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No polls found.'),
    ];

    $build['add_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Add new poll'),
      '#url' => Url::fromRoute('simple_poll.add'),
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];

    return $build;
  }

  public function publicPollList() {
    $voting_enabled = $this->pollService->isVotingEnabled();

    $polls = $this->entityTypeManager()
      ->getStorage('simple_poll')
      ->loadMultiple();

    $visible_polls = [];

    foreach ($polls as $poll) {
      $results = NULL;

      if ($poll->showResults()) {
        $results = $this->pollService->getPollResults($poll->id());
      }

      $visible_polls[] = [
        'poll' => $poll,
        'is_active' => $poll->isActive(),
        'show_results' => $poll->showResults(),
        'results' => $results,
      ];
    }

    return [
      '#theme' => 'simple_poll_list',
      '#polls' => $visible_polls,
      '#attached' => [
        'library' => ['simple_poll/simple_poll'],
      ],
      '#voting_enabled' => $voting_enabled,
      '#cache' => [
        'tags' => ['simple_poll_list'],
        'contexts' => ['user'],
      ],
    ];
  }

  /**
   * Displays a list of poll options.
   *
   * @param mixed $poll
   *   The poll entity.
   *
   * @return array
   *   A render array for the poll options list page.
   */
  public function optionList($poll) {
    $build = [];

    $build['poll_info'] = [
      '#type' => 'item',
      '#title' => $this->t('Poll'),
      '#markup' => $poll->getTitle(),
    ];

    $header = [
      'title' => $this->t('Title'),
      'description' => $this->t('Description'),
      'image' => $this->t('Image'),
      'weight' => $this->t('Weight'),
      'operations' => $this->t('Operations'),
    ];

    $rows = [];

    $options = $this->pollService->getPollOptions($poll->id());

    foreach ($options as $option) {
      $row = [];
      $row['title'] = $option->getTitle();
      $row['description'] = strip_tags($option->getDescription());

      $image = '';
      if ($option->getImageId()) {
        $file = $this->entityTypeManager()->getStorage('file')->load($option->getImageId());
        if ($file) {
          $image = [
            '#theme' => 'image_style',
            '#style_name' => 'thumbnail',
            '#uri' => $file->getFileUri(),
            '#width' => 100,
            '#height' => 100,
          ];
        }
      }
      $row['image'] = $image ? \Drupal::service('renderer')->render($image) : '';

      $row['weight'] = $option->getWeight();

      $operations = [];
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('simple_poll.option_edit', [
          'poll' => $poll->id(),
          'option' => $option->id(),
        ]),
      ];
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('simple_poll.option_delete', [
          'poll' => $poll->id(),
          'option' => $option->id(),
        ]),
      ];

      $row['operations'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $operations,
        ],
      ];

      $rows[] = $row;
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No options found.'),
    ];

    $build['add_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Add new option'),
      '#url' => Url::fromRoute('simple_poll.option_add', ['poll' => $poll->id()]),
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];

    $build['back_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Back to polls'),
      '#url' => Url::fromRoute('simple_poll.list'),
    ];

    return $build;
  }

  /**
   * Displays poll results.
   *
   * @param mixed $poll
   *   The poll entity.
   *
   * @return array
   *   A render array for the poll results page.
   */
  public function pollResults($poll) {
    $results = $this->pollService->getPollResults($poll->id());

    return [
      '#theme' => 'simple_poll_results',
      '#poll' => $poll,
      '#results' => $results['options'],
      '#total_votes' => $results['total_votes'],
    ];
  }

  /**
   * Processes a vote.
   *
   * @param mixed $poll
   *   The poll entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function vote($poll, Request $request) {
    // Check if the voting system is enabled.
    if (!$this->pollService->isVotingEnabled()) {
      $this->messenger()->addError($this->t('Voting is currently disabled.'));
      return $this->redirect('simple_poll.display', ['poll' => $poll->id()]);
    }

    // Check if the poll is active.
    if (!$poll->isActive()) {
      $this->messenger()->addError($this->t('This poll is not active.'));
      return $this->redirect('simple_poll.display', ['poll' => $poll->id()]);
    }

    $user_id = $this->currentUser()->id();

    // Get the selected option.
    $option_id = $request->request->get('option');
    if (!$option_id) {
      $this->messenger()->addError($this->t('No option selected.'));
      return $this->redirect('simple_poll.display', ['poll' => $poll->id()]);
    }

    // Validate that the option belongs to this poll.
    $option = $this->pollService->getPollOption($option_id);
    if (!$option || $option->getPollId() != $poll->id()) {
      $this->messenger()->addError($this->t('Invalid option selected.'));
      return $this->redirect('simple_poll.display', ['poll' => $poll->id()]);
    }

    // Record the vote.
    if ($this->pollService->recordVote($poll->id(), $option_id, $user_id)) {
      $this->messenger()->addStatus($this->t('Your vote has been recorded.'));
    }
    else {
      $this->messenger()->addError($this->t('There was an error recording your vote.'));
    }

    return $this->redirect('simple_poll.display', ['poll' => $poll->id()]);
  }

  public function debugTest() {
    die('Chegou no controller de teste!');
  }

  /**
   * Displays a poll for voting.
   *
   * @param mixed $poll
   *   The poll entity.
   *
   * @return array
   *   A render array for the poll display.
   */
  public function displayPoll($poll) {
    if (!$poll instanceof Poll) {
      $poll = Poll::load($poll);
      if (!$poll) {
        throw new NotFoundHttpException();
      }
    }

    $voting_enabled = $this->pollService->isVotingEnabled();
    $options = $this->pollService->getPollOptions($poll->id());
    $user_id = $this->currentUser()->id();
    $has_voted = $this->pollService->hasUserVoted($poll->id(), $user_id);

    $results = NULL;
    if ($has_voted && $poll->showResults()) {
      $results = $this->pollService->getPollResults($poll->id());
    }

    return [
      '#theme' => 'simple_poll',
      '#poll' => $poll,
      '#options' => $options,
      '#results' => $results,
      '#show_results' => $poll->showResults() && $has_voted,
      '#voting_enabled' => $voting_enabled,
      '#attached' => [
        'library' => ['simple_poll/simple_poll'],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['simple_poll:' . $poll->id()],
      ],
    ];
  }

  /**
   * Returns the title for the poll display page.
   *
   * @param mixed $poll
   *   The poll entity.
   *
   * @return string
   *   The title.
   */
  public function pollTitle($poll) {
    return $poll->getTitle();
  }

  /**
   * Access callback for voting.
   *
   * @param mixed $poll
   *   The poll entity.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function voteAccess($poll) {
    if (!$this->pollService->isVotingEnabled()) {
      return AccessResult::forbidden('Voting is disabled');
    }

    if (!$poll->isActive()) {
      return AccessResult::forbidden('Poll is not active');
    }

    return AccessResult::allowedIfHasPermission($this->currentUser(), 'vote in polls');
  }

}
