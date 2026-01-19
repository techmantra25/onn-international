@extends('admin.layouts.app')

@section('page', 'Product SKU codes')

@section('content')
<section>
    <div class="search__filter">
        <div class="row mb-3">
            <div class="col-12 text-end">
                <a href="{{ route('admin.product.sku_list.sync.all') }}" class="btn btn-sm btn-danger">SYNC ALL SKUs</a>
            </div>
        </div>
        <div class="row">
            <div class="col-8">
                <p class="small text-muted">Displaying {{$products->firstItem()}} - {{$products->lastItem()}} out of {{$products->total()}} data</p>
            </div>
            <div class="col-4">
                <form action="">
                    <div class="d-flex justify-content-end">
                        {{-- <div class="me-3">
                            <select name="order" id="order" class="form-control">
                                <option value="">Sort by</option>
                                <option value="synched_desc">Latest synched</option>
                            </select>
                        </div> --}}
                        <div class="me-3">
                            <input type="search" name="term" id="term" class="form-control" placeholder="Search SKU/ Style no..." value="{{ app('request')->input('term') }}" autocomplete="off">
                        </div>
                        <div>
                            <div class="btn-group">
                                <button type="submit" data-bs-toggle="tooltip" title="Search" class="btn btn-sm btn-danger"> <i class="fi fi-br-search"></i> </button>

                                <a href="{{ route('admin.product.sku_list') }}" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Clear search"> <i class="fi fi-br-x"></i> </a>

                                <a href="{{ route('admin.product.sku_list.export') }}" data-bs-toggle="tooltip" class="btn btn-sm btn-danger" title="Export"> <i class="fi fi-br-download"></i> </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- @if (Session::has('message'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>{{ Session::get('message') }}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif --}}

    <table class="table">
        <thead>
            <tr>
                <th>#SR</th>
                <th>SKU Code</th>
                <th>Inventory</th>
                <th>Product Name</th>
                <th>Color</th>
                <th>Size</th>
                <th>Price</th>
                <th>Sync</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($products as $index => $item)
            <tr>
                <td>{{$index + $products->firstItem()}}</td>
                <td>{{$item->code}}</td>
                <td>
                    <p class="text-dark mb-0">{{$item->stock}}</p>
                    <p class="small mb-0">{{ $item->last_stock_synched ? 'Synched '.\Carbon\Carbon::createFromTimeStamp(strtotime($item->last_stock_synched))->diffForHumans() : '' }}</p>
                </td>
                <td>
                    {{ $item->productDetails->name ?? '' }}
                    <p class="small mb-0">#{{ $item->productDetails->style_no ?? '' }}</p>
                    <div class="row__action">
                        <a href="{{ route('admin.product.edit', $item->product_id) }}">Edit</a>
                    </div>
                </td>
                <td>{{ $item->color_name ? $item->color_name : $item->colorDetails->name }}</td>
                <td>{{$item->sizeDetails->name ?? ''}}</td>
                <td>Rs.{{number_format($item->offer_price) ?? 0}}</td>
                <td>
                    <a href="{{ route('admin.product.unicommerce.sync.single', $item->id) }}" class="badge bg-danger rounded-0">
                        Sync NOW
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-refresh-cw"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="100%" class="small text-muted text-center">No data found</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    {{$products->appends($_GET)->links()}}

</section>
@endsection
