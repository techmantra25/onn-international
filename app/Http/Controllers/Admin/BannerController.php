<?php

namespace App\Http\Controllers\Admin;

use App\Interfaces\BannerInterface;
use App\Models\Banner;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class BannerController extends Controller
{
    public function __construct(BannerInterface $bannerInterface) 
    {
        $this->bannerRepository = $bannerInterface;
    }

    public function index(Request $request) 
    {
        $data = $this->bannerRepository->listAll();
        return view('admin.banner.index', compact('data'));
    }

    public function store(Request $request) 
    {
        $request->validate([
            "image" => "required|mimes:jpg,jpeg,png,svg,gif|max:10000000",
        ]);

        $params = $request->except('_token');
        $storeData = $this->bannerRepository->create($params);

        if ($storeData) {
            return redirect()->route('admin.banner.index');
        } else {
            return redirect()->route('admin.banner.create')->withInput($request->all());
        }
    }

    public function show(Request $request, $id)
    {
        $data = $this->bannerRepository->listById($id);
        return view('admin.banner.detail', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            "image" => "nullable|mimes:jpg,jpeg,png,svg,gif|max:10000000",
        ]);

        $params = $request->except('_token');
        $storeData = $this->bannerRepository->update($id, $params);

        if ($storeData) {
            return redirect()->route('admin.banner.index');
        } else {
            return redirect()->route('admin.banner.create')->withInput($request->all());
        }
    }

    public function status(Request $request, $id)
    {
        $storeData = $this->bannerRepository->toggle($id);

        if ($storeData) {
            return redirect()->route('admin.banner.index');
        } else {
            return redirect()->route('admin.banner.create')->withInput($request->all());
        }
    }

    public function destroy(Request $request, $id) 
    {
        $this->bannerRepository->delete($id);

        return redirect()->route('admin.banner.index');
    }
}
