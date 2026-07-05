<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Fine
 *
 * @property int $id
 * @property int $rental_id
 * @property string $title
 * @property string $description
 * @property float $amount
 * @property float $rating_penalty
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Rental $rental
 */
class Fine extends Model
{
    protected $table = 'fines';

    protected $casts = [
        'rental_id' => 'int',
        'amount' => 'float',
        'rating_penalty' => 'float',
    ];

    protected $fillable = [
        'rental_id',
        'title',
        'description',
        'amount',
        'rating_penalty',
        'status',
    ];

    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }
}
