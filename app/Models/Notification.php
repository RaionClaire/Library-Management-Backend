<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'member_id',
        'email_due_reminder',
        'email_overdue_reminder',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_due_reminder' => 'boolean',
            'email_overdue_reminder' => 'boolean',
        ];
    }

    /**
     * Get the member that owns the notification settings.
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
