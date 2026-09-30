<?php

use App\Http\Controllers\Api\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Api\Admin\ClassController;
use App\Http\Controllers\Api\Admin\ExamController as AdminExamController;
use App\Http\Controllers\Api\Admin\ExamResultsController as AdminExamResultsController;
use App\Http\Controllers\Api\Admin\LessonController;
use App\Http\Controllers\Api\Admin\StatsController;
use App\Http\Controllers\Api\Admin\SubjectController;
use App\Http\Controllers\Api\Admin\SubjectExamController as AdminSubjectExamController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Parent\DashboardController as ParentDashboardController;
use App\Http\Controllers\Api\Student\AssessmentController as StudentAssessmentController;
use App\Http\Controllers\Api\Student\AttendanceController as StudentAttendanceController;
use App\Http\Controllers\Api\Student\AttemptController as StudentAttemptController;
use App\Http\Controllers\Api\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Api\Student\ExamResultsController;
use App\Http\Controllers\Api\Student\LessonController as StudentLessonController;
use App\Http\Controllers\Api\Student\ResultsController as StudentResultsController;
use App\Http\Controllers\Api\Student\SubjectExamAttemptController as StudentSubjectExamAttemptController;
use App\Http\Controllers\Api\Student\SubjectExamController as StudentSubjectExamController;
use App\Http\Controllers\Api\Teacher\AssessmentController as TeacherAssessmentController;
use App\Http\Controllers\Api\Teacher\AssessmentQuestionController;
use App\Http\Controllers\Api\Teacher\AttendanceController as TeacherAttendanceController;
use App\Http\Controllers\Api\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Api\Teacher\ExamResultsController as TeacherExamResultsController;
use App\Http\Controllers\Api\Teacher\GradingController;
use App\Http\Controllers\Api\Teacher\LessonController as TeacherLessonController;
use App\Http\Controllers\Api\Teacher\SubjectExamController as TeacherSubjectExamController;
use App\Http\Controllers\Api\Teacher\SubjectExamGradingController;
use App\Http\Controllers\Api\Teacher\SubjectExamQuestionController;
use App\Http\Controllers\Api\Teacher\SubjectExamSectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    // Deliberately outside the auth:api group: jwt-auth's refresh() accepts
    // a token that has already expired (just signed and not yet past its
    // refresh_ttl window), and the auth:api middleware would reject such a
    // token before the request ever reached the controller — which is
    // exactly the case a "refresh" endpoint exists to handle. The frontend
    // calls this the moment a request 401s with an expired token, so a long
    // active session (e.g. a teacher slowly working through a form) never
    // gets bounced to the login screen.
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'updatePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('admin')->middleware(['auth:api', 'role:admin'])->group(function () {
    Route::get('/stats', [StatsController::class, 'index']);

    Route::apiResource('users', UserController::class)->except(['show']);
    Route::put('/users/{user}/parents', [UserController::class, 'syncParents']);
    Route::put('/users/{user}/subjects', [UserController::class, 'syncSubjects']);

    Route::apiResource('classes', ClassController::class)->except(['show']);
    Route::apiResource('subjects', SubjectController::class)->except(['show']);
    Route::put('/subjects/{subject}/teachers', [SubjectController::class, 'syncTeachers']);
    Route::apiResource('lessons', LessonController::class)->except(['show']);

    Route::get('/subject-exams/{subjectExam}', [AdminSubjectExamController::class, 'show']);

    Route::get('/exams', [AdminExamController::class, 'index']);
    Route::post('/exams', [AdminExamController::class, 'store']);
    Route::get('/exams/{exam}', [AdminExamController::class, 'show']);
    Route::put('/exams/{exam}', [AdminExamController::class, 'update']);
    Route::delete('/exams/{exam}', [AdminExamController::class, 'destroy']);
    Route::post('/exams/{exam}/publish', [AdminExamController::class, 'publish']);
    Route::post('/exams/{exam}/unpublish', [AdminExamController::class, 'unpublish']);
    Route::post('/exams/{exam}/publish-results', [AdminExamController::class, 'publishResults']);
    Route::post('/exams/{exam}/unpublish-results', [AdminExamController::class, 'unpublishResults']);

    Route::get('/exams/{exam}/results', [AdminExamResultsController::class, 'index']);
    Route::get('/exams/{exam}/results/{student}', [AdminExamResultsController::class, 'show']);

    Route::get('/attendance', [AdminAttendanceController::class, 'show']);
    Route::get('/attendance/summary', [AdminAttendanceController::class, 'summary']);
    Route::get('/attendance/dates', [AdminAttendanceController::class, 'dates']);
});

