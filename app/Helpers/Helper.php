<?php

use Carbon\Carbon;
use App\Models\User;
use App\Models\Coupon\Coupon;
use App\Models\Setting\Setting;
use App\Models\Coupon\CouponUsage;


if (!function_exists('setCoupon')) {
    // set coupon code in cookie
    function setCoupon($coupon)
    {
        $theTime = time() + 86400 * 7;
        setcookie('coupon_code', $coupon->code, $theTime, '/'); // 86400 = 1 day
    }
}


if (!function_exists('updateSettings')) {
    // remove coupon code from  cookie
    function updateSettings($data)
    {

        foreach($data as $key => $val){
            $setting = Setting::where('key', $key);

            if( $setting->exists() ){
                $setting->first()->update(['value' => $val]);
            }
        }

    }


}


if (!function_exists('setting')) {
    // remove coupon code from  cookie

    function setting($key){
        return  Setting::where('key','=',$key)->first()->value ?? '' ;
    }
}

if (!function_exists('removeCoupon')) {
    function removeCoupon()
    {
        if (isset($_COOKIE["coupon_code"])) {
            setcookie("coupon_code", "", time() - 3600);
            unset($_COOKIE["coupon_code"]);
        }
    }
}

if (!function_exists('getCoupon')) {
    function getCoupon()
    {
        if (request()->hasHeader("coupon_code")) {
            return request()->header("coupon_code");
        }
        if (isset($_COOKIE["coupon_code"])) {
            return $_COOKIE["coupon_code"];
        }
        return '';
    }
}

if (!function_exists('getCouponDiscount')) {
    function getCouponDiscount($subtotal, $code = '')
    {
        $amount = 0;
        $coupon = Coupon::where('code', $code)->first();

        if ($coupon) {
            $date = strtotime(date('d-m-Y H:i:s'));
            # check if coupon is not expired
            if ($coupon->start_date <= $date && $coupon->end_date >= $date) {
                if ($coupon->type == 'flat') {
                    $amount = (float) $coupon->amount;
                } else {
                    $amount = ((float) $coupon->amount * $subtotal) / 100;
                    if ($amount < (float) $coupon->min_price) {
                        $amount = (float) $coupon->min_price;
                    }
                }
            } else {
                removeCoupon();
            }
        } else {
            removeCoupon();
        }

        return $amount;
    }
}


if (!function_exists('getBundlesDate')) {
    function getBundlesDate($subtotal, $code = '')
    {
        $amount = 0;
        $coupon = Coupon::where('code', $code)->first();

        if ($coupon) {
            $date = strtotime(date('d-m-Y H:i:s'));
            # check if coupon is not expired
            if ($coupon->start_date <= $date && $coupon->end_date >= $date) {
                if ($coupon->type == 'flat') {
                    $amount = (float) $coupon->amount;
                } else {
                    $amount = ((float) $coupon->amount * $subtotal) / 100;
                    if ($amount < (float) $coupon->min_price) {
                        $amount = (float) $coupon->min_price;
                    }
                }
            } else {
                removeCoupon();
            }
        } else {
            removeCoupon();
        }

        return $amount;
    }
}

if (!function_exists('validateCouponForCoursesAndBundles')) {

    function validateCouponForCoursesAndBundles($item, $type,  $coupon)
    {
       // dd($item, $type,  $coupon);
        if (isset($coupon->bundle_ids) && $type == "bundle") {
            $bundle_ids = explode(',', $coupon->bundle_ids) ;
            if (in_array($item, $bundle_ids)) {
                    return true;
            }
        }
        if (isset($coupon->course_ids) && $type == "course") {

               $courses_ids = explode(',', $coupon->course_ids) ;
               if (in_array($item, $courses_ids)) {
                        return true;
               }
        }

        return false;
    }
}

