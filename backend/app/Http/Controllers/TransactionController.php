<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    // دالة تأكيد الدفع الكاش من طرف المالك
    public function validerPaiement(Request $request, $booking_id)
    {
        $user = auth('api')->user();

        // كنجبدو الحجز ومعاه العقار ديالو باش نتحققو من المالك
        $booking = Booking::with('property')->find($booking_id);

        if (!$booking) {
            return response()->json(['error' => 'Réservation introuvable'], 404);
        }

        // الحماية من IDOR: واش هاد اليوزر هو مول الدار فعلاً؟
        if ($booking->property->owner_id !== $user->id) {
            return response()->json(['error' => 'Non autorisé. Vous n\'êtes pas le propriétaire de ce bien.'], 403);
        }

        // واش ديجا مخلص؟
        if ($booking->status === 'confirmed') {
            return response()->json(['error' => 'Cette réservation est déjà confirmée et payée.'], 400);
        }

        if ($booking->status === 'cancelled') {
            return response()->json(['error' => 'Impossible de payer une réservation annulée.'], 400);
        }

        $existingTransaction = Transaction::where('booking_id', $booking->id)->where('status', 'success')->first();
        if ($existingTransaction) {
            return response()->json(['error' => 'Un paiement a déjà été effectué pour cette réservation.'], 400);
        }

        return DB::transaction(function () use ($booking) {
            // 1. تسجيل المعاملة المالية (Transaction)
            $transaction = Transaction::create([
                'booking_id' => $booking->id,
                'montant' => $booking->prix_total,
                'methode_paiement' => 'cash',
                'status' => 'success'
            ]);

            // 2. تبديل حالة الحجز لـ confirmed
            $booking->update(['status' => 'confirmed']);

            // إشعار للكليان بلي الدفع تأكد
            Notification::create([
                'user_id' => $booking->client_id,
                'type' => 'payment_validated',
                'message' => "Votre paiement de {$booking->prix_total} MAD pour '{$booking->property->titre}' a été validé. Bon séjour !",
                'related_id' => $booking->id
            ]);

            return response()->json([
                'message' => 'Paiement validé et réservation confirmée avec succès',
                'transaction' => $transaction,
                'nouveau_status' => $booking->status
            ], 201);
        });
    }
}
