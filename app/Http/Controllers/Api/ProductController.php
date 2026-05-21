<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;  // ← ADD THIS IMPORT
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/products",
     *     summary="Get all couture pieces with optional filtering",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of products")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Product::with('category');

            if ($request->has('category_id') && $request->category_id) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('featured') && $request->featured) {
                $query->where('is_featured', true);
            }

            if ($request->has('in_stock') && $request->in_stock) {
                $query->where('in_stock', true);
            }

            if ($request->has('search') && $request->search) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            if ($request->has('per_page')) {
                $products = $query->paginate($request->per_page);
            } else {
                $products = $query->get();
            }

            return response()->json([
                'success' => true,
                'products' => $products,
                'message' => 'Products retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/products/{id}",
     *     summary="Get details of a specific piece",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Product details")
     * )
     */
    public function show($id)
    {
        try {
            $product = Product::with('category')->find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'product' => $product,
                'message' => 'Product retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/collections/{type}",
     *     summary="Get products by collection type",
     *     tags={"Products"},
     *     @OA\Parameter(name="type", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Collection data")
     * )
     */
    public function getCollection($type)
    {
        try {
            // First find the category by name
            $category = Category::where('name', $type)->first();
            
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found',
                    'products' => []
                ], 404);
            }
            
            // Then get products using category_id (FIXED)
            $products = Product::where('category_id', $category->id)
                ->where('in_stock', true)
                ->get();
            
            $metadata = [
                'Busuuti' => ['title' => 'Busuuti Heritage', 'subtitle' => 'The Ugandan Atelier'],
                'Gomesi' => ['title' => 'Heritage Elegance', 'subtitle' => 'The Collection'],
                'Kanzu' => ['title' => 'The Kanzu Collection', 'subtitle' => 'Ceremonial Attire'],
                'Wedding Gowns' => ['title' => 'Wedding Gowns', 'subtitle' => 'The Dream Dress'],
                'Accessories' => ['title' => 'Accessories', 'subtitle' => 'Perfect Finishing Touches'],
            ];

            return response()->json([
                'success' => true,
                'header' => $metadata[$type] ?? ['title' => $type, 'subtitle' => 'The Collection'],
                'products' => $products,
                'category' => $category->name
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load products: ' . $e->getMessage(),
                'products' => []
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Get all product categories",
     *     tags={"Products"},
     *     @OA\Response(response=200, description="List of categories")
     * )
     */
    public function getCategories()
    {
        try {
            $categories = Category::where('is_active', true)->get();
            
            return response()->json([
                'success' => true,
                'categories' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load categories',
                'categories' => []
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/products",
     *     summary="Create a new product (Admin only)",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=201, description="Product created")
     * )
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'category_id' => 'required|exists:categories,id',
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'color' => 'nullable|string',
                'image' => 'nullable|string|url',
                'rating' => 'nullable|numeric|min:0|max:5',
                'in_stock' => 'sometimes|boolean',
                'is_featured' => 'sometimes|boolean',
            ]);

            $product = Product::create($request->all());
            $product->load('category');

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => $product,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/products/{id}",
     *     summary="Update an existing product (Admin only)",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Product updated")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $request->validate([
                'category_id' => 'sometimes|exists:categories,id',
                'name' => 'sometimes|string|max:255',
                'price' => 'sometimes|numeric|min:0',
                'description' => 'nullable|string',
                'color' => 'nullable|string',
                'image' => 'nullable|string|url',
                'rating' => 'nullable|numeric|min:0|max:5',
                'in_stock' => 'sometimes|boolean',
                'is_featured' => 'sometimes|boolean',
            ]);

            $product->update($request->all());
            $product->load('category');

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'product' => $product,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/products/{id}",
     *     summary="Delete a product (Admin only)",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Product deleted")
     * )
     */
    public function destroy($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}