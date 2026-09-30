<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AssessmentQuestionController extends Controller
{
    /**
     * Add a question to an assessment. Position defaults to the end of that
     * assessment's question list.
     */
    public function store(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorizeAssessment($request, $assessment);

        $type = $request->input('type');
        $isWritten = $type === AssessmentQuestion::TYPE_WRITTEN_RESPONSE;

        $correctAnswerRules = $isWritten ? ['nullable', 'string'] : ['required', 'string'];
        if ($type === AssessmentQuestion::TYPE_TRUE_FALSE) {
            $correctAnswerRules[] = Rule::in(['true', 'false']);
        }

        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in([
                AssessmentQuestion::TYPE_MULTIPLE_CHOICE,
                AssessmentQuestion::TYPE_SHORT_ANSWER,
                AssessmentQuestion::TYPE_FILL_IN_BLANK,
                AssessmentQuestion::TYPE_TRUE_FALSE,
                AssessmentQuestion::TYPE_WRITTEN_RESPONSE,
            ])],
            'answer_language' => ['nullable', Rule::in([
                AssessmentQuestion::ANSWER_LANGUAGE_ENGLISH, AssessmentQuestion::ANSWER_LANGUAGE_NEPALI,
            ])],
            'prompt' => ['required', 'string'],
            'options' => ['nullable', 'array', 'required_if:type,'.AssessmentQuestion::TYPE_MULTIPLE_CHOICE],
            'options.*.key' => ['required_with:options', 'string'],
            'options.*.label' => ['required_with:options', 'string'],
            'correct_answer' => $correctAnswerRules,
            'min_word_count' => ['nullable', 'integer', 'min:1'],
            'max_word_count' => ['nullable', 'integer', 'min:1'],
            'points' => ['nullable', 'integer', 'min:1'],
            'position' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $forcedType = $assessment->forcedQuestionType();
        abort_if(
            $forcedType !== null && $type !== $forcedType,
            422,
            "This assessment only accepts {$forcedType} questions."
        );

        $data = $validator->validated();
        $data['assessment_id'] = $assessment->id;
        $data['points'] ??= 1;
        $data['position'] ??= (int) $assessment->questions()->max('position') + 1;

        if ($type === AssessmentQuestion::TYPE_TRUE_FALSE) {
            $data['options'] = [['key' => 'true', 'label' => 'True'], ['key' => 'false', 'label' => 'False']];
        }

        $question = AssessmentQuestion::create($data);

        // fresh(): answer_language may have been left unset here and filled
        // in by the database's own default, which the in-memory model from
        // create() doesn't know about until it's re-queried.
        return response()->json(['question' => $question->fresh()], 201);
    }

    /**
     * Update a question.
     */
    public function update(Request $request, AssessmentQuestion $question): JsonResponse
    {
        $this->authorizeAssessment($request, $question->assessment);

        $type = $request->input('type', $question->type);
        $isWritten = $type === AssessmentQuestion::TYPE_WRITTEN_RESPONSE;

        $correctAnswerRules = $isWritten ? ['nullable', 'string'] : ['sometimes', 'required', 'string'];
        if ($type === AssessmentQuestion::TYPE_TRUE_FALSE) {
            $correctAnswerRules[] = Rule::in(['true', 'false']);
        }

        $validator = Validator::make($request->all(), [
            'type' => ['sometimes', 'required', Rule::in([
                AssessmentQuestion::TYPE_MULTIPLE_CHOICE,
                AssessmentQuestion::TYPE_SHORT_ANSWER,
                AssessmentQuestion::TYPE_FILL_IN_BLANK,
                AssessmentQuestion::TYPE_TRUE_FALSE,
                AssessmentQuestion::TYPE_WRITTEN_RESPONSE,
            ])],
            'answer_language' => ['nullable', Rule::in([
                AssessmentQuestion::ANSWER_LANGUAGE_ENGLISH, AssessmentQuestion::ANSWER_LANGUAGE_NEPALI,
            ])],
            'prompt' => ['sometimes', 'required', 'string'],
            'options' => ['nullable', 'array'],
            'options.*.key' => ['required_with:options', 'string'],
            'options.*.label' => ['required_with:options', 'string'],
            'correct_answer' => $correctAnswerRules,
            'min_word_count' => ['nullable', 'integer', 'min:1'],
            'max_word_count' => ['nullable', 'integer', 'min:1'],
            'points' => ['sometimes', 'required', 'integer', 'min:1'],
            'position' => ['sometimes', 'required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $forcedType = $question->assessment->forcedQuestionType();
        abort_if(
            $forcedType !== null && $type !== $forcedType,
            422,
            "This assessment only accepts {$forcedType} questions."
        );

        $data = $validator->validated();

        if ($type === AssessmentQuestion::TYPE_TRUE_FALSE) {
            $data['options'] = [['key' => 'true', 'label' => 'True'], ['key' => 'false', 'label' => 'False']];
        }

        $question->update($data);

        return response()->json(['question' => $question->fresh()]);
    }

    /**
     * Delete a question.
     */
    public function destroy(Request $request, AssessmentQuestion $question): JsonResponse
    {
        $this->authorizeAssessment($request, $question->assessment);

        $question->delete();

        return response()->json(['message' => 'Question deleted.']);
    }

    /**
     * Only a teacher assigned to the assessment's subject may manage its
     * questions.
     */
    protected function authorizeAssessment(Request $request, Assessment $assessment): void
    {
        abort_unless(
            $assessment->lesson->subject->teachers->contains('id', $request->user()->id),
            403,
            'You are not assigned to teach this subject.'
        );
    }
}