if (!function_exists('checkCouponValidityForCheckout')) {
    function checkCouponValidityForCheckout($type, $price, $item,$code)
    {
        $coupon = Coupon::where('code', $code)->first();

        if ($coupon) {

            $date = date('Y-m-d');

                $totalCouponUsage = CouponUsage::where('coupon_code', $coupon->code)->sum('usage_count');

                if ($totalCouponUsage == $coupon->total_usage_limit) {
                    removeCoupon();
                    return [
                        'status'    => false,
                        'message'   => 'Se ha alcanzado el límite de uso total del cupón.'
                    ];
                }

                if(Auth::check()){
                    $couponUsageByUser = CouponUsage::where('user_id', auth()->user()->id)->where('coupon_code', $coupon->code)->first();
                    if (!is_null($couponUsageByUser)) {
                        if ($couponUsageByUser->usage_count ==  $coupon->customer_usage_limit) {
                            removeCoupon();
                            return [
                                'status'    => false,
                                'message'   => 'Has utilizado este cupón durante el máximo de tiempo.'
                            ];
                        }
                    }
                }


                if ($coupon->start_date >= $date && $coupon->end_date >= $date) {
                    $subTotal = (float) getSubTotal($price ,$coupon->code, false);
                    if ($subTotal >= (float) $coupon->min_spend) {
                        # check if coupon is for categories or products
                        if (isset($coupon->bundle_ids) || isset($coupon->course_ids)) {

                            if (!validateCouponForCoursesAndBundles($item, $type, $coupon)) {
                                # coupon not valid for your cart items
                                removeCoupon();
                                return [
                                    'status'    => false,
                                    'message'   => 'El cupón no es válido para este articulo.'
                                ];
                            }

                            $discount = $coupon->amount;
                            $total = $price- $discount;
                            setCoupon($coupon);

                            return [
                                'status'    => true,
                                'discount'    => $discount,
                                'total'    => $total,
                                'subtotal'    => $subTotal,
                                'code'    => $code,
                                'message'   => 'Cupón aplicado exitosamente'
                            ];
                        }

                        return [
                            'status'    => true,
                            'message'   => 'El cupón no es válido para este articulo.'
                        ];
                    } else {
                        # min amount not reached
                        removeCoupon();
                        return [
                            'status'    => false,
                            'message'   => 'No se ha alcanzado el importe mínimo del pedido para utilizar este cupón'
                        ];
                    }
                } else {
                    # expired
                    removeCoupon();
                    return [
                        'status'    => false,
                        'message'   => 'El cupón ha caducado'
                    ];
                }
            } else {
                # coupon not found
                removeCoupon();

                return [
                    'status'    => false,
                    'message'   => 'El cupón no es válido'
                ];
            }
        }
}

if (!function_exists('formatPrice')) {

    function formatPrice($price, $truncate = false, $forceTruncate = false, $addSymbol = true, $numberFormat = true)

    {
        // convert amount equal to local currency
        if (request()->hasHeader('Currency-Code')) {
            $price = floatval($price) / (floatval(env('DEFAULT_CURRENCY_RATE')) || 1);
            $price = floatval($price) * floatval(ApiCurrencyMiddleWare::currencyData()->rate);
        } else if (Session::has('currency_code') && Session::has('local_currency_rate')) {
            $price = floatval($price) / (floatval(env('DEFAULT_CURRENCY_RATE')) || 1);
            $price = floatval($price) * floatval(Session::get('local_currency_rate'));
        }

        if ($numberFormat) {
            // truncate price
            if ($truncate) {
                if (getSetting('truncate_price') == 1 || $forceTruncate == true) {
                    if ($price < 1000000) {
                        // less than a million
                        $price = number_format($price, getSetting('no_of_decimals'));
                    } else if ($price < 1000000000) {
                        // less than a billion
                        $price = number_format($price / 1000000, getSetting('no_of_decimals')) . 'M';
                    } else {
                        // at least a billion
                        $price = number_format($price / 1000000000, getSetting('no_of_decimals')) . 'B';
                    }
                }
            } else {
                // decimals
                if (getSetting('no_of_decimals') > 0) {
                    $price = number_format($price, getSetting('no_of_decimals'));
                } else {
                    $price = number_format($price, getSetting('no_of_decimals'), '.', ',');
                }
            }
        }

        if ($addSymbol) {
            // currency symbol
            if (request()->hasHeader('Currency-Code')) {
                $symbol             =   ApiCurrencyMiddleWare::currencyData()->symbol;
                $symbolAlignment    =    ApiCurrencyMiddleWare::currencyData()->alignment;
            } else {
                $symbol             = Session::has('currency_symbol')           ? Session::get('currency_symbol')           : env('DEFAULT_CURRENCY_SYMBOL');
                $symbolAlignment    = Session::has('currency_symbol_alignment') ? Session::get('currency_symbol_alignment') : env('DEFAULT_CURRENCY_SYMBOL_ALIGNMENT');
            }
            if ($symbolAlignment == 0) {
                return $symbol . $price;
            } else if ($symbolAlignment == 1) {
                return $price . $symbol;
            } else if ($symbolAlignment == 2) {
                # space
                return $symbol . ' ' . $price;
            } else {
                # space
                return $price . ' ' .  $symbol;
            }
        }
        return $price;
    }
}

