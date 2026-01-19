<?php

namespace App\Http\Controllers\Admin;

use App\Interfaces\ProductInterface;
use App\Models\Product;
use App\Models\Color;
use App\Models\Size;
use App\Models\Collection;
use App\Models\Category;
use App\Models\ProductColorSize;
use App\Models\ProductImage;
use App\Http\Controllers\Controller;
use App\Models\OrderProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use PhpParser\Node\Stmt\Return_;

class ProductController extends Controller
{
    // private ProductInterface $productRepository;

    public function __construct(ProductInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function index(Request $request)
    {
        $catagory = !empty($request->category) ? $request->category : '';
        $range = !empty($request->range) ? $request->range : '';
        $term = !empty($request->term) ? $request->term : '';

        if (!empty($request->term) || !empty($request->category) || !empty($request->range)) {
            $data = $this->productRepository->filteredProducts($catagory, $range, $term);
        } else {
            $data = $this->productRepository->listAll();
        }

        $catagories = Product::select('cat_id')->groupBy('cat_id')->with('category')->get();
        $ranges = Product::select('collection_id')->groupBy('collection_id')->with('collection')->get();

        if ($request->ajax()) {
            $cid = $request->collection_id;
            $cc = Product::where('collection_id', $cid)->groupBy('cat_id')->select('cat_id')->with('category')->get();
            return json_encode($cc);
        }

        return view('admin.product.index', compact('data', 'catagories', 'ranges'));
    }

    public function create(Request $request)
    {
        $categories = $this->productRepository->categoryList();
        $sub_categories = $this->productRepository->subCategoryList();
        $collections = $this->productRepository->collectionList();
        $colors = $this->productRepository->colorList();
        $sizes = $this->productRepository->sizeList();
        return view('admin.product.create', compact('categories', 'sub_categories', 'collections', 'colors', 'sizes'));
    }

    public function store(Request $request)
    {
        // dd($request->all());

        $request->validate([
            "cat_id" => "required|integer",
            "sub_cat_id" => "nullable|integer",
            "collection_id" => "required|integer",
            "name" => "required|string|max:255",
            "short_desc" => "required",
            "desc" => "nullable",
            "price" => "required|integer",
            "offer_price" => "required|integer",
            "meta_title" => "nullable",
            "meta_desc" => "nullable",
            "meta_keyword" => "nullable",
            "style_no" => "required|unique:products",
            "image" => "required|mimes:jpg,jpeg,png,svg,gif|max:10000000",
            "product_images" => "nullable|array",
            "color" => "nullable|array",
            "size" => "nullable|array",
            "pack" => "required|string|max:255",
        ],
        [
            'collection_id.integer'=>'Select a proper Range!',
            'collection_id.required'=>'Select a proper range!',
            'cat_id.required'=>'Select a proper Category!',
            'cat_id.integer'=>'Select a proper Category!',
        ]);

        $params = $request->except('_token');
        $storeData = $this->productRepository->create($params);

        if ($storeData) {
            return redirect()->route('admin.product.edit', $storeData->id)->with('success', 'New Product created, add Product Variation!');
        } else {
            return redirect()->route('admin.product.create')->withInput($request->all());
        }
    }

    public function show(Request $request, $id)
    {
        $data = $this->productRepository->listById($id);
        $images = $this->productRepository->listImagesById($id);
        return view('admin.product.detail', compact('data', 'images'));
    }

    public function size(Request $request)
    {
        $productId = $request->productId;
        $colorId = $request->colorId;

        $data = ProductColorSize::where('product_id', $productId)->where('color', $colorId)->get();

        $resp = [];

        foreach ($data as $dataKey => $dataValue) {
            $resp[] = [
                'variationId' => $dataValue->id,
                'sizeId' => $dataValue->size,
                'sizeName' => $dataValue->sizeDetails->name
            ];
        }

        return response()->json(['error' => false, 'data' => $resp]);
    }

    public function edit(Request $request, $id)
    {
        $categories = $this->productRepository->categoryList();
        $sub_categories = $this->productRepository->subCategoryList();
        $collections = $this->productRepository->collectionList();
        $data = $this->productRepository->listById($id);
        $colors = $this->productRepository->colorListByName();
        $sizes = $this->productRepository->sizeList();
        $images = $this->productRepository->listImagesById($id);

        \DB::statement("SET SQL_MODE=''");
        // $productColorGroup = ProductColorSize::select('id', 'color', 'status')->where('product_id', $id)->groupBy('color')->orderBy('position')->orderBy('id')->get();
        $productColorGroup = ProductColorSize::select('id', 'color', 'status', 'position', 'color_name', 'color_fabric')->where('product_id', $id)->groupBy('color')->orderBy('position')->orderBy('id')->get();

        return view('admin.product.edit', compact('id', 'data', 'categories', 'sub_categories', 'collections', 'images', 'colors', 'sizes', 'productColorGroup'));
    }

    public function update(Request $request)
    {
        // dd($request->all());

        $request->validate([
            "product_id" => "required|integer",
            "cat_id" => "nullable|integer",
            "sub_cat_id" => "nullable|integer",
            "collection_id" => "nullable|integer",
            "name" => "required|string|max:255",
            "short_desc" => "required",
            "desc" => "nullable",
            "price" => "required|integer",
            "offer_price" => "required|integer",
            "meta_title" => "nullable|string",
            "meta_desc" => "nullable|string",
            "meta_keyword" => "nullable|string",
            "style_no" => "required",
            "image" => "nullable",
            "size_chart_image" => "nullable",
            "product_images" => "nullable|array",
            "pack" => "required|string|max:255",
        ]);

        $params = $request->except('_token');
        $storeData = $this->productRepository->update($request->product_id, $params);

        if ($storeData) {
            // return redirect()->route('admin.product.index')->with('success', 'Product updated successfully');
            return redirect()->back()->with('success', 'Product updated successfully');
        } else {
            return redirect()->route('admin.product.update', $request->product_id)->withInput($request->all());
        }
    }

    public function status(Request $request, $id)
    {
        $storeData = $this->productRepository->toggle($id);

        if ($storeData) {
            return redirect()->route('admin.product.index');
        } else {
            return redirect()->route('admin.product.create')->withInput($request->all());
        }
    }

    public function sale(Request $request, $id)
    {
        $storeData = $this->productRepository->sale($id);

        // if ($storeData) {
        return redirect()->route('admin.product.index');
        // } else {
        //     return redirect()->route('admin.product.create')->withInput($request->all());
        // }
    }

    public function trending(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        if ($product->is_trending == 1) {
            $product->is_trending = 0;
        } else {
            $product->is_trending = 1;
        }
        $product->save();

        return redirect()->route('admin.product.index');
    }

    public function destroy(Request $request, $id)
    {
        $this->productRepository->delete($id);

        return redirect()->route('admin.product.index');
    }

    public function destroySingleImage(Request $request, $id)
    {
        $this->productRepository->deleteSingleImage($id);
        return redirect()->back();

        // return redirect()->route('admin.product.index');
    }
    public function bulkDestroy(Request $request)
    {
        // $request->validate([
        //     'bulk_action' => 'required',
        //     'delete_check' => 'required|array',
        // ]);

        // dd($request->all());

        $validator = Validator::make($request->all(), [
            'bulk_action' => 'required',
            'delete_check' => 'required|array',
        ], [
            'delete_check.*' => 'Please select at least one item'
        ]);

        if (!$validator->fails()) {
            if ($request['bulk_action'] == 'delete') {
                foreach ($request->delete_check as $index => $delete_id) {
                    Product::where('id', $delete_id)->delete();
                }

                return redirect()->route('admin.product.index')->with('success', 'Selected items deleted');
            } else {
                return redirect()->route('admin.product.index')->with('failure', 'Please select an action')->withInput($request->all());
            }
        } else {
            return redirect()->route('admin.product.index')->with('failure', $validator->errors()->first())->withInput($request->all());
        }
    }

    public function variationSizeDestroy(Request $request, $id)
    {
        // dd($id);
        ProductColorSize::destroy($id);
        return redirect()->back()->with('success', 'Size deleted successfully');
    }

    public function variationImageDestroy(Request $request)
    {
        // dd($request->all());
        ProductImage::destroy($request->id);
        return response()->json(['status' => 200, 'message' => 'Image deleted successfully']);
        // return redirect()->back();
    }

    public function variationImageUpload(Request $request)
    {
        // dd($request->all());

        $request->validate([
            'product_id' => 'required',
            'color_id' => 'required',
            'image' => 'required|array',
        ]);

        $product_id = $request->product_id;
        $color_id = $request->color_id;

        // dd($request->image);

        foreach ($request->image as $imageKey => $imageValue) {
            // $newName = str_replace(' ', '-', $imageValue->getClientOriginalName());
            $newName = mt_rand() . '_' . time() . '.' . $imageValue->getClientOriginalExtension();
            $imageValue->move('public/uploads/product/product_images/', $newName);

            $productImage = new ProductImage();
            $productImage->product_id = $product_id;
            $productImage->color_id = $color_id;
            $productImage->image = 'public/uploads/product/product_images/' . $newName;
            $productImage->save();
        }

        return redirect()->back()->with('success', 'Images added successfully!');
    }

    public function variationSizeUpload(Request $request)
    {
        // dd($request->all());

        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            'color_id' => 'required',
            'size' => 'required',
            'price' => 'required',
            'offer_price' => 'required',
        ]);

