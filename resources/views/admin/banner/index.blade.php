@extends('admin.layouts.app')

@section('page', 'Banner')

@section('content')
<style>
    .file-holder img {
        height: 100px
    }
</style>

<section>
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#SR</th>
                                <th>FILE</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="file-holder">
                                    @if ($item->type == 'video')
                                        <video id="onn-video" height="100" muted loop controls playsinline>
                                            <source src="{{ asset($item->file_path) }}" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    @else
                                        <img src="{{ asset($item->file_path) }}" />
                                    @endif
                                    <div class="row__action">
                                        <a href="{{ route('admin.banner.view', $item->id) }}">Edit</a>
                                        <a href="{{ route('admin.banner.view', $item->id) }}">View</a>
                                        <a href="{{ route('admin.banner.status', $item->id) }}">{{($item->status == 1) ? 'Active' : 'Inactive'}}</a>
                                        <a href="{{ route('admin.banner.delete', $item->id) }}" onclick="return confirm('Are you sure?')" class="text-danger">Delete</a>
                                    </div>
                                </td>
                                <td>Published<br/>{{date('d M Y', strtotime($item->created_at))}}</td>
                                <td><span class="badge bg-{{($item->status == 1) ? 'success' : 'danger'}}">{{($item->status == 1) ? 'Active' : 'Inactive'}}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="100%" class="small text-muted">No data found</td></tr>
                            @endforelse
                        </tbody>
                    </table>    
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.banner.store') }}" enctype="multipart/form-data">@csrf
                        <h4 class="page__subtitle">Add New</h4>
                        <div class="row">
                            <div class="col-md-12 card">
                                <div class="card-header p-0 mb-3">Image <span class="text-danger">*</span></div>
                                <div class="card-body p-0">
                                    <div class="w-100 product__thumb">
                                        <label for="icon"><img id="iconOutput" src="{{ asset('admin/images/placeholder-image.jpg') }}" /></label>
                                    </div>
                                    <input type="file" name="image" id="icon" accept="image/*" onchange="loadIcon(event)" class="d-none">
                                    <p class="small text-muted">Click here to browse image</p>

                                    <script>
                                        let loadIcon = function(event) {
                                            let iconOutput = document.getElementById('iconOutput');
                                            iconOutput.src = URL.createObjectURL(event.target.files[0]);
                                            iconOutput.onload = function() {
                                                URL.revokeObjectURL(iconOutput.src) // free memory
                                            }
                                        };
                                    </script>
                                </div>
                                @error('image') <p class="small text-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-sm btn-danger">Add New</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection