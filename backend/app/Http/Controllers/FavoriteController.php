<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;

class FavoriteController extends Controller
{
    /**
     * Get all favorites for the authenticated client
     */
    public function index()
    {
        $user = auth('api')->user();

        if ($user->role !== 'client') {
            return response()->json(['error' => 'Action non autorisée'], 403);
        }

        // Return the favorited properties with their images
        $favorites = $user->favorites()->with('images')->get();

        return response()->json($favorites);
    }

    /**
     * Toggle a property in favorites
     */
    public function toggle($propertyId)
    {
        $user = auth('api')->user();

        if ($user->role !== 'client') {
            return response()->json(['error' => 'Action non autorisée'], 403);
        }

        // Check if property exists
        $property = Property::findOrFail($propertyId);

        // Toggle the favorite status
        $user->favorites()->toggle($property->id);

        return response()->json([
            'message' => 'Favoris mis à jour avec succès',
            'is_favorite' => $user->favorites()->where('property_id', $property->id)->exists()
        ]);
    }
}
