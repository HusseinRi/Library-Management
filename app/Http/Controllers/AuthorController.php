<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Http\Resources\AuthorResource;
use App\Models\Author;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function index()
    {
        $authors = Author::all();
        return AuthorResource::collection($authors);
    }

    public function store(StoreAuthorRequest $request)
    {
        $author = Author::create($request->only(['name']));

        return new AuthorResource($author);
    }

    public function show(Author $author)
    {
        $author->load('books'); // بشرط تكون عامل علاقة books() جوا مودل Category
        return new AuthorResource($author);
    }

    public function update(UpdateAuthorRequest $request, Author $author)
    {
        $author->update($request->only(['name', 'bio']));

        return new AuthorResource($author);
    }

    public function destroy(Author $author)
    {
        $author->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }
}
