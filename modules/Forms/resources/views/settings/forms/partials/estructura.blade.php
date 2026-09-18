<div class="row g-3">
    @foreach($form->fields as $field)
        @include('forms::public.partials.field', compact('field', 'formId', 'floatingLabel'))
    @endforeach
</div>
