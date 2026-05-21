<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/gallery",
     *     summary="Get all lookbook items",
     *     tags={"Gallery"},
     *     @OA\Parameter(name="category", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="List of gallery items")
     * )
     */
    public function index(Request $request)
    {
        $query = GalleryItem::where('is_active', true);

        if ($request->has('category') && $request->category !== 'All') {
            $query->where('category', $request->category);
        }

        $items = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data'    => $items,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/gallery",
     *     summary="Add a new lookbook item (Admin only)",
     *     tags={"Gallery"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=201, description="Gallery item created")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'image'             => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'category'          => 'required|in:Bride,Bridesmaid,Girls',
            'tags'              => 'nullable|array',
            'photographer_name' => 'required|string',
            'price'             => 'required|string',
        ]);

        $path = $request->file('image')->store('gallery', 'public');

        $item = GalleryItem::create([
            'title'             => $request->title,
            'description'       => $request->description,
            'image_url'         => Storage::url($path),
            'category'          => $request->category,
            'tags'              => $request->tags ?? [],
            'photographer_name' => $request->photographer_name,
            'price'             => $request->price,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gallery item created',
            'data'    => $item,
        ], 201);
    }
}
