<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'member_id',
        'book_id',
        'loaned_at',
        'due_at',
        'returned_at',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'loaned_at' => 'date',
            'due_at' => 'date',
            'returned_at' => 'date',
        ];
    }

    /**
     * Get the book that is loaned.
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the member who borrowed the book.
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the fine associated with the loan.
     */
    public function fine()
    {
        return $this->hasOne(Fine::class);
    }

    /**
     * Check if loan is overdue
     */
    public function isOverdue(): bool
    {
        return !$this->returned_at && now()->isAfter($this->due_at);
    }
}
