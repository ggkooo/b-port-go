<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListQuestionsRequest;
use App\Models\Question;
use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    public function index(ListQuestionsRequest $request): JsonResponse
    {
        $questionsQuery = Question::query()
            ->with([
                'difficulty:id,name',
                'schoolClass:id,name',
            ]);

        if ($request->filled('difficulty_id')) {
            $questionsQuery->where('difficulty_id', (int) $request->integer('difficulty_id'));
        }

        if ($request->filled('class_id')) {
            $questionsQuery->where('class_id', (int) $request->integer('class_id'));
        }

        $questionsQuery->inRandomOrder();

        if ($request->filled('quantity')) {
            $questionsQuery->limit((int) $request->integer('quantity'));
        }

        $questions = $questionsQuery->get([
            'id',
            'statement',
            'alternative_a',
            'alternative_b',
            'alternative_c',
            'alternative_d',
            'correct_alternative',
            'tip',
            'difficulty_id',
            'class_id',
        ]);

        return response()->json([
            'questions' => $questions,
        ]);
    }
}
