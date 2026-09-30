<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\SubjectExam;
use App\Models\SubjectExamAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectExamController extends Controller
{
    /**
     * List published exams for every subject in this student's class, with
     * a computed window status so the student can see what's upcoming, live
     * now, or already closed.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $exams = SubjectExam::query()
            ->whereHas('exam', fn ($query) => $query->whereNotNull('published_at'))
            ->whereHas('subject', fn ($query) => $query->where('class_id', $user->class_id))
            // A subject a teacher never added content to isn't a real exam
            // for that subject yet — nothing for the student to see or take.
            ->whereHas('sections')
            ->with(['subject:id,name,class_id', 'exam'])
            ->get()
            ->sortBy('starts_at')
            ->values();

        $attemptsByExam = SubjectExamAttempt::where('student_id', $user->id)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->get()
            ->keyBy('exam_id');

        $payload = $exams->map(function (SubjectExam $exam) use ($attemptsByExam) {
            $attempt = $attemptsByExam->get($exam->id);

            return [
                'id' => $exam->id,
                'title' => $exam->title,
                'subject' => $exam->subject,
                'starts_at' => $exam->starts_at,
                'ends_at' => $exam->ends_at,
                'window_status' => $this->windowStatus($exam),
                'attempt_status' => $attempt?->status,
                'score' => $attempt?->score,
                'max_score' => $attempt?->max_score,
                'exam_id' => $exam->exam_id,
                'results_published_at' => $exam->exam?->results_published_at,
            ];
        });

        return response()->json(['exams' => $payload]);
    }

    /**
     * Full detail for one exam. Section content (passages/questions/prompts,
     * never correct answers) is only revealed once the exam window has
     * opened, or to a student who already has an attempt on record (so they
     * can review it even after the window has since closed).
     */
    public function show(Request $request, SubjectExam $exam): JsonResponse
    {
        $user = $request->user();
        $subject = $exam->subject()->with('schoolClass:id,name,level')->firstOrFail();

        abort_unless($exam->published_at !== null, 404);
        abort_unless($user->class_id === $subject->class_id, 403);

        $attempt = SubjectExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $user->id)
            ->first();

        $windowStatus = $this->windowStatus($exam);

        $payload = [
            'id' => $exam->id,
            'title' => $exam->title,
            'instructions' => $exam->instructions,
            'subject' => $subject,
            'starts_at' => $exam->starts_at,
            'ends_at' => $exam->ends_at,
            'window_status' => $windowStatus,
            'attempt' => $attempt ? [
                'id' => $attempt->id,
                'status' => $attempt->status,
                'score' => $attempt->score,
                'max_score' => $attempt->max_score,
                'submitted_at' => $attempt->submitted_at,
                'graded_at' => $attempt->graded_at,
            ] : null,
        ];

        $contentUnlocked = $windowStatus === 'live' || $attempt !== null;

        if ($contentUnlocked) {
            $payload['sections'] = $exam->sections->map(fn ($section) => $this->sectionPayload($section, $attempt));
        }

        return response()->json(['exam' => $payload]);
    }

    protected function sectionPayload($section, ?SubjectExamAttempt $attempt): array
    {
        $base = [
            'id' => $section->id,
            'type' => $section->type,
            'title' => $section->title,
            'instructions' => $section->instructions,
            'passage' => $section->type === 'reading' ? $section->passage : null,
            'prompt' => $section->type !== 'reading' ? $section->prompt : null,
        ];

        // Only reveal the correct answer/grade once this attempt has
        // actually been submitted — never while still in_progress, or a
        // student could reload the page mid-exam and read the answers
        // straight off it.
        $reviewUnlocked = $attempt !== null && $attempt->status !== SubjectExamAttempt::STATUS_IN_PROGRESS;

        $base['questions'] = $section->questions->map(fn ($question) => [
            'id' => $question->id,
            'type' => $question->type,
            'answer_language' => $question->answer_language,
            'prompt' => $question->prompt,
            'options' => $question->options,
            'points' => $question->points,
            'min_word_count' => $question->min_word_count,
            'max_word_count' => $question->max_word_count,
            'answer_hint' => $question->type === 'fill_in_blank'
                ? explode('|', $question->correct_answer)[0]
                : null,
            'correct_answer' => $reviewUnlocked && $question->type !== 'written_response'
                ? $this->displayCorrectAnswer($question)
                : null,
        ]);

        if ($attempt) {
            $base['answers'] = $attempt->answers()
                ->whereIn('question_id', $section->questions->pluck('id'))
                ->get(['question_id', 'response_text', 'is_correct', 'points_awarded', 'feedback', 'word_count']);
        }

        return $base;
    }

    /**
     * A readable form of a question's stored correct_answer for review:
     * multiple_choice/true_false stay as the option key (the frontend
     * resolves it to a label via the question's own options); fill_in_blank
     * collapses each blank's "east|East" variant list down to its first
     * (most canonical) spelling and joins the blanks with ", ".
     */
    protected function displayCorrectAnswer($question): string
    {
        if ($question->type === 'fill_in_blank') {
            return collect(explode(';', $question->correct_answer))
                ->map(fn ($slot) => explode('|', $slot)[0])
                ->implode(', ');
        }

        return $question->correct_answer;
    }

    protected function windowStatus(SubjectExam $exam): string
    {
        $now = now();

        if ($exam->starts_at && $now->lt($exam->starts_at)) {
            return 'scheduled';
        }

        if ($exam->ends_at && $now->gt($exam->ends_at)) {
            return 'ended';
        }

        return 'live';
    }
}
