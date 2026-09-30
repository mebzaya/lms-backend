<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubjectExam;
use Illuminate\Http\JsonResponse;

class SubjectExamController extends Controller
{
    /**
     * Full detail for one subject's paper — its submitted sections/
     * questions, and every student's attempt with their answers, so an
     * admin can review what a subject's teacher(s) submitted and how
     * students answered without needing to grade anything themselves.
     *
     * @var array<int, string>
     */
    protected const RELATIONS = [
        'subject:id,name,class_id',
        'exam:id,title,class_id',
        'sections.questions',
        'attempts.student:id,name',
        'attempts.answers',
    ];

    public function show(SubjectExam $subjectExam): JsonResponse
    {
        return response()->json(['subject_exam' => $subjectExam->load(self::RELATIONS)]);
    }
}
