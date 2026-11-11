<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public $timestamps = true;
    protected $table = "order";
    protected $fillable = [
        'customer_id',
        'total_harga',
        'status',
        'noresi',
        'kurir',
        'layanan_ongkir',
        'biaya_ongkir',
        'estimasi_ongkir',
        'total_berat',
        'alamat',
        'pos',
        'telepon_penerima',
        'payment_status',
        'payment_proof_path',
        'payment_submitted_at',
        'paid_at',
        'shipped_at',
        'completed_at',
        'fulfillment_type',
        'digital_delivery_codes',
        'destination_province_id',
        'destination_province_name',
        'destination_city_id',
        'destination_city_name',
        'shipping_service_code',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    protected $casts = [
        'payment_submitted_at' => 'datetime',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
