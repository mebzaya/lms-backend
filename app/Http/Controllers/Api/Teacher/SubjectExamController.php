<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\SubjectExam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubjectExamController extends Controller
{
    /**
     * Relations to eager-load whenever an exam is returned.
     *
     * @var array<int, string>
     */
    protected const RELATIONS = [
        'sections.questions',
        'subject:id,name,class_id',
        'subject.schoolClass:id,name',
        'exam',
    ];

    /**
     * List exams for a subject this teacher teaches, so they can find the
     * one to add sections/questions to. Exams are scheduled by an admin —
     * a teacher can't create, edit, publish, or delete one here.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $subject = Subject::findOrFail($request->integer('subject_id'));
        $this->authorizeTeacherOwnsSubject($request, $subject);

        return response()->json([
            'exams' => $subject->subjectExams()->with(self::RELATIONS)->get(),
        ]);
    }

    /**
     * Declare this subject's questions finished and ready for the admin to
     * review — separate from merely having sections, since a section can
     * exist mid-draft with no questions in it yet. Requires every section to
     * actually have at least one question; the exam itself must still be a
     * draft (unpublished), same as editing sections/questions.
     */
    public function submit(Request $request, SubjectExam $exam): JsonResponse
    {
        $this->authorizeEditableExam($request, $exam);

        $emptySections = $exam->sections()->doesntHave('questions')->pluck('title');

        abort_if(
            $exam->sections()->count() === 0,
            422,
            'Add at least one section before submitting.'
        );

        abort_if(
            $emptySections->isNotEmpty(),
            422,
            'These sections have no questions yet: '.$emptySections->implode(', ')
        );

        $exam->update(['submitted_at' => now()]);

        return response()->json(['exam' => $exam->fresh(self::RELATIONS)]);
    }

    /**
     * Pull a submission back to draft — e.g. the teacher spots a mistake
     * after submitting and wants to keep editing before the admin reviews it.
     */
    public function unsubmit(Request $request, SubjectExam $exam): JsonResponse
    {
        $this->authorizeEditableExam($request, $exam);

        $exam->update(['submitted_at' => null]);

        return response()->json(['exam' => $exam->fresh(self::RELATIONS)]);
    }

    /**
     * Only a teacher assigned to the subject may view/manage its exams.
     */
    protected function authorizeTeacherOwnsSubject(Request $request, Subject $subject): void
    {
        abort_unless(
            $subject->teachers->contains('id', $request->user()->id),
            403,
            'You are not assigned to teach this subject.'
        );
    }

    /**
     * Only a teacher assigned to the exam's subject, and only while the exam
     * is still a draft — once published, submission state is frozen along
     * with everything else.
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
            'Unpublish this exam before changing its submission status.'
        );
    }
}
