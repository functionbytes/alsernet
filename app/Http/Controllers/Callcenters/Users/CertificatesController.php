<?php

namespace App\Http\Controllers\Callcenters\Users;

use App\Http\Controllers\Controller;
use App\Models\Users\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Course\Course;
use Illuminate\Http\Request;
use App\Models\Inscription;
use App\Models\User;

class CertificatesController extends Controller
{
    public function index(Request $request,$slack){

        $searchKey = null ?? $request->search;
        $course = null ?? $request->course;

        $courses = Course::latest()->get();
        $user = User::uid($slack);
        $certificates = $user->certificates()->latest();

        if ($searchKey) {
            $certificates = $certificates->where('firstname', 'like', '%' . $searchKey . '%');
        }

        if ($request->course != null) {
            $certificates = $certificates->where('course_id', $course);
        }

        $certificates = $certificates->paginate(paginationNumber());

        return view('callcenters.views.enterprises.users.certificates.index')->with([
            'certificates' => $certificates,
            'searchKey' => $searchKey,
            'courses' => $courses,
            'course' => $course,
            'user' => $user,
        ]);

    }

    public function user($slack){

        $inscription = Inscription::uid($slack);
        $certificate = $inscription->certificate;
        $pdf = PDF::loadview('callcenters.views.enterprises.users.certificates.download', compact('certificate'))->setPaper('A4', 'landscape');
        return $pdf->stream();

    }

    public function course($slack){

        $certificate = Certificate::uid($slack);
        $pdf = Pdf::loadview('callcenters.views.enterprises.users.certificates.download', compact('certificate'))->setPaper('a4', 'landscape');
        return $pdf->stream();

    }

    public function broad($slack){

        $user = User::uid($slack);
        $certificates = $user->certificates;
        $pdf = PDF::loadview('callcenters.views.enterprises.users.certificates.broad', compact('certificates'))->setWarnings(false)->setPaper('a4', 'landscape');
        return $pdf->stream();

    }

}



