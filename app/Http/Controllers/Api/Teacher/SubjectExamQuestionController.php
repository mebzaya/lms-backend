<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\SubjectExamQuestion;
use App\Models\SubjectExamSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubjectExamQuestionController extends Controller
{
    /**
     * Add a question to a section. Position defaults to the end of that
     * section's question list.
     */
    public function store(Request $request, SubjectExamSection $section): JsonResponse
    {
        $this->authorizeEditableSection($request, $section);

        $type = $request->input('type');
        $isWritten = $type === SubjectExamQuestion::TYPE_WRITTEN_RESPONSE;

        $correctAnswerRules = $isWritten ? ['nullable', 'string'] : ['required', 'string'];
        if ($type === SubjectExamQuestion::TYPE_TRUE_FALSE) {
            $correctAnswerRules[] = Rule::in(['true', 'false']);
        }

        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in([
                SubjectExamQuestion::TYPE_MULTIPLE_CHOICE,
                SubjectExamQuestion::TYPE_SHORT_ANSWER,
                SubjectExamQuestion::TYPE_FILL_IN_BLANK,
                SubjectExamQuestion::TYPE_TRUE_FALSE,
                SubjectExamQuestion::TYPE_WRITTEN_RESPONSE,
            ])],
            'answer_language' => ['nullable', Rule::in([
                SubjectExamQuestion::ANSWER_LANGUAGE_ENGLISH, SubjectExamQuestion::ANSWER_LANGUAGE_NEPALI,
            ])],
            'prompt' => ['required', 'string'],
            'options' => ['nullable', 'array', 'required_if:type,'.SubjectExamQuestion::TYPE_MULTIPLE_CHOICE],
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

        $forcedType = $section->forcedQuestionType();
        abort_if(
            $forcedType !== null && $type !== $forcedType,
            422,
            "This section only accepts {$forcedType} questions."
        );

        $data = $validator->validated();
        $data['section_id'] = $section->id;
        $data['points'] ??= 1;
        $data['position'] ??= (int) $section->questions()->max('position') + 1;

        if ($type === SubjectExamQuestion::TYPE_TRUE_FALSE) {
            $data['options'] = [['key' => 'true', 'label' => 'True'], ['key' => 'false', 'label' => 'False']];
        }

        $question = SubjectExamQuestion::create($data);

        return response()->json(['question' => $question->fresh()], 201);
    }

    /**
     * Update a question.
     */
    public function update(Request $request, SubjectExamQuestion $question): JsonResponse
    {
        $this->authorizeEditableSection($request, $question->section);

        $type = $request->input('type', $question->type);
        $isWritten = $type === SubjectExamQuestion::TYPE_WRITTEN_RESPONSE;

        $correctAnswerRules = $isWritten ? ['nullable', 'string'] : ['sometimes', 'required', 'string'];
        if ($type === SubjectExamQuestion::TYPE_TRUE_FALSE) {
            $correctAnswerRules[] = Rule::in(['true', 'false']);
        }

        $validator = Validator::make($request->all(), [
            'type' => ['sometimes', 'required', Rule::in([
                SubjectExamQuestion::TYPE_MULTIPLE_CHOICE,
                SubjectExamQuestion::TYPE_SHORT_ANSWER,
                SubjectExamQuestion::TYPE_FILL_IN_BLANK,
                SubjectExamQuestion::TYPE_TRUE_FALSE,
                SubjectExamQuestion::TYPE_WRITTEN_RESPONSE,
            ])],
            'answer_language' => ['nullable', Rule::in([
                SubjectExamQuestion::ANSWER_LANGUAGE_ENGLISH, SubjectExamQuestion::ANSWER_LANGUAGE_NEPALI,
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

        $forcedType = $question->section->forcedQuestionType();
        abort_if(
            $forcedType !== null && $type !== $forcedType,
            422,
            "This section only accepts {$forcedType} questions."
        );

        $data = $validator->validated();

        if ($type === SubjectExamQuestion::TYPE_TRUE_FALSE) {
            $data['options'] = [['key' => 'true', 'label' => 'True'], ['key' => 'false', 'label' => 'False']];
        }

        $question->update($data);

        return response()->json(['question' => $question->fresh()]);
    }

    /**
     * Delete a question.
     */
    public function destroy(Request $request, SubjectExamQuestion $question): JsonResponse
    {
        $this->authorizeEditableSection($request, $question->section);

        $question->delete();

        return response()->json(['message' => 'Question deleted.']);
    }

    /**
     * Only a teacher assigned to the exam's subject may manage questions,
     * and only while the exam is still a draft.
     */
    protected function authorizeEditableSection(Request $request, SubjectExamSection $section): void
    {
        $exam = $section->exam;

        abort_unless(
            $exam->subject->teachers->contains('id', $request->user()->id),
            403,
            'You are not assigned to teach this subject.'
        );

        abort_if(
            $exam->published_at !== null,
            422,
            'Unpublish this exam before editing its questions.'
        );
    }
}
