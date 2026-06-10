<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PropertyController extends Controller
{
    // 1. جلب كاع العقارات (يقدر يشوفهم أي واحد)
    public function index(Request $request)
    {
        $query = Property::with(['images', 'reviews', 'owner'])->where('is_active', true);

        // Filters
        if ($request->has('min_price')) {
            $query->where('prix_par_nuit', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('prix_par_nuit', '<=', $request->max_price);
        }
        if ($request->has('rooms_count') && $request->rooms_count > 0) {
            $query->where('rooms_count', '>=', $request->rooms_count);
        }
        if ($request->has('has_wifi') && $request->has_wifi == 'true') {
            $query->where('has_wifi', true);
        }
        if ($request->has('has_pool') && $request->has_pool == 'true') {
            $query->where('has_pool', true);
        }

        $properties = $query->latest()->paginate(20);
        return response()->json($properties);
    }

    // 2. إضافة عقار جديد (خاص يكون مكونيكطي ويكون owner)
    public function store(Request $request)
    {
        $user = auth('api')->user();

        // حماية: التأكد واش المستخدم عندو دور 'owner'
        if ($user->role !== 'owner') {
            return response()->json(['error' => 'Accès refusé. Seuls les propriétaires peuvent ajouter des annonces.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix_par_nuit' => 'required|numeric|min:0',
            'localisation' => 'required|string',
            'type' => 'required|string|in:appartement,villa,maison,studio,riad',
            'max_guests' => 'required|integer|min:1',
            'rooms_count' => 'integer|min:1',
            'has_wifi' => 'boolean',
            'has_pool' => 'boolean',
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // إنشاء العقار وربطه بالمالك
        $property = Property::create([
            'owner_id' => $user->id,
            'titre' => $request->titre,
            'description' => $request->description,
            'prix_par_nuit' => $request->prix_par_nuit,
            'localisation' => $request->localisation,
            'type' => $request->type,
            'max_guests' => $request->max_guests ?? 1,
            'rooms_count' => $request->rooms_count ?? 1,
            'has_wifi' => filter_var($request->has_wifi, FILTER_VALIDATE_BOOLEAN),
            'has_pool' => filter_var($request->has_pool, FILTER_VALIDATE_BOOLEAN),
        ]);

        // حفظ الصور يلا كانو مرفقين
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                // حفظ الصورة فـ storage/app/public/properties
                $path = $image->store('properties', 'public');

                PropertyImage::create([
                    'property_id' => $property->id,
                    'image_path' => $path,
                ]);
            }
        }

        // نرجعو العقار مع تصاورو باش نتأكدو
        return response()->json([
            'message' => 'Annonce créée avec succès',
            'property' => $property->load('images')
        ], 201);
    }

    // 3. عرض تفاصيل عقار واحد
    public function show($id)
    {
        $property = Property::with(['images', 'owner:id,name', 'reviews.client:id,name'])->where('is_active', true)->findOrFail($id);
        return response()->json($property);
    }

    // 4. تعديل عقار (خاص يكون المالك ديالو)
    public function update(Request $request, $id)
    {
        $user = auth('api')->user();
        $property = Property::findOrFail($id);

        if ($property->owner_id !== $user->id) {
            return response()->json(['error' => 'Non autorisé. Vous n\'êtes pas le propriétaire.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'titre' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'prix_par_nuit' => 'sometimes|required|numeric|min:0',
            'localisation' => 'sometimes|required|string',
            'type' => 'sometimes|required|string',
            'max_guests' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $property->update($request->only([
            'titre', 'description', 'prix_par_nuit', 'localisation', 'type', 'max_guests', 'is_active'
        ]));

        return response()->json([
            'message' => 'Annonce mise à jour avec succès',
            'property' => $property->load('images')
        ]);
    }

    // 5. حذف عقار (خاص يكون المالك ديالو)
    public function destroy($id)
    {
        $user = auth('api')->user();
        $property = Property::findOrFail($id);

        if ($property->owner_id !== $user->id) {
            return response()->json(['error' => 'Non autorisé. Vous n\'êtes pas le propriétaire.'], 403);
        }

        $property->delete();

        return response()->json(['message' => 'Annonce supprimée avec succès']);
    }

    // Owner's properties
    public function myProperties()
    {
        $user = auth('api')->user();
        $properties = Property::with(['images', 'reviews.client'])
            ->where('owner_id', $user->id)
            ->latest()
            ->get();
        return response()->json($properties);
    }

    // 6. Get booked dates for a property
    public function getBookedDates($id)
    {
        $bookings = \App\Models\Booking::where('property_id', $id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->get(['date_debut', 'date_fin']);

        $bookedDates = [];
        
        foreach ($bookings as $booking) {
            $current = \Carbon\Carbon::parse($booking->date_debut);
            $end = \Carbon\Carbon::parse($booking->date_fin);
            
            while ($current->lte($end)) {
                $bookedDates[] = $current->format('Y-m-d');
                $current->addDay();
            }
        }

        // Return unique dates
        $bookedDates = array_values(array_unique($bookedDates));

        return response()->json($bookedDates);
    }
}
