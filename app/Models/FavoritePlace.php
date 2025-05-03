<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class FavoritePlace extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'fsq_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'photo_url',
        'category',
        'category_icon',
    ];

    /**
     * Get the user that owns the favorite place.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
