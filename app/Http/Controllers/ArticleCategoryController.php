<?php

namespace App\Http\Controllers;

use App\Models\ArticleCategory;
use App\Models\Article;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArticleCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ArticleCategory::with('articles')->get();
        return Inertia::render('ArticleCategory/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Get categories for API usage (e.g., dropdowns)
     */
    public function getCategories()
    {
        $categories = ArticleCategory::select('id', 'name', 'type', 'icon')->get();
        return response()->json($categories);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $articles = Article::all();
        return Inertia::render('ArticleCategory/Create', [
            'articles' => $articles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'type' => 'required|in:large,small',
            'article_ids' => 'nullable|array',
            'article_ids.*' => 'exists:article,id',
        ]);

        $category = ArticleCategory::create([
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? null,
            'type' => $validated['type'],
        ]);

        if (!empty($validated['article_ids'])) {
            $category->articles()->sync($validated['article_ids']);
        }

        return redirect()->route('article-categories.index')->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $category = ArticleCategory::with('articles')->findOrFail($id);
        $articles = Article::all();
        return Inertia::render('ArticleCategory/Edit', [
            'category' => $category,
            'articles' => $articles,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'type' => 'required|in:large,small',
            'article_ids' => 'nullable|array',
            'article_ids.*' => 'exists:article,id',
        ]);

        $category = ArticleCategory::findOrFail($id);
        $category->update([
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? null,
            'type' => $validated['type'],
        ]);

        $category->articles()->sync($validated['article_ids'] ?? []);

        return redirect()->route('article-categories.index')->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy($id)
    {
        try {
            $category = ArticleCategory::findOrFail($id);
            
            // Detach all articles before soft deleting the category
            $category->articles()->detach();
            
            // Soft delete the category
            $category->delete();
            
            // Check if this is an AJAX/JSON request
            if (request()->wantsJson() || request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Category deleted successfully.'
                ]);
            }
            
            return redirect()->route('article-categories.index')->with('success', 'Category deleted successfully.');
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete category: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('article-categories.index')->with('error', 'Failed to delete category.');
        }
    }
}
