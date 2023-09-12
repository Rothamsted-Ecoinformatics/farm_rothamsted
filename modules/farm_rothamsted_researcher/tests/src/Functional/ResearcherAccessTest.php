<?php

namespace Drupal\Tests\farm_rothamsted_experiment_research\Functional;

use Drupal\farm_rothamsted_researcher\Entity\RothamstedResearcher;
use Drupal\Tests\farm_test\Functional\FarmBrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests access logic for Rothamsted Researcher CRUD.
 */
class ResearcherAccessTest extends FarmBrowserTestBase {

  /**
   * Test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Test researcher.
   *
   * @var \Drupal\farm_rothamsted_researcher\Entity\RothamstedResearcherInterface
   */
  protected $researcher;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'farm_rothamsted_researcher',
    'farm_ui_views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create roles for view/update assigned/any.
    Role::create([
      'id' => 'researcher_view_assigned',
      'label' => 'View assigned',
      'permissions' => [
        'view assigned rothamsted_researcher',
      ],
    ])->save();
    Role::create([
      'id' => 'researcher_view_any',
      'label' => 'View any',
      'permissions' => [
        'view any rothamsted_researcher',
      ],
    ])->save();
    Role::create([
      'id' => 'researcher_update_assigned',
      'label' => 'Update assigned',
      'permissions' => [
        'update assigned rothamsted_researcher',
      ],
    ])->save();
    Role::create([
      'id' => 'researcher_update_any',
      'label' => 'Update any',
      'permissions' => [
        'update any rothamsted_researcher',
      ],
    ])->save();

    // Researcher entity.
    $this->researcher = RothamstedResearcher::create([
      'name' => 'Tester',
      'role' => 'lead_scientist',
      'organization' => 'Rothamsted',
    ]);
    $this->researcher->save();

    // Create and login a user with necessary permissions.
    $this->user = $this->createUser();
    $this->drupalLogin($this->user);
  }

  /**
   * Test that custom blocks are added to the dashboard.
   */
  public function testResearcherAccess() {

    // Ensure the researcher was not created by the test user.
    $this->assertNotEquals($this->researcher->getOwnerId(), $this->user->id());

    $researcher_id = $this->researcher->id();
    $researcher_path = "/rothamsted/researcher/$researcher_id";

    // Test new user has no access.
    $this->drupalGet($researcher_path);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$researcher_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$researcher_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user view any role.
    $this->user->addRole('researcher_view_any');
    $this->user->save();

    // Test user only has view access.
    $this->drupalGet($researcher_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$researcher_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$researcher_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Set the farm_user on the researcher.
    $this->user->removeRole('researcher_view_any');
    $this->user->save();
    $this->researcher->set('farm_user', $this->user)->save();

    // Test new user has no access.
    $this->drupalGet($researcher_path);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$researcher_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$researcher_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user view assigned role.
    $this->user->addRole('researcher_view_assigned');
    $this->user->save();

    // Test user only has view access.
    $this->drupalGet($researcher_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$researcher_path/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("$researcher_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user update assigned researcher permission.
    $this->user->addRole('researcher_update_assigned');
    $this->user->save();

    // Test that user has edit access.
    $this->drupalGet($researcher_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$researcher_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$researcher_path/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Grant user update any researcher permission.
    $this->user->removeRole('researcher_update_assigned');
    $this->user->addRole('researcher_update_any');
    $this->user->save();

    // Test that user has edit access.
    $this->drupalGet($researcher_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$researcher_path/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("$researcher_path/delete");
    $this->assertSession()->statusCodeEquals(403);
  }

}
