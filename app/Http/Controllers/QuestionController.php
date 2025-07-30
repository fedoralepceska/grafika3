<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index()
    {
        return response()->json(Question::orderBy('order')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'question' => 'required|string',
            'order' => 'integer',
            'active' => 'boolean'
        ]);
        $question = Question::create($data);
        
        // Clear cache when questions are modified
        cache()->forget('questions.active');
        
        return response()->json($question, 201);
    }

    public function update(Request $request, Question $question)
    {
        $data = $request->validate([
            'question' => 'string',
            'order' => 'integer',
            'active' => 'boolean'
        ]);
        $question->update($data);
        
        // Clear cache when questions are modified
        cache()->forget('questions.active');
        
        return response()->json($question);
    }

    public function destroy(Question $question)
    {
        $question->delete();
        
        // Clear cache when questions are modified
        cache()->forget('questions.active');
        
        return response()->json(['message' => 'Deleted']);
    }

    public function enable(Question $question)
    {
        $question->update(['active' => true]);
        
        // Clear cache when questions are modified
        cache()->forget('questions.active');
        
        return response()->json($question);
    }

    public function disable(Question $question)
    {
        $question->update(['active' => false]);
        
        // Clear cache when questions are modified
        cache()->forget('questions.active');
        
        return response()->json($question);
    }

    public function reorder(Request $request)
    {
        $order = $request->input('order'); // array of question IDs in new order
        foreach ($order as $index => $id) {
            Question::where('id', $id)->update(['order' => $index]);
        }
        
        // Clear cache when questions are modified
        cache()->forget('questions.active');
        
        return response()->json(['message' => 'Order updated']);
    }

    public function active()
    {
        // Add caching and limit results for better production performance
        $questions = cache()->remember('questions.active', 300, function () {
            return Question::active()
                ->select('id', 'question', 'order') // Only select needed columns
                ->limit(100) // Prevent excessive data transfer
                ->get();
        });
        
        return response()->json($questions);
    }

    public function getByCatalogItem($catalogItemId)
    {
        // Use optimized query with caching
        $questions = cache()->remember("catalog_item.{$catalogItemId}.questions", 300, function () use ($catalogItemId) {
            return \App\Models\CatalogItem::findOrFail($catalogItemId)
                ->questions()
                ->select('questions.id', 'questions.question', 'questions.order')
                ->orderBy('questions.order')
                ->get();
        });
        
        return response()->json($questions);
    }

    public function updateCatalogItemQuestions(Request $request, $catalogItemId)
    {
        $data = $request->validate([
            'question_ids' => 'array',
            'question_ids.*' => 'exists:questions,id'
        ]);

        $catalogItem = \App\Models\CatalogItem::findOrFail($catalogItemId);
        $catalogItem->questions()->sync($data['question_ids'] ?? []);

        // Clear specific catalog item questions cache
        cache()->forget("catalog_item.{$catalogItemId}.questions");

        return response()->json(['message' => 'Questions updated successfully']);
    }
} 