if (!function_exists('sellCountPercentage')) {
    function sellCountPercentage($product)
    {
        $sold = $product->total_sale_count;
        $target = (int) $product->sell_target;
        $salePercentage = ($sold * 100) / ($target > 0 ? $target : 1);
        return round($salePercentage);
    }
}

if (!function_exists('discountPercentage')) {
    function discountPercentage($product)
    {
        $discountPercentage = $product->discount_value;

        if ($product->discount_type != "percent") {
            $price = productBasePrice($product);
            $discountAmount = discountedProductBasePrice($product);
            $discountValue = $price - $discountAmount;
            $discountPercentage = ($discountValue * 100) / ($price > 0 ? $price : 1);
        }

        return round($discountPercentage);
    }
}

if (!function_exists('getSubTotal')) {
    function getSubTotal($price, $couponDiscount = true, $couponCode = '', $addTax = true)
    {
        $amount = 0;

            # calculate coupon discount
            if ($couponDiscount) {
                $amount = getCouponDiscount($price, $couponCode);
            }


        return $price - $amount;
    }
}

if (!function_exists('getTotal')) {
    function getTotal($carts)
    {
        $tax = 0;
        if ($carts) {

            foreach ($carts as $cart) {
                $product    = $cart->product_variation->product;
                $variation  = $cart->product_variation;

                $variationTaxAmount = variationTaxAmount($product, $variation);
                $tax += (float) $variationTaxAmount * $cart->qty;
            }
        }
        return $tax;
    }
}

if (!function_exists('getFavicon')) {
    function getFavicon(){

        $setting = Setting::where('key', '=', "page_favicon")->first();
       return count($setting->getMedia('favicon'))>0 ? $setting->getfirstMedia('favicon')->getfullUrl()  : asset('/pages/images/favicon.png');
    }
}


if (!function_exists('getMeta')) {
    function getMeta()
    {
        $setting = Setting::where('key', '=', "meta_image")->first();
        return count($setting->getMedia('meta')) > 0 ? $setting->getfirstMedia('meta')->getfullUrl()  : asset('/pages/images/favicon.png');
    }
}


if (!function_exists('getUrl')) {
    function getUrl(){

        return URL::to('/');

    }
}

if (!function_exists('getLogo')) {
    function getLogo(){

        $setting = Setting::where('key', '=', "page_logo")->first();

        return count($setting->getMedia('logo'))>0 ? $setting->getfirstMedia('logo')->getfullUrl()  : asset('/pages/images/logo.png');
    }
}

if (!function_exists('getSetting')) {
    function getSetting(){

        return Setting::first();
    }
}

if (!function_exists('getMeta')) {
    function getMeta(){

        $setting = Setting::where('key', '=', "meta_image")->first();

        return count($setting->getMedia('metas'))>0 ? $setting->getfirstMedia('metas')->getfullUrl()  : asset('/pages/metas/logo.png');
    }
}

if (!function_exists('clearSessionExceptCurrent')) {
    function clearSessionExceptCurrent(User $user){

        if(config('session.driver') == 'database'){
            $user->sessions()->where('id', '<>', session()->getId())->delete();
        }else{
            $user->sessions()->where('id', '<>', session()->getId())->delete();
        }


    }
}

if (!function_exists('paginationNumber')) {
    # return number of data per page
    function paginationNumber($value = null)
    {
        return $value != null ? $value : env('DEFAULT_PAGINATION');
    }
}
function certificate_date($dates): string {
      $date = Carbon::parse($dates);
      return ucwords($date->format('d-m-Y'));
}

function humanize_date($dates): string {
      $date = Carbon::parse($dates);
      return ucwords($date->format('F j, Y'));
}

function month($dates): string {
      $date = Carbon::parse($dates);
      return ucwords($date->format('Y'));
}

function day($dates): string{
      $date = Carbon::parse($dates);
      return ucwords($date->format('j'));
}

function year($dates): string{
      $date = Carbon::parse($dates);
      return ucwords($date->format('Y'));
}


function dates($dates): string{
      $date = Carbon::parse($dates);
      return ucwords($date->format('d-m-Y'));
}


function input_date($dates): string{
      $date = Carbon::parse($dates);
      return ucwords($date->format('d-m-Y'));
}
