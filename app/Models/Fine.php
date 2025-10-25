<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fine extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'loan_id',
        'amount',
        'status',
        'note'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    /**
     * Get the loan that owns the fine.
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Check if fine is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
