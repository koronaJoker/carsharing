<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Car
 *
 * @property int $id
 * @property string $brand
 * @property int $year
 * @property string $number_plate
 * @property string $fuel_type
 * @property string $transmission
 * @property float $price_per_minute
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection|Rental[] $rentals
 */
class Car extends Model
{
    protected $table = 'cars';

    protected $casts = [
        'year' => 'int',
        'price_per_minute' => 'float',
    ];

    protected $fillable = [
        'brand',
        'year',
        'number_plate',
        'fuel_type',
        'transmission',
        'price_per_minute',
        'status',
        'image_url',
    ];

    public function rentals()
    {
        return $this->hasMany(Rental::class);
    }

    public function getImageSrcAttribute(): string
    {
        $image = trim((string) $this->image_url);

        if ($image === '') {
            return asset('images/car-placeholder.webp');
        }

        if (preg_match('/^https?:\/\//i', $image)) {
            return $image;
        }

        $path = ltrim($image, '/');

        if (! str_starts_with($path, 'images/')) {
            $path = 'images/'.$path;
        }

        return asset($path);
    }
}
