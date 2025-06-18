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
            'default_answer' => 'required|string',
            'order' => 'integer',
            'active' => 'boolean'
        ]);
        $question = Question::create($data);
        return response()->json($question, 201);
    }

    public function update(Request $request, Question $question)
    {
        $data = $request->validate([
            'question' => 'string',
            'default_answer' => 'string',
            'order' => 'integer',
            'active' => 'boolean'
        ]);
        $question->update($data);
        return response()->json($question);
    }

    public function destroy(Question $question)
    {
        $question->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function enable(Question $question)
    {
        $question->update(['active' => true]);
        return response()->json($question);
    }

    public function disable(Question $question)
    {
        $question->update(['active' => false]);
        return response()->json($question);
    }

    public function reorder(Request $request)
    {
        $order = $request->input('order'); // array of question IDs in new order
        foreach ($order as $index => $id) {
            Question::where('id', $id)->update(['order' => $index]);
        }
        return response()->json(['message' => 'Order updated']);
    }

    public function active()
    {
        return response()->json(Question::active()->get());
    }
} 