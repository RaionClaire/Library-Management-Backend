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
        'notes',
        'approved_by',
        'approved_at',
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
            'approved_at' => 'datetime',
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
     * Get the admin who approved/rejected the loan.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    /**
     * Check if loan is near due date (within specified days)
     */
    public function isNearDueDate(int $days = 3): bool
    {
        if ($this->returned_at) {
            return false;
        }

        $daysUntilDue = now()->diffInDays($this->due_at, false);
        return $daysUntilDue >= 0 && $daysUntilDue <= $days;
    }

    /**
     * Get days until due date
     */
    public function daysUntilDue(): int
    {
        if ($this->returned_at) {
            return 0;
        }

        return now()->diffInDays($this->due_at, false);
    }

    /**
     * Scope for active loans (not returned)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('returned_at');
    }

    /**
     * Scope for near due loans
     */
    public function scopeNearDue($query, int $days = 3)
    {
        return $query->active()
            ->where('due_at', '>=', now())
            ->where('due_at', '<=', now()->addDays($days));
    }

    /**
     * Scope for overdue loans
     */
    public function scopeOverdue($query)
    {
        return $query->active()
            ->where('due_at', '<', now());
    }

    /**
     * Scope for pending loans (awaiting approval)
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved but not yet picked up
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected loans
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
