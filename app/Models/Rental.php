<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Rental
 *
 * @property int $id
 * @property int $user_id
 * @property int $car_id
 * @property Carbon $start_time
 * @property Carbon|null $end_time
 * @property float $total_cost
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User $user
 * @property Car $car
 * @property Collection|Payment[] $payments
 * @property Collection|Fine[] $fines
 */
class Rental extends Model
{
    protected $table = 'rentals';

    protected $casts = [
        'user_id' => 'int',
        'car_id' => 'int',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'total_cost' => 'float',
    ];

    protected $fillable = [
        'user_id',
        'car_id',
        'start_time',
        'end_time',
        'total_cost',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function fines()
    {
        return $this->hasMany(Fine::class);
    }
}
