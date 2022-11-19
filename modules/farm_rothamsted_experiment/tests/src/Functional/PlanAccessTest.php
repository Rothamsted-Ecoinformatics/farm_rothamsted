<?php

namespace Drupal\Tests\farm_rothamsted_experiment\Functional;

use Drupal\plan\Entity\Plan;
use Drupal\Tests\farm_test\Functional\FarmBrowserTestBase;

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
    'farm_ui_views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a plan.
    $this->plan = Plan::create([
      'name' => 'Test experiment plan',
      'type' => 'rothamsted_experiment',
      'column_descriptors' => '',
    ]);
    $this->plan->save();

    // Create and login a user with necessary permissions.
    $this->user = $this->createUser();
    $this->drupalLogin($this->user);
  }

  /**
   * Test that custom blocks are added to the dashboard.
   */
  public function testPlanAccess() {
    $plan_id = $this->plan->id();
    $plan_path = "/plan/$plan_id";

    // Test new user has no access.
    $this->drupalGet($plan_path);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plan_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plan_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user viewer role.
    $this->user->addRole('farm_viewer');
    $this->user->save();

    // Test viewer role user only has view access.
    $this->drupalGet($plan_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plan_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plan_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user sponsor role.
    $this->user->addRole('rothamsted_sponsor');
    $this->user->save();

    // Test viewer + sponsor only has view access.
    $this->drupalGet($plan_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plan_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plan_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Add user to the plan.
    $this->plan->get('contact')->appendItem($this->user);
    $this->plan->save();

    // Test viewer + sponsor role has all access.
    $this->drupalGet($plan_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plan_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plan_path/delete");
    $this->assertSession()->statusCodeEquals(200);

    // Remove user from plan.
    $this->plan->set('contact', []);
    $this->plan->save();

    // Test viewer + sponsor role only has view access.
    $this->drupalGet($plan_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$plan_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$plan_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user the experiment admin role.
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
