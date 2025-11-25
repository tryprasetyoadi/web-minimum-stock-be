<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'jenis',
        'type',
        'qty',
        'warehouse',
        'sender_alamat',
        'sender_pic',
        'receiver_alamat',
        'receiver_warehouse',
        'receiver_pic',
        'tanggal_pengiriman',
        'tanggal_sampai',
        'batch',
        'sn_mac_picture',
    ];

    protected $casts = [
        'tanggal_pengiriman' => 'date',
        'tanggal_sampai' => 'date',
    ];
}