@if (isset($errors) && $errors->any())
    <div class="alert alert-success  text-black alert-dismissible fade show" role="alert">
        <i class="fa fa-circle-exclamation"></i>
        <strong>Error:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success  text-black alert-dismissible fade show" role="alert">
        <i class="fa fa-circle-check"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert bg-danger alert-dismissible fade show" role="alert">
        <i class="fa fa-circle-exclamation"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('warning'))
    <div class="alert bg-warning alert-dismissible fade show" role="alert">
        <i class="fa fa-triangle-exclamation"></i>
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('info'))
    <div class="alert alert-success  alert-dismissible fade show" role="alert">
        <i class="fa fa-circle-info"></i>
        {{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
