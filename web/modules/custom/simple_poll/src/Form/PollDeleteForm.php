<?php

namespace Drupal\simple_poll\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form controller for deleting a Poll.
 */
class PollDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity = $this->getEntity();
    return $this->t('Are you sure you want to delete the poll "%title"?', ['%title' => $entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('simple_poll.list');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $poll = $this->getEntity();

    $option_storage = \Drupal::entityTypeManager()->getStorage('simple_poll_option');
    $options = $option_storage->loadByProperties(['poll_id' => $poll->id()]);
    foreach ($options as $option) {
      $option->delete();
    }

    parent::submitForm($form, $form_state);

    $this->messenger()->addStatus($this->t('The poll "%title" and all its options were deleted.', [
      '%title' => $poll->label(),
    ]));

    $form_state->setRedirect('simple_poll.list');
  }
}
