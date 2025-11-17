
    <div class="row owl-nav">
        <div class="col-6 text-center">
            <form action="{{ route('customer.courses.prev') }}" method="POST">
                {{ csrf_field() }}

                <input type="hidden" name="course" value="{{ $course->id }}">
                <input type="hidden" name="class" value="{{ $classing->id }}">
                <input type="hidden" name="user" value="{{ $user->id }}">

                <button type="submit" class="buttom-lesion">
                    <i class="fa-duotone fa-arrow-left"></i>
                </button>
            </form>
        </div>
        <div class="col-6 text-center">
            <form action="{{ route('customer.courses.realized') }}" method="POST">

                {{ csrf_field() }}

                <input type="hidden" name="course" value="{{ $course->id }}">
                <input type="hidden" name="class" value="{{ $classing->id }}">
                <input type="hidden" name="user" value="{{ $user->id }}">

                <button type="submit" class="buttom-lesion">
                    <i class="fa-duotone fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