Route::prefix('student')->middleware(['auth:api', 'role:student'])->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index']);
    Route::get('/lessons/{lesson}', [StudentLessonController::class, 'show']);
    Route::get('/assessments/{assessment}', [StudentAssessmentController::class, 'show']);
    Route::post('/assessments/{assessment}/attempts', [StudentAttemptController::class, 'store']);
    Route::post('/attempts/{attempt}/submit', [StudentAttemptController::class, 'submit']);

    Route::get('/subject-exams', [StudentSubjectExamController::class, 'index']);
    Route::get('/subject-exams/{exam}', [StudentSubjectExamController::class, 'show']);
    Route::post('/subject-exams/{exam}/attempts', [StudentSubjectExamAttemptController::class, 'store']);
    Route::post('/subject-exam-attempts/{attempt}/submit', [StudentSubjectExamAttemptController::class, 'submit']);

    Route::get('/results', [StudentResultsController::class, 'index']);
    Route::get('/class-results', [ExamResultsController::class, 'published']);

    Route::get('/exams/{exam}/results', [ExamResultsController::class, 'index']);
    Route::get('/exams/{exam}/results/{student}', [ExamResultsController::class, 'show']);

    Route::get('/attendance', [StudentAttendanceController::class, 'index']);
});

Route::prefix('teacher')->middleware(['auth:api', 'role:teacher'])->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index']);
    Route::post('/lessons', [TeacherLessonController::class, 'store']);
    Route::get('/lessons/{lesson}', [TeacherLessonController::class, 'show']);
    Route::put('/lessons/{lesson}', [TeacherLessonController::class, 'update']);
    Route::post('/lessons/{lesson}/start', [TeacherLessonController::class, 'start']);
    Route::post('/lessons/{lesson}/end', [TeacherLessonController::class, 'end']);
    Route::put('/lessons/{lesson}/content', [TeacherLessonController::class, 'updateContent']);
    Route::post('/lessons/{lesson}/release-content', [TeacherLessonController::class, 'releaseContent']);
    Route::post('/lessons/{lesson}/unrelease-content', [TeacherLessonController::class, 'unreleaseContent']);

    Route::get('/assessments', [TeacherAssessmentController::class, 'index']);
    Route::post('/assessments', [TeacherAssessmentController::class, 'store']);
    Route::put('/assessments/{assessment}', [TeacherAssessmentController::class, 'update']);
    Route::delete('/assessments/{assessment}', [TeacherAssessmentController::class, 'destroy']);

    Route::post('/assessments/{assessment}/questions', [AssessmentQuestionController::class, 'store']);
    Route::put('/questions/{question}', [AssessmentQuestionController::class, 'update']);
    Route::delete('/questions/{question}', [AssessmentQuestionController::class, 'destroy']);

    Route::get('/grading', [GradingController::class, 'index']);
    Route::post('/assessment-answers/{answer}/grade', [GradingController::class, 'grade']);

    Route::get('/subject-exams', [TeacherSubjectExamController::class, 'index']);
    Route::post('/subject-exams/{exam}/submit', [TeacherSubjectExamController::class, 'submit']);
    Route::post('/subject-exams/{exam}/unsubmit', [TeacherSubjectExamController::class, 'unsubmit']);

    Route::post('/subject-exams/{exam}/sections', [SubjectExamSectionController::class, 'store']);
    Route::put('/subject-exam-sections/{section}', [SubjectExamSectionController::class, 'update']);
    Route::delete('/subject-exam-sections/{section}', [SubjectExamSectionController::class, 'destroy']);

    Route::post('/subject-exam-sections/{section}/questions', [SubjectExamQuestionController::class, 'store']);
    Route::put('/subject-exam-questions/{question}', [SubjectExamQuestionController::class, 'update']);
    Route::delete('/subject-exam-questions/{question}', [SubjectExamQuestionController::class, 'destroy']);

    Route::get('/subject-exam-grading', [SubjectExamGradingController::class, 'index']);
    Route::post('/subject-exam-answers/{answer}/grade', [SubjectExamGradingController::class, 'grade']);

    Route::get('/exams/{exam}/results', [TeacherExamResultsController::class, 'index']);
    Route::get('/exams/{exam}/results/{student}', [TeacherExamResultsController::class, 'show']);

    Route::get('/attendance/classes', [TeacherAttendanceController::class, 'classes']);
    Route::get('/attendance', [TeacherAttendanceController::class, 'show']);
    Route::post('/attendance', [TeacherAttendanceController::class, 'store']);
    Route::get('/attendance/summary', [TeacherAttendanceController::class, 'summary']);
    Route::get('/attendance/dates', [TeacherAttendanceController::class, 'dates']);
});

Route::prefix('parent')->middleware(['auth:api', 'role:parent'])->group(function () {
    Route::get('/dashboard', [ParentDashboardController::class, 'index']);
    Route::get('/children/{child}/scores', [ParentDashboardController::class, 'childScores']);
    Route::get('/children/{child}/class-results', [ParentDashboardController::class, 'childClassResults']);
    Route::get('/children/{child}/exams/{exam}/results', [ParentDashboardController::class, 'childExamResult']);
    Route::get('/children/{child}/attendance', [ParentDashboardController::class, 'childAttendance']);
});
