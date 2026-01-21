<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Interfaces\CheckoutInterface;
use App\Interfaces\CartInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CartOffer;
use App\Models\Order;
class CheckoutController extends Controller
{
    public function __construct(CheckoutInterface $checkoutRepository, CartInterface $cartRepository) 
    {
        $this->checkoutRepository = $checkoutRepository;
        $this->cartRepository = $cartRepository;
    }

    public function index(Request $request)
    {
        // $data = $this->cartRepository->viewByIp();
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

        $currentDate = date('Y-m-d');

        $cartOffers = CartOffer::where('status', 1)->whereRaw("date(valid_from) <= '$currentDate' AND date(valid_upto) >= '$currentDate'")->orderBy('min_cart_order', 'desc')->get();

        if (count($data) > 0) {
            $cartData = $this->checkoutRepository->viewCart();

            if (Auth::guard('web')->user()) {
                $addressData = $this->checkoutRepository->addressData();
            } else {
                $addressData = null;
            }

            if ($cartData) {
                return view('front.checkout.index', compact('cartData', 'addressData', 'cartOffers','currentDate','logedInUser'));
            } else {
                return redirect()->route('front.cart.index');
            }
        } else {
            return redirect()->route('front.cart.index');
        }
    }

    public function coupon(Request $request)
    {
        $couponData = $this->checkoutRepository->couponCheck($request->code);
        return $couponData;
    }

      public function store(Request $request)
    {
        //dd($request->all());

        $request->validate([
            'email' => 'required|email|max:255',
            'mobile' => 'required|integer|digits:10',
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
           'billing_country' => 'required_if:addressType,others,normal_user',
           'billing_address' => 'required_if:addressType,others,normal_user',
           'billing_landmark' => 'nullable|string|max:255',
           'billing_city' => 'required_if:addressType,others,normal_user',
           'billing_state' => 'required_if:addressType,others,normal_user',
            'billing_pin' => 'required_if:addressType,others,normal_user',
            'shippingSameAsBilling' => 'nullable|integer|digits:1',
            'shipping_country' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|string|max:255',
            'shipping_landmark' => 'nullable|string|max:255',
            'shipping_city' => 'nullable|string|max:255',
            'shipping_state' => 'nullable|string|max:255',
            'shipping_pin' => 'nullable|integer|digits:6',
            'shipping_method' => 'required|string',
        ], [
            'mobile.*' => 'Please enter valid 10 digit mobile number',
            'billing_pin.*' => 'Please enter valid 6 digit pin',
            'shipping_pin.*' => 'Please enter valid 6 digit pin',
        ]);

        $order_id = $this->checkoutRepository->create($request->except('_token'));
        if ($order_id) {
            // return redirect()->route('front.checkout.complete')->with('success', 'Order No: '.$order_no);
            //return view('front.checkout.complete', compact('order_no'))->with('success', 'Thank you for you order');
            //return redirect('/checkout/payment/'.$order_no)->with('success', 'Please complete your payment');
            //return view('front.checkout.payment', compact('order_no'))->with('success', 'Please complete your payment');
           return redirect()->route('front.checkout.payment',$order_id)->with('success', 'Please complete your payment');
        } else {
            $request->shippingSameAsBilling = 0;
            return redirect()->back()->with('failure', 'Something happened. Try again.')->withInput($request->all());
        }
    }


    public function payment(Request $request,$order_id)
    {
        //dd($order_id);
        if (auth()->guard('web')->check()) {
            $data = Order::where('id',$order_id)->orderby('id','desc')->first();
        } else {
            $data = Order::where('id',$order_id)->orderby('id','desc')->first();
            //dd($data);
            
        }
            if ($data) {
            return view('front.checkout.payment', compact('data'));
            }
       
    }


    public function paymentStore(Request $request)
    {
         //dd($request->all());

        $request->validate([
           
            'shipping_method' => 'nullable',
        
        ]);
		
        $order_no = $this->checkoutRepository->paymentCreate($request->order_id,$request->except('_token'));
       // dd($order_no);
        if ($order_no) {
            // return redirect()->route('front.checkout.complete')->with('success', 'Order No: '.$order_no);
            return view('front.checkout.complete', compact('order_no'))->with('success', 'Thank you for you order');
            //return view('front.checkout.payment', compact('order_no'))->with('success', 'Please complete your payment');
        } else {
            $request->shippingSameAsBilling = 0;
            return redirect()->back()->with('failure', 'Something happened. Try again.')->withInput($request->all());
        }
    }
}