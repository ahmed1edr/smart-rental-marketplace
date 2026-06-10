<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'property_id',
        'rating',
        'comment',
        'is_reported',
        'report_reason'
    ];

    public function client() {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function property() {
        return $this->belongsTo(Property::class);
    }
}
