<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'rider_id',
        'pickup_address',
        'delivery_address',
        'pickup_lat',
        'pickup_lng',
        'delivery_lat',
        'delivery_lng',
        'details',
        'total_amount',
        'status',
        'service_type',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }
}
