<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Property;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected $owner;
    protected $client;
    protected $property;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an Owner
        $this->owner = User::create([
            'name' => 'Test Owner',
            'email' => 'owner_test@rental.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'is_verified' => true,
        ]);

        // Create a Client
        $this->client = User::create([
            'name' => 'Test Client',
            'email' => 'client_test@rental.com',
            'password' => Hash::make('password'),
            'role' => 'client',
            'is_verified' => true,
        ]);

        // Create a Property for the Owner
        $this->property = Property::create([
            'owner_id' => $this->owner->id,
            'titre' => 'Villa de Test',
            'description' => 'Description de test',
            'prix_par_nuit' => 500,
            'localisation' => 'Rabat',
            'type' => 'villa',
            'max_guests' => 4,
        ]);
    }

    public function test_guests_can_view_property_reviews()
    {
        // Add a review manually
        Review::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'rating' => 5,
            'comment' => 'Superbe villa!'
        ]);

        $response = $this->getJson("/api/properties/{$this->property->id}/reviews");

        $response->assertStatus(200)
                 ->assertJsonCount(1)
                 ->assertJsonFragment([
                     'rating' => 5,
                     'comment' => 'Superbe villa!',
                 ]);
    }

    public function test_guest_cannot_submit_a_review()
    {
        $response = $this->postJson("/api/properties/{$this->property->id}/reviews", [
            'rating' => 5,
            'comment' => 'Magnifique!'
        ]);

        $response->assertStatus(401);
    }

    public function test_owner_cannot_submit_a_review()
    {
        $token = auth('api')->login($this->owner);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                         ->postJson("/api/properties/{$this->property->id}/reviews", [
                             'rating' => 5,
                             'comment' => 'Ma propre villa!'
                         ]);

        $response->assertStatus(403)
                 ->assertJsonPath('error', 'Seuls les clients peuvent laisser un avis.');
    }

    public function test_client_without_confirmed_booking_cannot_submit_a_review()
    {
        $token = auth('api')->login($this->client);

        // Scenario 1: No booking at all
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                         ->postJson("/api/properties/{$this->property->id}/reviews", [
                             'rating' => 4,
                             'comment' => 'Pas mal!'
                         ]);

        $response->assertStatus(403)
                 ->assertJsonPath('error', 'Vous devez avoir une réservation confirmée pour évaluer ce bien.');

        // Scenario 2: Booking exists but is pending (not confirmed)
        Booking::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'date_debut' => '2026-06-01',
            'date_fin' => '2026-06-05',
            'prix_total' => 2000,
            'status' => 'pending'
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                         ->postJson("/api/properties/{$this->property->id}/reviews", [
                             'rating' => 4,
                             'comment' => 'Pas mal!'
                         ]);

        $response->assertStatus(403);
    }

    public function test_client_with_confirmed_booking_can_submit_a_review()
    {
        $token = auth('api')->login($this->client);

        // Create a confirmed booking
        Booking::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'date_debut' => '2026-06-01',
            'date_fin' => '2026-06-05',
            'prix_total' => 2000,
            'status' => 'confirmed'
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                         ->postJson("/api/properties/{$this->property->id}/reviews", [
                             'rating' => 5,
                             'comment' => 'Séjour parfait!'
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('message', 'Avis ajouté avec succès')
                 ->assertJsonStructure([
                     'message',
                     'review' => ['id', 'client_id', 'property_id', 'rating', 'comment', 'created_at', 'updated_at']
                 ]);

        $this->assertDatabaseHas('reviews', [
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'rating' => 5,
            'comment' => 'Séjour parfait!'
        ]);
    }

    public function test_client_cannot_submit_duplicate_reviews()
    {
        $token = auth('api')->login($this->client);

        // Create a confirmed booking
        Booking::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'date_debut' => '2026-06-01',
            'date_fin' => '2026-06-05',
            'prix_total' => 2000,
            'status' => 'confirmed'
        ]);

        // Submit the first review
        Review::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'rating' => 5,
            'comment' => 'Premier avis'
        ]);

        // Attempt a duplicate review
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                         ->postJson("/api/properties/{$this->property->id}/reviews", [
                             'rating' => 3,
                             'comment' => 'Deuxième avis'
                         ]);

        $response->assertStatus(400)
                 ->assertJsonPath('error', 'Vous avez déjà évalué ce bien.');
    }
}
