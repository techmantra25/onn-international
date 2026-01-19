<?php

namespace App\Repositories;

use App\Interfaces\CheckoutInterface;
use App\Models\Cart;
use App\User;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\Settings;
use App\Models\Collection;
use App\Models\Transaction;
use App\Models\CouponUsage;
use App\Models\ProductColorSize;
use App\Models\ThirdPartyPayload;
use App\Models\CartOffer;
use App\Models\OrderOffer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class CheckoutRepository implements CheckoutInterface
{
    public function __construct() {
        $this->ip = $_SERVER['REMOTE_ADDR'];
    }

    public function viewCart()
    {
        if (Auth::guard()->check()) {
            $data = Cart::where('user_id', Auth::guard()->user()->id)->get();
        } else {
            if (!empty($_COOKIE['cartToken'])) {
                $data = Cart::where('guest_token', $_COOKIE['cartToken'])->get();
            } else {
                $data = [];
            }
        }
        
        // $data = Cart::where('ip', $this->ip)->get();

        // coupon check
        if (!empty($data[0]->coupon_code_id)) {
            $coupon_code_id = $data[0]->coupon_code_id;
            $coupon_code_end_date = $data[0]->couponDetails->end_date;
            $coupon_code_status = $data[0]->couponDetails->status;
            $coupon_code_max_usage_for_one = $data[0]->couponDetails->max_time_one_can_use;

            // coupon code validity check
            if ($coupon_code_end_date < \Carbon\Carbon::now() || $coupon_code_status == 0) {
                Cart::where('ip', $this->ip)->update(['coupon_code_id' => null]);
            }

            // coupon code usage check
            if (Auth::guard('web')->user()) {
                // $couponUsageCount = CouponUsage::where('user_id', Auth::guard('web')->user()->id)
                // ->orWhere('email', Auth::guard('web')->user()->email)
                // ->count();

                $couponUsageCount = CouponUsage::where('coupon_code_id', $coupon_code_id)
                ->where('user_id', Auth::guard('web')->user()->id)
                // ->orWhere('email', Auth::guard('web')->user()->email)
                ->count();
            } else {
                $couponUsageCount = CouponUsage::where('coupon_code_id', $coupon_code_id)->where('ip', $this->ip)->count();
                // $couponUsageCount = CouponUsage::where('ip', $this->ip)->count();
            }

            // dd($couponUsageCount);

            if ($couponUsageCount == $coupon_code_max_usage_for_one || $couponUsageCount > $coupon_code_max_usage_for_one) {
                Cart::where('ip', $this->ip)->update(['coupon_code_id' => null]);
            }
        }

        return $data;
    }

    public function addressData()
    {
        return Address::where('user_id', Auth::guard('web')->user()->id)->get();
    }

    public function create(array $data)
    {
        $collectedData = collect($data);

        DB::beginTransaction();

        try {
            $settings = Settings::all();

            // shipping charge fetch
            $shippingChargeJSON = json_decode($settings[22]->content);
            $minOrderAmount = $shippingChargeJSON->min_order??'';
            $shippingCharge = $shippingChargeJSON->shipping_charge??'';
			$empshippingChargeJSON = json_decode($settings[24]->content);
            $minEmpOrderAmount = $empshippingChargeJSON->min_order??'';
            $empshippingCharge = $empshippingChargeJSON->shipping_charge??'';
            // 1 order sequence
            $OrderChk = Order::select('order_sequence_int')->latest('id')->first();
            if($OrderChk->order_sequence_int == 0) $orderSeq = 1;
            else $orderSeq = (int) $OrderChk->order_sequence_int + 1;

            $ordNo = sprintf("%'.05d", $orderSeq);
			$nextYear = 24; 
            $curYear = date('y');
            $order_no = "OIS".$curYear . (($curYear != $nextYear) ? '-' . $nextYear : '').'/'.$ordNo;
           // $order_no = "OIS".date('y').'/'.$ordNo;

            // 2 order place
            $newEntry = new Order;
            $newEntry->order_sequence_int = $orderSeq;
            $newEntry->order_no = $order_no;
            $newEntry->user_id = Auth::guard('web')->user()->id ?? 0;
            $newEntry->ip = $this->ip;
            $newEntry->email = $collectedData['email'];
            $newEntry->mobile = $collectedData['mobile'];
            $newEntry->fname = $collectedData['fname'];
            $newEntry->lname = $collectedData['lname'];
            /*$newEntry->billing_country = $collectedData['billing_country'];
            $newEntry->billing_address = $collectedData['billing_address'];
            $newEntry->billing_landmark = $collectedData['billing_landmark'];
            $newEntry->billing_city = $collectedData['billing_city'];
            $newEntry->billing_state = $collectedData['billing_state'];
            $newEntry->billing_pin = $collectedData['billing_pin'];

            // shipping & billing address check
            $shippingSameAsBilling = $collectedData['shippingSameAsBilling'] ?? 0;
            $newEntry->shippingSameAsBilling = $shippingSameAsBilling;

            // dd($shippingSameAsBilling);

            if ($shippingSameAsBilling == 0) {
                $newEntry->shipping_country = $collectedData['shipping_country'];
                $newEntry->shipping_address = $collectedData['shipping_address'];
                $newEntry->shipping_landmark = $collectedData['shipping_landmark'];
                $newEntry->shipping_city = $collectedData['shipping_city'];
                $newEntry->shipping_state = $collectedData['shipping_state'];
                $newEntry->shipping_pin = $collectedData['shipping_pin'];
            } else {
                $newEntry->shipping_country = $collectedData['billing_country'];
                $newEntry->shipping_address = $collectedData['billing_address'];
                $newEntry->shipping_landmark = $collectedData['billing_landmark'];
                $newEntry->shipping_city = $collectedData['billing_city'];
                $newEntry->shipping_state = $collectedData['billing_state'];
                $newEntry->shipping_pin = $collectedData['billing_pin'];
            }*/
			if(!empty($collectedData['addressType'])){
                if($collectedData['addressType']=='ho')
                {
                    
                    $newEntry->billing_country = 'India';
                    $newEntry->billing_address = 'Adventz Infinity@5, BN Block, Sector V,Bidhannagar';
                    $newEntry->billing_landmark = '';
                    $newEntry->billing_city = 'Kolkata';
                    $newEntry->billing_state = 'West Bengal';
                    $newEntry->billing_pin = '700091';
					$newEntry->address_type = 'ho';
                    // shipping & billing address check
                    $shippingSameAsBilling = $collectedData['shippingSameAsBilling'] ?? 0;
                    $newEntry->shippingSameAsBilling = $shippingSameAsBilling;

                    // dd($shippingSameAsBilling);

                    if ($shippingSameAsBilling == 0) {
                        $newEntry->shipping_country = 'India';
                        $newEntry->shipping_address = 'Adventz Infinity@5, BN Block, Sector V,Bidhannagar';
                        $newEntry->shipping_landmark = '';
                        $newEntry->shipping_city = 'Kolkata';
                        $newEntry->shipping_state = 'West Bengal';
                        $newEntry->shipping_pin = '700091';
                    } else {
                        $newEntry->shipping_country = 'India';
                        $newEntry->shipping_address = 'Adventz Infinity@5, BN Block, Sector V,Bidhannagar';
                        $newEntry->shipping_landmark = '';
                        $newEntry->shipping_city = 'Kolkata';
                        $newEntry->shipping_state = 'West Bengal';
                        $newEntry->shipping_pin = '700091';
                    }
                }
                else if($collectedData['addressType']=='dankuni')
                {
                    
                    $newEntry->billing_country = 'India';
                    $newEntry->billing_address = 'JL22, Mollarber, Janai Main Road';
                    $newEntry->billing_landmark = '';
                    $newEntry->billing_city = 'Dankuni';
                    $newEntry->billing_state =  'West Bengal';
                    $newEntry->billing_pin = '712310';
					$newEntry->address_type = 'dankuni';
                    // shipping & billing address check
                    $shippingSameAsBilling = $collectedData['shippingSameAsBilling'] ?? 0;
                    $newEntry->shippingSameAsBilling = $shippingSameAsBilling;

                    // dd($shippingSameAsBilling);

                    if ($shippingSameAsBilling == 0) {
                        $newEntry->shipping_country = 'India';
                        $newEntry->shipping_address = 'JL22, Mollarber, Janai Main Road';
                        $newEntry->shipping_landmark = '';
                        $newEntry->shipping_city = 'Dankuni';
                        $newEntry->shipping_state =  'West Bengal';
                        $newEntry->shipping_pin = '712310';
                    } else {
                        $newEntry->shipping_country = 'India';
                        $newEntry->shipping_address = 'JL22, Mollarber, Janai Main Road';
                        $newEntry->shipping_landmark = '';
                        $newEntry->shipping_city = 'Dankuni';
                        $newEntry->shipping_state =  'West Bengal';
                        $newEntry->shipping_pin = '712310';
                    }
                }else{
                    
                    $newEntry->billing_country = $collectedData['billing_country'];
                    $newEntry->billing_address = $collectedData['billing_address'];
                    $newEntry->billing_landmark = $collectedData['billing_landmark'];
                    $newEntry->billing_city = $collectedData['billing_city'];
                    $newEntry->billing_state = $collectedData['billing_state'];
                    $newEntry->billing_pin = $collectedData['billing_pin'];
					$newEntry->address_type = 'other';
                    // shipping & billing address check
                    $shippingSameAsBilling = $collectedData['shippingSameAsBilling'] ?? 0;
                    $newEntry->shippingSameAsBilling = $shippingSameAsBilling;

                    // dd($shippingSameAsBilling);

                    if ($shippingSameAsBilling == 0) {
                        $newEntry->shipping_country = $collectedData['shipping_country'];
                        $newEntry->shipping_address = $collectedData['shipping_address'];
                        $newEntry->shipping_landmark = $collectedData['shipping_landmark'];
                        $newEntry->shipping_city = $collectedData['shipping_city'];
                        $newEntry->shipping_state = $collectedData['shipping_state'];
                        $newEntry->shipping_pin = $collectedData['shipping_pin'];
                    } else {
                        $newEntry->shipping_country = $collectedData['billing_country'];
                        $newEntry->shipping_address = $collectedData['billing_address'];
                        $newEntry->shipping_landmark = $collectedData['billing_landmark'];
                        $newEntry->shipping_city = $collectedData['billing_city'];
                        $newEntry->shipping_state = $collectedData['billing_state'];
                        $newEntry->shipping_pin = $collectedData['billing_pin'];
                    }
                }
            }else{
                $newEntry->billing_country = $collectedData['billing_country'];
                $newEntry->billing_address = $collectedData['billing_address'];
                $newEntry->billing_landmark = $collectedData['billing_landmark'];
                $newEntry->billing_city = $collectedData['billing_city'] ?? '';
                $newEntry->billing_state = $collectedData['billing_state'];
                $newEntry->billing_pin = $collectedData['billing_pin'];
				$newEntry->address_type = 'other';
                // shipping & billing address check
                $shippingSameAsBilling = $collectedData['shippingSameAsBilling'] ?? 0;
                $newEntry->shippingSameAsBilling = $shippingSameAsBilling;

                // dd($shippingSameAsBilling);

                if ($shippingSameAsBilling == 0) {
                    $newEntry->shipping_country = $collectedData['shipping_country'];
                    $newEntry->shipping_address = $collectedData['shipping_address'];
                    $newEntry->shipping_landmark = $collectedData['shipping_landmark'];
                    $newEntry->shipping_city = $collectedData['shipping_city'];
                    $newEntry->shipping_state = $collectedData['shipping_state'];
                    $newEntry->shipping_pin = $collectedData['shipping_pin'];
                } else {
                    $newEntry->shipping_country = $collectedData['billing_country'];
                    $newEntry->shipping_address = $collectedData['billing_address'];
                    $newEntry->shipping_landmark = $collectedData['billing_landmark'];
                    $newEntry->shipping_city = $collectedData['billing_city'];
                    $newEntry->shipping_state = $collectedData['billing_state'];
                    $newEntry->shipping_pin = $collectedData['billing_pin'];
                }
            }

            $newEntry->shipping_method = $collectedData['shipping_method'];

            
            // fetch cart details
            // $cartData = Cart::where('ip', $this->ip)->get();
            if (Auth::guard()->check()) {
                $cartData = Cart::where('user_id', Auth::guard()->user()->id)->get();
            } else {
                if (!empty($_COOKIE['cartToken'])) {
                    $cartData = Cart::where('guest_token', $_COOKIE['cartToken'])->get();
                } else {
                    $cartData = [];
                    return false;
                }
            }

            $subtotal = 0;
            
            foreach($cartData as $cartValue) {
                $subtotal += $cartValue->offer_price * $cartValue->qty;
            }
            $coupon_code_id = $cartData[0]->coupon_code_id ?? 0;
            $newEntry->coupon_code_id = $coupon_code_id;
            $newEntry->amount = $subtotal;

            $total = (int) $subtotal;

            $newEntry->tax_amount = 0;
            $shippingCharges = 0;

            // if coupon found
            if (!empty($coupon_code_id) || $coupon_code_id != 0) {
                // check for voucher/ coupon
                if ($cartData[0]->couponDetails->is_coupon == 0) {
                    $newEntry->coupon_code_type = 'voucher';
                    
                    if($cartData[0]->couponDetails->type == 1){
                        $newEntry->discount_amount = $cartData[0]->couponDetails->amount;

                        $couponCodeDiscount = (int) ($total * ($cartData[0]->couponDetails->amount / 100));

                        $newEntry->coupon_code_discount_type = 'Percentage';
                        $final_amount = ceil($total - $couponCodeDiscount);

                        // shipping charges
                        if ((int) $minOrderAmount >= (int) $final_amount ) {
                            $shippingCharges = $shippingCharge;
                            $final_amount = $final_amount + $shippingCharges;
                        }
                        $newEntry->shipping_charges = $shippingCharges;

                        $newEntry->final_amount = $final_amount;
                    }else{
                        $newEntry->discount_amount = $cartData[0]->couponDetails->amount;

                        $couponCodeDiscount = $cartData[0]->couponDetails->amount;

                        $newEntry->coupon_code_discount_type = 'Flat';
                        $final_amount = ceil($total - $couponCodeDiscount) > 0  ? ceil($total - $couponCodeDiscount) : 0;

                        // shipping charges
                        if ((int) $minOrderAmount >= (int) $final_amount ) {
                            $shippingCharges = $shippingCharge;
                            $final_amount = $final_amount + $shippingCharges;
                        }
                        $newEntry->shipping_charges = $shippingCharges;

                        $newEntry->final_amount = $final_amount;
                    }

                    $newEntry->save();

                    // dd($newEntry);
                } else {
                    $newEntry->coupon_code_type = 'coupon';
                    if($cartData[0]->couponDetails->type == 1){
						$couponCode = substr($cartData[0]->couponDetails->coupon_code, 0, 3);
                        $firstCode=$couponCode;
                        $newEntry->discount_amount = $cartData[0]->couponDetails->amount;

                        $couponCodeDiscount = (int) ($total * ($cartData[0]->couponDetails->amount / 100));

                        $newEntry->coupon_code_discount_type = 'Percentage';
                        $final_amount = ceil($total - $couponCodeDiscount);

                        // shipping charges
						if ($firstCode=='EMP'){
                            //if ((int) $minEmpOrderAmount >= (int) $final_amount ) {
                            //$shippingCharges = $empshippingCharge;
                            //$final_amount = $final_amount + $shippingCharges;
                           //}
                           if($collectedData['addressType'] =='ho' || $collectedData['addressType'] =='dankuni'){
                                $shippingCharges = 0;
                                $final_amount = $final_amount + $shippingCharges;
                            }else{
                                if ((int) $final_amount > 2000 ) {
                                    $shippingCharges = 200;
                                    $final_amount = $final_amount + $shippingCharges;
                                }else{
                                    $shippingCharges = 100;
                                    $final_amount = $final_amount + $shippingCharges;
                                }
                            }
                        
                        }else{
							if ((int) $minOrderAmount >= (int) $final_amount ) {
								$shippingCharges = $shippingCharge;
								$final_amount = $final_amount + $shippingCharges;
							}
						}
                        $newEntry->shipping_charges = $shippingCharges;

                        $newEntry->final_amount = $final_amount;
                    }else{
						$couponCode = substr($cartData[0]->couponDetails->coupon_code, 0, 3);
                        $firstCode=$couponCode;
                        $newEntry->discount_amount = $cartData[0]->couponDetails->amount;

                        $couponCodeDiscount = $cartData[0]->couponDetails->amount;

                        $newEntry->coupon_code_discount_type = 'Flat';
                        $final_amount = ceil($total - $couponCodeDiscount) > 0  ? ceil($total - $couponCodeDiscount) : 0;

                        // shipping charges
						if ($firstCode=='EMP'){
                            //if ((int) $minEmpOrderAmount >= (int) $final_amount ) {
                             //   $shippingCharges = $empshippingCharge;
                             //   $final_amount = $final_amount + $shippingCharges;
                            //}
                            if($collectedData['addressType'] =='ho' || $collectedData['addressType'] =='dankuni'){
                                $shippingCharges = 0;
                                $final_amount = $final_amount + $shippingCharges;
                            }else{
                                if ((int) $final_amount > 2000 ) {
                                    $shippingCharges = 200;
                                    $final_amount = $final_amount + $shippingCharges;
                                }else{
                                    $shippingCharges = 100;
                                    $final_amount = $final_amount + $shippingCharges;
                                }
                            }
                        
                        }else{
							if ((int) $minOrderAmount >= (int) $final_amount ) {
								$shippingCharges = $shippingCharge;
								$final_amount = $final_amount + $shippingCharges;
							}
						}
                        $newEntry->shipping_charges = $shippingCharges;

                        $newEntry->final_amount = $final_amount;
                    }
                    $newEntry->save();
                }
            } else {
                $buy_one_get_one_result = $this->getGetOneByOneDiscountAmount($cartData);
                $buy_one_get_one_discount_amount = $buy_one_get_one_result['discount_amount'];
                // shipping charges
                if ((int) $minOrderAmount >= (int) $total ) {
                    $shippingCharges = $shippingCharge;
                    $total = $total + $shippingCharges;
                }

                $newEntry->shipping_charges = $shippingCharges;

                $newEntry->coupon_code_type = '';
                $newEntry->discount_amount = $buy_one_get_one_discount_amount;
                $newEntry->coupon_code_type = $buy_one_get_one_discount_amount>0?"buy_one_get_one":$newEntry->coupon_code_type;
                $newEntry->coupon_code_discount_type = $buy_one_get_one_discount_amount>0?"Flat":"";
				$newEntry->final_amount = $total-$buy_one_get_one_discount_amount;
				$newEntry->save();

                // IF NO COUPON CODE, CHECK FOR OFFERS
                // 1 all offers
                $currentDate = date('Y-m-d');
                $cartOffers = CartOffer::where('status', 1)->whereRaw("date(valid_from) <= '$currentDate' AND date(valid_upto) >= '$currentDate'")->orderBy('min_cart_order', 'desc')->get();
                
                $finalOrderAmount = $newEntry->final_amount;
                $elligibleOfferCount = 1;

                // 2 check if offer exists
                if(count($cartOffers) > 0) {
                    // 3 looping through all offers
                    foreach($cartOffers as $offerKey => $offer) {
                        // if offer exists for the order
                        if($offer->min_cart_order < $finalOrderAmount) {
                            // in case of multiple elligible offer, use highest one
                            if ($elligibleOfferCount == 1) {
                                // checking multiplier - when double the order amount of offer amount, customer receives multiplier offer
                                $receiveQty = $offer->offer_product_qty;
                                if ($offer->min_order_multiplier == 1) {
                                    $receiveQty = (int) ($finalOrderAmount / $offer->min_cart_order) * (int) $offer->offer_product_qty;
                                }

                                // this offer is applicable for this order
                                $newOrderOffer = new OrderOffer();
                                $newOrderOffer->order_id = $newEntry->id;
                                $newOrderOffer->offer_image = $offer->offer_image;
                                $newOrderOffer->offer_name = $offer->offer_name;
                                $newOrderOffer->min_cart_order = $offer->min_cart_order;
                                $newOrderOffer->max_cart_order = $offer->max_cart_order;
                                $newOrderOffer->min_order_multiplier = $offer->min_order_multiplier;
                                $newOrderOffer->valid_from = $offer->valid_from;
                                $newOrderOffer->valid_upto = $offer->valid_upto;
                                $newOrderOffer->offer_product_name = $offer->offer_product_name;
                                $newOrderOffer->offer_product_qty = $offer->offer_product_qty;
                                $newOrderOffer->total_order_amount = $finalOrderAmount;
                                $newOrderOffer->customer_receive_offer = 1;
                                $newOrderOffer->customer_receive_product_name = $offer->offer_product_name;
                                $newOrderOffer->customer_receive_product_qty = $receiveQty;
                                $newOrderOffer->save();
                            }
                            $elligibleOfferCount++;
                        }
                    }
                }
            }

            // coupon code usage handler
            if (!empty($coupon_code_id) || $coupon_code_id != 0) {
                $newEntry->discount_amount = $cartData[0]->couponDetails->amount;
                $newEntry->final_amount = $total - (int) $cartData[0]->couponDetails->amount;

                // update coupon code usage
                $couponDetails = Coupon::findOrFail($coupon_code_id);
                $old_no_of_usage = $couponDetails->no_of_usage;
                $new_no_of_usage = $old_no_of_usage + 1;
                $couponDetails->no_of_usage = $new_no_of_usage;
                if ($new_no_of_usage == $couponDetails->max_time_of_use) $couponDetails->status = 0;
                $couponDetails->save();

                $newCouponUsageEntry = new CouponUsage();
                $newCouponUsageEntry->coupon_code_id = $coupon_code_id;
                $newCouponUsageEntry->coupon_code = $couponDetails->coupon_code;
                $newCouponUsageEntry->discount = $cartData[0]->couponDetails->amount;
                $newCouponUsageEntry->total_checkout_amount = $total;
                $newCouponUsageEntry->final_amount = $total - (int) $cartData[0]->couponDetails->amount;
                $newCouponUsageEntry->user_id = Auth::guard('web')->user()->id ?? 0;
                $newCouponUsageEntry->email = $collectedData['email'];
                $newCouponUsageEntry->ip = $this->ip;
                $newCouponUsageEntry->order_id = $newEntry->id;
                $newCouponUsageEntry->usage_time = date('Y-m-d H:i:s');
                $newCouponUsageEntry->save();
            }

            // store address
            // if user is logged in
            if (Auth::guard('web')->check()) {
                // check if address exists
                $addrChk = Address::where('user_id', Auth::guard('web')->user()->id)
                ->where('pin', $newEntry->billing_pin)
                ->where('city', $newEntry->billing_city)
                ->where('state', $newEntry->billing_state)
                ->where('country', $newEntry->billing_country)
                ->where('landmark', $newEntry->billing_landmark)
                ->count();

                if ($addrChk == 0) {
                    $address = new Address();
                    $address->user_id = Auth::guard('web')->user()->id;
                    $address->address = $newEntry->billing_address;
                    $address->landmark = $newEntry->billing_landmark;
                    $address->lat = "";
                    $address->lng = "";
                    $address->state = $newEntry->billing_state;
                    $address->city = $newEntry->billing_city;
                    $address->pin = $newEntry->billing_pin;
                    $address->country = $newEntry->billing_country;
                    $address->type = 3;
                    $address->billing = 1;
                    $address->save();

                    if ($shippingSameAsBilling == 0) {
                        $address = new Address();
                        $address->user_id = Auth::guard('web')->user()->id;
                        $address->address = $newEntry->shipping_address;
                        $address->landmark = $newEntry->shipping_landmark;
                        $address->lat = "";
                        $address->lng = "";
                        $address->state = $newEntry->shipping_state;
                        $address->city = $newEntry->shipping_city;
                        $address->pin = $newEntry->shipping_pin;
                        $address->country = $newEntry->shipping_country;
                        $address->type = 3;
                        $address->billing = 2;
                        $address->save();
                    }
                }
            }

            // 2 insert cart data into order products
            $orderProducts = [];
            $buy_one_get_one_result = $this->getGetOneByOneDiscountAmount($cartData);
            $buy_one_get_one_discount_amount = $buy_one_get_one_result['discount_amount'];
            $selectedProductId = $buy_one_get_one_result['selected_product'] ?? null;
            foreach($cartData as $cartValue) {
                $isBogoFree = (!empty($selectedProductId) && $selectedProductId == $cartValue->product_id) ? 1 : 0;
                $orderProducts[] = [
                    'order_id' => $newEntry->id,
                    'product_id' => $cartValue->product_id,
                    'product_name' => $cartValue->product_name,
                    'product_image' => $cartValue->product_image,
                    'product_slug' => $cartValue->product_slug,
                    'product_variation_id' => $cartValue->product_variation_id,
                    'colour_name' => ProductColorSize::find($cartValue->product_variation_id)->color_name ?? '',
                    'size_name' => ProductColorSize::find($cartValue->product_variation_id)->size_name ?? '',
                    'sku_code' => ProductColorSize::find($cartValue->product_variation_id)->code ?? '',
                    'price' => $cartValue->price,
                    'offer_price' => $cartValue->offer_price,
                    'qty' => $cartValue->qty,
                    'bogo_type' => $isBogoFree,
                ];
            }
            $orderProductsNewEntry = OrderProduct::insert($orderProducts);

            // dd($settings[23]->content);

            // new guest user  - password send via email
            $userCheck = User::where('email', $collectedData['email'])->first();

            if(empty($userCheck)) {
                $password = generateUniqueAlphaNumeric(10);
                // insert new user 
                $full_name = '';
                if (isset($data['fname'])) {
                    $full_name = $collectedData['fname'] . ' ' . $collectedData['lname'];
                }

                $newUserEntry = new User();
                $newUserEntry->fname = $collectedData['fname'] ?? NULL;
                $newUserEntry->lname = $collectedData['lname'] ?? NULL;
                $newUserEntry->name = $full_name;
                $newUserEntry->email = $collectedData['email'];
                $newUserEntry->mobile = $collectedData['mobile'];
                $newUserEntry->gender = $collectedData['gender'] ?? NULL;
                $newUserEntry->password = Hash::make($password);

                $newUserEntry->save();

                //send email
                if ($newUserEntry) {
                    $email_data = [
                        'name' => $full_name,
                        'subject' => 'Onn - New registration',
                        'email' => $collectedData['email'],
                        'password' => $password,
                        'blade_file' => 'front/mail/register',
                    ];
                    if ($settings[23]->content == "1") SendMail($email_data);
                }
            }

            // 3 send product details mail
            // $email_data = [
            //     'name' => $collectedData['fname'].' '.$collectedData['lname'],
            //     'subject' => 'Onn - New Order',
            //     'email' => $collectedData['email'],
            //     'orderId' => $newEntry->id,
            //     'orderNo' => $order_no,
            //     'orderAmount' => $total,
            //     'orderProducts' => $orderProducts,
            //     'blade_file' => 'front/mail/order-confirm',
            // ];
            // if ($settings[23]->content == "1") SendMail($email_data);

            // send invoice mail starts
            // $invoice_email_data = [
            //     'name' => $collectedData['fname'].' '.$collectedData['lname'],
            //     'subject' => 'Onn - Order Invoice',
            //     'email' => $collectedData['email'],
            //     'orderId' => $newEntry->id,
            //     'payment_method' => $newEntry->payment_method,
            //     'orderNo' => $order_no,
            //     'orderAmount' => $total,
            //     // 'orderProducts' => $orderProducts,
            //     'blade_file' => 'front/mail/invoice',
            // ];
            // if ($settings[23]->content == "1") SendMail($invoice_email_data);

			// // Shiprocket
            // if ($settings[23]->content == "1") $this->shiprocket($newEntry, $cartData);

			// // Unicommerce
            // if ($settings[23]->content == "1") $this->feedUnicommerce($newEntry, $cartData);

            // 4 remove cart data
            $emptyCart = Cart::where('ip', $this->ip)->delete();
            if (Auth::guard()->check()) {
                $emptyCart = Cart::where('user_id', Auth::guard()->user()->id)->delete();
            } else {
                if (!empty($_COOKIE['cartToken'])) {
                    $emptyCart = Cart::where('guest_token', $_COOKIE['cartToken'])->delete();
                } else {
                    $emptyCart = [];
                    return false;
                }
            }

            // 5 online payment
            // if (isset($data['razorpay_payment_id'])) {
            //     // fetch order details
            //     $ordDetails = Order::findOrFail($newEntry->id);
            //     // dd($data);

            //     // Razorpay auto capture code
            //     $amm = $ordDetails->final_amount*100;
            //     $pay_id = $collectedData['razorpay_payment_id'];

            //     $url = 'https://api.razorpay.com/v1/payments/'.$pay_id.'/capture';

            //     $data_string = 'amount='.$amm;
            //     $razorpay_key_id = $settings[20]->content;
            //     $razorpay_key_secret = $settings[21]->content;

            //     $headers = array(
            //         'Content-Type: application/x-www-form-urlencoded',
            //         'Authorization: Basic '. base64_encode("$razorpay_key_id:$razorpay_key_secret")
            //     );

            //     // Open connection
            //     $ch = curl_init();
            //     // Set the url, number of POST vars, POST data
            //     curl_setopt($ch, CURLOPT_URL, $url);
            //     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
            //     curl_setopt($ch, CURLOPT_POST, true);                                                                  
            //     curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            //     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //     // Disabling SSL Certificate support temporarly
            //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            //     //curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            //     // Execute post
            //     $result = curl_exec($ch);
            //     // dd($result);
            //     //echo $result;
            //     //pr($result);
            //     curl_close($ch);

            //     // save in transaction
            //     $txnData = new Transaction();
            //     $txnData->user_id = Auth::guard('web')->user()->id ?? 0;
            //     $txnData->order_id = $newEntry->id;
            //     $txnData->transaction = 'TXN_'.strtoupper(Str::random(20));
            //     $txnData->online_payment_id = $collectedData['razorpay_payment_id'];
            //     // $txnData->amount = $total;razorpay_amount
            //     //$txnData->amount = $ordDetails->final_amount;
            //     $txnData->amount = $collectedData['razorpay_amount'];
            //     $txnData->currency = "INR";
            //     $txnData->method = "";
            //     $txnData->description = "";
            //     $txnData->bank = "";
            //     $txnData->upi = "";
            //     $txnData->save();
            // }
            
            DB::commit();
            // dd($newEntry);
            return $newEntry->id;
        } catch (\Throwable $th) {
            throw $th;
            dd($th);
            DB::rollback();
            return false;
        }
    }
    
    
    protected function getGetOneByOneDiscountAmount($cartData)
    {
        // Already available from controller / class
        $logedInUser = Auth::guard()->user() ?? null;
        $currentDate = date('Y-m-d');
    
        $totalQty = $cartData->sum('qty');
        // BOGO CONDITIONS
        if (checkGoBo()
            && !empty($logedInUser) &&
            $logedInUser->type == "event-user" &&
            $currentDate <= '2026-01-15' &&
            $totalQty > 1
        ) {
          
            // FIND LOWEST PRICE PRODUCT
            $lowestProductId = null;
            $lowestPrice = null;
        
            foreach ($cartData as $cartValue) {
        
                if ($lowestPrice === null || $cartValue->offer_price < $lowestPrice) {
        
                    $lowestPrice = $cartValue->offer_price;
                    $lowestProductId = $cartValue->product_id;
                }
            }
        
            return [
                'discount_amount' => $lowestPrice,
                'selected_product' => $lowestProductId
            ];
        }else{
             return [
                'discount_amount' => 0,
                'selected_product' => null
            ]; 
        }
    
        
    }



    //payment

    public function paymentCreate($order_id,array $data)
    {
        $collectedData = collect($data);
		
        DB::beginTransaction();

        try {
            $settings = Settings::all();

            // shipping charge fetch
            $shippingChargeJSON = json_decode($settings[22]->content);
            $minOrderAmount = $shippingChargeJSON->min_order??'';
            $shippingCharge = $shippingChargeJSON->shipping_charge??'';

            $newEntry = Order::findOrFail($order_id);
            if (isset($data['payment_method'])) {
                $newEntry->payment_method = $collectedData['payment_method'];
            } else {
                $newEntry->payment_method = "cash_on_delivery";
            }
            $newEntry->save();
			
            $order_no=$newEntry->order_no;
            $orderPro=OrderProduct::where('order_id',$order_id)->get();
            $orderProducts = [];
            foreach($orderPro as $cartValue) {
                $orderProducts[] = [
                    'order_id' => $order_id,
                    'product_id' => $cartValue->product_id,
                    'product_name' => $cartValue->product_name,
                    'product_image' => $cartValue->product_image,
                    'product_slug' => $cartValue->product_slug,
                    'product_variation_id' => $cartValue->product_variation_id,
                    'colour_name' => ProductColorSize::find($cartValue->product_variation_id)->color_name ?? '',
                    'size_name' => ProductColorSize::find($cartValue->product_variation_id)->size_name ?? '',
                    'sku_code' => ProductColorSize::find($cartValue->product_variation_id)->code ?? '',
                    'price' => $cartValue->price,
                    'offer_price' => $cartValue->offer_price,
                    'qty' => $cartValue->qty,
                ];
            }
			
            // payment method
            
			
            // 3 send product details mail
            $email_data = [
                'name' => $newEntry->fname.' '.$newEntry->lname,
                'subject' => 'Onn - New Order',
                'email' => $newEntry->email,
                'orderId' => $newEntry->id,
                'orderNo' => $order_no,
                'orderAmount' => $newEntry->final_amount,
                'orderProducts' => $orderProducts,
                'blade_file' => 'front/mail/order-confirm',
            ];
            if ($settings[23]->content == "1") SendMail($email_data);

            // send invoice mail starts
            $invoice_email_data = [
                'name' => $newEntry->fname.' '.$newEntry->lname,
                'subject' => 'Onn - Order Invoice',
                'email' => $newEntry->email,
                'orderId' => $newEntry->id,
                'payment_method' => $newEntry->payment_method,
                'orderNo' => $order_no,
                'orderAmount' => $newEntry->final_amount,
                //'orderProducts' => $orderProducts,
                'blade_file' => 'front/mail/invoice',
            ];
            if ($settings[23]->content == "1") SendMail($invoice_email_data);

			
           
			//dd($collectedData['razorpay_payment_id']);
            // 5 online payment
            if (!empty($collectedData['razorpay_payment_id'])) {
				//dd('hi');
                // fetch order details
                $ordDetails = Order::findOrFail($order_id);
                 

                // Razorpay auto capture code
                $amm = $ordDetails->final_amount*100;
                $pay_id = $collectedData['razorpay_payment_id'];

                $url = 'https://api.razorpay.com/v1/payments/'.$pay_id.'/capture';

                $data_string = 'amount='.$amm;
                $razorpay_key_id = $settings[20]->content;
                $razorpay_key_secret = $settings[21]->content;
                
                $headers = array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'Authorization: Basic '. base64_encode("$razorpay_key_id:$razorpay_key_secret")
                );

                // Open connection
                $ch = curl_init();
                // Set the url, number of POST vars, POST data
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
                curl_setopt($ch, CURLOPT_POST, true);                                                                  
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                // Disabling SSL Certificate support temporarly
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                //curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                // Execute post
                $result = curl_exec($ch);
                 //dd($result);
                //echo $result;
                //pr($result);
                curl_close($ch);


                //$payment = 'https://api.razorpay.com/v1/payments/'.$pay_id;
               // dd($payment);
                // save in transaction
                $txnData = new Transaction();
                $txnData->user_id = Auth::guard('web')->user()->id ?? 0;
                $txnData->order_id = $ordDetails->id;
                $txnData->transaction = 'TXN_'.strtoupper(Str::random(20));
                $txnData->online_payment_id = $collectedData['razorpay_payment_id'];
                // $txnData->amount = $total;razorpay_amount
                $txnData->amount = $ordDetails->final_amount;
             
                $txnData->currency = "INR";
                $txnData->method = "";
                $txnData->description = "";
                $txnData->bank = "";
                $txnData->upi = "";
                $txnData->save();
            }
            // Shiprocket
            if ($settings[23]->content == "1") $this->shiprocket($newEntry, $orderPro);

			// Unicommerce
            if ($settings[23]->content == "1") $this->feedUnicommerce($newEntry, $orderPro);

            DB::commit();
            // dd($order_no);
            return $order_no;
        } catch (\Throwable $th) {
            throw $th;
            //dd($th);
            DB::rollback();
            return false;
        }
    }

    public function shiprocket($booking,$items){

        $logindetails = $this->shiprocketlogin();
        $logindetails = json_decode($logindetails);

        $dt = date('Y-m-d H:i:s');

        $pushdata = array();

        foreach($items as $n){
            $sku_code = ProductColorSize::findOrFail($n->product_variation_id);

            ($sku_code->code == "" || $sku_code->code == null) ? $SKU_code = "ONN_272_BLK_S_1PC" : $SKU_code = $sku_code->code;

            $data['name'] = $n->product_name;
            // $data['sku'] = $n->product_style_no;
            $data['sku'] = $SKU_code;
            $data['units'] = $n->qty;
            $data['selling_price'] = $n->offer_price;

            array_push($pushdata, $data);
        }

        //$name =  $this->split_name($booking->name);
        $jsondata['order_id'] = $booking->order_no;
        $jsondata['order_date'] = date('Y-m-d H:i:s');
        $jsondata['pickup_location'] = 'Lux Industries Limited';
        $jsondata['channel_id'] = '2865152';
        // $jsondata['pickup_location'] = 'Lux Dankuni';
        $jsondata['billing_customer_name'] = $booking->fname;
        $jsondata['billing_last_name'] = $booking->lname;
        $jsondata['billing_address'] = $booking->billing_address;
        $jsondata['billing_address_2'] = $booking->billing_landmark;
        $jsondata['billing_city'] = $booking->billing_city;
        $jsondata['billing_pincode'] = $booking->billing_pin;
        $jsondata['billing_state'] = $booking->billing_state;
        $jsondata['billing_country'] = $booking->billing_country;
        $jsondata['billing_email'] = $booking->email;
        $jsondata['billing_phone'] = $booking->mobile;
        $jsondata['shipping_is_billing'] = true;
        $jsondata['shipping_customer_name'] = '';
        $jsondata['shipping_last_name'] = '';
        $jsondata['shipping_address'] = '';
        $jsondata['shipping_address_2'] = '';
        $jsondata['shipping_city'] = '';
        $jsondata['shipping_pincode'] = '';
        $jsondata['shipping_country'] = '';
        $jsondata['shipping_state'] = '';
        $jsondata['shipping_email'] = '';
        $jsondata['shipping_phone'] = '';
        $jsondata['order_items'] = $pushdata;

        $payment_method = '';
        if($booking->payment_method=='online_payment') {
            $payment_method = "Prepaid";
        } else{
			$payment_method = "Cod";
		}

        $jsondata['payment_method'] = $payment_method;
        $jsondata['shipping_charges'] = $booking->shipping_charge;
        $jsondata['total_discount'] = $booking->discount_amount;
        $jsondata['sub_total'] = $booking->final_amount;
        $jsondata['length'] = 22;
        $jsondata['breadth'] = 22;
        $jsondata['height'] = 5;
        $jsondata['weight'] = 0.5;

        $token = $logindetails->token;

        // echo json_encode($jsondata);

        // die();

        //echo $token;

        $url = 'https://apiv2.shiprocket.in/v1/external/orders/create/adhoc';

        $headers = array(
            "Content-Type: application/json",
            "Authorization: Bearer ". $token
        );
        //  echo '<pre>';
        //  print_r($headers);
        //  die();
        // Open connection
        $ch = curl_init();
        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsondata));
        // Execute post
        $result = curl_exec($ch);

        //echo "result : ".$result;
        //die();

        curl_close($ch);

		$payload = DB::table('third_party_payloads')->insert([
            "type" => "shiprocket",
            "status" => "success",
            "order_id" => $booking->order_no,
            "request_body" => json_encode($jsondata),
            "payload" => $result
        ]);

        return $result;

        /*return response()
            ->json(["jsondata"=>$jsondata]);*/
    }

    public function shiprocketlogin(){
        $headers = array(
            'Content-Type: application/json'
        );

        $jsondata['email'] = "suvajit.bardhan@onenesstechs.in";
        $jsondata['password'] = "Welcome#2022";

        $url = 'https://apiv2.shiprocket.in/v1/external/auth/login';

        $ch = curl_init();
        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsondata));
        // Execute post
        $result = curl_exec($ch);

        curl_close($ch);
        return $result;
    }

    //unicommerce
    public function feedUnicommerce($orderDetails, $cartData)
    {
        $loginCred = $this->unicommerceLogin();
        $loginResp = json_decode($loginCred);
		
        // if (!isset($loginResp->successful)) {
        if (!isset($loginResp->successful) || $loginResp->successful == "true") {
            $url = 'https://cozyworld.unicommerce.com/services/rest/v1/oms/saleOrder/create';
            $headers = array(
                'Authorization: Bearer '.$loginResp->access_token,
                'Content-Type: application/json'
            );
            if($orderDetails->payment_method == 'online_payment') {
                $cashOnDelivery = false;
            } else{
                $cashOnDelivery = true;
            }
            $billing_refId = mt_rand();
            $shipping_refId = mt_rand();

            if ($orderDetails->shippingSameAsBilling != 0) {
                $shippingAddressLine1 = $orderDetails->shipping_address;
                $shippingAddressLine2 = '';
                $shippingAddressPincode = $orderDetails->shipping_pin;
                $shippingAddressCity = $orderDetails->shipping_city;
                $shippingAddressState = $orderDetails->shipping_state;
                $shippingAddressCountry = $orderDetails->shipping_country;
            } else {
                $shippingAddressLine1 = $orderDetails->billing_address;
                $shippingAddressLine2 = '';
                $shippingAddressPincode = $orderDetails->billing_pin;
                $shippingAddressCity = $orderDetails->billing_city;
                $shippingAddressState = $orderDetails->billing_state;
                $shippingAddressCountry = $orderDetails->billing_country;
            }

            $productsData = array();

            foreach($cartData as $cartProduct) {
                $sku_code = ProductColorSize::findOrFail($cartProduct->product_variation_id);

                ($sku_code->code == "" || $sku_code->code == null) ? $SKU_code = "ONN_272_BLK_S_1PC" : $SKU_code = $sku_code->code;

                $total_price = $cartProduct->offer_price * $cartProduct->qty;

                $productsData[] = [
                    "itemSku" => $SKU_code,
                    // "code" => $cartProduct->product_style_no,
                    "code" => $SKU_code,
                    "itemName" => $cartProduct->product_name,
                    "totalPrice" => $total_price,
                    "sellingPrice" => $cartProduct->offer_price,
                    "prepaidAmount" => 0,
                    "onHold" => false,
                    "shippingAddress" => "$shipping_refId",
                    // "shippingMethodCharges" => 0,
                    "shippingMethodCode" => "STD",
                    "channelTransferPrice" => 0
                ];
            }

            // dd($productsData, json_encode($productsData));

            $body2['saleOrder'] = [];
            $body2['saleOrder']['code'] = $orderDetails->order_no;
            $body2['saleOrder']['channel'] = "ONNINTERNATIONAL";
            // $body2['saleOrder']['displayOrderCode'] = $orderDetails->order_no;
            $body2['saleOrder']['cashOnDelivery'] = true;
            $body2['saleOrder']['addresses'] = [
                [
                    "id" => "$billing_refId",
                    "addressLine1" => $orderDetails->billing_address,
                    "addressLine2" => "",
                    "name" => $orderDetails->fname.' '.$orderDetails->lname,
                    "pincode" => $orderDetails->billing_pin,
                    "phone" => $orderDetails->mobile,
                    "email" => $orderDetails->email,
                    "city" => $orderDetails->billing_city,
                    "state" => $orderDetails->billing_state,
                    "country" => $orderDetails->billing_country
                ],
                [
                    "id" => "$shipping_refId",
                    "addressLine1" => $shippingAddressLine1,
                    "addressLine2" => $shippingAddressLine2,
                    "name" => $orderDetails->fname.' '.$orderDetails->lname,
                    "pincode" => $shippingAddressPincode,
                    "phone" => $orderDetails->mobile,
                    "email" => $orderDetails->email,
                    "city" => $shippingAddressCity,
                    "state" => $shippingAddressState,
                    "country" => $shippingAddressCountry
                ]
            ];
            $body2['saleOrder']['billingAddress']['referenceId'] = "$billing_refId";
            $body2['saleOrder']['shippingAddress']['referenceId'] = "$shipping_refId";
            $body2['saleOrder']['saleOrderItems'] = $productsData;
            // $body2['saleOrder']['totalCashOnDeliveryCharges'] = 0;
            // $body2['saleOrder']['totalDiscount'] = 0;
            // $body2['saleOrder']['totalGiftWrapCharges'] = 0;
            // $body2['saleOrder']['totalPrepaidAmount'] = 0;
            // $body2['saleOrder']['totalShippingCharges'] = 0;
            // $body2['saleOrder']['totalStoreCredit'] = 0;

            // dd(json_encode($body2));

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($body2),
                CURLOPT_HTTPHEADER => $headers,
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $decoded_response = json_decode($response);

            if ($decoded_response->successful == true) {
                $payload = DB::table('third_party_payloads')->insert([
                    "type" => "unicommerce",
                    "status" => "success",
                    "order_id" => $orderDetails->order_no,
                    "request_body" => json_encode($body2),
                    "payload" => $response
                ]);
            } else {
                $payload = DB::table('third_party_payloads')->insert([
                    "type" => "unicommerce",
                    "status" => "failure",
                    "order_id" => $orderDetails->order_no,
                    "request_body" => json_encode($body2),
                    "payload" => $response
                ]);
            }
        } else {
            $payload = DB::table('third_party_payloads')->insert([
                "type" => "unicommerce",
                "status" => "failure",
                "order_id" => $orderDetails->order_no,
                "request_body" => json_encode($body2),
                "payload" => json_encode($loginResp)
            ]);
        }
    }

    public function unicommerceLogin()
    {
        $username = 'rohit@onenesstechs.in';
        $password = 'q%23393KHVqRBPDTE';

        $url = 'https://cozyworld.unicommerce.com/oauth/token?grant_type=password&client_id=my-trusted-client&username='.$username.'&password='.$password;

        $headers = array(
            'Content-Type: application/json'
        );

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    public function split_name($name) {
		$name = trim($name);
		$last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
		$first_name = trim( preg_replace('#'.$last_name.'#', '', $name ) );
		return array($first_name, $last_name);
    }
}