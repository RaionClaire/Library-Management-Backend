<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'author_id',
        'title',
        'isbn',
        'publisher',
        'year',
        'stock',
        'cover_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'stock' => 'integer',
        ];
    }

    /**
     * Get the category that owns the book.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the author that owns the book.
     */
    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    /**
     * Get the loans for the book.
     */
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Check if book is available for loan
     */
    public function isAvailable(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Get available copies count
     */
    public function availableCopies(): int
    {
        $loanedCount = $this->loans()
            ->whereIn('status', ['borrowed', 'overdue'])
            ->count();
        
        return max(0, $this->stock - $loanedCount);
    }
}
