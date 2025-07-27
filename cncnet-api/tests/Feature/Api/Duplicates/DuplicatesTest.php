<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DuplicatesTest extends TestCase
{
    use RefreshDatabase;
    use JwtAuthHelper;

    private $admin;
    private $max;
    private $maxime;
    private $helen;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin user who performs all the tests.
        $this->admin = User::create([
            'name' => 'Superman',
            'email' => 'superman@test.com',
            'password' => Hash::make('testpass'),
        ]);
        $this->admin->group = 'Admin';
        $this->admin->alias = 'Superman';
        $this->admin->save();

        $this->max = User::create([
            'name' => 'Max',
            'email' => 'max@example.com',
            'password' => Hash::make('secret'),
        ]);
        $this->max->alias = 'Max';
        $this->max->save();

        $this->helen = User::create([
            'name' => 'Helen',
            'email' => 'helen@example.com',
            'password' => Hash::make('secret')
        ]);

        $this->maxime = User::create([
            'name' => 'Maxime',
            'email' => 'maxime@example.com',
            'password' => Hash::make('secret')
        ]);
    }

    public function test_link_unconfirmed_primary_to_duplicate(): void
    {
        // This is the most complex scenario. We are linking a duplicate 

        $this->actingAs($this->admin);

        // First, connect Max and Maxime.
        $response = $this->post(route('users.duplicate.confirm'), [
            'user_id' => $this->max->id,
            'duplicate_user_id' => $this->maxime->id,
        ]);
        $response->assertRedirect();
        $response->assertSessionMissing('errors');
        $this->check_maxime_is_duplicate_of_max();

        // Now link Helen to Maxime.
        $response = $this->post(route('users.duplicate.confirm'), [
            'user_id' => $this->helen->id,
            'duplicate_user_id' => $this->maxime->id,
        ]);
        
        $this->check_maxime_and_helen_are_duplicates_of_max();
    }

    public function test_link_duplicate_to_unconfirmed_primary(): void
    {
        // This is the most complex scenario. We are linking a duplicate 

        $this->actingAs($this->admin);

        // First, connect Max and Maxime.
        $response = $this->post(route('users.duplicate.confirm'), [
            'user_id' => $this->max->id,
            'duplicate_user_id' => $this->maxime->id,
        ]);
        $response->assertRedirect();
        $response->assertSessionMissing('errors');
        $this->check_maxime_is_duplicate_of_max();

        // Now link Maxime to Helen.
        $response = $this->post(route('users.duplicate.confirm'), [
            'user_id' => $this->maxime->id,
            'duplicate_user_id' => $this->helen->id,
        ]);
        
        $this->check_maxime_and_helen_are_duplicates_of_max();
    }

    public function test_link_and_unlink_unconfirmed_to_unconfirmed(): void
    {
        $this->actingAs($this->admin);
        
        // When linking two unconfirmed primary accounts, user_d becomes
        // confirmed primary and duplicate_user_id becomes duplicates -
        // just like you would expect.

        $response = $this->post(route('users.duplicate.confirm'), [
            'user_id' => $this->helen->id,
            'duplicate_user_id' => $this->maxime->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionMissing('errors');

        $this->helen->refresh();
        $this->assertTrue($this->helen->isConfirmedPrimary());
        $this->assertTrue($this->helen->hasDuplicate($this->maxime->id));

        $this->maxime->refresh();
        $this->assertTrue($this->maxime->isDuplicate());
        $this->assertTrue($this->maxime->hasDuplicate($this->helen->id));
        $this->assertTrue($this->maxime->primaryId() === $this->helen->id);

        $response = $this->post(route('users.duplicate.unlink'), [
            'user_id' => $this->maxime->id,
            'duplicate_user_id' => $this->helen->id,
        ]);

        $this->helen->refresh();
        $this->assertTrue($this->helen->isUnconfirmedPrimary());
        $this->assertFalse($this->helen->hasDuplicate($this->maxime->id));

        $this->maxime->refresh();
        $this->assertFalse($this->maxime->isDuplicate());
        $this->assertFalse($this->maxime->hasDuplicate($this->maxime->id));
        $this->assertFalse($this->maxime->primaryId() === $this->helen->id);
    }

    public function check_maxime_is_duplicate_of_max(): void
    {
        // Maxime is a duplicate of Max.
        $this->assertDatabaseHas('users', [
            'id' => $this->maxime->id,
            'primary_user_id' => $this->max->id,
        ]);

        // Max' primary_user_id is now his own id, which indicates, that he has
        // other users assigned to him
        $this->assertDatabaseHas('users', [
            'id' => $this->max->id,
            'primary_user_id' => $this->max->id,
        ]);

        // Refresh both from database and check User methods.
        $this->max->refresh();
        $this->maxime->refresh();
        $this->assertTrue($this->max->isConfirmedPrimary());
        $this->assertTrue($this->max->hasDuplicates());
        $this->assertTrue($this->maxime->isDuplicate());

        // Getting duplicates for Max without himself.
        $this->assertTrue($this->max->collectDuplicates(false)->count() == 1);
        $this->assertContains($this->maxime->id, $this->max->collectDuplicates(false)->pluck('id')->all());

        // Getting duplicates for Max including himself.
        $this->assertTrue($this->max->collectDuplicates(true)->count() == 2);
        $this->assertContains($this->max->id, $this->max->collectDuplicates(true)->pluck('id')->all());
        $this->assertContains($this->maxime->id, $this->max->collectDuplicates(true)->pluck('id')->all());
        
        // Maxime has same results.
        $this->assertTrue($this->maxime->collectDuplicates(false)->count() == 1);
        $this->assertContains($this->max->id, $this->maxime->collectDuplicates(false)->pluck('id')->all());

        // Getting duplicates for Max including himself.
        $this->assertTrue($this->maxime->collectDuplicates(true)->count() == 2);
        $this->assertContains($this->max->id, $this->maxime->collectDuplicates(true)->pluck('id')->all());
        $this->assertContains($this->maxime->id, $this->maxime->collectDuplicates(true)->pluck('id')->all());
    }

    public function check_maxime_and_helen_are_duplicates_of_max(): void
    {
        // Maxime is a duplicate of Max.
        $this->assertDatabaseHas('users', [
            'id' => $this->maxime->id,
            'primary_user_id' => $this->max->id,
        ]);

        // Helen is a duplicate of Max.
        $this->assertDatabaseHas('users', [
            'id' => $this->helen->id,
            'primary_user_id' => $this->max->id,
        ]);

        // Max' primary_user_id is now his own id, which indicates, that he has
        // other users assigned to him
        $this->assertDatabaseHas('users', [
            'id' => $this->max->id,
            'primary_user_id' => $this->max->id,
        ]);

        // Refresh all involved users from database and check User methods.
        $this->max->refresh();
        $this->maxime->refresh();
        $this->helen->refresh();
        $this->assertTrue($this->max->isConfirmedPrimary());
        $this->assertTrue($this->max->hasDuplicates());
        $this->assertTrue($this->maxime->isDuplicate());
        $this->assertTrue($this->helen->isDuplicate());

        // Getting duplicates for Max without himself.
        $this->assertTrue($this->max->collectDuplicates(false)->count() == 2);
        $this->assertContains($this->maxime->id, $this->max->collectDuplicates(false)->pluck('id')->all());
        $this->assertContains($this->helen->id, $this->max->collectDuplicates(false)->pluck('id')->all());

        // Getting duplicates for Max including himself.
        $this->assertTrue($this->max->collectDuplicates(true)->count() == 3);
        $this->assertContains($this->max->id, $this->max->collectDuplicates(true)->pluck('id')->all());
        $this->assertContains($this->maxime->id, $this->max->collectDuplicates(true)->pluck('id')->all());
        $this->assertContains($this->helen->id, $this->max->collectDuplicates(false)->pluck('id')->all());

        // Maxime has same results.
        $this->assertTrue($this->maxime->collectDuplicates(false)->count() == 2);
        $this->assertContains($this->max->id, $this->maxime->collectDuplicates(false)->pluck('id')->all());
        $this->assertContains($this->helen->id, $this->maxime->collectDuplicates(false)->pluck('id')->all());

        // Getting duplicates for Max including himself.
        $this->assertTrue($this->maxime->collectDuplicates(true)->count() == 3);
        $this->assertContains($this->max->id, $this->maxime->collectDuplicates(true)->pluck('id')->all());
        $this->assertContains($this->maxime->id, $this->maxime->collectDuplicates(true)->pluck('id')->all());
        $this->assertContains($this->helen->id, $this->maxime->collectDuplicates(true)->pluck('id')->all());

        // Same for helen.
        $this->assertTrue($this->helen->collectDuplicates(true)->count() == 3);
        $this->assertContains($this->max->id, $this->helen->collectDuplicates(true)->pluck('id')->all());
        $this->assertContains($this->maxime->id, $this->helen->collectDuplicates(true)->pluck('id')->all());
        $this->assertContains($this->helen->id, $this->helen->collectDuplicates(true)->pluck('id')->all());
    }

    public function test_link_unconfirmed_to_confirmed_primary(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue($this->max->isConfirmedPrimary());
        $this->assertFalse($this->max->hasDuplicates());
        $this->assertFalse($this->maxime->isDuplicate());
        
        $response = $this->post(route('users.duplicate.confirm'), [
            'user_id' => $this->max->id,
            'duplicate_user_id' => $this->maxime->id,
        ]);

        // Assume a redirect with no errors.
        $response->assertRedirect();
        $response->assertSessionMissing('errors');
  
        $this->check_maxime_is_duplicate_of_max();
    }

    public function test_link_confirmed_primary_to_unconfirmed(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue($this->max->isConfirmedPrimary());
        $this->assertFalse($this->max->hasDuplicates());
        $this->assertFalse($this->maxime->isDuplicate());
        
        $response = $this->post(route('users.duplicate.confirm'), [
            'user_id' => $this->maxime->id,
            'duplicate_user_id' => $this->max->id,
        ]);

        // Assume a redirect with no errors.
        $response->assertRedirect();
        $response->assertSessionMissing('errors');

        $this->check_maxime_is_duplicate_of_max();
    }

    public function test_unlink_duplicate_from_confirmed_primary(): void
    {
        $this->actingAs($this->admin);

        $max = User::where('name', 'Max')->firstOrFail();
        $maxime = User::where('name', 'Maxime')->firstOrFail();

        $this->assertTrue($max->isConfirmedPrimary());
        $this->assertFalse($max->hasDuplicates());
        $this->assertFalse($maxime->isDuplicate());

        // Confirm duplicate first.
        $response = $this->post(route('users.duplicate.confirm'), [
            'user_id' => $max->id,
            'duplicate_user_id' => $maxime->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionMissing('errors');

        // Quick check.
        $max->refresh();
        $maxime->refresh();
        $this->assertTrue($max->isConfirmedPrimary());
        $this->assertTrue($max->hasDuplicates());
        $this->assertTrue($maxime->isDuplicate());

        // Unlink duplicate.
        $response = $this->post(route('users.duplicate.unlink'), [
            'user_id' => $max->id,
            'duplicate_user_id' => $maxime->id,
        ]);

        // We are back to normal.
        $max->refresh();
        $maxime->refresh();
        $this->assertTrue($max->isConfirmedPrimary());
        $this->assertFalse($max->hasDuplicates());
        $this->assertFalse($maxime->isDuplicate());

        // Primary user ids of both users have been deleted.
        $this->assertDatabaseHas('users', [
            'id' => $maxime->id,
            'primary_user_id' => null,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $max->id,
            'primary_user_id' => null,
        ]);
    }

    public function test_check_bad_link_attempts(): void
    {
        $this->actingAs($this->admin);

        $max = User::where('name', 'Max')->firstOrFail();
        $maxime = User::where('name', 'Maxime')->firstOrFail();

        // Can't link a user to himself.
        $response = $this->post(route('users.duplicate.confirm'), [
            'user_id' => $max->id,
            'duplicate_user_id' => $max->id,
        ]);

        $response->assertSessionHasErrors(['duplicate_action']);

        // Can't link to unknown user id.
        $response = $this->post(route('users.duplicate.confirm'), [
            'user_id' => $max->id,
            'duplicate_user_id' => -1,
        ]);

        $response->assertSessionHasErrors(['duplicate_action']);

        // Can't link confirmed primary to confirmed primary.
        $response = $this->post(route('users.duplicate.confirm'), [
            'user_id' => $max->id,
            'duplicate_user_id' => $this->admin->id,
        ]);

        $response->assertSessionHasErrors(['duplicate_action']);

        // Link users.
        $response = $this->post(route('users.duplicate.confirm'), [
            'user_id' => $max->id,
            'duplicate_user_id' => $maxime->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionMissing('errors');

        // Can't link Maxime to admin, because he already points to a primary account (Max).
        $response = $this->post(route('users.duplicate.confirm'), [
            'user_id' => $this->admin->id,
            'duplicate_user_id' => $maxime->id,
        ]);

        $response->assertSessionHasErrors(['duplicate_action']);
    }

    public function test_check_bad_unlink_attempts(): void
    {
        $this->actingAs($this->admin);

        $max = User::where('name', 'Max')->firstOrFail();
        $maxime = User::where('name', 'Maxime')->firstOrFail();

        // Link users.
        $response = $this->post(route('users.duplicate.confirm'), [
            'user_id' => $max->id,
            'duplicate_user_id' => $maxime->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionMissing('errors');

        // Unknown duplicate users.
        $response = $this->post(route('users.duplicate.unlink'), [
            'user_id' => $max->id,
            'duplicate_user_id' => -1,
        ]);

        $response->assertSessionHasErrors(['duplicate_user_id']);

        // Unknown primary users.
        $response = $this->post(route('users.duplicate.unlink'), [
            'user_id' => -1,
            'duplicate_user_id' => $maxime->id,
        ]);

        $response->assertSessionHasErrors(['user_id']);

        // Can't self-unlink.
        $response = $this->post(route('users.duplicate.unlink'), [
            'user_id' => $maxime->id,
            'duplicate_user_id' => $maxime->id,
        ]);

        $response->assertSessionHasErrors(['duplicate_action']);

        // Unlinking with swapped users ids works.
        // is fixed.
        $response = $this->post(route('users.duplicate.unlink'), [
            'user_id' => $maxime->id,
            'duplicate_user_id' => $max->id,
        ]);

        $response->assertSessionMissing('errors');
    }

}
