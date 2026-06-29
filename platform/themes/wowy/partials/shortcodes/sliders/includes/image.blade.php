@if ($slider->link)
    <a href="{{ url($slider->link) }}" class="d-block m-auto">
        @include('plugins/simple-slider::includes.image', ['slider' => $slider, 'attributes' => ['class' => 'animated', 'loading' => 'eager']])
    </a>
@else
    @include('plugins/simple-slider::includes.image', ['slider' => $slider, 'attributes' => ['class' => 'animated', 'loading' => 'eager']])
@endif
