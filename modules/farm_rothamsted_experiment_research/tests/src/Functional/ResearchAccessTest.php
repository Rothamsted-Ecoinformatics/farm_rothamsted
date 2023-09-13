<?php

namespace Drupal\Tests\farm_rothamsted_experiment_research\Functional;

use Drupal\asset\Entity\Asset;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedDesign;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedExperiment;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProgram;
use Drupal\farm_rothamsted_researcher\Entity\RothamstedResearcher;
use Drupal\plan\Entity\Plan;
use Drupal\Tests\farm_test\Functional\FarmBrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the hierarchical research access logic.
 */
class ResearchAccessTest extends FarmBrowserTestBase {

  /**
   * Test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Test researchers.
   *
   * @var \Drupal\farm_rothamsted_researcher\Entity\RothamstedResearcherInterface[]
   */
  protected $researchers;

  /**
   * Test program.
   *
   * @var \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProgramInterface
   */
  protected $program;

  /**
   * Test experiment.
   *
   * @var \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedExperimentInterface
   */
  protected $experiment;

  /**
   * Test design.
   *
   * @var \Drupal\farm_rothamsted_experiment_research\Entity\RothamstedDesignInterface
   */
  protected $design;

  /**
   * Test experiment plan.
   *
   * @var \Drupal\plan\Entity\PlanInterface
   */
  protected $plan;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'farm_rothamsted_experiment',
    'farm_rothamsted_experiment_research',
    'farm_rothamsted_researcher',
    'farm_ui_views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create user.
    $this->user = $this->createUser();

    // Research entities.
    $new_researchers = [
      [
        'name' => 'Researcher 1',
        'role' => 'lead_scientist',
        'organization' => 'Rothamsted',
        'department' => 'Pathology',
      ],
      [
        'name' => 'Researcher 2',
        'role' => 'phd_student',
        'organization' => 'Rothamsted',
        'department' => 'Soils',
        'farm_user' => $this->user,
      ],
      [
        'name' => 'Statistician',
        'role' => 'statistician',
        'organization' => 'Rothamsted',
        'department' => 'Soils',
      ],
    ];
    $this->researchers = [];
    foreach ($new_researchers as $researcher) {
      $new = RothamstedResearcher::create([
        'name' => $researcher['name'],
        'role' => $researcher['role'],
        'organization' => $researcher['organization'],
        'department' => $researcher['department'],
        'farm_user' => $researcher['farm_user'] ?? NULL,
      ]);
      $new->save();
      $this->researchers[] = $new;
    }
    $this->program = RothamstedProgram::create([
      'code' => 'P01-TEST',
      'name' => 'Program 1',
      'abbreviation' => 'P01',
      'principal_investigator' => $new_researchers[0],
    ]);
    $this->program->save();
    $this->experiment = RothamstedExperiment::create([
      'program' => $this->program,
      'code' => 'P01-E01',
      'name' => 'Experiment 1',
      'abbreviation' => 'E01',
    ]);
    $this->experiment->save();
    $this->design = RothamstedDesign::create([
      'experiment' => $this->experiment,
      'name' => 'Design 1',
      'description' => 'Initial design for experiment 1',
      'statistician' => reset($this->researchers),
    ]);
    $this->design->save();

    // Experiment plan.
    $this->plan = Plan::create([
      'type' => 'rothamsted_experiment',
      'name' => 'Experiment 1',
      'experiment_design' => $this->design,
    ]);
    $this->plan->save();

