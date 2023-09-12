<?php

namespace Drupal\Tests\farm_rothamsted_experiment_research\Functional;

use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedDesign;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedExperiment;
use Drupal\farm_rothamsted_experiment_research\Entity\RothamstedProgram;
use Drupal\farm_rothamsted_researcher\Entity\RothamstedResearcher;
use Drupal\plan\Entity\Plan;
use Drupal\Tests\farm_test\Functional\FarmBrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the farmOS dashboard functionality.
 */
class PlanAccessTest extends FarmBrowserTestBase {

  /**
   * Test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

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

    // Create and login a user with necessary permissions.
    $this->user = $this->createUser();
    $this->drupalLogin($this->user);
  }

  /**
   * Test that custom blocks are added to the dashboard.
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
    $researchers = [];
    foreach ($new_researchers as $researcher) {
      $new = RothamstedResearcher::create([
        'name' => $researcher['name'],
        'role' => $researcher['role'],
        'organization' => $researcher['organization'],
        'department' => $researcher['department'],
        'farm_user' => $researcher['farm_user'] ?? NULL,
      ]);
      $new->save();
      $researchers[] = $new;
    }
    $program = RothamstedProgram::create([
      'code' => 'P01-TEST',
      'name' => 'Program 1',
      'abbreviation' => 'P01',
      'principal_investigator' => $new_researchers[0],
    ]);
    $program->save();
    $experiment = RothamstedExperiment::create([
      'program' => $program,
      'code' => 'P01-E01',
      'name' => 'Experiment 1',
      'abbreviation' => 'E01',
    ]);
    $experiment->save();
    $design = RothamstedDesign::create([
      'experiment' => $experiment,
      'name' => 'Design 1',
      'description' => 'Initial design for experiment 1',
      'statistician' => reset($researchers),
    ]);
    $design->save();

    // Experiment plan.
    $plan = Plan::create([
      'type' => 'rothamsted_experiment',
      'name' => 'Experiment 1',
      'experiment_design' => $design,
    ]);
    $plan->save();

    $plan_id = $plan->id();
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
    $experiment->set('researcher', [$researchers[0], $researchers[1]]);
    $experiment->save();

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

}
