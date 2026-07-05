<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Payment
 *
 * @property int $id
 * @property int $rental_id
 * @property float $amount
 * @property string $payment_method
 * @property string $payment_status
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Rental $rental
 */
class Payment extends Model
{
    protected $table = 'payments';

    protected $casts = [
        'rental_id' => 'int',
        'amount' => 'float',
        'paid_at' => 'datetime',
    ];

    protected $fillable = [
        'rental_id',
        'amount',
        'payment_method',
        'payment_status',
        'paid_at',
    ];

    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }
}
