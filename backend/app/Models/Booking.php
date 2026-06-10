<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'property_id',
        'date_debut',
        'date_fin',
        'prix_total',
        'status',
        'phone'
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'prix_total' => 'decimal:2',
        ];
    }

    public function client() {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function property() {
        return $this->belongsTo(Property::class);
    }

    public function transaction() {
        return $this->hasOne(Transaction::class);
    }
}
