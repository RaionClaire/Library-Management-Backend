<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'code',
        'phone',
        'address',
        'join_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'join_date' => 'date',
        ];
    }

    /**
     * Get the user that owns the member profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the loans for the member.
     */
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Get the notifications for the member.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