    // Login user.
    $this->drupalLogin($this->user);
  }

  /**
   * Test access logic on programs.
   */
  public function testProgramAccess() {

    // Create roles for view/update assigned/any.
    Role::create([
      'id' => 'program_view_assigned',
      'label' => 'View assigned',
      'permissions' => [
        'view research_assigned rothamsted_program',
      ],
    ])->save();
    Role::create([
      'id' => 'program_view_any',
      'label' => 'View any',
      'permissions' => [
        'view any rothamsted_program',
      ],
    ])->save();
    Role::create([
      'id' => 'program_update_assigned',
      'label' => 'Update assigned',
      'permissions' => [
        'update research_assigned rothamsted_program',
      ],
    ])->save();
    Role::create([
      'id' => 'program_update_any',
      'label' => 'Update any',
      'permissions' => [
        'update any rothamsted_program',
      ],
    ])->save();

    $program_id = $this->program->id();
    $program_path = "/rothamsted/program/$program_id";

    // Test new user has no access.
    $this->drupalGet($program_path);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$program_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$program_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user view any role.
    $this->user->addRole('program_view_any');
    $this->user->save();

    // Test user only has view access.
    $this->drupalGet($program_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$program_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$program_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Remove role.
    $this->user->removeRole('program_view_any');
    $this->user->save();

    // Add user to the program.
    $this->program->set('principal_investigator', [$this->researchers[0], $this->researchers[1]]);
    $this->program->save();

    // Test user has no view access.
    $this->drupalGet($program_path);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$program_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$program_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the view assigned role.
    $this->user->addRole('program_view_assigned');
    $this->user->save();

    // Test user only has view access.
    $this->drupalGet($program_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$program_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$program_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the update assigned role.
    $this->user->addRole('program_update_assigned');
    $this->user->save();

    // Test user has view + update access.
    $this->drupalGet($program_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$program_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$program_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the update any assigned role.
    $this->user->removeRole('program_update_assigned');
    $this->user->addRole('program_update_any');
    $this->user->save();

    // Test user has view + update access.
    $this->drupalGet($program_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$program_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$program_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the experiment admin role.
    $this->user->removeRole('program_view_assigned');
    $this->user->removeRole('program_update_any');
    $this->user->addRole('rothamsted_experiment_admin');
    $this->user->save();

    // Test experiment admin role has all access.
    $this->drupalGet($program_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$program_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$program_path/delete");
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test access logic on experiments.
   */
  public function testExperimentAccess() {

    // Create roles for view/update assigned/any.
    Role::create([
      'id' => 'experiment_view_assigned',
      'label' => 'View assigned',
      'permissions' => [
        'view research_assigned rothamsted_experiment',
      ],
    ])->save();
    Role::create([
      'id' => 'experiment_view_any',
      'label' => 'View any',
      'permissions' => [
        'view any rothamsted_experiment',
      ],
    ])->save();
    Role::create([
      'id' => 'experiment_update_assigned',
      'label' => 'Update assigned',
      'permissions' => [
        'update research_assigned rothamsted_experiment',
      ],
    ])->save();
    Role::create([
      'id' => 'experiment_update_any',
      'label' => 'Update any',
      'permissions' => [
        'update any rothamsted_experiment',
      ],
    ])->save();

    $experiment_id = $this->experiment->id();
    $experiment_path = "/rothamsted/experiment/$experiment_id";

    // Test new user has no access.
    $this->drupalGet($experiment_path);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$experiment_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$experiment_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user view any role.
    $this->user->addRole('experiment_view_any');
    $this->user->save();

    // Test user only has view access.
    $this->drupalGet($experiment_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$experiment_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$experiment_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Remove role.
    $this->user->removeRole('experiment_view_any');
    $this->user->save();

    // Add user to the experiment.
    $this->experiment->set('researcher', [$this->researchers[0], $this->researchers[1]]);
    $this->experiment->save();

    // Test user has no view access.
    $this->drupalGet($experiment_path);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$experiment_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$experiment_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the view assigned role.
    $this->user->addRole('experiment_view_assigned');
    $this->user->save();

    // Test user only has view access.
    $this->drupalGet($experiment_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$experiment_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$experiment_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the update assigned role.
    $this->user->addRole('experiment_update_assigned');
    $this->user->save();

    // Test user has view + update access.
    $this->drupalGet($experiment_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$experiment_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$experiment_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the update any assigned role.
    $this->user->removeRole('experiment_update_assigned');
    $this->user->addRole('experiment_update_any');
    $this->user->save();

    // Test user has view + update access.
    $this->drupalGet($experiment_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$experiment_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$experiment_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the experiment admin role.
    $this->user->removeRole('experiment_view_assigned');
    $this->user->removeRole('experiment_update_any');
    $this->user->addRole('rothamsted_experiment_admin');
    $this->user->save();

    // Test experiment admin role has all access.
    $this->drupalGet($experiment_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$experiment_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$experiment_path/delete");
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test access logic on designs.
   */
  public function testDesignAccess() {

    // Create roles for view/update assigned/any.
    Role::create([
      'id' => 'design_view_assigned',
      'label' => 'View assigned',
      'permissions' => [
        'view research_assigned rothamsted_design',
      ],
    ])->save();
    Role::create([
      'id' => 'design_view_any',
      'label' => 'View any',
      'permissions' => [
        'view any rothamsted_design',
      ],
    ])->save();
    Role::create([
      'id' => 'design_update_assigned',
      'label' => 'Update assigned',
      'permissions' => [
        'update research_assigned rothamsted_design',
      ],
    ])->save();
    Role::create([
      'id' => 'design_update_any',
      'label' => 'Update any',
      'permissions' => [
        'update any rothamsted_design',
      ],
    ])->save();

    $design_id = $this->design->id();
    $design_path = "/rothamsted/design/$design_id";

    // Test new user has no access.
    $this->drupalGet($design_path);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$design_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$design_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user view any role.
    $this->user->addRole('design_view_any');
    $this->user->save();

    // Test user only has view access.
    $this->drupalGet($design_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$design_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$design_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Remove role.
    $this->user->removeRole('design_view_any');
    $this->user->save();

    // Add user to the experiment.
    $this->experiment->set('researcher', [$this->researchers[0], $this->researchers[1]]);
    $this->experiment->save();

    // Test user has no view access.
    $this->drupalGet($design_path);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$design_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$design_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the view assigned role.
    $this->user->addRole('design_view_assigned');
    $this->user->save();

    // Test user only has view access.
    $this->drupalGet($design_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$design_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$design_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the update assigned role.
    $this->user->addRole('design_update_assigned');
    $this->user->save();

    // Test user has view + update access.
    $this->drupalGet($design_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$design_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$design_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the update any assigned role.
    $this->user->removeRole('design_update_assigned');
    $this->user->addRole('design_update_any');
    $this->user->save();

    // Test user has view + update access.
    $this->drupalGet($design_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$design_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$design_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the experiment admin role.
    $this->user->removeRole('design_view_assigned');
    $this->user->removeRole('design_update_any');
    $this->user->addRole('rothamsted_experiment_admin');
    $this->user->save();

    // Test experiment admin role has all access.
    $this->drupalGet($design_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$design_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$design_path/delete");
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test access logic on plans.
   */
  public function testPlanAccess() {

    // Create roles for view/update assigned/any.
    Role::create([
      'id' => 'plan_view_assigned',
      'label' => 'View assigned',
      'permissions' => [
        'view research_assigned rothamsted_experiment plan',
      ],
    ])->save();
    Role::create([
      'id' => 'plan_view_any',
      'label' => 'View any',
      'permissions' => [
        'view any rothamsted_experiment plan',
      ],
    ])->save();
    Role::create([
      'id' => 'plan_update_assigned',
      'label' => 'Update assigned',
      'permissions' => [
        'update research_assigned rothamsted_experiment plan',
      ],
    ])->save();
    Role::create([
      'id' => 'plan_update_any',
      'label' => 'Update any',
      'permissions' => [
        'update any rothamsted_experiment plan',
      ],
    ])->save();

    $plan_id = $this->plan->id();
    $plan_path = "/plan/$plan_id";

    // Test new user has no access.
    $this->drupalGet($plan_path);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plan_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plan_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user view any role.
    $this->user->addRole('plan_view_any');
    $this->user->save();

    // Test user only has view access.
    $this->drupalGet($plan_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plan_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plan_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Remove role.
    $this->user->removeRole('plan_view_any');
    $this->user->save();

    // Add user to the experiment.
    $this->experiment->set('researcher', [$this->researchers[0], $this->researchers[1]]);
    $this->experiment->save();

    // Test user has no view access.
    $this->drupalGet($plan_path);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plan_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plan_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the view assigned role.
    $this->user->addRole('plan_view_assigned');
    $this->user->save();

    // Test user only has view access.
    $this->drupalGet($plan_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plan_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plan_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the update assigned role.
    $this->user->addRole('plan_update_assigned');
    $this->user->save();

    // Test user has view + update access.
    $this->drupalGet($plan_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plan_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plan_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the update any assigned role.
    $this->user->removeRole('plan_update_assigned');
    $this->user->addRole('plan_update_any');
    $this->user->save();

    // Test user has view + update access.
    $this->drupalGet($plan_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plan_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plan_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the experiment admin role.
    $this->user->removeRole('plan_view_assigned');
    $this->user->removeRole('plan_update_any');
    $this->user->addRole('rothamsted_experiment_admin');
    $this->user->save();

    // Test experiment admin role has all access.
    $this->drupalGet($plan_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plan_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plan_path/delete");
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test access logic on plots.
   */
  public function testPlotAccess() {

    // Create roles for view/update assigned/any.
    Role::create([
      'id' => 'plot_view_assigned',
      'label' => 'View assigned',
      'permissions' => [
        'view research_assigned plot asset',
      ],
    ])->save();
    Role::create([
      'id' => 'plot_view_any',
      'label' => 'View any',
      'permissions' => [
        'view any plot asset',
      ],
    ])->save();
    Role::create([
      'id' => 'plot_update_assigned',
      'label' => 'Update assigned',
      'permissions' => [
        'update research_assigned plot asset',
      ],
    ])->save();
    Role::create([
      'id' => 'plot_update_any',
      'label' => 'Update any',
      'permissions' => [
        'update any plot asset',
      ],
    ])->save();

    // Create a plot and add to the plan.
    $plot = Asset::create([
      'type' => 'plot',
      'plot_id' => 1,
    ]);
    $plot->save();
    $this->plan->set('plot', $plot);
    $this->plan->save();

    // Plot path.
    $plot_id = $plot->id();
    $plot_path = "/asset/$plot_id";

    // Test new user has no access.
    $this->drupalGet($plot_path);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plot_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plot_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user view any role.
    $this->user->addRole('plot_view_any');
    $this->user->save();

    // Test user only has view access.
    $this->drupalGet($plot_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plot_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plot_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Remove role.
    $this->user->removeRole('plot_view_any');
    $this->user->save();

    // Add user to the experiment.
    $this->experiment->set('researcher', [$this->researchers[0], $this->researchers[1]]);
    $this->experiment->save();

    // Test user has no view access.
    $this->drupalGet($plot_path);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plot_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plot_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the view assigned role.
    $this->user->addRole('plot_view_assigned');
    $this->user->save();

    // Test user only has view access.
    $this->drupalGet($plot_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plot_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plot_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the update assigned role.
    $this->user->addRole('plot_update_assigned');
    $this->user->save();

    // Test user has view + update access.
    $this->drupalGet($plot_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plot_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plot_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the update any assigned role.
    $this->user->removeRole('plot_update_assigned');
    $this->user->addRole('plot_update_any');
    $this->user->save();

    // Test user has view + update access.
    $this->drupalGet($plot_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plot_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plot_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the experiment admin role.
    $this->user->removeRole('plot_view_assigned');
    $this->user->removeRole('plot_update_any');
    $this->user->addRole('rothamsted_experiment_admin');
    $this->user->save();

    // Test experiment admin role has all access.
    $this->drupalGet($plot_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plot_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plot_path/delete");
    $this->assertSession()->statusCodeEquals(200);
  }

}
