<?php

namespace Drupal\simple_poll\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_poll\Entity\Poll;

/**
 * Form controller for the poll option entity.
 */
class PollOptionForm extends ContentEntityForm
{

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    if ($entity->isNew()) {
      $poll_param = \Drupal::routeMatch()->getParameter('poll');
      $poll = $poll_param instanceof Poll ? $poll_param : Poll::load($poll_param);

      if ($poll) {
        $entity->setPollId($poll->id());

        if (isset($form['poll_id'])) {
          $form['poll_id']['#access'] = FALSE;
          $form['poll_id']['widget'][0]['target_id']['#default_value'] = $poll;
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state)
  {
    $entity = $this->getEntity();

//    dump($entity->getPollId());
//    die;

    if (!$entity->getPollId()) {
      $this->messenger()->addError($this->t('Failed to associate this option with a poll.'));
      $form_state->setRedirect('<front>');
      return;
    }

    $status = parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('The option %title has been saved.', [
      '%title' => $entity->label(),
    ]));

    $form_state->setRedirect('simple_poll.option_list', [
      'poll' => $entity->getPollId(),
    ]);

    return $status;
  }
}
