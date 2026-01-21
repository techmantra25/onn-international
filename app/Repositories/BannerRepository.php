<?php

namespace App\Repositories;

use App\Interfaces\BannerInterface;
use App\Models\Banner;

class BannerRepository implements BannerInterface 
{
    public function listAll() 
    {
        return Banner::orderBy('position')->get();
    }

    public function listById($id) 
    {
        return Banner::findOrFail($id);
    }

    public function create(array $data) 
    {
        $upload_path = "public/uploads/banner/";
        $collection = collect($data);

        $banner = new Banner;

        // image
        $image = $collection['image'];
        $imageName = time().".".mt_rand().".".$image->getClientOriginalName();
        $image->move($upload_path, $imageName);
        $uploadedImage = $imageName;
        $banner->file_path = $upload_path.$uploadedImage;

        // file type
        $extension = $image->getClientOriginalExtension();
        $imageTypes = array('jpg', 'jpeg', 'png', 'svg', 'gif');

        if (in_array($extension, $imageTypes)) {
            $banner->type = 'img';
        } else {
            $banner->type = 'video';
        }

        // position
        $latestPosition = Banner::select('position')->orderBy('position', 'desc')->first();
        if(!empty($latestPosition)) {
            $banner->position = $latestPosition->position + 1;
        } else {
            $banner->position = 1;
        }

        $banner->save();

        return $banner;
    }

    public function update($id, array $newDetails) 
    {
        $upload_path = "public/uploads/banner/";
        $banner = Banner::findOrFail($id);
        $collection = collect($newDetails); 

        if (isset($newDetails['image'])) {
            // image
            $image = $collection['image'];
            $imageName = time().".".mt_rand().".".$image->getClientOriginalName();
            $image->move($upload_path, $imageName);
            $uploadedImage = $imageName;
            $banner->file_path = $upload_path.$uploadedImage;

            // file type
            $extension = $image->getClientOriginalExtension();
            $imageTypes = array('jpg', 'jpeg', 'png', 'svg', 'gif');

            if (in_array($extension, $imageTypes)) {
                $banner->type = 'img';
            } else {
                $banner->type = 'video';
            }
        }

        $banner->save();

        return $banner;
    }

    public function toggle($id){
        $updatedEntry = Banner::findOrFail($id);

        $status = ( $updatedEntry->status == 1 ) ? 0 : 1;
        $updatedEntry->status = $status;
        $updatedEntry->save();

        return $updatedEntry;
    }

    public function delete($id) 
    {
        Banner::destroy($id);
    }
}