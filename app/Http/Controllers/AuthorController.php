<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;

class AuthorController extends Controller
{

    public function index()
    {
        $authors = Author::withCount('books')->get();
        return response()->json($authors);
    }




    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'biography' => 'nullable|string|max:1000',
        ]);

        $author = Author::create([
            'name' => $request->name,
            'biography' => $request->biography,
        ]);

        return response()->json([
            'message' => 'Penulis berhasil ditambahkan',
            'author' => $author
        ], 201);
    }


    public function show($id)
    {
        $author = Author::find($id);

        if (!$author) {
            return response()->json([
                'message' => 'Penulis tidak ditemukan'
            ], 404);
        }

        return response()->json($author);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $author = Author::find($id);

        if (!$author) {
            return response()->json([
                'message' => 'Penulis tidak ditemukan'
            ], 404);
        }

        $author->update([
            'name' => $request->name,
        ]);
    }



    public function destroy($id)
    {
        $author = Author::find($id);

        if (!$author) {
            return response()->json([
                'message' => 'Penulis tidak ditemukan'
            ], 404);
        }

        $author->delete();

        return response()->json([
            'message' => 'Penulis berhasil dihapus'
        ]);
    }

    public function getBooksByAuthor($id)
    {
        $author = Author::with('books')->find($id);

        if (!$author) {
            return response()->json([
                'message' => 'Penulis tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'author' => $author->name,
            'books' => $author->books
        ]);
    }
}

