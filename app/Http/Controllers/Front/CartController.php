<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Interfaces\CartInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Cart;
use App\Models\CartOffer;

class CartController extends Controller
{
    public function __construct(CartInterface $cartRepository) 
    {
        $this->cartRepository = $cartRepository;
    }

    public function couponCheck(Request $request)
    {
        $couponData = $this->cartRepository->couponCheck($request->code);
        return $couponData;
    }

    public function couponRemove(Request $request)
    {
        $couponData = $this->cartRepository->couponRemove();
        return $couponData;
    }

    public function add(Request $request) 
    {
        // dd($request->all());

        $request->validate([
            "product_id" => "required|string|max:255",
            "product_name" => "required|string|max:255",
            "product_style_no" => "required|string|max:255",
            "product_image" => "required|string|max:255",
            "product_slug" => "required|string|max:255",
            "product_variation_id" => "nullable|integer",
            "price" => "required|string",
            "offer_price" => "required|string",
            "qty" => "required|integer|min:1",
            "user_id" => "nullable",
            "token" => "nullable"
        ]);

        $params = $request->except('_token');

        $cartStore = $this->cartRepository->addToCart($params);

        if ($cartStore) {
            return response()->json(['status' => 200, 'message' => 'Product added to cart', 'response' => $cartStore]);
            // return redirect()->back()->with('success', 'Product added to cart');
        } else {
            return response()->json(['status' => 400, 'message' => 'Product cannot be added to cart']);
            // return redirect()->back()->with('failure', 'Something happened');
        }
    }

    public function index(Request $request)
    {
        $isEligibleForGOBO = checkGoBo();
        // cart offers
        $currentDate = date('Y-m-d');
        $cartOffers = CartOffer::where('status', 1)->whereRaw("date(valid_from) <= '$currentDate' AND date(valid_upto) >= '$currentDate'")->orderBy('min_cart_order', 'desc')->get();
        $logedInUser = auth()->guard('web')->user();
        if (auth()->guard('web')->check()) {
            $data = $this->cartRepository->viewByUserId(auth()->guard('web')->user()->id);
        } else {
            if (!empty($_COOKIE['cartToken'])) {
                $data = $this->cartRepository->viewBytoken($_COOKIE['cartToken']);
            } else {
                $data = [];
            }
        }
        // if ($data) {
            return view('front.cart.index', compact('data', 'cartOffers','logedInUser','isEligibleForGOBO'));
        // } else {
            // return view('front.404');
        // }
    }

    /*
    public function viewByIp(Request $request)
    {
        $data = $this->cartRepository->viewByIp();

        $currentDate = date('Y-m-d');
        $cartOffers = CartOffer::where('status', 1)->whereRaw("date(valid_from) <= '$currentDate' AND date(valid_upto) >= '$currentDate'")->orderBy('min_cart_order', 'desc')->get();

        if ($data) {
            return view('front.cart.index', compact('data', 'cartOffers'));
        } else {
            return view('front.404');
        }
    }
    */

    public function delete(Request $request, $id)
    {
        $data = $this->cartRepository->delete($id);

        if ($data) {
            return redirect()->route('front.cart.index')->with('success', 'Product removed from cart');
        } else {
            return redirect()->route('front.cart.index')->with('failure', 'Something happened');
        }
    }

    public function qtyUpdate(Request $request, $id, $type)
    {
        $data = $this->cartRepository->qtyUpdate($id, $type);

        if ($data) {
            return redirect()->route('front.cart.index')->with('success', $data);
        } else {
            return redirect()->route('front.cart.index')->with('failure', 'Something happened');
        }
    }
}
