<?php

namespace Drupal\simple_poll\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PollOptionDeleteForm extends ContentEntityDeleteForm
{

  /**
   * {@inheritdoc}
   */
  public function getQuestion()
  {
    /** @var \Drupal\simple_poll\Entity\PollOption $entity */
    $entity = $this->getEntity();
    return $this->t('Are you sure you want to delete the option "%title"?', ['%title' => $entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl()
  {
    $entity = $this->getEntity();

    $poll_id = $entity->getPollId();
    return $poll_id
      ? Url::fromRoute('simple_poll.option_list', ['poll' => $poll_id])
      : parent::getCancelUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $entity = $this->getEntity();
    $poll_id = $entity->getPollId();

    parent::submitForm($form, $form_state);

    if ($poll_id) {
      $form_state->setRedirect('simple_poll.option_list', ['poll' => $poll_id]);
    }
  }
}
