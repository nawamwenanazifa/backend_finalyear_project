<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all categories (Public access - no login required)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Get all active categories with product count
            $categories = Category::where('is_active', true)
                ->withCount('products')
                ->orderBy('name')
                ->get();
            
            // If no categories in database, return default ones
            if ($categories->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'categories' => $this->getDefaultCategories(),
                    'message' => 'Using default categories (database empty)'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'categories' => $categories,
                'message' => 'Categories retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'categories' => $this->getDefaultCategories(),
                'message' => 'Using fallback categories'
            ]);
        }
    }

    /**
     * Get specific category details (Public access)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $category = Category::with(['products' => function($query) {
                $query->where('is_active', true);
            }])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'category' => $category,
                'message' => 'Category retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }
    }

    /**
     * Create new category (Admin only)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'icon' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'icon' => $request->icon ?? 'category',
            'description' => $request->description,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'category' => $category,
        ], 201);
    }

    /**
     * Update category (Admin only)
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        
        $request->validate([
            'name' => 'sometimes|string|max:255|unique:categories,name,' . $id,
            'icon' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'category' => $category,
        ]);
    }

    /**
     * Delete category (Admin only)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        
        // Check if category has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing products'
            ], 400);
        }
        
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

    /**
     * Get default categories for fallback
     * 
     * @return array
     */
    private function getDefaultCategories()
    {
        return [
            (object)[
                'id' => 1,
                'name' => 'Gomesi',
                'description' => 'Traditional Buganda wedding attire',
                'icon' => 'woman',
                'is_active' => true,
                'products_count' => 0
            ],
            (object)[
                'id' => 2,
                'name' => 'Busuuti',
                'description' => 'Elegant traditional dress',
                'icon' => 'dress',
                'is_active' => true,
                'products_count' => 0
            ],
            (object)[
                'id' => 3,
                'name' => 'Kanzu',
                'description' => 'Traditional men\'s attire',
                'icon' => 'man',
                'is_active' => true,
                'products_count' => 0
            ],
            (object)[
                'id' => 4,
                'name' => 'Wedding Gowns',
                'description' => 'Modern wedding dresses',
                'icon' => 'celebration',
                'is_active' => true,
                'products_count' => 0
            ],
            (object)[
                'id' => 5,
                'name' => 'Accessories',
                'description' => 'Bridal accessories',
                'icon' => 'diamond',
                'is_active' => true,
                'products_count' => 0
            ],
        ];
    }
}