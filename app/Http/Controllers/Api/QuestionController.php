<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListQuestionsRequest;
use App\Http\Requests\Api\StoreQuestionRequest;
use App\Http\Requests\Api\UpdateQuestionRequest;
use App\Models\Question;
use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    public function store(StoreQuestionRequest $request): JsonResponse
    {
        $question = Question::query()->create($request->validated());

        $question->load([
            'difficulty:id,name',
            'schoolClass:id,name',
            'activityType:id,name,slug',
        ]);

        return response()->json([
            'message' => 'Questão criada com sucesso.',
            'question' => $question,
        ], 201);
    }

    public function show(Question $question): JsonResponse
    {
        $question->load([
            'difficulty:id,name',
            'schoolClass:id,name',
            'activityType:id,name,slug',
        ]);

        return response()->json([
            'question' => $question,
        ]);
    }

    public function index(ListQuestionsRequest $request): JsonResponse
    {
        $questionsQuery = Question::query()
            ->with([
                'difficulty:id,name',
                'schoolClass:id,name',
                'activityType:id,name,slug',
            ]);

        if ($request->filled('difficulty_id')) {
            $questionsQuery->where('difficulty_id', (int) $request->integer('difficulty_id'));
        }

        if ($request->filled('class_id')) {
            $questionsQuery->where('class_id', (int) $request->integer('class_id'));
        }

        if ($request->filled('activity_type_id')) {
            $questionsQuery->where('activity_type_id', (int) $request->integer('activity_type_id'));
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
            'activity_type_id',
        ]);

        return response()->json([
            'questions' => $questions,
        ]);
    }

    public function update(UpdateQuestionRequest $request, Question $question): JsonResponse
    {
        $question->update($request->validated());

        $question->load([
            'difficulty:id,name',
            'schoolClass:id,name',
            'activityType:id,name,slug',
        ]);

        return response()->json([
            'message' => 'Questão atualizada com sucesso.',
            'question' => $question,
        ]);
    }

    public function destroy(Question $question): JsonResponse
    {
        $question->delete();

        return response()->json([
            'message' => 'Questão deletada com sucesso.',
        ]);
    }
}
