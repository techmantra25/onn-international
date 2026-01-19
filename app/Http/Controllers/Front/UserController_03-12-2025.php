<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Interfaces\UserInterface;
use App\Interfaces\OrderInterface;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Cart;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct(UserInterface $userRepository, OrderInterface $orderRepository)
    {
        $this->userRepository = $userRepository;
        $this->orderRepository = $orderRepository;
    }

    public function login(Request $request)
    {
        $recommendedProducts = $this->userRepository->recommendedProducts();
        return view('front.auth.login', compact('recommendedProducts'));
    }

    public function register(Request $request)
    {
        $recommendedProducts = $this->userRepository->recommendedProducts();
        return view('front.auth.register', compact('recommendedProducts'));
    }
    
    public function buyonegetoneRegister(Request $request)
    {
       
        $recommendedProducts = $this->userRepository->recommendedProducts();
        return view('front.auth.special-register', compact('recommendedProducts'));
    }
    
    public function register1(Request $request)
    {
        $recommendedProducts = $this->userRepository->recommendedProducts();
        return view('front.auth.register1', compact('recommendedProducts'));
    }

    public function create(Request $request)
    {
        $request->validate([
            'fname' => 'required|string',
            'lname' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'mobile' => 'required|integer|digits:10|unique:users,mobile',
            'password' => 'required|string|min:8|max:100',
        ]);

        $storeData = $this->userRepository->create($request->except('_token'));

        if ($storeData) {
            // $credentials = $request->only('email', 'password');

            // if (Auth::attempt($credentials)) {
            //     // return redirect()->intended('home');
            //     return redirect()->url('home');
            // }

            return redirect()->route('front.user.login')->with('success', 'Account created successfully');
        } else {
            return redirect()->route('front.user.register')->withInput($request->all())->with('failure', 'Something happened');
        }
    }

    public function check(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:2|max:100',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {

            // update cart
            if (!empty($_COOKIE['cartToken'])) {
                // dd($_COOKIE['cartToken']);
                Cart::where('guest_token', $_COOKIE['cartToken'])->update([
                    'user_id' => Auth::guard('web')->user()->id
                ]);

                // removing cookie
                setcookie('cartToken', NULL, 1);
                // setcookie('cartToken', time() - 3600);
            }

            return redirect()->intended('home');
            // return redirect()->url('home');
        } else {
            return redirect()->route('front.user.login')->withInput($request->all())->with('failure', 'Please enter valid credentials');
        }
    }

	public function forgotPassword(Request $request)
    {
        return view('auth.passwords.email');
    }

    public function order(Request $request)
    {
        $data = $this->userRepository->orderDetails();
        return view('front.profile.order', compact('data'));
    }

    public function coupon(Request $request)
    {
        $data = $this->userRepository->couponList();
        return view('front.profile.coupon', compact('data'));
    }

    public function address(Request $request)
    {
        $data = $this->userRepository->addressById(Auth::guard('web')->user()->id);
        if ($data) {
            return view('front.profile.address', compact('data'));
        } else {
            return view('front.404');
        }
    }

    public function addressCreate(Request $request)
    {
        $request->validate([
            "user_id" => "required|integer",
            "address" => "required|string|max:255",
            "landmark" => "required|string|max:255",
            "lat" => "nullable",
            "lng" => "nullable",
            "type" => "required|integer",
            "state" => "required|string",
            "city" => "required|string",
            "country" => "required|string",
            "pin" => "required|integer|digits:6",
            "type" => "required|integer",
        //], [
          //  "lat.*" => "Please enter Location",
          //  "lng.*" => "Please enter Location"
        ]);

        $params = $request->except('_token');
        $storeData = $this->userRepository->addressCreate($params);

        if ($storeData) {
            return redirect()->route('front.user.address');
        } else {
            return redirect()->route('front.user.address.add')->withInput($request->all());
        }
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            "fname" => "required|string|max:255",
            "lname" => "required|string|max:255",
            "mobile" => "required|integer|digits:10",
        ], [
            "mobile.*" => "Please enter a valid 10 digit mobile number"
        ]);

        $params = $request->except('_token');
        $storeData = $this->userRepository->updateProfile($params);

        if ($storeData) {
            return redirect()->route('front.user.manage')->with('success', 'Profile updated successfully');
        } else {
            return redirect()->route('front.user.manage')->withInput($request->all())->with('failure', 'Something happened. Try again');
        }
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            "old_password" => "required|string|max:255",
            "new_password" => "required|string|max:255|same:confirm_password",
            "confirm_password" => "required|string|max:255",
        ]);

        $params = $request->except('_token');
        $storeData = $this->userRepository->updatePassword($params);

        if ($storeData) {
            return redirect()->route('front.user.manage')->with('success', 'Password updated successfully');
        } else {
            return redirect()->route('front.user.manage')->withInput($request->all())->with('failure', 'Something happened');
        }
    }

    public function wishlist(Request $request)
    {
        $data = $this->userRepository->wishlist();
        if ($data) {
            return view('front.profile.wishlist', compact('data'));
        } else {
            return view('front.404');
        }
    }

    public function invoice(Request $request, $id)
    {
        $data = $this->orderRepository->listById($id);
        return view('front.profile.invoice', compact('data'));
    }

    public function orderCancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "orderId" => "required | integer",
            "cancellationReason" => "required | string"
        ]);

        if (!$validator->fails()) {
            $order = Order::findOrFail($request->orderId);
            $order->status = 5;
            $order->orderCancelledBy = auth()->guard('web')->user()->id;
            $order->orderCancelledReason = $request->cancellationReason;
            $order->save();

            // send cancellation email 1
            // fetching ordered products
            $orderedProducts = OrderProduct::findOrFail($order->id);

            $email_data = [
                'name' => auth()->guard('web')->user()->fname.' '.auth()->guard('web')->user()->lname,
                'subject' => 'Onn - Order update for #'.$order->order_no,
                'email' => auth()->guard('web')->user()->email,
                'orderId' => $order->id,
                'orderNo' => $order->order_no,
                'orderAmount' => $order->final_amount,
                'status' => $order->status,
                'statusTitle' => 'Cancelled',
                'statusDesc' => 'Your order is cancelled',
                'orderProducts' => $orderedProducts,
                'blade_file' => 'front/mail/order-update',
            ];

            SendMail($email_data);

            // send cancellation email 2
            $email_data2 = [
                'name' => 'ONN ADMIN',
                'subject' => 'ONN - Order cancel for #'.$order->order_no,
                'email' => 'ecom.cozyworld@luxinnerwear.com',
                'orderId' => $order->id,
                'orderNo' => $order->order_no,
                'orderAmount' => $order->final_amount,
                'status' => $order->status,
                'statusTitle' => 'Cancelled',
                'statusDesc' => 'This order is cancelled',
                'orderProducts' => $orderedProducts,
                'blade_file' => 'front/mail/order-cancel-admin',
            ];

            SendMail($email_data2);

            return redirect()->back()->with('success', 'You have cancelled your order');
        } else {
            return redirect()->back()->with('failure', $validator->errors()->first());
        }
    }

    public function orderReturn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "orderProductId" => "required | integer",
            "returnReasonType" => "required | string",
            "returnReasonComment" => "required | string"
        ], [
            "returnReasonType.*" => "Please select Return reason",
            "returnReasonComment.*" => "Please enter Return comment",
        ]);

        if (!$validator->fails()) {
            $orderProduct = OrderProduct::findOrFail($request->orderProductId);
            $orderProduct->status = 6;
            $orderProduct->return_reason_type = $request->returnReasonType;
            $orderProduct->return_reason_comment = $request->returnReasonComment;
            $orderProduct->save();

            // $order->orderCancelledBy = auth()->guard('web')->user()->id;
            // $order->orderCancelledReason = $request->cancellationReason;

            return redirect()->back()->with('success', 'You have requested return for this product');
        } else {
            return redirect()->back()->with('failure', $validator->errors()->first());
        }
    }
}
