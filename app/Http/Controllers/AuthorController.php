<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;

class AuthorController extends Controller
{

    public function index()
    {
        $authors = Author::all();
        return response()->json($authors);
    }




    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $author = Author::create([
            'name' => $request->name,
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
}

