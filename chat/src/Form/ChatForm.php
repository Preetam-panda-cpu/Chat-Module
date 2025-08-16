<?php

namespace Drupal\chat\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

class ChatForm extends FormBase {

  public function getFormId() {
    return 'chat_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enter your message'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];

    // Display chat messages
    $messages = Database::getConnection()
      ->select('chat_messages', 'c')
      ->fields('c', ['uid', 'message', 'created'])
      ->orderBy('created', 'DESC')
      ->range(0, 10)
      ->execute()
      ->fetchAll();

    $output = '<div class="chat-box">';
    foreach ($messages as $msg) {
      $user = \Drupal\user\Entity\User::load($msg->uid);
      $username = $user ? $user->getDisplayName() : 'Anonymous';
      $output .= '<p><strong>' . $username . ':</strong> ' . $msg->message . '</p>';
    }
    $output .= '</div>';

    $form['messages'] = [
      '#type' => 'markup',
      '#markup' => $output,
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $message = $form_state->getValue('message');
    $uid = \Drupal::currentUser()->id();

    Database::getConnection()->insert('chat_messages')
      ->fields([
        'uid' => $uid,
        'message' => $message,
        'created' => REQUEST_TIME,
      ])
      ->execute();

    \Drupal::messenger()->addStatus($this->t('Message sent.'));
    $form_state->setRebuild(TRUE);
  }
}
