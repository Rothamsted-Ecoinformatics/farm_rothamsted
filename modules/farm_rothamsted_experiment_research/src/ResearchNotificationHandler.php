<?php

namespace Drupal\farm_rothamsted_experiment_research;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedDesignInterface;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedExperimentInterface;
use Drupal\farm_rothamsted_researcher\Entity\RothamstedResearcherInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for managing research notification.
 */
class ResearchNotificationHandler implements ContainerInjectionInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ResearchNotificationHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MailManagerInterface $mail_manager, AccountProxyInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->mailManager = $mail_manager;
    $this->currentUser = $current_user;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.mail'),
      $container->get('current_user')
    );
  }

  /**
   * Builds a new entity alert.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to create an alert for.
   */
  public function buildNewEntityAlert(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $function_name = lcfirst(str_replace('_', '', ucwords("build_new_{$entity_type}_alert", '_')));
    if (!is_callable([$this, $function_name])) {
      return;
    }
    $this->$function_name($entity);
  }

  /**
   * Build a new alert for Rothamsted Proposal.
   *
   * @param \Drupal\Core\Entity\EntityInterface $proposal
   *   The research proposal entity.
   */
  protected function buildNewRothamstedProposalAlert(EntityInterface $proposal) {

    // Get the researchers from the research proposal entity.
    $researchLeads = $this->getResearcherEmails($proposal->get('contact'));
    $statisticians = $this->getResearcherEmails($proposal->get('statistician'));
    $dataStewards = $this->getResearcherEmails($proposal->get('data_steward'));

    // Merge all the emails into an array, limiting to non-duplicate values.
    $emails = array_unique(array_merge($researchLeads, $statisticians, $dataStewards));

    // Build email content.
    $entity_type_id = $proposal->getEntityTypeId();
    $subject = "[site:name]: [$entity_type_id:uid:entity:display-name] has added you to a Research Proposal in FarmOS";
    $body[] = "You have been added to the following Research Proposal by [$entity_type_id:uid:entity:display-name]: [$entity_type_id:name] [$entity_type_id:url:absolute]";
    $body[] = "You will receive [period] updates about this proposal. To change your alert preferences please [click here].";
    $body[] = "If you have any questions or queries, please contact your FarmOS Data Administrator. [hyperlink list]";

    // Send mail.
    $params['subject_template'] = $subject;
    $params['body_template'] = $body;
    $this->sendMail($proposal, $emails, $params);
  }

  /**
   * Build a new alert for Rothamsted Program.
   *
   * @param \Drupal\Core\Entity\EntityInterface $program
   *   The program entity.
   */
  protected function buildNewRothamstedProgramAlert(EntityInterface $program) {

    // Get principal investigator emails.
    $emails = $this->getResearcherEmails($program->get('principal_investigator'));

    // Build email content.
    $entity_type_id = $program->getEntityTypeId();
    $subject = "[site:name]: You have been named as a Principal Investigator on [$entity_type_id:name]";
    $body[] = "[$entity_type_id:uid:entity:display-name] has added you as a Principal Investigator on the following Research Program: [$entity_type_id:name] [$entity_type_id:url:absolute]";
    $body[] = "Please check the details are correct. If not, please amend them by clicking on the above link and pressing 'edit'.";
    $body[] = "You will continue to receive updates about this Research Profile if it is edited by a Farm Manager or Farm Data Administrator. To change your alert, preferences please [click here].";
    $body[] = "If you have any questions or queries, please contact your FarmOS Data Administrator. [hyperlink list]";

    // Send mail.
    $params['subject_template'] = $subject;
    $params['body_template'] = $body;
    $this->sendMail($program, $emails, $params);
  }

  /**
   * Build a new alert for Rothamsted Experiment.
   *
   * @param \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedExperimentInterface $experiment
   *   The experiment entity.
   */
  protected function buildNewRothamstedExperimentAlert(RothamstedExperimentInterface $experiment) {
    $emails = $this->getExperimentResearcherEmails($experiment);

    $entity_type_id = $experiment->getEntityTypeId();

    $subject = "[site:name]: [$entity_type_id:uid:entity:display-name] has added you to an experiment in farmOS";
    $body[] = "You have been added to the following experiment by [$entity_type_id:uid:entity:display-name]: [$entity_type_id:name] [$entity_type_id:url:absolute]";
    $body[] = 'You will receive [period] updates about this experiment. You may change your alert preferences [here].';
    $body[] = 'If you have any questions or queries, please contact your FarmOS Data Administrator. [link].';

    $params['subject_template'] = $subject;
    $params['body_template'] = $body;
    $this->sendMail($experiment, $emails, $params);
  }

  /**
   * Build a new alert for Rothamsted Design.
   *
   * @param \Drupal\Core\Entity\EntityInterface $design
   *   The design entity.
   */
  protected function buildNewRothamstedDesignAlert(EntityInterface $design) {

    $emails = $this->getDesignResearcherEmails($design);

    // Build email content.
    $entity_type_id = $design->getEntityTypeId();
    $subject = "[site:name]: An Experiment Design has been added to [$entity_type_id:experiment:entity:name]";
    $body[] = "[$entity_type_id:uid:entity:display-name] has added the following Experiment Design to [$entity_type_id:experiment:entity:name]: [$entity_type_id:name] [$entity_type_id:url:absolute]";
    $body[] = "You are receiving this email because you are named on [$entity_type_id:experiment:entity:name] or because you have been nominated as a Statistician for this Experiment Design. To change your alert preferences please [click here].";
    $body[] = "If you have any questions or queries, please contact your FarmOS Data Administrator. [hyperlink list]";

    // Send mail.
    $params['subject_template'] = $subject;
    $params['body_template'] = $body;
    $this->sendMail($design, $emails, $params);
  }

  /**
   * Build a new alert for Plan.
   *
   * @param \Drupal\Core\Entity\EntityInterface $plan
   *   The plan entity.
   */
  protected function buildNewPlanAlert(EntityInterface $plan) {
    $emails = $this->getDesignResearcherEmails($plan->get('experiment_design')->entity);

    // Build email content.
    $entity_type_id = $plan->getEntityTypeId();
    $subject = "[site:name]: An Experiment Plan has been added to [$entity_type_id:experiment_design:entity:experiment:entity:name]";
    $body[] = "[$entity_type_id:uid:entity:display-name] has added the following Experiment Plan to [$entity_type_id:experiment_design:entity:experiment:entity:name]: [$entity_type_id:name] [$entity_type_id:url:absolute]";
    $body[] = "You are receiving this email because you are named on [$entity_type_id:experiment_design:entity:experiment:entity:name]. To change your alert preferences please [click here].";
    $body[] = "If you have any questions or queries, please contact your FarmOS Data Administrator. [hyperlink list]";

    // Send mail.
    $params['subject_template'] = $subject;
    $params['body_template'] = $body;
    $this->sendMail($plan, $emails, $params);
  }

  /**
   * Get the emails of the researchers of a design.
   *
   * @param \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedDesignInterface $design
   *   The design entity.
   *
   * @return array
   *   An array of researcher emails.
   */
  protected function getDesignResearcherEmails(RothamstedDesignInterface $design) {
    // Get the researcher and statistician from the research design entity.
    $researchers = $this->getExperimentResearcherEmails($design->get('experiment')->entity);
    $statisticians = $this->getResearcherEmails($design->get('statistician'));

    // Merge all the emails into an array, limiting to non-duplicate values.
    return array_unique(array_merge($researchers, $statisticians));
  }

  /**
   * Get the emails of the researchers of an experiment.
   *
   * @param \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedExperimentInterface $experiment
   *   The experiment entity.
   *
   * @return array
   *   An array of researcher emails.
   */
  protected function getExperimentResearcherEmails(RothamstedExperimentInterface $experiment) {
    return $this->getResearcherEmails($experiment->get('researcher'));
  }

  /**
   * Get the emails of researchers.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field
   *   The field list containing the researchers.
   *
   * @return array
   *   An array of researcher emails.
   */
  protected function getResearcherEmails(EntityReferenceFieldItemListInterface $field) {
    return array_map(function (RothamstedResearcherInterface $researcher) {
      return $researcher->getNotificationEmail();
    }, $field->referencedEntities());
  }

  /**
   * Sends a mail.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that the mail is about.
   * @param array                              $emails
   *   The recipient emails.
   * @param array                              $params
   *   An array of parameters for the mail.
   */
  protected function sendMail(EntityInterface $entity, array $emails, array $params = []) {

    // Do not send updates to the current user.
    if ($current_user_email = $this->currentUser->getEmail()) {
      $emails = array_diff($emails, [$current_user_email]);
    }
    $emails = array_unique(array_filter($emails));

    // Set the entity param.
    $params['entity'] = $entity;

    // Delegate to farm_rothamsted_notification.
    $this->mailManager->mail('farm_rothamsted_notification', 'entity_template', implode(', ', $emails), 'en', $params);
  }
}
