<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    // هادا هو السطر لي خاصك تقاد، تأكد بلي owner_id كاينا فيه
    protected $fillable = [
        'owner_id',
        'titre',
        'description',
        'prix_par_nuit',
        'localisation',
        'type',
        'max_guests',
        'rooms_count',
        'has_wifi',
        'has_pool',
        'is_active'
    ];

    protected function casts(): array
    {
        return [
            'prix_par_nuit' => 'decimal:2',
            'max_guests' => 'integer',
            'rooms_count' => 'integer',
            'has_wifi' => 'boolean',
            'has_pool' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // العلاقات لي درنا ديجا خليهم كيما هما
    public function owner() {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function images() {
        return $this->hasMany(PropertyImage::class);
    }

    public function bookings() {
        return $this->hasMany(Booking::class);
    }

    public function reviews() {
        return $this->hasMany(Review::class);
    }

    public function favoritedBy() {
        return $this->belongsToMany(User::class, 'favorites', 'property_id', 'user_id')->withTimestamps();
    }
}
