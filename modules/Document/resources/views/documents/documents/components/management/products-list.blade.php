{{-- Component: Products List Card --}}
@if($products && $products->count())
    <div class="card mb-3">
        <div class="card-header ">
            <h5 class="mb-1 fw-bold">Listado de productos</h5>
            <p class="small mb-0 text-muted">Productos relacionados con la orden</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover bg-light mb-0">
                    <tbody>
                    @foreach($products as $item)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $item->product_name }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary text-white">{{ $item->quantity}} ud</span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
