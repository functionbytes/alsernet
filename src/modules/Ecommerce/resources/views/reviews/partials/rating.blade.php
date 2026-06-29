@for($i = 1; $i <= 5; $i++)
    <i class="fa{{ $i <= $star ? 's' : 'r' }} fa-star text-warning"></i>
@endfor
