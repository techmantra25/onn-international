<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductColorSize extends Model
{
    protected $fillable = ['id', 'product_id', 'color', 'size', 'stock', 'code', 'price', 'offer_price', 'position'];

    public function colorDetails() {
        return $this->belongsTo('App\Models\Color', 'color', 'id');
    }

    public function sizeDetails() {
        return $this->belongsTo('App\Models\Size', 'size', 'id');
    }

    public function productDetails() {
        return $this->belongsTo('App\Models\Product', 'product_id', 'id');
    }

    // csv upload
//     public static function insertData($data, $successCount) {
//         $resp = [];

//         $productCheck = DB::table('products')->select('id')->where('style_no', $data['PRODUCT_STYLE_NO'])->first();
//         if (!$productCheck) {
//             $failedRows[] = [
//                 "PRODUCT_STYLE_NO" => $data['PRODUCT_STYLE_NO'],
//                 "COLOR"            => $data['COLOR_MASTER'],
//                 "SIZE"             => $data['SIZE'],
//                 "Reason"           => "Product not found"
//             ];
//             continue; // Skip this CSV row and move to next
//         }
//         $color = DB::table('colors')->select('id', 'name')->where('name', $data['COLOR_MASTER'])->first();
//         $size = DB::table('sizes')->select('id', 'name')->where('name', $data['SIZE'])->first();
//         // $code = DB::table('product_color_sizes')->select('id')->where('code', $data['SKU_CODE'])->get();

//         // check if size already exists
//         $sizeChk = DB::table('product_color_sizes')
//         ->select('id', 'code')
//         ->where('product_id', $productCheck->id)
//         ->where('color', $color->id)
//         ->where('size', $size->id)
//         ->first();

//         if (!isset($sizeChk)) {
//             // check if SKU exists
//             DB::table('product_color_sizes')->insert([
//                 'product_id' => $productCheck->id,
//                 'product_style_no' => $data['PRODUCT_STYLE_NO'],
//                 'color' => $color->id,
//                 'color_name' => $color->name,
//                 'color_fabric' => null,
//                 'size' => $size->id,
//                 'size_name' => $size->name,
//                 'size_details' => null,
//                 'assorted_flag' => 0,
//                 'price' => $data['PRICE'],
//                 'offer_price' => $data['OFFER_PRICE'],
//                 'stock' => $data['STOCK'],
//                 'code' => $data['SKU_CODE'],
//                 'position' => $data['COLOR_POSITION'],
//                 'status' => $data['STATUS']
//             ]);
//             $successCount++;
    
//             $csvStatus = 'success';
//             $csvDesc = 'All correct data provided';
//         } else {
//             if (!empty($sizeChk->code)) {
//                 $csvStatus = 'failure';
//                 $csvDesc = 'This Size & code already exists for this product';
//             } else {
//                 // update sku code
//                 DB::table('product_color_sizes')
//                 ->where('id', $sizeChk->id)
//                 ->update([
//                     'code' => $data['SKU_CODE']
//                 ]);
//                 $successCount++;
        
//                 $csvStatus = 'success';
//                 $csvDesc = 'Size exists & SKU code updated';
//             }
//         }

//         // csv status upload
//         DB::table('csv_upload_logs')->insert([
//             'type' => 'bulk product variation upload',
//             'product_style_no' => $data['PRODUCT_STYLE_NO'],
//             'sku_code' => $data['SKU_CODE'],
//             'color' => $data['COLOR_MASTER'],
//             'size' => $data['SIZE'],
//             'status' => $csvStatus,
//             'desc' => $csvDesc,
//         ]);

//         $resp = [
//             "successCount" => $successCount,    
//         ];

//         return $resp;
//     }


public static function insertData($data, $successCount)
{
    $resp = [];

    /* --------------------------------------
        1. CHECK PRODUCT EXISTS
    -------------------------------------- */
    $productCheck = DB::table('products')
        ->select('id')
        ->where('style_no', $data['PRODUCT_STYLE_NO'])
        ->first();

    if (!$productCheck) {
        $failedRows[] = [
            "PRODUCT_STYLE_NO" => $data['PRODUCT_STYLE_NO'],
            "COLOR"            => $data['COLOR_MASTER'],
            "SIZE"             => $data['SIZE'],
            "Reason"           => "Product not found"
        ];
        return ["successCount" => $successCount];  
    }

    /* --------------------------------------
        2. CHECK COLOR
    -------------------------------------- */
    $color = DB::table('colors')
        ->select('id', 'name')
        ->where('name', $data['COLOR_MASTER'])
        ->first();

    if (!$color) {
        $failedRows[] = [
            "PRODUCT_STYLE_NO" => $data['PRODUCT_STYLE_NO'],
            "COLOR"            => $data['COLOR_MASTER'],
            "SIZE"             => $data['SIZE'],
            "Reason"           => "Color not found"
        ];
        return ["successCount" => $successCount];
    }

    /* --------------------------------------
        3. CHECK SIZE
    -------------------------------------- */
    $size = DB::table('sizes')
        ->select('id', 'name')
        ->where('name', $data['SIZE'])
        ->first();

    if (!$size) {
        $failedRows[] = [
            "PRODUCT_STYLE_NO" => $data['PRODUCT_STYLE_NO'],
            "COLOR"            => $data['COLOR_MASTER'],
            "SIZE"             => $data['SIZE'],
            "Reason"           => "Size not found"
        ];
        return ["successCount" => $successCount];
    }

    /* --------------------------------------
        4. CHECK IF SIZE + COLOR EXISTS FOR THIS PRODUCT
    -------------------------------------- */
    $sizeChk = DB::table('product_color_sizes')
        ->select('id', 'code')
        ->where('product_id', $productCheck->id)
        ->where('color', $color->id)
        ->where('size', $size->id)
        ->first();

    /* --------------------------------------
        5. INSERT NEW OR UPDATE
    -------------------------------------- */
    if (!$sizeChk) {

        DB::table('product_color_sizes')->insert([
            'product_id'        => $productCheck->id,
            'product_style_no'  => $data['PRODUCT_STYLE_NO'],
            'color'             => $color->id,
            'color_name'        => $color->name,
            'color_fabric'      => null,
            'size'              => $size->id,
            'size_name'         => $size->name,
            'size_details'      => null,
            'assorted_flag'     => 0,
            'price'             => $data['PRICE'],
            'offer_price'       => $data['OFFER_PRICE'],
            'stock'             => $data['STOCK'],
            'code'              => $data['SKU_CODE'],
            'position'          => $data['COLOR_POSITION'],
            'status'            => $data['STATUS']
        ]);

        $successCount++;
        $csvStatus = 'success';
        $csvDesc   = 'All correct data provided';

    } else {
        if (!empty($sizeChk->code)) {
            // Already exists, treat as failed row
            $failedRows[] = [
                "PRODUCT_STYLE_NO" => $data['PRODUCT_STYLE_NO'],
                "COLOR"            => $data['COLOR_MASTER'],
                "SIZE"             => $data['SIZE'],
                "Reason"           => "This Size & SKU Code already exists"
            ];

            $csvStatus = 'failure';
            $csvDesc   = 'This Size & code already exists for this product';

        } else {
            // Update existing
            DB::table('product_color_sizes')
                ->where('id', $sizeChk->id)
                ->update(['code' => $data['SKU_CODE']]);

            $successCount++;
            $csvStatus = 'success';
            $csvDesc   = 'Size exists & SKU code updated';
        }
    }

    /* --------------------------------------
        6. Insert log
    -------------------------------------- */
    DB::table('csv_upload_logs')->insert([
        'type'            => 'bulk product variation upload',
        'product_style_no'=> $data['PRODUCT_STYLE_NO'],
        'sku_code'        => $data['SKU_CODE'],
        'color'           => $data['COLOR_MASTER'],
        'size'            => $data['SIZE'],
        'status'          => $csvStatus,
        'desc'            => $csvDesc,
    ]);

    return [
        "successCount" => $successCount,
    ];
}

 }
