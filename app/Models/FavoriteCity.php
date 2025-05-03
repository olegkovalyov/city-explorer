<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User; // Import User model

class FavoriteCity extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'city_name',
        'latitude',
        'longitude',
    ];

    /**
     * Get the user that owns the favorite city.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
