<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $books = Book::all();
        return response()->json($books);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'author_id' => 'required|integer|exists:authors,id',
            'title' => 'required|string|max:255',
            'isbn' => 'required|string|max:13|unique:books,isbn',
            'publisher' => 'required|string|max:255',
            'year' => 'required|integer|min:1000|max:' . date('Y'),
            'stock' => 'required|integer|min:0',
            'cover_url' => 'nullable|url',
        ]);

        if ($request->hasFile('cover_url')) {
            $path = $request->file('cover_url')->store('covers', 'public');
            $validated['cover_url'] = asset('storage/' . $path);
        }

        $book = Book::create($validated);

        return response()->json([
            'message' => 'Buku berhasil ditambahkan',
            'book' => $book
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'message' => 'Buku tidak ditemukan'
            ], 404);
        }

        return response()->json($book);
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

        return response()->json([
            'message' => 'Buku berhasil diperbarui',
            'book' => $book
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
}
