<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Property;
use App\Models\Booking;
use App\Models\Transaction;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Get platform statistics.
     */
    public function stats()
    {
        $usersCount = User::count();
        $propertiesCount = Property::count();
        $bookingsCount = Booking::count();
        $revenue = Transaction::where('status', 'completed')->sum('montant');

        return response()->json([
            'users_count' => $usersCount,
            'properties_count' => $propertiesCount,
            'bookings_count' => $bookingsCount,
            'revenue' => $revenue ?? 0,
        ]);
    }

    /**
     * Get all users.
     */
    public function users()
    {
        $users = User::select('id', 'name', 'email', 'role', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($users);
    }

    /**
     * Delete a user (cannot delete self).
     */
    public function deleteUser(Request $request, $id)
    {
        $admin = $request->user();

        if ((int)$id === $admin->id) {
            return response()->json([
                'error' => 'Vous ne pouvez pas supprimer votre propre compte.'
            ], 403);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé avec succès.']);
    }

    /**
     * Get all properties with owner info.
     */
    public function properties()
    {
        $properties = Property::with('owner:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($properties);
    }

    /**
     * Delete a property.
     */
    public function deleteProperty($id)
    {
        $property = Property::findOrFail($id);
        $property->delete();

        return response()->json(['message' => 'Annonce supprimée avec succès.']);
    }

    /**
     * Get all bookings with client and property info.
     */
    public function bookings()
    {
        $bookings = Booking::with(['client:id,name,email', 'property:id,titre'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($bookings);
    }

    /**
     * Get all reported reviews.
     */
    public function reportedReviews()
    {
        $reviews = \App\Models\Review::with(['client:id,name,email', 'property:id,titre,owner_id', 'property.owner:id,name'])
            ->where('is_reported', true)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($reviews);
    }

    /**
     * Delete a review.
     */
    public function deleteReview($id)
    {
        $review = \App\Models\Review::findOrFail($id);
        $review->delete();

        return response()->json(['message' => 'L\'avis a été supprimé avec succès.']);
    }

    /**
     * Dismiss a report (un-report the review).
     */
    public function dismissReport($id)
    {
        $review = \App\Models\Review::findOrFail($id);
        $review->update([
            'is_reported' => false,
            'report_reason' => null
        ]);

        return response()->json(['message' => 'Le signalement a été ignoré avec succès.']);
    }
}
