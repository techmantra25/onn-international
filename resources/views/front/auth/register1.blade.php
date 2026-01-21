<!doctype html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ONN Total Comfort | Register</title>

    <link rel="icon" href="img/favicon.png" type="image/png" sizes="16x16">
    <link rel="stylesheet" href="{{ asset('css/plugin.css') }}">
    <link rel="stylesheet" href="{{ asset('node_modules/swiper/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('node_modules/select2/dist/css/select2.min.css') }}">
    <link rel='stylesheet' href='{{ asset('node_modules/lightbox2/dist/css/lightbox.min.css?ver=5.8.2') }}'>
    <link rel="stylesheet" href="{{ asset('css/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('scss/css/preload.css') }}">
    {{-- <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css') }}" /> --}}
    <link rel="stylesheet" href="{{ asset('css/themify-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    
    
    
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Allura&display=swap" rel="stylesheet">

    <style>
        .custom-modal {
            /*background: linear-gradient(132.45deg, #F41319 9%, #B40A15 79.04%);*/
            padding: 44px;
            position:relative;
            z-index:5;
            text-align:center;
        }
        
        .custom-modal .onn-bogo {
            width: 118px;
            margin-bottom:10px;
        }
        
        .custom-modal a {
            text-align:center;
            color:#c10909;
            font-size: 16px;
            display:block;
            font-weight:500;
        }
        
        .custom-modal p {
            text-align: center;
            font-size: 16px;
            color: #000;
            line-height: 26px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .custom-modal h2 {
            font-family: "Allura", cursive;
            font-weight: 400;
            font-style: normal;
            text-align: center;
            font-size: 49px;
            color: #c60000;
            margin-bottom: -5px;
        }
        

        .custom-layer {
            position: absolute;
            width: 100%;
            overflow: hidden;
        }
        .custom-layer img {
            width: 100%;
            hight:100%;
            object-fit: cover;
            
        }
        
        .custom-dialog .modal-content {
            background:transparent;
            border:none;
        }
        
        .regis-logo-field {
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding: 0 62px;
            margin-bottom: 24px;
        }
        
        .regis-logo-field img {
            width: 100px;
        }
    
    @media(max-width:480px){
        .regis-logo-field {
          padding: 0 23px;
          margin-bottom: 12px;
        }
        .regis-logo-field .onn-regis {
            width: 90px;
        }
        .regis-logo-field .rv-college {
            width: 71px;
        }
        
        .main-custom {
            padding-right: 0 !important;
        }
        .custom-modal h2 {
          font-size: 48px;
          margin-bottom: 9px;
          line-height: 1;
        }
        .custom-modal p {
          font-size: 14px;
          line-height: 26px;
          margin-bottom: 8px;
        }
        

        
        .register-right::before {
            display:none;
        }
    }
    
        @media(max-width:431px){
            .custom-modal {
              padding: 29px;
            }
          .custom-modal h2 {
                font-size: 32px;
                margin-bottom: 0;
            }
            
          .custom-modal p {
            font-size: 13px;
            line-height: 20px;
            margin-bottom: 2px;
          }
            .custom-modal .onn-bogo {
              width: 99px;
              margin-bottom: 6px;
            }
        }

    </style>
</head>

<body>
    <main>
        <!-- <section class="register-wrapper">
            <div class="register-left">
                <div class="register-header">
                    <h4>Recommended Product</h4>
                </div>
                <div class="register-collection__thumb swiper-container">
                    <div class="slider swiper-wrapper">
                        @foreach ($recommendedProducts as $productKey => $productValue)
                           <a href="{{route('front.product.detail', $productValue->slug)}}" class="register-collection__thumb-single swiper-slide">
                                <figure>
                                    <img src="{{asset($productValue->image)}}" />
                                    <h4>{{$productValue->name}}</h4>
                                    <h6>Style # OF {{$productValue->style_no}}</h6>
                                </figure>
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="register-collection__thumb swiper-container" dir="rtl">
                    <div class="slider swiper-wrapper">
                        @foreach ($recommendedProducts as $productKey => $productValue)
                           <a href="{{route('front.product.detail', $productValue->slug)}}" class="register-collection__thumb-single swiper-slide">
                                <figure>
                                    <img src="{{asset($productValue->image)}}" />
                                    <h4>{{$productValue->name}}</h4>
                                    <h6>Style # OF {{$productValue->style_no}}</h6>
                                </figure>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="register-right">
                <form method="POST"  class="register-block full" action="{{route('front.user.create')}}">@csrf
                    <div class="register-logo">
                        <a href="{{route('front.home')}}"><img src="{{asset('img/logo.png')}}"></a>
                    </div>

                    <h3>Sign Up</h3>
                    <h4>Welcome to ONN</h4>

                    {{-- @if (Session::get('success'))<div class="alert alert-success">{{Session::get('success')}}</div>@endif
                    @if (Session::get('failure'))<div class="alert alert-danger">{{Session::get('failure')}}</div>@endif --}}

                    <div class="register-card">
                        <div class="register-group">
                            <input type="text" class="register-box" name="fname" placeholder="First Name" value="{{old('fname')}}" autofocus>
                            <label class="floating-label">First Name</label>
                            @error('fname') <p class="small text-danger mb-0">{{$message}}</p> @enderror
                        </div>
                        <div class="register-group">
                            <input type="text" class="register-box" name="lname" placeholder="Last Name" value="{{old('lname')}}">
                            <label class="floating-label">Last Name</label>
                            @error('lname') <p class="small text-danger mb-0">{{$message}}</p> @enderror
                        </div>
                        <div class="register-group">
                            <input type="email" class="register-box" name="email" placeholder="Email id" value="{{old('email')}}">
                            <label class="floating-label">Email id</label>
                            @error('email') <p class="small text-danger mb-0">{{$message}}</p> @enderror
                        </div>
                        <div class="register-group">
                            <input type="tel" class="register-box" name="mobile" placeholder="Mobile no" value="{{old('mobile')}}">
                            <label class="floating-label">Mobile no</label>
                            @error('mobile') <p class="small text-danger mb-0">{{$message}}</p> @enderror
                        </div>
                        <div class="register-group full">
                            <input type="password" class="register-box" name="password" placeholder="Password">
                            <label class="floating-label">Password</label>
                            @error('password') <p class="small text-danger mb-0">{{$message}}</p> @enderror
                        </div>
                    </div>

                    <div class="row align-items-center">
                        <div class="col-5">
                            <a href="{{route('front.user.login')}}">Back to Login</a>
                        </div>
                        <div class="col-7">
                            <button type="submit">Sign Up</button>
                        </div>
                    </div>
                </form>
            </div>
        </section> -->
		
		
		<section class="register-wrapper">
            <div class="register-right">
                 <div class="register-logo">
                    <a href="{{route('front.home')}}"><img src="{{asset('img/footer-logo.png')}}"></a>
                </div>
                <div class="container">
                    <div class="row m-0 justify-content-center">
                        <div class="col-12 col-lg-5 p-0">
                            <div class="regis-logo-field">
                                <img src="{{asset('public/img/onn-register.png')}}" class="onn-regis">
                                <img src="{{asset('public/img/rv-college.png')}}" class="rv-college">
                            </div>
                             <form method="POST"  class="register-block full" action="{{route('register1_create')}}">@csrf
                                <!--<div class="register-logo">
                                    <a href="{{route('front.home')}}"><img src="{{asset('img/logo.png')}}"></a>
                                </div>-->
            
                                <h3>Welcome to ONN</h3>
                                <!--<h4>Register now for buy one get one free</h4>-->
            
                                {{-- @if (Session::get('success'))<div class="alert alert-success">{{Session::get('success')}}</div>@endif
                                @if (Session::get('failure'))<div class="alert alert-danger">{{Session::get('failure')}}</div>@endif --}}
            
                                <div class="register-card newuser">
                                    <div class="register-group">
                                        <input type="text" class="register-box" name="fname" placeholder="First Name" value="{{old('fname')}}" autofocus>
                                        <label class="floating-label">First Name</label>
                                        @error('fname') <p class="small text-danger mb-0">{{$message}}</p> @enderror
                                    </div>
                                    <div class="register-group">
                                        <input type="text" class="register-box" name="lname" placeholder="Last Name" value="{{old('lname')}}">
                                        <label class="floating-label">Last Name</label>
                                        @error('lname') <p class="small text-danger mb-0">{{$message}}</p> @enderror
                                    </div>
                                    <div class="register-group">
                                        <input type="email" class="register-box" name="email" placeholder="Email id" value="{{old('email')}}">
                                        <label class="floating-label">Email id</label>
                                        @error('email') <p class="small text-danger mb-0">{{$message}}</p> @enderror
                                    </div>
                                    <div class="register-group">
                                        <input type="tel" class="register-box" name="mobile" placeholder="Mobile no" value="{{old('mobile')}}">
                                        <label class="floating-label">Mobile no</label>
                                        @error('mobile') <p class="small text-danger mb-0">{{$message}}</p> @enderror
                                    </div>
                                    <!--<div class="register-group full">-->
                                    <!--    <input type="password" class="register-box" name="password" placeholder="Password">-->
                                    <!--    <label class="floating-label">Password</label>-->
                                    <!--    @error('password') <p class="small text-danger mb-0">{{$message}}</p> @enderror-->
                                    <!--</div>-->
                                </div>
            
                                 <div class="row align-items-center justify-content-center text-center">
                                    <div class="col-12">
                                         <button type="submit">Sign Up</button>
                                        <a href="{{route('front.user.login')}}">Back to Login</a>
                                        <!--<a href="" data-bs-toggle="modal" data-bs-target="#fireworksModal">s</a>-->
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- The Modal -->
        <div class="modal main-custom" id="fireworksModal">
          <div class="modal-dialog modal-dialog-centered custom-dialog ">
              <div class="custom-layer">
                    <img src="{{asset('public/img/thanks-bg.jpg')}}"> 
              </div>
            <div class="modal-content">
              <div class="custom-modal">
                  <h2>Congratulations!</h2>
                  <img src="{{asset('public/img/bogo.png')}}" class="onn-bogo">
                  <p>Valid on all products</p>
                  <p>Visit our website! <br> <a href="https://onninternational.com/">onninternational.com</a></p>
                  
              </div>
            </div>
          </div>
        </div>      
        
        
    </main>

    <script src="{{ asset('node_modules/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('js/plugin.js') }}"></script>
    <script src="{{ asset('node_modules/bootstrap/dist/js/bootstrap.js') }}"></script>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script src="{{ asset('node_modules/gsap/dist/gsap.min.js') }}"></script>
    <script src="{{ asset('node_modules/gsap/dist/ScrollTrigger.min.js') }}"></script>
    <script src="{{ asset('node_modules/waypoints/lib/jquery.waypoints.min.js') }}"></script>
    <script src="{{ asset('node_modules/counterup/jquery.counterup.min.js') }}"></script>
    <script src="{{ asset('node_modules/lightbox2/dist/js/lightbox.min.js') }}"></script>
    <script src="{{ asset('node_modules/select2/dist/js/select2.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/1.16.0/TweenMax.min.js"></script>
    <script src="{{ asset('node_modules/scrollmagic/scrollmagic/minified/ScrollMagic.min.js') }}"></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/ScrollMagic/2.0.3/plugins/animation.gsap.min.js'></script>
    <script src="{{ asset('node_modules/scrollmagic/scrollmagic/minified/plugins/debug.addIndicators.min.js') }}"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/app.js') }}"></script>

    <script>
        // sweetalert fires | type = success, error, warning, info, question
        function toastFire(type = 'success', title, body = '') {
            /* Swal.fire({
                icon: type,
                title: title,
                text: body,
                confirmButtonColor: 'black',
                timer: 5000
            }) */

            const Toast = Swal.mixin({
				toast: true,
				position: 'top-end',
				showConfirmButton: false,
				timer: 3000,
				// timerProgressBar: true,
                showCloseButton: true,
				didOpen: (toast) => {
					toast.addEventListener('mouseenter', Swal.stopTimer)
					toast.addEventListener('mouseleave', Swal.resumeTimer)
				}
			});

			Toast.fire({
				icon: type,
				title: title
			});
        }
        </script>

        @if (Session::has('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var myModal = new bootstrap.Modal(document.getElementById('fireworksModal'));
                myModal.show();
            });
        </script>
        @endif

    <script>
        // input key validation
        /*
        $('input[name="fname"]').on("keypress",function(e){
            var code = e.charCode;
            if (code >= 97 && 122 <= code) {
                return true;
            } else {
                return false;
            }

            // console.log(value);
        });
        */
        $('input[name="fname"]').on('keypress', function(event) {
			validate(event, 'charOnly');
        });
		$('input[name="lname"]').on('keypress', function(event) {
			validate(event, 'charOnly');
        });
		$('input[name="mobile"]').on('keypress', function(event) {
			validate(event, 'numbersOnly');
        });
        $('input[name="billing_pin"]').on('keypress', function(event) {
			validate(event, 'numbersOnly');
        });
        $('input[name="shipping_pin"]').on('keypress', function(event) {
			validate(event, 'numbersOnly');
        });
		$('input[name="billing_country"]').on('keypress', function(event) {
			validate(event, 'charOnly');
        });
		$('input[name="billing_city"]').on('keypress', function(event) {
			validate(event, 'charOnly');
        });
		$('input[name="billing_state"]').on('keypress', function(event) {
			validate(event, 'charOnly');
        });
		$('input[name="shipping_country"]').on('keypress', function(event) {
			validate(event, 'charOnly');
        });
		$('input[name="shipping_city"]').on('keypress', function(event) {
			validate(event, 'charOnly');
        });
		$('input[name="shipping_state"]').on('keypress', function(event) {
			validate(event, 'charOnly');
        });

        /*
		function validate(evt, type) {
			var theEvent = evt || window.event;
			// var regex = /[0-9]|\./;
			var regex = /^[A-Za-z]+$/;

			// Handle paste
			if (theEvent.type === 'paste') {
				key = event.clipboardData.getData('text/plain');
			} else {
				// Handle key press
				var key = theEvent.keyCode || theEvent.which;
				key = String.fromCharCode(key);
			}

			if( regex.test(key) ) {
				if(type == 'numbersOnly') {
					theEvent.returnValue = false;
					if(theEvent.preventDefault) theEvent.preventDefault();
				}
			} else {
                // alert()
				if(type == 'charOnly') {
					theEvent.returnValue = false;
					if(theEvent.preventDefault) theEvent.preventDefault();
				}
				// console.log(theEvent.length)
			}
		}
        */

        function validate(evt, type) {
			var theEvent = evt || window.event;
			// var regex = /[0-9]|\./;
			var charOnlyRegex = /^[A-Za-z]+$/;
			var numberOnlyRegex = /^[0-9]+$/;

            // Handle paste
			if (theEvent.type === 'paste') {
				key = event.clipboardData.getData('text/plain');
			} else {
				// Handle key press
				var key = theEvent.keyCode || theEvent.which;
				key = String.fromCharCode(key);
			}

            // character only
            if (type == "charOnly") {
                if( !charOnlyRegex.test(key) ) {
					theEvent.returnValue = false;
					if(theEvent.preventDefault) theEvent.preventDefault();
                }
            }

            // number only
            if (type == "numbersOnly") {
                if( !numberOnlyRegex.test(key) ) {
					theEvent.returnValue = false;
					if(theEvent.preventDefault) theEvent.preventDefault();
                }
            }
        }
    </script>
    
    

</body>

</html>