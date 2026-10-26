<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'user_id',
        'transaction_number',
        'subtotal',
        'total',
        'amount_paid',
        'change_amount',
        'payment_method',
        'payment_status',
        'status',
        'completed_at',
        'notes',
        'customer_name',
        'customer_phone',
        'shipping_address',
        'pickup_note',
        'sales_channel',
        'handled_by',
        'total_cost',
        'delivery_type',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }
}