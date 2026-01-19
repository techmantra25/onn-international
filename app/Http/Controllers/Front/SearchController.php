<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Interfaces\SearchInterface;
use Illuminate\Http\Request;
use App\Models\Product;
// use Illuminate\Http\Response;

class SearchController extends Controller
{
    public function __construct(SearchInterface $searchRepository) 
    {
        $this->searchRepository = $searchRepository;
    }

    public function index(Request $request) 
    {
        $params = $request->except('_token');

        $data = $this->searchRepository->index($params);
		//if (count($data) > 0) {
       //     return view('front.search.index', compact('data', 'request'));
       // } else {
        //    return view('front.404');
       // }
        return view('front.search.index', compact('data', 'request'));
    }
	
	public function suggestion(Request $request) 
    {
		$val = $request->val;
		//$data = Product::where('name', 'like', '%'.$val.'%')
        //->orWhere('slug', 'like', '%'.$val.'%')
        //->orWhere('style_no', 'like', '%'.$val.'%')
        //->orWhere('short_desc', 'like', '%'.$val.'%')
       // ->orWhere('desc', 'like', '%'.$val.'%')->orWhere('status',1)
       // ->get();
		 $data = Product::where('status',1)->where(function($query) use ($val){
            $query->where('name', 'like', '%'.$val.'%')
           ->orWhere('slug', 'like', '%'.$val.'%')
           ->orWhere('style_no', 'like', '%'.$val.'%')
           ->orWhere('short_desc', 'like', '%'.$val.'%')
           ->orWhere('desc', 'like', '%'.$val.'%');
       })->get();
		
		if(count($data) > 0) {
            $resp = [];

            foreach($data as $product) {
                $resp[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'style_no' => $product->style_no,
                    'image' => asset($product->image),
                    'price' => $product->price,
                    'offer_price' => $product->offer_price,
                    'url' => route('front.product.detail', $product->slug),
                ];
            }

			return response()->json([
				'status' => 200,
				'message' => 'Result found',
				'data' => $resp,
			]);
		} else {
			return response()->json([
				'status' => 400,
				'message' => 'Result not found'
			]);
		}
    }
}
