<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Property;
use App\Models\Notification;
use App\Models\Message;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingController extends Controller
{
    // إضافة حجز جديد
    public function store(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'date_debut' => 'required|date|after_or_equal:today',
            'date_fin' => 'required|date|after:date_debut',
            'phone' => 'required|string|min:8|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $property = Property::findOrFail($request->property_id);

        if ($property->owner_id === $user->id) {
            return response()->json(['error' => 'Vous ne pouvez pas réserver votre propre annonce.'], 403);
        }

        // Only clients can make bookings
        if ($user->role !== 'client') {
            return response()->json(['error' => 'Seuls les clients peuvent effectuer des réservations.'], 403);
        }

        return DB::transaction(function () use ($request, $property, $user) {
            // حماية من الحجز المزدوج (Double Booking) مع قفل
            $overlap = Booking::where('property_id', $request->property_id)
                ->where('status', '!=', 'cancelled')
                ->lockForUpdate()
                ->where(function ($query) use ($request) {
                    $query->where(function ($q) use ($request) {
                        $q->where('date_debut', '<', $request->date_fin)
                          ->where('date_fin', '>', $request->date_debut);
                    });
                })->exists();

            if ($overlap) {
                return response()->json(['error' => 'Ces dates sont déjà réservées pour cette propriété.'], 409);
            }

            $debut = Carbon::parse($request->date_debut);
            $fin = Carbon::parse($request->date_fin);
            $nuits = $debut->diffInDays($fin);
            $prix_total = $nuits * $property->prix_par_nuit;

            $booking = Booking::create([
                'client_id' => $user->id,
                'property_id' => $property->id,
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
                'prix_total' => $prix_total,
                'status' => 'pending',
                'phone' => $request->phone
            ]);

            // إشعار للمالك بلي كاين حجز جديد
            Notification::create([
                'user_id' => $property->owner_id,
                'type' => 'booking_created',
                'message' => "Nouvelle réservation pour votre logement '{$property->titre}' par {$user->name}.",
                'related_id' => $booking->id
            ]);

            // رسالة أوتوماتيكية من الكليان للمالك باش يبداو المحادثة ويتلاقاو
            Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $property->owner_id,
                'content' => "Bonjour, je viens de réserver votre logement '{$property->titre}' du {$request->date_debut} au {$request->date_fin}. Pouvons-nous discuter des détails pour le paiement et la remise des clés ? Voici mon numéro : {$request->phone}",
                'is_read' => false
            ]);

            return response()->json([
                'message' => 'Réservation effectuée avec succès',
                'prix_total' => $prix_total,
                'nuits' => $nuits,
                'booking' => $booking
            ], 201);
        });
    }

    // عرض الحجوزات ديال المستخدم
    public function myBookings()
    {
        $user = auth('api')->user();

        if ($user->role === 'client') {
            $bookings = Booking::with('property')->where('client_id', $user->id)->latest()->get();
        } else {
            $bookings = Booking::with('client', 'property')
                ->whereHas('property', function ($query) use ($user) {
                    $query->where('owner_id', $user->id);
                })->latest()->get();
        }

        return response()->json($bookings);
    }
    // Cancel a booking
    public function cancel($id)
    {
        $user = auth('api')->user();
        $booking = Booking::with('property')->findOrFail($id);

        // Authorization: only the client who made the booking or the property owner can cancel
        if ($booking->client_id !== $user->id && $booking->property->owner_id !== $user->id) {
            return response()->json(['error' => 'Non autorisé.'], 403);
        }

        if ($booking->status === 'cancelled') {
            return response()->json(['error' => 'La réservation est déjà annulée.'], 400);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Réservation annulée avec succès.']);
    }
}
