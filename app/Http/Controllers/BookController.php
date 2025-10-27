<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Load books with their category and author relationships
        $query = Book::with(['category', 'author']);

        // Filter by category if provided
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by author if provided
        if ($request->has('author_id')) {
            $query->where('author_id', $request->author_id);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('isbn', 'like', "%{$search}%")
                  ->orWhere('publisher', 'like', "%{$search}%");
            });
        }

        // Sort by field
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Get all books
        $books = $query->get();

        return response()->json([
            'data' => $books,
            'total' => $books->count()
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
    // Validasi input
    $validated = $request->validate([
        'category_id' => 'required|integer|exists:categories,id',
        'author_id'   => 'required|integer|exists:authors,id',
        'title'       => 'required|string|max:255',
        'isbn'        => 'required|string|max:13|unique:books,isbn',
        'publisher'   => 'required|string|max:255',
        'year'        => 'required|integer|min:1000|max:' . date('Y'),
        'stock'       => 'required|integer|min:0',
        'cover_url'   => 'nullable|url',
        'cover_file'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
    ]);

    // Jika user upload file gambar
    if ($request->hasFile('cover_file')) {
        $path = $request->file('cover_file')->store('covers', 'public');
        $validated['cover_url'] = asset('storage/' . $path);
    }

    // Simpan ke database
    $book = Book::create($validated);

    // Load relationships before returning
    $book->load(['category', 'author']);

    return response()->json([
        'message' => 'Buku berhasil ditambahkan',
        'data'    => $book
    ], 201);
}

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Load book with category and author relationships
        $book = Book::with(['category', 'author'])->find($id);

        if (!$book) {
            return response()->json([
                'message' => 'Buku tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'data' => $book
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'author_id' => 'required|integer|exists:authors,id',
            'title' => 'required|string|max:255',
            'isbn' => 'required|string|max:13|unique:books,isbn,' . $id,
            'publisher' => 'required|string|max:255',
            'year' => 'required|integer|min:1000|max:' . date('Y'),
            'stock' => 'required|integer|min:0',
            'cover_url' => 'nullable|url',
        ]);

        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'message' => 'Buku tidak ditemukan'
            ], 404);
        }

        $book->update($request->all());

        // Load relationships before returning
        $book->load(['category', 'author']);

        return response()->json([
            'message' => 'Buku berhasil diperbarui',
            'data' => $book
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'message' => 'Buku tidak ditemukan'
            ], 404);
        }

        $book->delete();

        return response()->json([
            'message' => 'Buku berhasil dihapus'
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');

        $books = Book::with(['category', 'author'])
            ->where('title', 'like', "%{$query}%")
            ->orWhere('isbn', 'like', "%{$query}%")
            ->orWhere('publisher', 'like', "%{$query}%")
            ->get();

        return response()->json([
            'data' => $books,
            'total' => $books->count()
        ]);
    }

    public function filterByCategory(Request $request)
    {
        $categoryId = $request->input('category_id');

        $books = Book::with(['category', 'author'])
            ->where('category_id', $categoryId)
            ->get();

        return response()->json([
            'data' => $books,
            'total' => $books->count()
        ]);
    }

    public function filterByAuthor(Request $request)
    {
        $authorId = $request->input('author_id');

        $books = Book::with(['category', 'author'])
            ->where('author_id', $authorId)
            ->get();

        return response()->json([
            'data' => $books,
            'total' => $books->count()
        ]);
    }

    public function totalBooks()
    {
        $total = Book::count();

        return response()->json([
            'total_books' => $total
        ]);
    }

}
