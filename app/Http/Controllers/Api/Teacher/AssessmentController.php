<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AssessmentController extends Controller
{
    /**
     * Relations to eager-load whenever an assessment is returned.
     *
     * @var array<int, string>
     */
    protected const RELATIONS = ['questions', 'lesson:id,title,subject_id'];

    /**
     * List assessments for a lesson this teacher teaches.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $lesson = Lesson::findOrFail($request->integer('lesson_id'));
        $this->authorizeTeacherOwnsLesson($request, $lesson);

        return response()->json([
            'assessments' => $lesson->assessments()->with(self::RELATIONS)->get(),
        ]);
    }

    /**
     * Create an assessment under a lesson this teacher teaches. Position
     * defaults to the end of that lesson's assessment list. Always
     * practice mode — a lesson's reading/writing assessments are for
     * self-paced study, not a graded exam; that role belongs to the
     * class-wide Exam/SubjectExam system instead.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'type' => ['required', Rule::in([
                Assessment::TYPE_READING, Assessment::TYPE_WRITING,
                Assessment::TYPE_FILL_IN_BLANK, Assessment::TYPE_TRUE_FALSE,
            ])],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'passage' => ['nullable', 'string', 'required_if:type,'.Assessment::TYPE_READING],
            'prompt' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'max_attempts' => ['nullable', 'integer', 'min:1'],
            'position' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $lesson = Lesson::findOrFail($data['lesson_id']);
        $this->authorizeTeacherOwnsLesson($request, $lesson);

        $data['position'] ??= (int) Assessment::where('lesson_id', $data['lesson_id'])->max('position') + 1;
        $data['teacher_id'] = $request->user()->id;
        $data['mode'] = Assessment::MODE_PRACTICE;

        $assessment = Assessment::create($data);

        return response()->json(['assessment' => $assessment->fresh(self::RELATIONS)], 201);
    }

    /**
     * Update an assessment.
     */
    public function update(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorizeTeacherOwnsLesson($request, $assessment->lesson);

        $validator = Validator::make($request->all(), [
            'type' => ['sometimes', 'required', Rule::in([
                Assessment::TYPE_READING, Assessment::TYPE_WRITING,
                Assessment::TYPE_FILL_IN_BLANK, Assessment::TYPE_TRUE_FALSE,
            ])],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'passage' => ['nullable', 'string', 'required_if:type,'.Assessment::TYPE_READING],
            'prompt' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'max_attempts' => ['nullable', 'integer', 'min:1'],
            'position' => ['sometimes', 'required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $assessment->update($validator->validated());

        return response()->json(['assessment' => $assessment->fresh(self::RELATIONS)]);
    }

    /**
     * Delete an assessment (and its questions/attempts/answers, via cascade).
     */
    public function destroy(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorizeTeacherOwnsLesson($request, $assessment->lesson);

        $assessment->delete();

        return response()->json(['message' => 'Assessment deleted.']);
    }

    /**
     * Only a teacher assigned to a lesson's subject may manage its assessments.
     */
    protected function authorizeTeacherOwnsLesson(Request $request, Lesson $lesson): void
    {
        abort_unless(
            $lesson->subject->teachers->contains('id', $request->user()->id),
            403,
            'You are not assigned to teach this subject.'
        );
    }
}
