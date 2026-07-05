<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Class User
 * 
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $idnp
 * @property string|null $driver_license
 * @property string $password
 * @property string|null $remember_token
 * @property string $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $email_verified_at
 * 
 * @property Collection|Rental[] $rentals
 *
 * @package App\Models
 */
class User extends Authenticatable
{
	protected $table = 'users';
	protected $casts = [
		'email_verified_at' => 'datetime'
	];
	protected $hidden = [
		'password',
		'remember_token'
	];
	protected $fillable = [
		'name',
		'email',
		'phone',
		'idnp',
		'driver_license',
		'password',
		'remember_token',
		'role',
		'email_verified_at'
	];

	public function rentals()
	{
		return $this->hasMany(Rental::class);
	}
}
