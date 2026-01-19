@extends('layouts.app')
@section('page', 'Checkout')

@section('content')
<style>
    section.cart-wrapper {
        position: static;
    }
    .cart-flow li:before {
        width: calc(1200px / 3);
    }
    .readonly_select{
        background: #fff !important;
        padding: 11px 11px 12px !important;
    }
    .readonly_select.active{
        padding: 19px 11px 4px !important;
        padding-left: 7px !important
    }
    .readonly_select.active + label.floating-label{
        margin-top: -8px;
        -webkit-transform: translateY(-50%);
        -ms-transform: translateY(-50%);
        transform: translateY(-50%);
        -webkit-transform-origin: top left;
        -ms-transform-origin: top left;
        transform-origin: top left;
        opacity: 1;
    }
    .select2-container--default .select2-selection--single {
        padding: 7px;
        border: 1px solid #d5d5d5;
    }
	.offer__img img {
		height: 35px;
		display: block;
		margin: 15px auto 30px;
	}
</style>

<section class="cart-header mb-3 mb-sm-5">
    <div class="container">
        <div class="row">
            <div class="col-sm-6">
                <h4>Shopping Checkout</h4>
            </div>
        </div>
    </div>
</section>

<section class="cart-wrapper">
    <div class="container">
        <form class="" method="POST" action="{{route('front.checkout.store')}}">@csrf
            <input type="hidden" name="billing_city" value="">
            <div class="row justify-content-between flex-sm-row-reverse">
                <div class="col-md-5 col-lg-4 mb-3 mb-sm-0">
                    <h4 class="cart-heading">Cart Summary</h4>
                    <ul class="cart-summary">
                        @php
                            $subTotal = $grandTotal = $couponCodeDiscount = $shippingCharges = $taxPercent = 0;

                            // shipping charge fetch
                            $shippingChargeJSON = json_decode($settings[22]->content);
                            $minOrderAmount = $shippingChargeJSON->min_order;
                            $shippingCharge = $shippingChargeJSON->shipping_charge;
						    $empshippingChargeJSON = json_decode($settings[24]->content);
                            $minEmpOrderAmount = $empshippingChargeJSON->min_order??'';
                           
                            $empshippingCharge = $empshippingChargeJSON->shipping_charge??'';
                            $buy_one_get_one_selected_product = [];
                            $totalQty = $cartData->sum('qty');
                            $buy_one_get_one_disount_price = 0;
                        @endphp

                        @foreach ($cartData as $cartKey => $cartValue)
                            @php
                                // First item or new lowest price
                                if (
                                    $totalQty > 1 &&
                                    (
                                        empty($buy_one_get_one_selected_product) ||
                                        $cartValue->offer_price < $buy_one_get_one_selected_product['offer_price']
                                    )
                                ){
                        
                                    $buy_one_get_one_selected_product = [
                                        'id'               => $cartValue->id,
                                        'product_style_no' => $cartValue->product_style_no,
                                        'product_name'     => $cartValue->product_name,
                                        'offer_price'      => $cartValue->offer_price,
                                        'qty'              => 1
                                    ];
                                }
                            @endphp
                        <li>
                            <figure>
                                <img src="{{$cartValue->product_image}}" />
                            </figure>
                            <figcaption>
                                <div class="cart-info">
                                    <h4>{{$cartValue->product_name}}</h4>
                                    <h6>Style # OF {{$cartValue->product_style_no}}</h6>
                                    <p>QTY : {{$cartValue->qty}}
                                    @if ($cartValue->cartVariationDetails)
                                        | {{$cartValue->cartVariationDetails->sizeDetails->name.', '.ucwords($cartValue->cartVariationDetails->colorDetails->name)}}</p>
                                    @endif
                                </div>
                                <div class="card-meta">
                                    <h4>&#8377;{{$cartValue->offer_price}}</h4>
                                </div>
                            </figcaption>
                        </li>

                        @php
                            // subtotal calculation
                            $subTotal += (int) $cartValue->offer_price * $cartValue->qty;

                            // coupon code calculation
                            // if (!empty($cartData[0]->coupon_code_id)) {
                            //     $couponCodeDiscount = (int) $cartData[0]->couponDetails->amount;
                            // }

                            // coupon code calculation
                            if (!empty($cartData[0]->coupon_code_id)) {
                                // 1 is coupon, else voucher
                                if (($cartData[0]->couponDetails->is_coupon == 1)) {
                                    if($cartData[0]->couponDetails->type == 1){
                                        $couponCodeDiscount = (int) ($subTotal * ($cartData[0]->couponDetails->amount / 100));
                                    }else {
                                        $couponCodeDiscount = (int) $cartData[0]->couponDetails->amount;
                                    }
                                } else {
                                    if($cartData[0]->couponDetails->type == 1){
                                        $couponCodeDiscount = (int) ($subTotal * ($cartData[0]->couponDetails->amount / 100));
                                    }else {
                                        $couponCodeDiscount = (int) $cartData[0]->couponDetails->amount;
                                    }
                                }
                            }

                            // grand total calculation
                            $grandTotalWithoutCoupon = $subTotal;
                            $grandTotal = ($subTotal + $shippingCharges) - $couponCodeDiscount;
                          
                            if($grandTotal < 0){
                                $grandTotal = 0;
                            }
                        @endphp

                        @endforeach
                    </ul>

                    {{-- cart offer --}}
                    @if(count($cartOffers) > 0)
                        <h4 class="cart-heading">Cart Offer</h4>
                        @foreach($cartOffers as $offerKey => $offer)
                            <div class="offer-holder mb-2 offer-holder-count{{$offerKey}}">
                                <div class="d-flex">
                                    <div class="img-holder mr-3">
                                        <a href="javascript: void(0)" onclick="offerDetailModal({{$offerKey}})">
                                            <img src="{{ $offer->offer_image }}" alt="...">
                                        </a>
                                    </div>
                                    <div class="text-holder align-self-center">
                                        <p>{!! $offer->offer_name !!}</p>
                                    </div>
                                </div>
                                <p class="small" id="moreCartAmountNeeded{{$offerKey}}"></p>
                            </div>

                            {!! (!$loop->last) ? '<hr>' : '' !!}

                            <div class="modal fade cartOfferModal" id="CartOfferModal{{$offerKey}}" tabindex="-1" 
                            aria-labelledby="CartOfferModal{{$offerKey}}Label" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-body">
                                            <div class="row justify-content-center text-center">
                                                <div class="col-12 col-md-10">
                                                    <h3 class="offer__img">{!! $offer->offer_name !!}</h3>
                                                </div>
                                                <div class="col-12 text-center">
                                                    <img src="{{ $offer->offer_image }}" alt="...">
                                                </div>
                                                <div class="col-12 text-left">
                                                    <div class="offerModalList">
                                                        <ul style="list-style: disc;" class="pl-4">
                                                            @foreach (explode(', ', $offer->offer_conditions) as $condition)
                                                                <li>{{$condition}}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <button class="btn ok-btn" data-bs-dismiss="modal">close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="22CartOfferModal{{$offerKey}}">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            {{-- <h5 class="text-muted font-weight-normal">Add new Remarks for <span id="emailShow"></span></h5> --}}
                                            <button class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <img src="{{ $offer->offer_image }}" alt="...">
                                            <p>{!! $offer->offer_name !!}</p>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <hr class="multiple-offer-hr">
                    @endif
                    {{-- cart offer --}}

                    <div class="w-100">
                        <div class="cart-total">
                            <div class="cart-total-label">
                                Subtotal
                            </div>
                            <div class="cart-total-value">
                                &#8377;<span id="subTotalAmount">{{$subTotal}}</span>
                            </div>
                            {{-- <div class="cart-total-value">
                                &#8377;{{number_format($subTotal)}}
                            </div> --}}
                        </div>

                        <div id="appliedCouponHolder" style="border-bottom: 1px solid #eee;">
                            @if (!empty($cartData[0]->coupon_code_id))
                                @if ($cartData[0]->couponDetails)
                                    <div class="cart-total">
                                        <div class="cart-total-label">
                                            @php
                                                if (($cartData[0]->couponDetails->is_coupon == 1)) {
                                                    $typeDisplay = 'COUPON';
                                                    if($cartData[0]->couponDetails->type == 1){
                                                        $amountDisplay = '- '.$cartData[0]->couponDetails->amount.'%';
                                                    }else{
                                                        $amountDisplay = '- &#8377; '.$cartData[0]->couponDetails->amount;
                                                    }
                                                } else {
                                                    $typeDisplay = 'VOUCHER';
                                                    if($cartData[0]->couponDetails->type == 1){
                                                        $amountDisplay = '- '.$cartData[0]->couponDetails->amount.'%';
                                                    }else{
                                                        $amountDisplay = '- &#8377; '.$cartData[0]->couponDetails->amount;
                                                    }
                                                }
                                            @endphp
                                            {{ $typeDisplay }} APPLIED - <strong>{{$cartData[0]->couponDetails->coupon_code}}</strong><br/>
                                            <a href="javascript:void(0)" onclick="removeAppliedCoupon()"><small class="text-danger">Remove this {{ ($cartData[0]->couponDetails->is_coupon == 1) ? 'coupon' : 'voucher' }}</small></a>
                                        </div>
                                        <div class="cart-total-value">{!! $amountDisplay !!}</div>
                                    </div>
                                @endif
                            @endif
                        </div>

                        <div class="cart-total-label mt-3 mb-3">
                            Shipping Method
                        </div>
                        <ul class="checkout-meta mb-2">
                            <li>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="flexRadioDefault1" value="standard" checked>
                                    <label class="form-check-label" for="flexRadioDefault1">
                                        Standard
                                    </label>
                                </div>
                            </li>
                            {{-- <li>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="flexRadioDefault2" value="standard_cod">
                                    <label class="form-check-label" for="flexRadioDefault2">
                                        Standard Shipping (Cash on Delivery)
                                    </label>
                                </div>
                            </li>
                            <li>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="flexRadioDefault3" value="express">
                                    <label class="form-check-label" for="flexRadioDefault3">
                                        Express
                                    </label>
                                </div>
                            </li> --}}
                        </ul>

                        @if (checkGoBo()
                            && !empty($logedInUser) 
                            && $logedInUser->type === "event-user" 
                            && $currentDate <= '2026-01-15'
                            && $totalQty > 1)

                            <div class="cart-total">
                                <div class="cart-total-label">
                                    Discount
                                    <span><small class="d-block mb-0">Buy One Get One Offer Applied.</small></span>
                                </div>
                                <div class="cart-total-value text-danger">
                                    @if(!empty($buy_one_get_one_selected_product))
                                        @php
                                            $buy_one_get_one_disount_price += $buy_one_get_one_selected_product['offer_price'];
                                        @endphp
                                    &#8377;<span id="">{{ $buy_one_get_one_selected_product['offer_price'] }}</span>
                                     @else
                                        <span style="margin:0; color:#999;">No discount available</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class="cart-total">
                            <div class="cart-total-label">
                                Shipping Charges
                                <span id="shippingMore"></span>
                            </div>
                            <div class="cart-total-value" id="totalShipping">
                                @php
								$firstCode='';
								 if (!empty($cartData[0]->coupon_code_id)) {
                                        $couponCode = substr($cartData[0]->couponDetails->coupon_code, 0, 3);
                                        $firstCode=$couponCode;
                                  }
								 if ($firstCode=='EMP'){
                                        if ((int) $grandTotal > 2000 ) {
                                           $shippingCharges = 200;
                                           $grandTotal = $grandTotal + $shippingCharges;
                                        }else{
                                            $shippingCharges = 100;
                                           $grandTotal = $grandTotal + $shippingCharges;
                                        }
                                         
                                    
                                 }else{
										if ((int) $minOrderAmount >= (int) $grandTotal ) {
											$shippingCharges = $shippingCharge;
											$grandTotal = $grandTotal + $shippingCharges;
										}
								   }
								   
								     $initialGrandTotal = $grandTotal ; 
                                @endphp
                                &#8377;{{$shippingCharges}}
                            </div>
                        </div>
                        {{-- <div class="cart-total">
                            <div class="cart-total-label">
                                Tax and Others - <strong>{{$taxPercent}}%</strong><br/>
                                <small>(Inclusive of all taxes)</small>
                            </div>
                            <div class="cart-total-value"></div>
                        </div> --}}

                        <div class="cart-total">
                            <div class="cart-total-label">
                                Total
                            </div>
                            <div class="cart-total-value">
                                <input type="hidden" value="{{$grandTotalWithoutCoupon}}" name="grandTotalWithoutCoupon">
                                &#8377;<span id="displayGrandTotal">{{number_format($grandTotal-$buy_one_get_one_disount_price)}}</span>
                            </div>
                        </div>
                        {{-- <div class="cart-total-label mt-3 mb-3">
                            Payment Method
                        </div>
                        <ul class="checkout-meta mb-2">
                            <li>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_method_cod" value="cash_on_delivery" checked>
                                    <label class="form-check-label" for="payment_method_cod">
                                        Cash on Delivery (COD)
                                    </label>
                                </div>
                            </li>
                            <li>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_method_online" value="online_payment">
                                    <label class="form-check-label" for="payment_method_online">
                                        Razorpay (Cards, UPI, NetBanking, Wallets, Paypal)
                                    </label>
                                </div>
                            </li>
                        </ul> --}}

                        {{-- <div class="col-sm-12">
                            <ul class="cart-summary-list">
                                <li>
                                    <img src="img/delivery-truck.png" />
                                    <h5><span>&#8377;60</span> Apply Below order &#8377;499</h5>
                                    <a href="{{route('front.content.shipping')}}">See all Shipping charges and policies</a>
                                </li>
                                <li>
                                    <img src="img/coupon.png" />
                                    <div class="coupon-block">
                                        <input type="text" class="coupon-text" name="couponText" id="couponText" placeholder="Enter coupon code here">
                                        <button id="applyCouponBtn">Apply</button>
                                    </div>
                                    @error('lname')<p class="small text-danger mb-0 mt-2">{{$message}}</p>@enderror
                                    <a href="{{route('front.user.coupon')}}" class="d-inline-block mt-2">Get latest coupon from here</a>
                                </li>
                            </ul>
                        </div> --}}
                    </div>
                </div>
                <div class="col-md-7 col-lg-6">
                    <h4 class="cart-heading">Contact Information</h4>
                    <div class="row mb-5">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <input type="text" class="form-control" name="fname" id="checkoutFname" value="@auth{{Auth::guard('web')->user()->fname}}@else{{old('fname')}}@endauth" placeholder="First Name">
                                <label class="floating-label">First Name</label>
                            </div>
                            @error('fname')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <input type="text" class="form-control" name="lname" id="checkoutLname" value="@auth{{Auth::guard('web')->user()->lname}}@else{{old('lname')}}@endauth" placeholder="Last Name">
                                <label class="floating-label">Last Name</label>
                            </div>
                            @error('lname')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <input type="email" class="form-control" name="email" id="checkoutEmail" placeholder="Enter Your Email Address" value="@auth{{Auth::guard('web')->user()->email}}@else{{old('email')}}@endauth">
                                <label class="floating-label">Enter Your Email Address</label>
                            </div>
                            @error('email')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <input type="tel" class="form-control" name="mobile" id="checkoutMobile" placeholder="Enter Your Contact Number" value="@auth{{Auth::guard('web')->user()->mobile}}@else{{old('mobile')}}@endauth" maxlength="10">
                                <label class="floating-label">Enter Your Contact Number</label>
                            </div>
                            @error('mobile')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                        </div>
                    </div>
					
                   @php
                    if (!empty($cartData[0]->coupon_code_id)) {
                      $couponCode = substr($cartData[0]->couponDetails->coupon_code, 0, 3);
                      $firstCode=$couponCode;
                    }
                    @endphp
                    <h4 class="cart-heading">Billing address</h4>
                    @if (!empty($cartData[0]->coupon_code_id))
                        @if ($firstCode=='EMP')
                        
                            <div class="form-group custom_radio">
                                <input class="form-check-input" type="radio" value="ho" {{ old('addressType') ? (( old('addressType') == "ho" ) ? 'checked' : '') : '' }} id="flexCheckho" name="addressType" onClick="recurringCheck();">
                                <label class="form-check-label" for="flexCheckho">
                                    HO
                                </label>
                                
                            </div>
    
                            {{-- <div class="form-group custom_radio">
                                <input type="radio" name="paymentMethod" id="cod" value="1">
                                <label for="cod">Cash on delivery</label>
                            </div>
    
                            <div class="form-group custom_radio">
                                <input type="radio" name="paymentMethod" id="online" value="2">
                                <label for="online">Cash on delivery</label>
                            </div> --}}
                            
                            <div class="form-group custom_radio">
                                <input class="form-check-input" type="radio" value="dankuni" {{ old('addressType') ? (( old('addressType') == "dankuni" ) ? 'checked' : '') : '' }} id="flexCheckdankuni" name="addressType" onClick="recurringCheck();">
                                <label class="form-check-label" for="flexCheckdankuni">
                                    Dankuni
                                </label>
                                
                            </div>
                            <div class="form-group custom_radio">
                                <input class="form-check-input" type="radio" value="others" {{ old('addressType') ? (( old('addressType') == "others" ) ? 'checked' : '') : '' }} id="flexCheckother" name="addressType" onClick="recurringCheck();">
                                <label class="form-check-label" for="flexCheckother">
                                    Others
                                </label>
                                
                            </div>
                            <div id="yes">
                            @if (is_array($addressData) && count($addressData) > 0)
                                <ul class="checkout-meta mb-2">
                                @foreach ($addressData as $addressKey => $addressValue)
                                    <li><div class="form-check">
                                        <input class="form-check-input" type="radio" name="existing_billing_address" id="existing_billing_address.{{$addressValue->id}}" value="{{$addressValue->id}}"  billing_address="{{$addressValue->address}}" billing_country="{{$addressValue->country ? $addressValue->country : ''}}" billing_landmark="{{$addressValue->landmark ? $addressValue->landmark : ''}}" billing_city="{{$addressValue->city}}" billing_state="{{$addressValue->state}}" billing_pin="{{$addressValue->pin}}" {{$addressKey == 0 ? 'checked' : ''}}>
                                        <label class="form-check-label" for="existing_billing_address.{{$addressValue->id}}">
                                            <span class="billing_address">{{$addressValue->address}}</span>,
                                            <span class="billing_country">{{$addressValue->country ? $addressValue->country.', ' : ''}}</span>
                                            <span class="billing_landmark">{{$addressValue->landmark ? $addressValue->landmark.', ' : ''}}</span>
                                            
                                            <span class="billing_city">{{$addressValue->city}}</span>,
                                            <span class="billing_state">{{$addressValue->state}}</span>,
                                            <span class="billing_pin" >{{$addressValue->pin}}</span>
                                        </label>
                                    </div></li>
                                @endforeach
                                </ul>
                            @endif
                       
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="tel" class="form-control" name="billing_pin" value="{{old('billing_pin')}}" placeholder="Pin Code *" maxlength="6">
                                        <label class="floating-label">Pin Code *</label>
                                    </div>
                                    @error('billing_pin')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>   
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="billing_address" value="{{old('billing_address')}}" placeholder="Address *">
                                        <label class="floating-label">Address *</label>
                                    </div>
                                    @error('billing_address')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="billing_landmark" value="{{old('billing_landmark')}}" placeholder="Landmark">
                                        <label class="floating-label">Landmark</label>
                                    </div>
                                    @error('billing_landmark')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group" id="loadCities">
                                        <select class="form-control readonly_select" name="billing_city" style="color: #97938f;font-weight: 500;" readonly>
                                            @if (old('billing_city'))
                                                <option value="{{old('billing_city')}}">{{old('billing_city')}}</option>
                                            @else
                                                <option selected disabled>City *</option>
                                            @endif
                                        </select>
                                        <label class="floating-label">City *</label>
                                    
                                    </div>
                                    @error('billing_city')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="billing_state" value="{{old('billing_state')}}" placeholder="State *" readonly>
                                        <label class="floating-label">State *</label>
                                    </div>
                                    @error('billing_state')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="billing_country" value="{{old('billing_country')}}" placeholder="Country/Region *" readonly>
                                        <label class="floating-label">Country/Region *</label>
                                    </div>
                                    @error('billing_country')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                            </div>
    
                            <h4 class="cart-heading mt-4">Shipping address</h4>
    
                        
    
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group mb-4">
                                        <div class="form-check">
                                            {{-- <input class="form-check-input" name="shippingSameAsBilling" type="checkbox" value="1" id="shippingaddress" {{ (old('shippingSameAsBilling') == 1) ? 'checked' : '' }}> --}}
                                            <input type="hidden" name="shippingSameAsBilling" value="0">
                                            <input class="form-check-input" name="shippingSameAsBilling" type="checkbox" value="1" id="shippingaddress" 
                                            @php
                                                if (old('shippingSameAsBilling') != null) {
                                                    if (old('shippingSameAsBilling') == 0) {
                                                        echo '';
                                                    } else {
                                                        echo 'checked';
                                                    }
                                                } else {
                                                    echo 'checked';
                                                }
                                            @endphp
                                            >
                                            <label class="form-check-label" for="shippingaddress" >
                                                Same as Billing Address
                                            </label>
                                        </div>
                                    </div>
                                    @error('shippingSameAsBilling')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                            </div>
    
                            {{-- <div class="row shipping-address d-none"> --}}
                            <div class="row shipping-address 
                            @php
                                if (old('shippingSameAsBilling') != null) {
                                    if (old('shippingSameAsBilling') == 0) {
                                        echo '';
                                    } else {
                                        echo 'd-none';
                                    }
                                } else {
                                    echo 'd-none';
                                }
                            @endphp
                            ">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="tel" class="form-control" name="shipping_pin" value="{{old('shipping_pin')}}" placeholder="Pin Code *" maxlength="6">
                                        <label class="floating-label">Pin Code *</label>
                                    </div>
                                    @error('shipping_pin')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="shipping_address" value="{{old('shipping_address')}}" placeholder="Address *">
                                        <label class="floating-label">Address *</label>
                                    </div>
                                    @error('shipping_address')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="shipping_landmark" value="{{old('shipping_landmark')}}" placeholder="Landmark">
                                        <label class="floating-label">Landmark</label>
                                    </div>
                                    @error('shipping_landmark')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="shipping_city" value="{{old('shipping_city')}}" placeholder="City *" readonly>
                                        <label class="floating-label">City *</label>
                                    </div>
                                    @error('shipping_city')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="shipping_state" value="{{old('shipping_state')}}" placeholder="State *" readonly>
                                        <label class="floating-label">State *</label>
                                    </div>
                                    @error('shipping_state')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="shipping_country" value="{{old('shipping_country')}}" placeholder="Country/Region *" readonly>
                                        <label class="floating-label">Country/Region *</label>
                                    </div>
                                    @error('shipping_country')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                            </div>
                        </div>
                        @else
                          @if (is_array($addressData) && count($addressData) > 0)
                                <ul class="checkout-meta mb-2">
                                @foreach ($addressData as $addressKey => $addressValue)
                                    <li><div class="form-check">
                                        <input class="form-check-input" type="radio" name="existing_billing_address" id="existing_billing_address.{{$addressValue->id}}" value="{{$addressValue->id}}"  billing_address="{{$addressValue->address}}" billing_country="{{$addressValue->country ? $addressValue->country : ''}}" billing_landmark="{{$addressValue->landmark ? $addressValue->landmark : ''}}" billing_city="{{$addressValue->city}}" billing_state="{{$addressValue->state}}" billing_pin="{{$addressValue->pin}}" {{$addressKey == 0 ? 'checked' : ''}}>
                                        <label class="form-check-label" for="existing_billing_address.{{$addressValue->id}}">
                                            <span class="billing_address">{{$addressValue->address}}</span>,
                                            <span class="billing_country">{{$addressValue->country ? $addressValue->country.', ' : ''}}</span>
                                            <span class="billing_landmark">{{$addressValue->landmark ? $addressValue->landmark.', ' : ''}}</span>
                                            
                                            <span class="billing_city">{{$addressValue->city}}</span>,
                                            <span class="billing_state">{{$addressValue->state}}</span>,
                                            <span class="billing_pin" >{{$addressValue->pin}}</span>
                                        </label>
                                    </div></li>
                                @endforeach
                                </ul>
                            @endif
                       
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="tel" class="form-control" name="billing_pin" value="{{old('billing_pin')}}" placeholder="Pin Code *" maxlength="6">
                                        <label class="floating-label">Pin Code *</label>
                                    </div>
                                    @error('billing_pin')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>   
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="billing_address" value="{{old('billing_address')}}" placeholder="Address *">
                                        <label class="floating-label">Address *</label>
                                    </div>
                                    @error('billing_address')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="billing_landmark" value="{{old('billing_landmark')}}" placeholder="Landmark">
                                        <label class="floating-label">Landmark</label>
                                    </div>
                                    @error('billing_landmark')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group" id="loadCities">
                                        <select class="form-control readonly_select" name="billing_city" style="color: #97938f;font-weight: 500;" readonly>
                                            @if (old('billing_city'))
                                                <option value="{{old('billing_city')}}">{{old('billing_city')}}</option>
                                            @else
                                                <option selected disabled>City *</option>
                                            @endif
                                        </select>
                                        <label class="floating-label">City *</label>
                                    
                                    </div>
                                    @error('billing_city')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="billing_state" value="{{old('billing_state')}}" placeholder="State *" readonly>
                                        <label class="floating-label">State *</label>
                                    </div>
                                    @error('billing_state')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="billing_country" value="{{old('billing_country')}}" placeholder="Country/Region *" readonly>
                                        <label class="floating-label">Country/Region *</label>
                                    </div>
                                    @error('billing_country')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                            </div>
    
                            <h4 class="cart-heading mt-4">Shipping address</h4>
    
                        
    
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group mb-4">
                                        <div class="form-check">
                                            {{-- <input class="form-check-input" name="shippingSameAsBilling" type="checkbox" value="1" id="shippingaddress" {{ (old('shippingSameAsBilling') == 1) ? 'checked' : '' }}> --}}
                                            <input type="hidden" name="shippingSameAsBilling" value="0">
                                            <input class="form-check-input" name="shippingSameAsBilling" type="checkbox" value="1" id="shippingaddress" 
                                            @php
                                                if (old('shippingSameAsBilling') != null) {
                                                    if (old('shippingSameAsBilling') == 0) {
                                                        echo '';
                                                    } else {
                                                        echo 'checked';
                                                    }
                                                } else {
                                                    echo 'checked';
                                                }
                                            @endphp
                                            >
                                            <label class="form-check-label" for="shippingaddress" >
                                                Same as Billing Address
                                            </label>
                                        </div>
                                    </div>
                                    @error('shippingSameAsBilling')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                            </div>
    
                            {{-- <div class="row shipping-address d-none"> --}}
                            <div class="row shipping-address 
                            @php
                                if (old('shippingSameAsBilling') != null) {
                                    if (old('shippingSameAsBilling') == 0) {
                                        echo '';
                                    } else {
                                        echo 'd-none';
                                    }
                                } else {
                                    echo 'd-none';
                                }
                            @endphp
                            ">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="tel" class="form-control" name="shipping_pin" value="{{old('shipping_pin')}}" placeholder="Pin Code *" maxlength="6">
                                        <label class="floating-label">Pin Code *</label>
                                    </div>
                                    @error('shipping_pin')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="shipping_address" value="{{old('shipping_address')}}" placeholder="Address *">
                                        <label class="floating-label">Address *</label>
                                    </div>
                                    @error('shipping_address')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="shipping_landmark" value="{{old('shipping_landmark')}}" placeholder="Landmark">
                                        <label class="floating-label">Landmark</label>
                                    </div>
                                    @error('shipping_landmark')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="shipping_city" value="{{old('shipping_city')}}" placeholder="City *" readonly>
                                        <label class="floating-label">City *</label>
                                    </div>
                                    @error('shipping_city')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="shipping_state" value="{{old('shipping_state')}}" placeholder="State *" readonly>
                                        <label class="floating-label">State *</label>
                                    </div>
                                    @error('shipping_state')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="shipping_country" value="{{old('shipping_country')}}" placeholder="Country/Region *" readonly>
                                        <label class="floating-label">Country/Region *</label>
                                    </div>
                                    @error('shipping_country')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                                </div>
                            </div>
                        </div>
                        @endif
					@else
                        @if (is_array($addressData) && count($addressData) > 0)
                            <ul class="checkout-meta mb-2">
                            @foreach ($addressData as $addressKey => $addressValue)
                                <li><div class="form-check">
                                    <input class="form-check-input" type="radio" name="existing_billing_address" id="existing_billing_address.{{$addressValue->id}}" value="{{$addressValue->id}}"  billing_address="{{$addressValue->address}}" billing_country="{{$addressValue->country ? $addressValue->country : ''}}" billing_landmark="{{$addressValue->landmark ? $addressValue->landmark : ''}}" billing_city="{{$addressValue->city}}" billing_state="{{$addressValue->state}}" billing_pin="{{$addressValue->pin}}" {{$addressKey == 0 ? 'checked' : ''}}>
                                    <label class="form-check-label" for="existing_billing_address.{{$addressValue->id}}">
                                        <span class="billing_address">{{$addressValue->address}}</span>,
                                        <span class="billing_country">{{$addressValue->country ? $addressValue->country.', ' : ''}}</span>
                                        <span class="billing_landmark">{{$addressValue->landmark ? $addressValue->landmark.', ' : ''}}</span>
                                        {{-- <span class="billing_country">{{$addressValue->country ?? ''}}</span>,
                                        <span class="billing_landmark">{{$addressValue->landmark ?? ''}}</span>, --}}
                                        <span class="billing_city">{{$addressValue->city}}</span>,
                                        <span class="billing_state">{{$addressValue->state}}</span>,
                                        <span class="billing_pin" >{{$addressValue->pin}}</span>
                                    </label>
                                </div></li>
                            @endforeach
                            </ul>
                        @endif

                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <input type="tel" class="form-control" name="billing_pin" value="{{old('billing_pin')}}" placeholder="Pin Code *" maxlength="6">
                                <label class="floating-label">Pin Code *</label>
                            </div>
                            @error('billing_pin')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                            {{-- <div class="form-group">
                                <input type="text" class="form-control" name="billing_country" value="{{old('billing_country')}}" placeholder="Country/Region *">
                                <label class="floating-label">Country/Region *</label>
                            </div>
                            @error('billing_country')<p class="small text-danger mb-0">{{$message}}</p>@enderror --}}
                        </div>
                        {{-- <div class="col-sm-12">
                            <div class="form-group">
                                <input type="text" class="form-control" name="email"  value="{{old('email')}}" placeholder="Company (Optional)">
                                <label class="floating-label">Company (Optional)</label>
                            </div>
                        </div> --}}
                        <div class="col-sm-12">
                            <div class="form-group">
                                <input type="text" class="form-control" name="billing_address" value="{{old('billing_address')}}" placeholder="Address *">
                                <label class="floating-label">Address *</label>
                            </div>
                            @error('billing_address')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group">
                                <input type="text" class="form-control" name="billing_landmark" value="{{old('billing_landmark')}}" placeholder="Landmark">
                                <label class="floating-label">Landmark</label>
                            </div>
                            @error('billing_landmark')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group" id="loadCities">
                                <select class="form-control readonly_select" name="billing_city" style="color: #97938f;font-weight: 500;" readonly>
                                    @if (old('billing_city'))
                                        <option value="{{old('billing_city')}}">{{old('billing_city')}}</option>
                                    @else
                                        <option selected disabled>City *</option>
                                    @endif
                                </select>
                                <label class="floating-label">City *</label>
                                {{-- <input type="text" class="form-control" name="billing_city" value="{{old('billing_city')}}" placeholder="City *" readonly>
                                <label class="floating-label">City *</label> --}}
                            </div>
                            @error('billing_city')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <input type="text" class="form-control" name="billing_state" value="{{old('billing_state')}}" placeholder="State *" readonly>
                                <label class="floating-label">State *</label>
                            </div>
                            @error('billing_state')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <input type="text" class="form-control" name="billing_country" value="{{old('billing_country')}}" placeholder="Country/Region *" readonly>
                                <label class="floating-label">Country/Region *</label>
                            </div>
                            @error('billing_country')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                            {{-- <div class="form-group">
                                <input type="tel" class="form-control" name="billing_pin" value="{{old('billing_pin')}}" placeholder="Pin Code *" maxlength="6">
                                <label class="floating-label">Pin Code *</label>
                            </div>
                            @error('billing_pin')<p class="small text-danger mb-0">{{$message}}</p>@enderror --}}
                        </div>
                    </div>

                    <h4 class="cart-heading mt-4">Shipping address</h4>

                    {{-- @if (isset($addressData))
                    @foreach ($addressData as $addressKey => $addressValue)
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="existing_shipping_address" id="existing_shipping_address.{{$addressValue->id}}" value="{{$addressValue->id}}" {{$addressKey == 0 ? 'checked' : ''}}>
                            <label class="form-check-label" for="existing_shipping_address.{{$addressValue->id}}">
                                <span class="billing_address">{{$addressValue->address}}</span>,
                                <span class="billing_country">{{$addressValue->country ? $addressValue->country.', ' : ''}}</span>
                                <span class="billing_landmark">{{$addressValue->landmark ? $addressValue->landmark.', ' : ''}}</span>,
                                <span class="billing_city">{{$addressValue->city}}</span>,
                                <span class="billing_state">{{$addressValue->state}}</span>,
                                <span class="billing_pin">{{$addressValue->pin}}</span>
                            </label>
                        </div>
                    @endforeach
                    @endif --}}

                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group mb-4">
                                <div class="form-check">
                                    {{-- <input class="form-check-input" name="shippingSameAsBilling" type="checkbox" value="1" id="shippingaddress" {{ (old('shippingSameAsBilling') == 1) ? 'checked' : '' }}> --}}
                                    <input type="hidden" name="shippingSameAsBilling" value="0">
                                    <input class="form-check-input" name="shippingSameAsBilling" type="checkbox" value="1" id="shippingaddress" 
                                    @php
                                        if (old('shippingSameAsBilling') != null) {
                                            if (old('shippingSameAsBilling') == 0) {
                                                echo '';
                                            } else {
                                                echo 'checked';
                                            }
                                        } else {
                                            echo 'checked';
                                        }
                                    @endphp
                                    >
                                    <label class="form-check-label" for="shippingaddress" >
                                        Same as Billing Address
                                    </label>
                                </div>
                            </div>
                            @error('shippingSameAsBilling')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                        </div>
                    </div>

                    {{-- <div class="row shipping-address d-none"> --}}
                    <div class="row shipping-address 
                    @php
                        if (old('shippingSameAsBilling') != null) {
                            if (old('shippingSameAsBilling') == 0) {
                                echo '';
                            } else {
                                echo 'd-none';
                            }
                        } else {
                            echo 'd-none';
                        }
                    @endphp
                    ">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <input type="tel" class="form-control" name="shipping_pin" value="{{old('shipping_pin')}}" placeholder="Pin Code *" maxlength="6">
                                <label class="floating-label">Pin Code *</label>
                            </div>
                            @error('shipping_pin')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group">
                                <input type="text" class="form-control" name="shipping_address" value="{{old('shipping_address')}}" placeholder="Address *">
                                <label class="floating-label">Address *</label>
                            </div>
                            @error('shipping_address')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group">
                                <input type="text" class="form-control" name="shipping_landmark" value="{{old('shipping_landmark')}}" placeholder="Landmark">
                                <label class="floating-label">Landmark</label>
                            </div>
                            @error('shipping_landmark')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <input type="text" class="form-control" name="shipping_city" value="{{old('shipping_city')}}" placeholder="City *" readonly>
                                <label class="floating-label">City *</label>
                            </div>
                            @error('shipping_city')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <input type="text" class="form-control" name="shipping_state" value="{{old('shipping_state')}}" placeholder="State *" readonly>
                                <label class="floating-label">State *</label>
                            </div>
                            @error('shipping_state')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <input type="text" class="form-control" name="shipping_country" value="{{old('shipping_country')}}" placeholder="Country/Region *" readonly>
                                <label class="floating-label">Country/Region *</label>
                            </div>
                            @error('shipping_country')<p class="small text-danger mb-0">{{$message}}</p>@enderror
                        </div>
                    </div>
				@endif
                    <div class="row align-items-center justify-content-between">
                        <div class="col-sm-12">
                            <input type="hidden" name="payment_method" value="">
                            <input type="hidden" name="razorpay_payment_id" value="">
                            <input type="hidden" name="razorpay_amount" value="">
                            <input type="hidden" name="razorpay_method" value="">
                            <input type="hidden" name="razorpay_callback_url" value="">
                            {{-- <div id="method1" class="method"> --}}
                                <button type="submit" class="btn checkout-btn">
                                    Complete order
                                    <!-- <p class="small mb-0" style="font-weight: 800">Cash on delivery</p> -->
                                </button>
                            {{-- </div> --}}
                        </div>
                    </div>
                    {{-- <h4 class="cart-heading mt-4">Select a payment method</h4>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group custom_radio">
                                <input type="radio" name="paymentMethod" id="cod" value="1">
                                <label for="cod">Cash on delivery</label>
                            </div>
                        </div>
                        @if($grandTotal != 0)
                        <div class="col-12">
                            <div class="form-group custom_radio">
                                <input type="radio" name="paymentMethod" id="online" value="2">
                                <label for="online">Pay online</label>
                            </div>
                        </div>
                        @endif
                    </div> --}}

                    {{-- <div class="row align-items-center justify-content-between">
                        <div class="col-sm-12">
                            <input type="hidden" name="payment_method" value="cash_on_delivery">
                            <input type="hidden" name="razorpay_payment_id" value="">
                            <input type="hidden" name="razorpay_amount" value="">
                            <input type="hidden" name="razorpay_method" value="">
                            <input type="hidden" name="razorpay_callback_url" value="">
                            <div id="method1" class="method">
                                <button type="submit" class="btn checkout-btn">
                                    Place order
                                    <!-- <p class="small mb-0" style="font-weight: 800">Cash on delivery</p> -->
                                </button>
                            </div>
                            @if($grandTotal != 0)
                            <!-- <strong>OR</strong> -->
                            <div id="method2" class="method">
                                <button type="submit" id="rzp-button1" class="btn checkout-btn">
                                    Pay Online
                                    <!-- <p class="small mb-0" style="font-weight: 800">Secure payment</p> -->
                                </button>
                            </div>
                            @endif
                        </div> --}}
                        <div class="col-sm-12 mt-3 mt-sm-0">
                            <a href="{{route('front.cart.index')}}">Return to Cart</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<div class="modal fade seeDetailsModal" id="userOfferModal" tabindex="-1" aria-labelledby="userOfferModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row justify-content-center text-center" id="offerContent">
                    <div class="col-12 col-md-10"><h5></h5></div>
                    <div class="col-12 text-center">
                        <img src="" alt="">
                    </div>
                    <div class="col-12">
                        <button class="btn ok-btn" data-bs-dismiss="modal">close</button>   
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        function fetchEmail() {
            alert('email>>'+$('#checkoutEmail').val());
            return $('#checkoutEmail').val();
        }

        
        /*
        document.getElementById('rzp-button1').onclick = function(e){
            e.preventDefault();
            if (checkoutDetailsExists()) {
                // let chekoutAmount = getCookie('checkoutAmount');
                rzp1.open();
            }
        }
        */

        // billing pinode detail fetch
        $('input[name="billing_pin"]').on('keyup', ()=>{
            var pincode = $('input[name="billing_pin"]').val();

            if (pincode.length == 6) {
                toastFire('info', 'Please wait...');
                $('input[name="billing_pin"]').css('borderColor', '#4caf50').css('boxShadow', '0 0 0 0.2rem #4caf5057');

                $.ajax({
                    url: 'https://api.postalpincode.in/pincode/'+pincode,
                    method: 'GET',
                    success: function(result){
                        if(result[0].Message != 'No records found') {
                            // state & country added
                            $('input[name="billing_state"]').val(result[0].PostOffice[0].State);
                            $('input[name="billing_country"]').val(result[0].PostOffice[0].Country);

                            // fetch city
                            $.ajax({
                                url: "{{url('/')}}/state/"+result[0].PostOffice[0].State+"/detail",
                                type: 'GET',
                                success: function(result) {
                                    let content = `
                                    <select class="form-control readonly_select active" name="billing_city" readonly>`;

                                        $.each(result.data, (key, value) => {
                                            content += `<option value="${value.city_name}">${value.city_name}</option>`;
                                        });

                                    content += `</select>
                                    <label class="floating-label">City *</label>
                                    `;

                                    $('#loadCities').html(content);
                                    $('.readonly_select.active').select2();
                                    // console.log(result);
                                }
                            });

                            // $('input[name="billing_city"]').val(result[0].PostOffice[0].District);
                        } else {
                            toastFire('warning', 'Enter valid pincode');
                            $('input[name="billing_pin"]').css('borderColor', 'red').css('boxShadow', '0 0 0 0.2rem #dc34345c');
                            $('input[name="billing_state"]').val('');
                            $('input[name="billing_country"]').val('');
                            $('input[name="billing_city"]').val('');
                        }
                    }
                });
                swal.close();
            } else {
                $('input[name="billing_pin"]').css('borderColor', 'red').css('boxShadow', '0 0 0 0.2rem #dc34345c');
				$('input[name="billing_state"]').val('');
				$('input[name="billing_country"]').val('');
                $('input[name="billing_city"]').val('');
            }
        });

        // shipping pinode detail fetch
        $('input[name="shipping_pin"]').on('keyup', ()=>{
            var pincode = $('input[name="shipping_pin"]').val();

            if (pincode.length == 6) {
                toastFire('info', 'Please wait...');
                $('input[name="shipping_pin"]').css('borderColor', '#4caf50').css('boxShadow', '0 0 0 0.2rem #4caf5057');

                $.ajax({
                    url: 'https://api.postalpincode.in/pincode/'+pincode,
                    method: 'GET',
                    success: function(result){
                        if(result[0].Message != 'No records found'){
                            $('input[name="shipping_state"]').val(result[0].PostOffice[0].State);
                            $('input[name="shipping_country"]').val(result[0].PostOffice[0].Country);
                            $('input[name="shipping_city"]').val(result[0].PostOffice[0].District);
                        } else {
                            toastFire('warning', 'Enter valid pincode');
                            $('input[name="shipping_pin"]').css('borderColor', 'red').css('boxShadow', '0 0 0 0.2rem #dc34345c');
                            $('input[name="shipping_state"]').val('');
                            $('input[name="shipping_country"]').val('');
                            $('input[name="shipping_city"]').val('');
                        }
                    }
                });
                swal.close();
            } else {
                $('input[name="shipping_pin"]').css('borderColor', 'red').css('boxShadow', '0 0 0 0.2rem #dc34345c');
				$('input[name="shipping_state"]').val('');
				$('input[name="shipping_country"]').val('');
                $('input[name="shipping_city"]').val('');
            }
        });

        // offer detail modal
        function offerDetailModal(id) {
            $('#CartOfferModal'+id).modal('show');
        }

        // elligible offer modal
        function elligibleOfferDetail(image, orderMultiplier, productName, productValue, productQty, cartAmount, minOfferAmount) {
            let receiveQty = productQty;
            let receiveWorth = productValue;
            let productQtys = '';
            $('#offerContent img').prop('src', image);

            // ACTIVE multiplier
            if (orderMultiplier == 1) {
                receiveQty = parseInt(cartAmount / minOfferAmount) * productQty;
                receiveWorth = parseInt(receiveWorth * receiveQty);
                if (receiveQty > 1) { productQtys = 's'; }
            }

            $('#offerContent h5').html('Congrats ! You will get <span>'+receiveQty+' '+productName+productQtys+'</span> worth &#8377;'+receiveWorth+' on this order');
            $('#userOfferModal').modal('show');
        }

        // offer calculation
        @if(count($cartOffers) > 0)
            // checking total cart amount - without coupon
            // const grandTotal = $('#displayGrandTotal').text();
            const grandTotal = $('input[name="grandTotalWithoutCoupon"]').val();
            // success svg
            const checkSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check"><polyline points="20 6 9 17 4 12"></polyline></svg>';
            const infoSvg = '<svg xmlns="http://www.w3.org/2000/svg" class="mr-1" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-circle"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
            // count elligible offers
            let elligibleOfferCount = 1

            // all offers
            @foreach($cartOffers as $offerKey => $offer)
                // check if offer is applicable
                if (parseInt({{$offer->min_cart_order}}) > grandTotal) {
                    // OFFER NOT APPLICABLE
                    const moreAmount{{$offerKey}} = parseInt({{$offer->min_cart_order}} - grandTotal);
                    const moreAmountHtml{{$offerKey}} = `Add <strong>&#8377;${moreAmount{{$offerKey}}}</strong> more to cart to avail this offer`;
                    $('#moreCartAmountNeeded'+{{$offerKey}}).html(moreAmountHtml{{$offerKey}});
                } else {
                    // OFFER APPLICABLE
                    if (elligibleOfferCount == 1) {
                        // in case of multiple elligible offers, use the max valued one
                        @if (!empty($cartData[0]->coupon_code_id))
                            // if coupon applied
                            const moreAmountHtml{{$offerKey}} = `<a href="javascript:void(0)" onclick="removeAppliedCoupon()" class="text-danger">${infoSvg}<strong>Remove coupon to avail this offer !</strong></a>`;
                            $('#moreCartAmountNeeded'+{{$offerKey}}).html(moreAmountHtml{{$offerKey}});
                        @else
                            let receiveQty = {{$offer->offer_product_qty}};
                            let offerWorth = {{$offer->offer_product_value}};
                            let productQtys = '';

                            // ACTIVE multiplier
                            if ({{$offer->min_order_multiplier}} == 1) {
                                receiveQty = parseInt(grandTotal / parseInt({{$offer->min_cart_order}})) * {{$offer->offer_product_qty}};
                                offerWorth = {{$offer->offer_product_value}} * receiveQty;
                                if (receiveQty > 1) { productQtys = 's'; }
                            }

                            // id coupon not applied
                            const moreAmountHtml{{$offerKey}} = `${checkSvg}<strong>Congrats !</strong> You will get ${receiveQty} {{$offer->offer_product_name}}${productQtys} worth &#8377;${offerWorth} on this order. <a href="javascript: void(0)" onclick="elligibleOfferDetail('{{asset($offer->offer_image)}}', {{$offer->min_order_multiplier}}, '{{$offer->offer_product_name}}', {{$offer->offer_product_value}}, {{$offer->offer_product_qty}}, ${grandTotal}, parseInt({{$offer->min_cart_order}}))">See details !</a>`;

                            $('#moreCartAmountNeeded'+{{$offerKey}}).addClass('text-success').html(moreAmountHtml{{$offerKey}});

                            // $('.offer-holder-count{{$offerKey}}').hide();

                            // IF OFFER APPLIED - UPDATED CONTENT
                            let offerKey = '{{$offerKey}}';
                            let offerImage = '{{asset($offer->offer_image)}}';
                            $('.offer-holder-count{{$offerKey}}').html('');

                            let NewContent = `
                            <div class="d-flex">
                                <div class="img-holder mr-3">
                                    <a href="javascript: void(0)" onclick="offerDetailModal(${offerKey})">
                                        <img src="${offerImage}" alt="...">
                                    </a>
                                </div>
                                <div class="text-holder align-self-center">
                                    <p class="small text-success" id="moreCartAmountNeeded${offerKey}">
                                        ${checkSvg}<strong>Congrats !</strong> You will get ${receiveQty} {{$offer->offer_product_name}}${productQtys} worth &#8377;${offerWorth} on this order. <a href="javascript: void(0)" onclick="elligibleOfferDetail('{{asset($offer->offer_image)}}', {{$offer->min_order_multiplier}}, '{{$offer->offer_product_name}}', {{$offer->offer_product_value}}, {{$offer->offer_product_qty}}, ${grandTotal}, parseInt({{$offer->min_cart_order}}))">See details !</a>    
                                    </p>
                                </div>
                            </div>
                            `;
                            $('.offer-holder-count{{$offerKey}}').html(NewContent);
                        @endif
                    }
                    else {
                        // in case of multiple elligible offers, hide min valued ones - DIFFERENT CODE IN CART PAGE
                        $('.offer-holder-count{{$offerKey}}').hide();
                        $('.multiple-offer-hr').hide();
                    }
                    elligibleOfferCount++;
                }
            @endforeach
        @endif

        // more shipping charge
        function shippingChargeShow() {
            
            var cartSubAmount = $('#subTotalAmount').text();

            @if (!empty($cartData[0]->coupon_code_id))
                @php
                if (($cartData[0]->couponDetails->is_coupon == 1)) {
                    if($cartData[0]->couponDetails->type == 1){
                        $couponCodeDiscount = (int) ($subTotal * ($cartData[0]->couponDetails->amount / 100));
                    }else{
                        $couponCodeDiscount = (int) $cartData[0]->couponDetails->amount;
                    }
                } else {
                    if($cartData[0]->couponDetails->type == 1){
                        $couponCodeDiscount = (int) ($subTotal * ($cartData[0]->couponDetails->amount / 100));
                    }else{
                        $couponCodeDiscount = (int) $cartData[0]->couponDetails->amount;
                    }
                }

                $grandTotal = $subTotal - $couponCodeDiscount;
			    $couponCode = substr($cartData[0]->couponDetails->coupon_code, 0, 3);
                $firstCode=$couponCode;
                @endphp
				if($firstCode='EMP'){
                            if ( parseInt({{$minEmpOrderAmount}}) > parseInt({{$grandTotal}})) {
                                var amountDiff = parseInt({{$minEmpOrderAmount}}) - parseInt({{$grandTotal}});
                                //$('#shippingMore').html('<small class="d-block mb-0">Add &#8377;'+amountDiff+' more for FREE Shipping.</small>');
                                $('#shippingMore').html('<small class="d-block mb-0">You are not elligible for FREE Shipping.</small>');
                            } else {
                                //$('#shippingMore').html('<small class="d-block mb-0">You are elligible for FREE Shipping.</small>');
                                $('#shippingMore').html('<small class="d-block mb-0">You are not elligible for FREE Shipping.</small>');
                            }
                    }else{
						if ( parseInt({{$minOrderAmount}}) > parseInt({{$grandTotal}})) {
							var amountDiff = parseInt({{$minOrderAmount}}) - parseInt({{$grandTotal}});
							$('#shippingMore').html('<small class="d-block mb-0">Add &#8377;'+amountDiff+' more for FREE Shipping.</small>');
						} else {
							$('#shippingMore').html('<small class="d-block mb-0">You are elligible for FREE Shipping.</small>');
						}
					}
            // if coupon not applied
            @else
                if ( parseInt({{$minOrderAmount}}) > parseInt(cartSubAmount)) {
                    var amountDiff = parseInt({{$minOrderAmount}}) - parseInt(cartSubAmount);
                    $('#shippingMore').html('<small class="d-block mb-0">Add &#8377;'+amountDiff+' more for FREE Shipping.</small>');
                } else {
                    $('#shippingMore').html('<small class="d-block mb-0">You are elligible for FREE Shipping.</small>');
                }
            @endif
        }

        shippingChargeShow()
    </script>

		   <script>

        function recurringCheck() {
            var checkbox = document.getElementById("flexCheckother");
           
        
            if (checkbox.checked) {
                // Checkbox is checked
                document.getElementById('yes').style.display = 'block';
                // You can perform actions here
            } else {
                document.getElementById('yes').style.display = 'none';
                // Checkbox is not checked
                // You can perform actions here
            }
            
            
        }
          
       function openFormOnLoad() {
		   var checkbox = document.getElementById("flexCheckother");
		   if (checkbox.checked) {
          	var form = document.getElementById('yes');
        	form.style.display = 'block';
		   }else{
			    document.getElementById('yes').style.display = 'none';
		   }
  	  }

    // Call the function when the page loads
    window.onload = openFormOnLoad;
    </script>
   <script>
    const defaultShippingCharges = {{ $shippingCharges }};
    const initialGrandTotal = {{ $initialGrandTotal }};
    console.log(defaultShippingCharges);
    console.log(initialGrandTotal);
    function updateShippingCharges() {
        const ho = document.getElementById("flexCheckho");
        const dankuni = document.getElementById("flexCheckdankuni");
        const checkbox = document.getElementById("flexCheckother");
        const totalShipping = document.getElementById("totalShipping");
        const totalGrand = document.getElementById("displayGrandTotal");

        // Calculate shipping charges and grand total
        //let shippingCharges = (ho.checked || dankuni.checked) ? 0 : defaultShippingCharges;
        let shippingCharges = 0;
        
        if (ho.checked || dankuni.checked) {
            // No shipping charges for "ho" or "dankuni"
            shippingCharges = 0;
        }else if(checkbox.checked){
            shippingCharges = defaultShippingCharges;
        } else {
            // Default shipping calculation if conditions apply
            shippingCharges = defaultShippingCharges;
        }
        let grandTotal = initialGrandTotal - defaultShippingCharges + shippingCharges;

        // Update the displayed values
        totalShipping.innerHTML = "₹" + shippingCharges;
        totalGrand.innerHTML =  grandTotal;
        if (shippingCharges === 100 || shippingCharges === 200) {
            shippingMore.innerHTML = '<small class="d-block mb-0">You are not eligible for FREE Shipping.</small>';
        } else {
            shippingMore.innerHTML = '<small class="d-block mb-0">You are eligible for FREE Shipping.</small>'; // Clear message if not eligible
        }
    }

   
    document.getElementById("flexCheckho").addEventListener("change", updateShippingCharges);
    document.getElementById("flexCheckdankuni").addEventListener("change", updateShippingCharges);
    document.getElementById("flexCheckother").addEventListener("change", updateShippingCharges);
</script>
@endsection
