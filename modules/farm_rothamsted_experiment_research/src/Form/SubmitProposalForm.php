<?php

namespace Drupal\farm_rothamsted_experiment_research\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProposalInterface;

/**
 * Confirmation form for submitting proposals.
 */
class SubmitProposalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'submit_proposal_form';
  }

  /**
   * Access callback for submit proposal form.
   *
   * @param \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProposalInterface|null $rothamsted_proposal
   *   The proposal entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(?RothamstedProposalInterface $rothamsted_proposal = NULL) {
    if (empty($rothamsted_proposal)) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowedIf($rothamsted_proposal->get('status')->value == 'draft' && $rothamsted_proposal->get('status')->access('update', $this->currentUser()));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?RothamstedProposalInterface $rothamsted_proposal = NULL) {

    // Build form. See ConfirmFormBase.
    $form_state->set('entity', $rothamsted_proposal);
    $form['#title'] = $this->t('Submit Proposal: %proposal', ['%proposal' => $rothamsted_proposal->label()]);
    $form['#attributes']['class'][] = 'confirmation';
    $form['description'] = ['#markup' => $this->t('This proposal is currently in a draft state. Are you sure you want to submit?')];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Proposal'),
      '#button_type' => 'primary',
    ];

    // Prepare cancel link.
    $query = $this->getRequest()->query;
    $url = Url::fromRoute('entity.rothamsted_proposal.collection');

    // If a destination is specified, that serves as the cancel link.
    if ($query->has('destination')) {
      $options = UrlHelper::parse($query->get('destination'));
      try {
        $url = Url::fromUserInput('/' . ltrim($options['path'], '/'), $options);
      }
      catch (\InvalidArgumentException $e) {
      }
    }

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button', 'dialog-cancel']],
      '#url' => $url,
      '#cache' => [
        'contexts' => [
          'url.query_args:destination',
        ],
      ],
    ];

    // By default, render the form using theme_confirm_form().
    if (!isset($form['#theme'])) {
      $form['#theme'] = 'confirm_form';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProposalInterface $entity */
    $entity = $form_state->get('entity');
    if ($entity) {
      $entity->set('status', 'submitted');
      $entity->save();
      $this->messenger()->addStatus($this->t('Submitted proposal'));
      $form_state->setRedirect('entity.rothamsted_proposal.canonical', ['rothamsted_proposal' => $entity->id()]);
    }
  }

}