        if (!$validator->fails()) {
            $productImage = new ProductColorSize();
            $productImage->product_id = $request->product_id;
            $productImage->color = $request->color_id;
            $productImage->size = $request->size;
            $productImage->assorted_flag = $request->assorted_flag ? $request->assorted_flag : 0;
            $productImage->price = $request->price;
            $productImage->offer_price = $request->offer_price;
            $productImage->stock = $request->stock ? $request->stock : 0;
            $productImage->code = $request->code ? $request->code : 0;
            $productImage->save();

            // return response()->json(['status' => 200, 'message' => 'Size added successfully']);
            return redirect()->back();
        } else {
            // return response()->json(['status' => 200, 'message' => $validator->errors()->first()]);
            return redirect()->back()->with('failure', $validator->errors()->first())->withInput($request->all());

            // return redirect()->route('admin.product.index')->with('failure', $validator->errors()->first())->withInput($request->all());
        }

        /* $request->validate([
            'product_id' => 'required',
            'color_id' => 'required',
            'size' => 'required',
            'price' => 'required',
            'offer_price' => 'required',
        ]);

        $productImage = new ProductColorSize();
        $productImage->product_id = $request->product_id;
        $productImage->color = $request->color_id;
        $productImage->size = $request->size;
        $productImage->assorted_flag = $request->assorted_flag ? $request->assorted_flag : 0;
        $productImage->price = $request->price;
        $productImage->offer_price = $request->offer_price;
        $productImage->stock = $request->stock ? $request->stock : 0;
        $productImage->code = $request->code ? $request->code : 0;
        $productImage->save();

        // return redirect()->back();
        return response()->json(['status' => 200, 'message' => 'Size added successfully']); */
    }

    public function variationColorDestroy(Request $request, $productId, $colorId)
    {
        // dd($productId, $colorId);
        ProductColorSize::where('product_id', $productId)->where('color', $colorId)->delete();
        return redirect()->back()->with('success', 'Color variation deleted!');
    }

    public function variationColorAdd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            'color' => 'required',
            'size' => 'required',
            'price' => 'required',
            'offer_price' => 'nullable',
            'sku_code' => 'required|unique:product_color_sizes,code',
        ]);

        if (!$validator->fails()) {

            $check = ProductColorSize::where('product_id', $request->product_id)->where('color', $request->color)->where('size', $request->size)->count();

            if ($check == 0) {
                $colorName = Color::select('name')->where('id', $request->color)->first();
                $sizeName = Size::select('name')->where('id', $request->size)->first();

                $productImage = new ProductColorSize();
                $productImage->product_id = $request->product_id;
                $productImage->color = $request->color;
                $productImage->color_name = $colorName->name;
                $productImage->size = $request->size;
                $productImage->size_name = $sizeName->name;
                $productImage->assorted_flag = $request->assorted_flag ? $request->assorted_flag : 0;
                $productImage->price = $request->price ?? 0;
                $productImage->offer_price = $request->offer_price ?? $request->price;
                $productImage->stock = $request->stock ? $request->stock : 0;
                $productImage->code = $request->sku_code ?? '';
                $productImage->save();

                return redirect()->back()->with('success', 'Color added successfully');
            } else {
                return redirect()->back()->with('failure', 'This color & size already exist. Select a different one.')->withInput($request->all());
            }
        } else {
            return redirect()->back()->with('failure', $validator->errors()->first())->withInput($request->all());
        }

        // dd($request->all());

        /* $request->validate([
            'product_id' => 'required',
            'color' => 'required',
            'size' => 'required',
            'price' => 'required',
            'offer_price' => 'required',
        ]);

        $productImage = new ProductColorSize();
        $productImage->product_id = $request->product_id;
        $productImage->color = $request->color;
        $productImage->size = $request->size;
        $productImage->assorted_flag = $request->assorted_flag ? $request->assorted_flag : 0;
        $productImage->price = $request->price;
        $productImage->offer_price = $request->offer_price;
        $productImage->stock = $request->stock ? $request->stock : 0;
        $productImage->code = $request->code ? $request->code : 0;
        $productImage->save();

        return redirect()->back(); */
    }

    public function variationColorRename(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'current_color2' => 'required|integer',
            'update_color_name' => 'required'
        ]);

        // $colorsCHeck = ProductColorSize::select('color')->where('product_id', $request->product_id)->groupBy('color')->pluck('color')->toArray();

        // if(in_array($request->update_color, $colorsCHeck)) {
        //     return redirect()->back()->with('failure', 'Color exists already');
        // }

        // $color = Color::findOrFail($request->update_color);

        ProductColorSize::where('product_id', $request->product_id)->where('color', $request->current_color2)->update(['color_name' => $request->update_color_name]);

        return redirect()->back()->with('success', 'Color name updated');
    }

    public function variationColorEdit(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'product_id' => 'required|integer',
            'current_color' => 'required|integer',
            'update_color' => 'required|integer'
        ]);

        $colorsCHeck = ProductColorSize::select('color')->where('product_id', $request->product_id)->groupBy('color')->pluck('color')->toArray();

        if (in_array($request->update_color, $colorsCHeck)) {
            return redirect()->back()->with('failure', 'Color exists already');
        }

        $color = Color::findOrFail($request->update_color);

        ProductColorSize::where('product_id', $request->product_id)->where('color', $request->current_color)->update(['color' => $request->update_color, 'color_name' => $color->name, 'color_fabric' => null]);
        return redirect()->back()->with('success', 'Color updated');
    }

    public function variationSizeEdit(Request $request)
    {
        // dd($request->all());

        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'size' => 'nullable',
            'size_details' => 'nullable',
            'price' => 'required',
            'code' => 'required',
        ]);

        if (!$validator->fails()) {
            if (empty($request->size)) {
                ProductColorSize::where('id', $request->id)->update([
                    'size_details' => $request->size_details,
                    'price' => $request->price,
                    'offer_price' => $request->price,
                    'code' => $request->code,
                ]);
            } else {
                // check if the size exists already
                $productColorSizeDetail = ProductColorSize::findOrFail($request->id);

                $check = ProductColorSize::where('product_id', $productColorSizeDetail->product_id)->where('color', $productColorSizeDetail->color)->where('size', $productColorSizeDetail->size)->count();

                if ($check == 0) {
                    $sizeName = Size::select('name')->where('id', $request->size)->first();

                    ProductColorSize::where('id', $request->id)->update([
                        'size' => $request->size,
                        'size_name' => $sizeName->name,
                        'size_details' => $request->size_details,
                        'price' => $request->price,
                        'offer_price' => $request->price,
                        'code' => $request->code,
                    ]);
                } else {
                    return redirect()->back()->with('failure', 'This color & size already exist for this product. Select a different one.')->withInput($request->all());
                }
            }

            return redirect()->back()->with('success', 'Size details updated successfully');
        } else {
            return redirect()->back()->with('failure', $validator->errors()->first())->withInput($request->all());
        }
    }

    public function variationColorPosition(Request $request)
    {
        // dd($request->all());
        $position = $request->position;
        $i = 1;
        foreach ($position as $key => $value) {
            $banner = ProductColorSize::findOrFail($value);
            $banner->position = $i;
            $banner->save();
            $i++;
        }
        return response()->json(['status' => 200, 'message' => 'Position updated']);
    }

    public function variationStatusToggle(Request $request)
    {
        // dd($request->all());
        $data = ProductColorSize::where('product_id', $request->productId)->where('color', $request->colorId)->first();

        if ($data) {
            if ($data->status == 1) {
                $status = 0;
                $statusType = 'inactive';
                $statusMessage = 'Color is inactive';
            } else {
                $status = 1;
                $statusType = 'active';
                $statusMessage = 'Color is active';
            }

            $data->status = $status;
            $data->save();
            return response()->json(['status' => 200, 'type' => $statusType, 'message' => $statusMessage]);
        } else {
            return response()->json(['status' => 400, 'message' => 'Something happened']);
        }
    }

    public function variationFabricUpload(Request $request)
    {
        // dd($request->all());

        $save_location = 'public/uploads/color/';
        $data = $request->image;
        $image_array_1 = explode(";", $data);
        $image_array_2 = explode(",", $image_array_1[1]);
        $data = base64_decode($image_array_2[1]);
        $imageName = mt_rand() . '_' . time() . '.png';

        if (file_put_contents($save_location . $imageName, $data)) {
            // $user = Auth::user();
            // $user->image_path = $save_location.$imageName;
            // $user->save();
            // return response()->json(['error' => false, 'message' => 'Image updated', 'image' => asset($save_location.$imageName)]);

            $productVariation = ProductColorSize::where('product_id', $request->product_id)->where('color', $request->color_id)->get();

            foreach ($productVariation as $item) {
                $item->color_fabric = $save_location . $imageName;
                $item->save();
            }

            return response()->json(['error' => false, 'message' => 'Image uploaded', 'image' => asset($save_location . $imageName), 'color_id' => $request->color_id]);
        } else {
            return response()->json(['error' => true, 'message' => 'Something went wrong']);
        }
    }

    public function variationCSVUpload(Request $request)
    {
        if (!empty($request->file)) {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $tempPath = $file->getRealPath();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();

            $valid_extension = array("csv");
            $maxFileSize = 50097152;
            if (in_array(strtolower($extension), $valid_extension)) {
                if ($fileSize <= $maxFileSize) {
                    $location = 'public/uploads/csv';
                    $file->move($location, $filename);
                    // $filepath = public_path($location . "/" . $filename);
                    $filepath = $location . "/" . $filename;

                    // dd($filepath);

                    $file = fopen($filepath, "r");
                    $importData_arr = array();
                    $i = 0;
                    while (($filedata = fgetcsv($file, 10000, ",")) !== FALSE) {
                        $num = count($filedata);
                        // Skip first row
                        if ($i == 0) {
                            $i++;
                            continue;
                        }
                        for ($c = 0; $c < $num; $c++) {
                            $importData_arr[$i][] = $filedata[$c];
                        }
                        $i++;
                    }
                    fclose($file);
                    $successCount = 0;

                    foreach ($importData_arr as $importData) {
                        $insertData = array(
                            "PRODUCT_STYLE_NO" => isset($importData[0]) ? $importData[0] : null,
                            "COLOR_MASTER" => isset($importData[1]) ? $importData[1] : null,
                            "CUSTOM_COLOR_NAME" => isset($importData[2]) ? $importData[2] : null,
                            "SIZE" => isset($importData[3]) ? $importData[3] : null,
                            "PRICE" => isset($importData[4]) ? $importData[4] : null,
                            "OFFER_PRICE" => isset($importData[5]) ? $importData[5] : null,
                            "STOCK" => isset($importData[6]) ? $importData[6] : 1,
                            "SKU_CODE" => isset($importData[7]) ? $importData[7] : null,
                            "COLOR_POSITION" => isset($importData[8]) ? $importData[8] : 1,
                            "STATUS" => isset($importData[9]) ? $importData[9] : 1
                        );

                        $resp = ProductColorSize::insertData($insertData, $successCount);
                        $successCount = $resp['successCount'];
                    }

                    Session::flash('message', 'CSV Import Complete. Total no of entries: ' . count($importData_arr) . '. Successfull: ' . $successCount . ', Failed: ' . (count($importData_arr) - $successCount));
                } else {
                    Session::flash('message', 'File too large. File must be less than 50MB.');
                }
            } else {
                Session::flash('message', 'Invalid File Extension. supported extensions are ' . implode(', ', $valid_extension));
            }
        } else {
            Session::flash('message', 'No file found.');
        }

        return redirect()->back();
    }

    public function variationBulkEdit(Request $request)
    {
        $request->validate([
            "bulkAction" => "required | in:edit",
            "variation_id" => "required | array",
        ]);
        $data = $request->variation_id;

        return view('admin.product.bulk.edit', compact('data', 'request'));
    }

    public function variationBulkUpdate(Request $request)
    {
        // dd($request->all());

        $request->validate([
            "id" => "required|array",
            // "price" => "required|array",
            "offer_price" => "required|array"
        ]);

        // dd('here');

        foreach ($request->id as $key => $value) {
            // $price = $request->price[$key];
            $offer_price = $request->offer_price[$key];

            DB::table('product_color_sizes')
                ->where('id', $value)
                ->update([
                    // 'price' => $price,
                    'offer_price' => $offer_price
                ]);
        }

        return redirect()->route('admin.product.edit', $request->product_id)->with('success', 'Bulk update successfull');
    }

    public function exportAll(Request $request)
    {
        // $data = Product::with('category', 'collection', 'colorSize')->orderBy('collection_id')->get();
        // $data = ProductColorSize::with('colorDetails', 'sizeDetails')->orderBy('collection_id')->get();
        $data = DB::select("SELECT p.name, p.style_no, cls.name AS collection_name, ctr.name AS category_name, c.name AS org_color, pcs.color_name, s.name AS org_size, pcs.size_name, pcs.price, pcs.code FROM product_color_sizes AS pcs INNER JOIN colors AS c ON c.id = pcs.color INNER JOIN sizes AS s ON s.id = pcs.size INNER JOIN products AS p ON p.id = pcs.product_id INNER JOIN collections AS cls ON cls.id = p.collection_id INNER JOIN categories AS ctr ON ctr.id = p.cat_id");

        // dd($data[0]->orderDetails->id);

        if (count($data) > 0) {
            $delimiter = ",";
            $filename = "onninternational-all-products-" . date('Y-m-d') . ".csv";

            // Create a file pointer 
            $f = fopen('php://memory', 'w');

            // Set column headers 
            $fields = array('SR', 'NAME', 'STYLE NUMBER', 'COLLECTION', 'CATEGORY', 'COLOR', 'SIZE', 'PRICE', 'SKU CODE');
            fputcsv($f, $fields, $delimiter);

            $count = 1;

            foreach ($data as $row) {
                $color = $row->color_name ?? $row->org_color;
                $size = $row->size_name ?? $row->org_size;

                $lineData = array(
                    $count,
                    $row->name ?? '',
                    $row->style_no ?? '',
                    $row->collection_name ?? '',
                    $row->category_name ?? '',
                    $color,
                    $size,
                    'Rs. ' . number_format($row->price) ?? '0',
                    $row->code ?? '',
                );

                fputcsv($f, $lineData, $delimiter);

                $count++;
            }

            // Move back to beginning of file
            fseek($f, 0);

            // Set headers to download file rather than displayed
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            //output all remaining data on a file pointer
            fpassthru($f);
        }
    }

    public function productSkuList(Request $request)
    {
        if(!empty($request->term)) {
            $products = ProductColorSize::where('code','like','%'.$request->term.'%')->with('sizeDetails','productDetails','sizeDetails')
            ->where('code', '!=', '')->where('code', '!=', NULL)
            ->orderBy('id', 'asc')
            ->paginate(100);
        } else {
            $products = ProductColorSize::with('sizeDetails','productDetails','sizeDetails')
            ->where('code', '!=', '')->where('code', '!=', NULL)
            // ->latest('last_stock_synched')
            ->orderBy('id', 'asc')
            ->paginate(100); 
        }

        return view('admin.product.product-sku',compact('products'));
    }

    public function productSkuListExport(Request $request)
    {
        $data = DB::select("SELECT pcs.code, pcs.stock, pcs.last_stock_synched, p.name, p.style_no from product_color_sizes as pcs inner join products as p on pcs.product_id = p.id where pcs.code is not null and pcs.code != ''");

        if (count($data) > 0) {
            $delimiter = ",";
            $filename = "onninternational-all-sku-inventory-" . date('Y-m-d-H-i-s') . ".csv";

            // Create a file pointer 
            $f = fopen('php://memory', 'w');

            // Set column headers 
            $fields = array('SR', 'SKU CODE', 'INVENTORY', 'LAST SYNCHED', 'NAME', 'STYLE NUMBER');
            fputcsv($f, $fields, $delimiter);

            $count = 1;

            foreach ($data as $row) {
                $lineData = array(
                    $count,
                    $row->code,
                    $row->stock,
                    $row->last_stock_synched,
                    $row->name,
                    $row->style_no
                );

                fputcsv($f, $lineData, $delimiter);
                $count++;
            }

            // Move back to beginning of file
            fseek($f, 0);

            // Set headers to download file rather than displayed
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            //output all remaining data on a file pointer
            fpassthru($f);
        }
    }

    public function productSkuListSyncAll(Request $request)
    {
        $data = (object)[];
        // DB::enableQueryLog();
        $data->skuCount = ProductColorSize::where('code', '!=', '')->Where('code', '!=', NULL)->count();
        // dd(DB::getQUeryLog());

        return view('admin.product.product-sku-all', compact('data', 'request'));
    }
}
