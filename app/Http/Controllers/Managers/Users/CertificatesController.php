<?php

namespace App\Http\Controllers\Managers\Users;

use App\Http\Controllers\Controller;
use App\Models\Group\Course\Course;
use App\Models\Order\Order;
use App\Models\User;
use App\Models\Users\Certificate;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CertificatesController extends Controller
{

    public function index(Request $request,$slack){

        $searchKey = null ?? $request->search;
        $course = null ?? $request->course;

        $courses = Course::latest()->get();
        $user = User::slack($slack);
        $certificates = $user->certificates()->latest();;

        if ($searchKey) {
            $certificates = $certificates->where('firstname', 'like', '%' . $searchKey . '%');
        }

        if ($request->course != null) {
            $certificates = $certificates->where('course_id', $course);
        }

        $certificates = $certificates->paginate(paginationNumber());

        return view('managers.views.users.certificates.index')->with([
            'certificates' => $certificates,
            'searchKey' => $searchKey,
            'courses' => $courses,
            'course' => $course,
            'user' => $user,
        ]);

    }
    public function download($slack){

        $certificate = Certificate::slack($slack);
        $pdf = \Pdf::loadView('managers.views.users.certificates.download', compact('certificate'))->setPaper('a4', 'landscape');
        return $pdf->stream();


    }
    public function user($slack){

        $order = Order::slack($slack);
        $certificate = $order->certificate;

            if ($certificate == null) {

                $certificate = new Certificate;
                $certificate->slack = $this->generate_slack('certificates');
                $certificate->course_id = $order->course_id;
                $certificate->user_id = $order->user_id;
                $certificate->order_id = $order->order_id;
                $certificate->start_at = $certificate->culminated_at;
                $certificate->end_at = Carbon::parse($order->culminated_at)->addMonths(12);
                $certificate->created_at = $order->culminated_at;
                $certificate->updated_at = $order->culminated_at;
                $certificate->save();


                $pdf = \Pdf::loadView('managers.views.users.certificates.download', compact('certificate'))->setPaper('a4', 'landscape');

                return $pdf->stream();

            } else {

                $pdf = \Pdf::loadView('managers.views.users.certificates.download', compact('certificate'))->setPaper('a4', 'landscape');

                return $pdf->stream();

            }

    }
    public function course($slack){

        $certificate = Certificate::slack($slack);
        $pdf = \Pdf::loadView('managers.views.users.certificates.download', compact('certificate'))->setPaper('a4', 'landscape');
        return $pdf->stream();

    }
    public function broad($slack){

        $user = User::slack($slack);
        $certificates = $user->certificates;
        $pdf = \Pdf::loadView('managers.views.users.certificates.broad', compact('certificates'))->setPaper('a4', 'landscape');
        return $pdf->stream();

    }

}

