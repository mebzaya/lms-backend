<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\SubjectExam;
use App\Models\SubjectExamSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubjectExamSectionController extends Controller
{
    /**
     * Add a section to an exam this teacher owns. Position defaults to the
     * end of that exam's section list. Every section type shares the same
     * shape now — title, optional instructions, an optional shared
     * prompt/context (reading uses "passage" instead) — since each type's
     * own questions carry whatever else they need (prompt text, points,
     * word-count limits, answer language).
     */
    public function store(Request $request, SubjectExam $exam): JsonResponse
    {
        $this->authorizeEditableExam($request, $exam);

        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in([
                SubjectExamSection::TYPE_READING,
                SubjectExamSection::TYPE_WRITING,
                SubjectExamSection::TYPE_FILL_IN_BLANK,
                SubjectExamSection::TYPE_TRUE_FALSE,
            ])],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'passage' => ['nullable', 'string', 'required_if:type,'.SubjectExamSection::TYPE_READING],
            'prompt' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['exam_id'] = $exam->id;
        $data['position'] ??= (int) $exam->sections()->max('position') + 1;

        $section = SubjectExamSection::create($data);

        return response()->json(['section' => $section->fresh(['questions'])], 201);
    }

    /**
     * Update a section.
     */
    public function update(Request $request, SubjectExamSection $section): JsonResponse
    {
        $this->authorizeEditableExam($request, $section->exam);

        $validator = Validator::make($request->all(), [
            'type' => ['sometimes', 'required', Rule::in([
                SubjectExamSection::TYPE_READING,
                SubjectExamSection::TYPE_WRITING,
                SubjectExamSection::TYPE_FILL_IN_BLANK,
                SubjectExamSection::TYPE_TRUE_FALSE,
            ])],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'passage' => ['nullable', 'string', 'required_if:type,'.SubjectExamSection::TYPE_READING],
            'prompt' => ['nullable', 'string'],
            'position' => ['sometimes', 'required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $section->update($validator->validated());

        return response()->json(['section' => $section->fresh(['questions'])]);
    }

    /**
     * Delete a section (and its questions, via cascade).
     */
    public function destroy(Request $request, SubjectExamSection $section): JsonResponse
    {
        $this->authorizeEditableExam($request, $section->exam);

        $section->delete();

        return response()->json(['message' => 'Section deleted.']);
    }

    /**
     * Only a teacher assigned to the exam's subject may manage its
     * sections, and only while the exam is still a draft — once published,
     * students may already be mid-attempt, so content is frozen until the
     * teacher unpublishes it again.
     */
    protected function authorizeEditableExam(Request $request, SubjectExam $exam): void
    {
        abort_unless(
            $exam->subject->teachers->contains('id', $request->user()->id),
            403,
            'You are not assigned to teach this subject.'
        );

        abort_if(
            $exam->published_at !== null,
            422,
            'Unpublish this exam before editing its sections.'
        );
    }
}
