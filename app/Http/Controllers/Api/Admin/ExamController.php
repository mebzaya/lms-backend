<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\SubjectExam;
use App\Models\SubjectExamAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExamController extends Controller
{
    /**
     * Relations to eager-load whenever an exam is returned.
     *
     * @var array<int, string>
     */
    protected const RELATIONS = [
        'schoolClass:id,name',
        'subjectExams.subject:id,name,class_id',
        'subjectExams.subject.teachers:id,name,email',
        'subjectExams.sections.questions',
        // Just enough to tell the admin UI whether any student has already
        // started this exam — deleting it afterwards would wipe their work.
        'subjectExams.attempts:id,exam_id',
    ];

    /**
     * List exams — every exam across every class by default, or just one
     * class's when class_id is given (e.g. the "Schedule exam" flow, which
     * already knows which class it's working in).
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Exam::with(self::RELATIONS)->latest('id');

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->integer('class_id'));
        }

        return response()->json(['exams' => $query->get()]);
    }

    /**
     * A single exam by id — so its detail/manage page is directly
     * browsable/bookmarkable/refreshable, without needing to already know
     * (or re-fetch) which class it belongs to.
     */
    public function show(Exam $exam): JsonResponse
    {
        return response()->json(['exam' => $exam->load(self::RELATIONS)]);
    }

    /**
     * Schedule a new exam for every subject in a class. Each subject gets
     * its own empty "paper" (a SubjectExam) for that subject's teacher(s)
     * to add sections/questions to.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $class = SchoolClass::findOrFail($request->integer('class_id'));

        abort_if($class->subjects()->count() === 0, 422, 'This class has no subjects yet.');

        $exam = Exam::create([
            'class_id' => $class->id,
            'created_by' => $request->user()->id,
            'title' => $request->input('title'),
            'instructions' => $request->input('instructions'),
        ]);

        foreach ($class->subjects as $subject) {
            SubjectExam::create(['exam_id' => $exam->id, 'subject_id' => $subject->id]);
        }

        return response()->json(['exam' => $exam->fresh(self::RELATIONS)], 201);
    }

    /**
     * Update an exam's title/instructions. Once published, the schedule is
     * changed via publish()/unpublish() instead of here.
     */
    public function update(Request $request, Exam $exam): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $exam->update($validator->validated());

        return response()->json(['exam' => $exam->fresh(self::RELATIONS)]);
    }

    /**
     * Delete an exam (and its subject-papers/sections/questions, via
     * cascade) — but only before any student has started taking it. Once an
     * attempt exists, deleting would silently destroy a student's work, so
     * that has to go through unpublishing/grading instead.
     */
    public function destroy(Exam $exam): JsonResponse
    {
        $hasAttempts = SubjectExamAttempt::whereIn('exam_id', $exam->subjectExams()->pluck('id'))->exists();

        abort_if($hasAttempts, 422, "This exam can't be deleted — students have already started taking it.");

        $exam->delete();

        return response()->json(['message' => 'Exam deleted.']);
    }

    /**
     * Publish the exam with a scheduled window, shared by every subject's
     * paper. Requires every subject's paper to already have at least one
     * subject's teacher(s) to have actually submitted their questions — not
     * just started drafting them — so a published exam is never missing
     * content some teacher is still mid-draft on.
     */
    public function publish(Request $request, Exam $exam): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $notSubmitted = $exam->subjectExams()->whereNull('submitted_at')->with('subject:id,name')->get();

        abort_if(
            $notSubmitted->isNotEmpty(),
            422,
            'These subjects have not submitted their questions yet: '.$notSubmitted->pluck('subject.name')->implode(', ')
        );

        $exam->update([
            ...$validator->validated(),
            'published_at' => now(),
        ]);

        return response()->json(['exam' => $exam->fresh(self::RELATIONS)]);
    }

    /**
     * Pull a published exam back to draft — e.g. to let a subject's teacher
     * fix a mistake in a section/question. Section/question edits are
     * blocked while published, so this is also how that access is reopened.
     */
    public function unpublish(Exam $exam): JsonResponse
    {
        $exam->update(['published_at' => null, 'starts_at' => null, 'ends_at' => null]);

        return response()->json(['exam' => $exam->fresh(self::RELATIONS)]);
    }

    /**
     * Publish the class-wide ranking/results — separate from publish()
     * above, which only controls whether students can take the exam.
     * Requires the exam itself to be published and every attempt across
     * every subject's paper to already be graded, so the ranking a
     * student sees is never based on incomplete grading.
     */
    public function publishResults(Exam $exam): JsonResponse
    {
        abort_unless($exam->published_at !== null, 422, 'Publish the exam itself before publishing its results.');

        $subjectExamIds = $exam->subjectExams()->pluck('id');

        // A student who opened the exam but never submitted before the
        // window closed is absent, not "still being graded" — resolve that
        // automatically (score 0) so one no-show doesn't block the whole
        // class's results forever. Only once the window has actually closed,
        // so a student still legitimately mid-attempt is never touched.
        if ($exam->ends_at !== null && now()->gt($exam->ends_at)) {
            SubjectExamAttempt::whereIn('exam_id', $subjectExamIds)
                ->where('status', SubjectExamAttempt::STATUS_IN_PROGRESS)
                ->each(fn (SubjectExamAttempt $attempt) => $attempt->update([
                    'status' => SubjectExamAttempt::STATUS_GRADED,
                    'submitted_at' => $attempt->submitted_at ?? now(),
                    'graded_at' => now(),
                    'score' => 0,
                ]));
        }

        $stillGrading = SubjectExamAttempt::whereIn('exam_id', $subjectExamIds)
            ->whereIn('status', [SubjectExamAttempt::STATUS_IN_PROGRESS, SubjectExamAttempt::STATUS_SUBMITTED])
            ->exists();

        abort_if($stillGrading, 422, 'Every attempt must be graded before publishing results.');

        $exam->update(['results_published_at' => now()]);

        return response()->json(['exam' => $exam->fresh(self::RELATIONS)]);
    }

    /**
     * Pull published results back — e.g. a grading mistake was found after
     * publishing.
     */
    public function unpublishResults(Exam $exam): JsonResponse
    {
        $exam->update(['results_published_at' => null]);

        return response()->json(['exam' => $exam->fresh(self::RELATIONS)]);
    }
}
