<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Booking;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    // 1. عرض التقييمات ديال شي دار (أي واحد يقدر يشوفهم)
    public function index($property_id)
    {
        // كنجيبو التقييمات ومعاهم سمية الكليان لي حطهم
        $reviews = Review::with('client:id,name')->where('property_id', $property_id)->latest()->paginate(20);
        return response()->json($reviews);
    }

    // 2. إضافة تقييم (خاص بالكليان لي ديجا حجز ومخلص)
    public function store(Request $request, $property_id)
    {
        $user = auth('api')->user();

        // حماية 1: واش هاد اليوزر كليان؟
        if ($user->role !== 'client') {
            return response()->json(['error' => 'Seuls les clients peuvent laisser un avis.'], 403);
        }

        $property = \App\Models\Property::where('id', $property_id)->where('is_active', true)->first();
        if (!$property) {
            return response()->json(['error' => 'Propriété introuvable.'], 404);
        }

        // حماية 2: واش هاد الكليان ديجا حجز هاد الدار وتأكد ليه الدفع؟
        $hasBooked = Booking::where('client_id', $user->id)
                            ->where('property_id', $property_id)
                            ->where('status', 'confirmed')
                            ->exists();

        if (!$hasBooked) {
            return response()->json(['error' => 'Vous devez avoir une réservation confirmée pour évaluer ce bien.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // حماية 3: واش ديجا دار تقييم من قبل؟ (باش مايسباميش الدار)
        $existingReview = Review::where('client_id', $user->id)->where('property_id', $property_id)->first();
        if ($existingReview) {
            return response()->json(['error' => 'Vous avez déjà évalué ce bien.'], 400);
        }

        // تسجيل التقييم
        $review = Review::create([
            'client_id' => $user->id,
            'property_id' => $property_id,
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return response()->json([
            'message' => 'Avis ajouté avec succès',
            'review' => $review
        ], 201);
    }

    public function report(Request $request, $id)
    {
        // Require auth
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Non autorisé.'], 401);
        }

        $review = Review::with('property')->findOrFail($id);

        // Ensure the reporter is the owner of the property
        if ($review->property->owner_id !== $user->id) {
            return response()->json(['error' => 'Vous ne pouvez signaler que les avis laissés sur vos propres annonces.'], 403);
        }

        $review->update([
            'is_reported' => true,
            'report_reason' => $request->input('reason', 'Signalé par le propriétaire')
        ]);

        return response()->json(['message' => 'L\'avis a été signalé avec succès.']);
    }
}
