<?php

namespace Drupal\farm_rothamsted_experiment_research;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedDesignInterface;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedExperimentInterface;
use Drupal\farm_rothamsted_researcher\Entity\RothamstedResearcherInterface;
use Drupal\log\Entity\LogInterface;
use Drupal\user\UserInterface;
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
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.mail'),
      $container->get('current_user')
    );
  }

  /**
   * Build a new alert for Log entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $log
   *   The log entity.
   */
  public function buildNewLogAlert(EntityInterface $log) {

    // Build email content.
    $entity_type_id = $log->getEntityTypeId();
    $log_type = $log->get('type')->entity->label();
    $subject = "[site:name]: $log_type Log added: [$entity_type_id:name]";
    $body[] = "[$entity_type_id:uid:entity:display-name] has added a $log_type Log to FarmOS:";
    $body[] = "- [$entity_type_id:name] [$entity_type_id:url:absolute]";
    $body[] = "- Timestamp: [$entity_type_id:timestamp]";

    if (!$log->get('asset')->isEmpty()) {
      $body[] = "- Asset: [$entity_type_id:asset]";
    }
    if (!$log->get('location')->isEmpty()) {
      $body[] = "- Location: [$entity_type_id:location]";
    }

    $body[] = "Please check the details are correct. If you notice anything that needs to be amended, please comment on the log and mark it as 'Needs Review'. Alternatively, if you are named as the owner of this log, you can edit it.";
    $body[] = "If you no longer want to receive log alerts, please click here and opt out of Log Alerts: [configure-notifications]";
    $body[] = "If you have any questions or queries, please contact your FarmOS Data Administrator.";

    // Send mail.
    $params['subject_template'] = $subject;
    $params['body_template'] = $body;
    $emails = $this->getLogExperimentEmails($log);
    $this->sendMail($log, $emails, $params);
  }

  /**
   * Builds a new entity alert.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to create an alert for.
   * @param bool $new_researcher
   *   Boolean if emails should only send to new researchers.
   */
  public function buildNewEntityAlert(EntityInterface $entity, bool $new_researcher = FALSE) {
    $entity_type = $entity->getEntityTypeId();
    $function_name = lcfirst(str_replace('_', '', ucwords("build_new_{$entity_type}_alert", '_')));
    if (!is_callable([$this, $function_name])) {
      return;
    }
    $this->$function_name($entity, $new_researcher);
  }

  /**
   * Build a new alert for Rothamsted researcher.
   *
   * @param \Drupal\Core\Entity\EntityInterface $researcher
   *   The researcher entity.
   * @param bool $new_researcher
   *   Boolean if emails should only send to new researchers.
   */
  protected function buildNewRothamstedResearcherAlert(EntityInterface $researcher, bool $new_researcher = FALSE) {

    // Check the farm_user.
    $email = NULL;
    if (!$researcher->get('farm_user')->isEmpty() && $user_email = $researcher->get('farm_user')->entity->get('mail')->value) {
      $email = $user_email;
    }

    // Exclude the previous farm_user email.
    if ($new_researcher && !$researcher->isNew()) {
      if (!$researcher->original->get('farm_user')->isEmpty() && $old_user_email = $researcher->original->get('farm_user')->entity->get('mail')->value) {
        $email = $email == $old_user_email ? NULL : $email;
      }
    }

    // Bail if no email.
    if (empty($email)) {
      return;
    }

    // Build email content.
    $entity_type_id = $researcher->getEntityTypeId();
    $label = $researcher->get('title')->isEmpty() ? "[$entity_type_id:name]" : "[$entity_type_id:title] [$entity_type_id:name]";
    $subject = "[site:name]: You have been added as a Researcher to FarmOS";
    $body[] = "[$entity_type_id:uid:entity:display-name] has added you as a Researcher to FarmOS. You can click here to view your profile:";
    $body[] = "$label: [$entity_type_id:url:absolute]";
    $body[] = "Please check the details are correct. If not, please amend them by clicking on the above link and pressing 'edit'.";
    $body[] = "You will continue to receive updates about this Research Profile if it is edited by a Farm Manager or Farm Data Administrator. To change your alert preferences please click here: [configure-notifications]";
    $body[] = "If you have any questions or queries, please contact your FarmOS Data Administrator.";

    // Send mail.
    $params['subject_template'] = $subject;
    $params['body_template'] = $body;
    $this->sendMail($researcher, [$email], $params);
  }

  /**
   * Build a new alert for Rothamsted Proposal.
   *
   * @param \Drupal\Core\Entity\EntityInterface $proposal
   *   The research proposal entity.
   * @param bool $new_researcher
   *   Boolean if emails should only send to new researchers.
   */
  protected function buildNewRothamstedProposalAlert(EntityInterface $proposal, bool $new_researcher = FALSE) {

    // Get the researchers from the research proposal entity.
    $researchLeads = $this->getResearcherEmails($proposal->get('contact'));
    $statisticians = $this->getResearcherEmails($proposal->get('statistician'));
    $dataStewards = $this->getResearcherEmails($proposal->get('data_steward'));

    if ($new_researcher && !$proposal->isNew()) {
      $researchLeads = array_diff($researchLeads, $this->getResearcherEmails($proposal->original->get('contact')));
      $statisticians = array_diff($statisticians, $this->getResearcherEmails($proposal->original->get('statistician')));
      $dataStewards = array_diff($dataStewards, $this->getResearcherEmails($proposal->original->get('data_steward')));
    }

    // Merge all the emails into an array, limiting to non-duplicate values.
    $emails = array_unique(array_merge($researchLeads, $statisticians, $dataStewards));

    // Build email content.
    $entity_type_id = $proposal->getEntityTypeId();
    $subject = "[site:name]: [$entity_type_id:uid:entity:display-name] has added you to a Research Proposal in FarmOS";
    $body[] = "You have been added to the following Research Proposal by [$entity_type_id:uid:entity:display-name]: [$entity_type_id:name] [$entity_type_id:url:absolute]";
    $body[] = "You will receive [period] updates about this proposal. To change your alert preferences please click here: [configure-notifications]";
    $body[] = "If you have any questions or queries, please contact your FarmOS Data Administrator.";

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
   * @param bool $new_researcher
   *   Boolean if emails should only send to new researchers.
   */
  protected function buildNewRothamstedProgramAlert(EntityInterface $program, bool $new_researcher = FALSE) {

    // Get principal investigator emails.
    $emails = $this->getResearcherEmails($program->get('principal_investigator'));
    if ($new_researcher && !$program->isNew()) {
      $old_emails = $this->getResearcherEmails($program->original->get('principal_investigator'));
      $emails = array_diff($emails, $old_emails);
    }

    // Build email content.
    $entity_type_id = $program->getEntityTypeId();
    $subject = "[site:name]: You have been named as a Principal Investigator on [$entity_type_id:name]";
    $body[] = "[$entity_type_id:uid:entity:display-name] has added you as a Principal Investigator on the following Research Program: [$entity_type_id:name] [$entity_type_id:url:absolute]";
    $body[] = "Please check the details are correct. If not, please amend them by clicking on the above link and pressing 'edit'.";
    $body[] = "You will continue to receive updates about this Research Profile if it is edited by a Farm Manager or Farm Data Administrator. To change your alert, preferences please click here: [configure-notifications]";
    $body[] = "If you have any questions or queries, please contact your FarmOS Data Administrator.";

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
   * @param bool $new_researcher
   *   Boolean if emails should only send to new researchers.
   */
  protected function buildNewRothamstedExperimentAlert(RothamstedExperimentInterface $experiment, bool $new_researcher = FALSE) {
    $emails = $this->getExperimentResearcherEmails($experiment, $new_researcher);

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
   * @param bool $new_researcher
   *   Boolean if emails should only send to new researchers.
   */
  protected function buildNewRothamstedDesignAlert(EntityInterface $design, bool $new_researcher = FALSE) {

    $emails = $this->getDesignResearcherEmails($design, $new_researcher);

    // Build email content.
    $entity_type_id = $design->getEntityTypeId();
    $subject = "[site:name]: An Experiment Design has been added to [$entity_type_id:experiment:entity:name]";
    $body[] = "[$entity_type_id:uid:entity:display-name] has added the following Experiment Design to [$entity_type_id:experiment:entity:name]: [$entity_type_id:name] [$entity_type_id:url:absolute]";
    $body[] = "You are receiving this email because you are named on [$entity_type_id:experiment:entity:name] or because you have been nominated as a Statistician for this Experiment Design. To change your alert preferences please click here: [configure-notifications]";
    $body[] = "If you have any questions or queries, please contact your FarmOS Data Administrator.";

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
    $body[] = "You are receiving this email because you are named on [$entity_type_id:experiment_design:entity:experiment:entity:name]. To change your alert preferences please click here: [configure-notifications]";
    $body[] = "If you have any questions or queries, please contact your FarmOS Data Administrator.";

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
   * @param bool $new_researcher
   *   Boolean if emails should only send to new researchers.
   *
   * @return array
   *   An array of researcher emails.
   */
  protected function getDesignResearcherEmails(RothamstedDesignInterface $design, bool $new_researcher = FALSE) {
    // Get the researcher and statistician from the research design entity.
    $researchers = $this->getExperimentResearcherEmails($design->get('experiment')->entity);
    $statisticians = $this->getResearcherEmails($design->get('statistician'));
    if ($new_researcher && !$design->isNew()) {
      $old_stats = $this->getResearcherEmails($design->original->get('statistician'));
      $statisticians = array_diff($statisticians, $old_stats);
    }

    // Merge all the emails into an array, limiting to non-duplicate values.
    return array_unique(array_merge($researchers, $statisticians));
  }

  /**
   * Get the emails of the researchers of an experiment.
   *
   * @param \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedExperimentInterface $experiment
   *   The experiment entity.
   * @param bool $new_researcher
   *   Boolean if emails should only send to new researchers.
   *
   * @return array
   *   An array of researcher emails.
   */
  protected function getExperimentResearcherEmails(RothamstedExperimentInterface $experiment, bool $new_researcher = FALSE) {
    $current_emails = $this->getResearcherEmails($experiment->get('researcher'));

    if ($new_researcher && !$experiment->isNew()) {
      $old_emails = $this->getResearcherEmails($experiment->original->get('researcher'));
      return array_diff($current_emails, $old_emails);
    }

    return $current_emails;
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
      return $researcher->getNotificationEmail(TRUE);
    }, $field->referencedEntities());
  }

  /**
   * Helper function to get log experiment emails.
   *
   * Returns user emails associated with an experiment that the log references.
   *
   * @param \Drupal\log\Entity\LogInterface $log
   *   The log entity.
   *
   * @return array
   *   Array of emails.
   */
  protected function getLogExperimentEmails(LogInterface $log) {

    $emails = [];

    // Query plans that the log references (asset, location or plot).
    $asset_ids = array_column($log->get('asset')->getValue(), 'target_id');
    $location_ids = array_column($log->get('location')->getValue(), 'target_id');
    $all_asset_ids = array_merge($asset_ids, $location_ids);

    // Bail if the log does not reference any asset.
    if (empty($all_asset_ids)) {
      return $emails;
    }

    // Query for experiment plans that include this asset.
    $query = $this->entityTypeManager->getStorage('plan')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'rothamsted_experiment')
      ->condition('experiment_design.entity:rothamsted_design.experiment', NULL, 'IS NOT NULL');
    $or_group = $query->orConditionGroup()
      ->condition('plot.entity:asset', $all_asset_ids, 'IN')
      ->condition('asset.entity:asset', $all_asset_ids, 'IN')
      ->condition('location.entity:asset', $all_asset_ids, 'IN');
    $plan_ids = $query
      ->condition($or_group)
      ->execute();
    $plan_storage = $this->entityTypeManager->getStorage('plan');
    $plans = $plan_storage->loadMultiple($plan_ids);

    // Do not send email if there are no matching plans.
    if (empty($plans)) {
      return $emails;
    }

    // Collect experiment emails for each plan.
    foreach ($plans as $plan) {

      // Get the design.
      $designs = $plan->get('experiment_design')->referencedEntities();
      if (empty($designs)) {
        continue;
      }

      // Get the experiment.
      $design = reset($designs);
      $experiments = $design->get('experiment')->referencedEntities();
      if (empty($experiments)) {
        continue;
      }

      // Check for matching researcher.
      $experiment = reset($experiments);
      $researcher_emails = array_map(function (RothamstedResearcherInterface $researcher) {
        return $researcher->getNotificationEmail(FALSE, 'log');
      }, $experiment->get('researcher')->referencedEntities());
      array_push($emails, ...$researcher_emails);
    }

    // Include owner emails.
    if (!$log->get('owner')->isEmpty()) {
      $owner_emails = array_map(function (UserInterface $user) {
        if ($user->get('rothamsted_notification_log')->value) {
          return $user->getEmail();
        }
        return NULL;
      }, $log->get('owner')->referencedEntities());
      array_push($emails, ...$owner_emails);
    }

    return $emails;
  }

  /**
   * Sends a mail.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that the mail is about.
   * @param array $emails
   *   The recipient emails.
   * @param array $params
   *   An array of parameters for the mail.
   */
  protected function sendMail(EntityInterface $entity, array $emails, array $params = []) {

    // Do not send updates to the current user.
    if ($current_user_email = $this->currentUser->getEmail()) {
      $emails = array_diff($emails, [$current_user_email]);
    }
    $emails = array_unique(array_filter($emails));

    // Bail if there is no one to send an email to.
    if (empty($emails)) {
      return;
    }

    // Set the entity param.
    $params['entity'] = $entity;

    // Delegate to farm_rothamsted_notification.
    $this->mailManager->mail('farm_rothamsted_notification', 'entity_template', implode(', ', $emails), 'en', $params);
  }

}
