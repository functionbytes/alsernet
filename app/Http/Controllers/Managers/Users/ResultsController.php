<?php

namespace App\Http\Controllers\Managers\Users;

use App\Exports\Managers\ResultsExport;
use App\Http\Controllers\Controller;
use App\Models\Group\Course\Course;
use App\Models\User;
use App\Models\Users\Certificate;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ResultsController extends Controller
{

    public function index(Request $request,$slack){

        $searchKey = null ?? $request->search;
        $course = null ?? $request->course;

        $courses = Course::latest()->get();
        $user = User::uid($slack);
        $certificates = $user->certificates();

        if ($searchKey) {
            $certificates = $certificates->where('firstname', 'like', '%' . $searchKey . '%');
        }

        if ($request->course != null) {
            $certificates = $certificates->where('course_id', $course);
        }

        $certificates = $certificates->paginate(paginationNumber());

        return view('managers.views.exams.results.index')->with([
            'certificates' => $certificates,
            'searchKey' => $searchKey,
            'courses' => $courses,
            'course' => $course,
        ]);
    }

    public function view($slack){

        $certificate = Certificate::uid($slack);
        $exam = $certificate->exam;
        $answers = $certificate->exam?->answers;
        $wrongs = $certificate->exam?->answers()?->wrong()->count();
        $corrects = $certificate->exam?->answers()?->correct()->count();

        return view('managers.views.exams.results.view')->with([
            'certificate' => $certificate,
            'exam' => $exam,
            'answers' => $answers,
            'corrects' => $corrects,
            'wrongs' => $wrongs,
        ]);

    }

    public function download($slack){

        $certificate = Certificate::uid($slack);
        $exam = $certificate->exam;
        $user = $certificate->user;
        $answers = $certificate->exam?->answers();

        return Excel::download(new ResultsExport($exam,$answers), $user->identification.'.xlsx');

    }

}
