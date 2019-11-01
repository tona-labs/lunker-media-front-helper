<?php

require 'recapchalib.php'; 
$config_var = [];

if(file_exists('.env')) {
    $dotenv = Dotenv\Dotenv::create($_ENV['APP_BASE_PATH']);
    $dotenv->load();
}

function env($first, $second=null) {
    $return = getenv($first);
    if(!$return) {
        return $second;
    }
    return $return;
}

function config($variable, $value = null)
{
    global $config_var;
    if(!is_null($value)) {
        // Lets save the data
        $config_var[$variable] = $value;
        return;
    }
    if(isset($config_var[$variable])) {
        return $config_var[$variable];
    }
    $configs = include($_ENV['APP_BASE_PATH'].'/config/app.php');
    return $configs[$variable] ?? null;
}

function apiUrl($variable = null)
{
    $url = config('backend.url');
    if(!is_null($variable)) {
        $url = $url.$variable;
    }
    return $url.'?api_token='.config('backend.api_token');
}

function reviewDate($months) 
{
    $retVal = '';
    $anio = date('Y');
    if ((date('m') - $months) < 1)
    {
        $anio = $anio -1;
    }
    $fecha = strtotime('-'.$months.' month',strtotime(date('Y-m-j')));
    $retVal = date('F', $fecha)." ".$anio." "; 
    return $retVal;
}

function post($url, $data = [])
{
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) {
        return false;
    }
    return $result;
}

function get($url)
{
    $result = file_get_contents($url);
    if ($result === FALSE) {
        return false;
    }
    return $result;
}

function getTour()
{
    $url = apiUrl('/api/tours/'.config('backend.tour_id'));
    $response = get($url);
    return json_decode($response);
}

function getTourOnDate($date, $pax)
{
    $url = apiUrl('/api/tours/'.config('backend.tour_id').'/on-date');
    $response = post($url, compact('date', 'pax'));
    return json_decode($response);
}

function max_pax_option()
{
    $tour = getTour();
    $return = '';
    $return .= "<option value=''>--</option>\n\r";
    for($i = $tour->min_pax; $i <= $tour->max_pax; $i++) {
        if($i==100) {
            break;
        }
        $return .= '<option value="'.$i.'">'.$i."</option>\n\r";
    }
    return $return;
}

function is_available($date, $pax)
{
    $url = apiUrl('/api/tours/'.config('backend.tour_id').'/available');
    $response = post($url, compact('date', 'pax'));
    return json_decode($response)->result;
}

function makeClient($name, $email, $phone, $date, $people)
{
    $data = [
        'name'      => $name,
        'email'     => $email,
        'telephone' => $phone,
        'tour_id'   => config('backend.tour_id'),
        'date'      => $date,
        'people'    => $people
    ];
    $url = apiUrl('/api/clients');
    $response = post($url, $data);
    return json_decode($response);
}

function createOptions($tour)
{
    $return = [];
    foreach($tour->tour_departures as $departure) {
        foreach ($departure->tour_options as $option) {
            $array = [
                'Option_ID' => $option->id,
                'Option_Name' => $option->name,
                'Base_Price' => $option->base_price,
                'Adult_Price' => $option->adult_price,
                'Kid_Price' => $option->kid_price,
                'Infant_Price' => $option->infant_price,
                'Senior_Price' => $option->senior_price,
                'Departure_ID' => $departure->id,
                'Departure_Hour' => $departure->name,
                'Partial_Data' => $option->partial_data,
            ];
            $return[] = $array;
        }
    }
    return json_encode($return);
}

function reservate($data)
{
    $client_id = $data["customer_ID"];
    $date = $data["date"];
    $tour_departure_id = $data["departure"];
    $tour_option = $data["tour-option"];
    $pax_adults = $data["adults"] ?? 0;
    $pax_kids = $data["kids"] ?? 0;
    $pax_infant = $data["infants"] ?? 0;
    $pax_senior = $data["senior"] ?? 0;
    $total_payed = $data["to_pay"];
    $total = $data["total"];
    $additional_fields = json_encode($data["additional_fields"]);

    // User stack
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $access_key = 'e129c241b8a9e24224966ab364ae3147';

    $ch = curl_init('http://api.userstack.com/api/detect?access_key='.$access_key.'&ua='.urlencode($user_agent));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($ch);
    curl_close($ch);

    $api_result = json_decode($json, true);
    $user_agent = $api_result['device']['type'];

    // Set variables
    $date     = date('Y-m-d', strtotime($date));
    $sell_url = $_SERVER["HTTP_HOST"];
  
    $adults = $pax_adults;
    $infants = $pax_infant;
    $kids = $pax_kids;
    $seniors = $pax_senior;
    $tour_option_id = $tour_option;
  
    $ip_costumer = $_SERVER['REMOTE_ADDR'];
    
    $data = compact('client_id', 'tour_departure_id', 'tour_option_id', 'kids', 'infants', 'adults', 'seniors', 'total', 'additional_fields', 'date', 'sell_url', 'ip_costumer', 'user_agent', 'total_payed');
    $url = apiUrl('/api/tours/'.config('backend.tour_id').'/reservate');
    $response = post($url, $data);
    return json_decode($response);
}

function pay($reservation)
{
    $tour = getTour();
    if($tour->payment_type=='paypal') {
        return payWithPaypal($reservation);
    } else if($tour->payment_type=='stripe') {
        return payWithStripe($reservation);
    }
    return 'Error';
}

function payWithPaypal($reservation)
{
    $tour = getTour();
    $names = explode(' ', $reservation->client->name);
  
    $datos_paypal = array(
        "cmd"             => "_xclick",
        "business"        => $tour->payment_data->email,
        "lc"              => "US",
        "item_name"       => $reservation->tour->name,
        "item_number"     => $reservation->id,
        "amount"          => $reservation->total_payed,
        "currency_code"   => $tour->payment_data->currency,
        "no_note"         => 1,
        "no_shipping"     => 1,
        'rm'              => 2,
        "return"          => config('app.url').'/thank-you',
        "cancel_return"   => config('app.url').'/payment-uncompleted',
        "bn"              => "PP-BuyNowBF:btn_buynowCC_LG.gif:NonHosted",
        "notify_url"      => config('backend.url').'/webhook/paypal',
    );

    $querystring = "?";  

    //loop for posted values and append to querystring
    foreach($datos_paypal as $key => $value){
        $value = urlencode(stripslashes($value));
        $querystring .= "$key=$value&";
    }

    // Redirect to paypal
    $paypal_site = 'https://www.paypal.com';
    if($tour->payment_data->sandbox) {
        $paypal_site = 'https://www.sandbox.paypal.com';
    }
    $url = $paypal_site.'/cgi-bin/webscr'.$querystring;
    header('location:'.$url);
    exit();
}

function payWithStripe($reservation)
{
    $tour = getTour();
    \Stripe\Stripe::setApiKey($tour->payment_data->secret_key);

    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'client_reference_id' => $reservation->id,
        'line_items' => [[
            'name' => 'Tour Reservation for '.$tour->name,
            'description' => 'For '.$reservation->total_passengers.' passangers',
            'amount' => ($reservation->total_payed * 100), // Its with cents
            'currency' => $tour->payment_data->currency,
            'quantity' => 1,
        ]],
        'success_url' => config('app.url').'/thank-you?id='.$reservation->id,
        'cancel_url' => config('app.url').'/payment-uncompleted',
        'payment_intent_data' => [
            'description' => 'Payment for reservation #'.$reservation->id
        ]
    ]);
    return showFrontView('stripe-checkout', compact('tour', 'session'));
}

function getPaymentWithStripe($reservation_id)
{
    $tour = getTour();
    \Stripe\Stripe::setApiKey($tour->payment_data->secret_key);

    $events = \Stripe\Event::all([
      'type' => 'checkout.session.completed',
      'created' => [
        // Check for events created in the last 10 minutes.
        'gte' => time() - 10 * 60,
      ],
    ]);

    $session = collect($events->autoPagingIterator())->map(function($event) {
        return $event->data->object;
    })->filter(function($session) use ($reservation_id) {
        return $session->client_reference_id==$reservation_id;
    })->first();
    if(!isset($session) || !isset($session->payment_intent)) {
        return;
    }

    $intent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
    $charge = collect($intent->charges->data)->first();
    $balance = $charge->balance_transaction;

    $balance = \Stripe\BalanceTransaction::retrieve($balance);
    $net = $balance->net / 100;
    return [
        'confirmation' => $session->payment_intent,
        'net_payed' => $net
    ];
}

function confirmReservation($data)
{
    $uses_webhook = true;
    $net_payed = null;

    // its paypal data on post
    if(isset($data['txn_id'])) {
        $reservation_id = $data["item_number"];
        $confirmation = $data["txn_id"];
    }

    // its paypal data on get
    if(isset($data['tx'])) {
        $reservation_id = $data["item_number"];
        $confirmation = $data["tx"];
    }

    // its stripe data
    if(isset($data['id'])) {
        $reservation_id = $data["id"];
        $payment = getPaymentWithStripe($reservation_id);
        $confirmation = $payment['confirmation'];
        $net_payed = $payment['net_payed'];
        $uses_webhook = false;
    }

    if(!isset($confirmation) || is_null($confirmation)) {
        return;
    }

    $url = apiUrl('/api/reservations/'.$reservation_id.'/confirm');
    $response = post($url, compact('confirmation', 'uses_webhook', 'net_payed'));
    return json_decode($response);
}

function contactForm($data)
{
    $url = apiUrl('/api/tours/'.config('backend.tour_id').'/contact');
    $response = post($url, $data);
    return json_decode($response);
}

function getPopupMessage($number) 
{
    $config = 'popup.'.$number;
    $messages = config($config);
    $key = array_rand($messages, 1);
    return $messages[$key];
}

function showFrontView($name, $vars = []) 
{
    foreach ($vars as $key => $value) {
        $$key = $value;
    }
    return include __DIR__.'/views/'.$name.'.php';
}

function contactIsValid()
{
    // your secret key recapcha
    $secret = config('captcha.secret');
    // empty response
    $response = null;
    // check secret key
    $reCaptcha = new ReCaptcha($secret);
    // if submitted check response
    if ($_POST["g-recaptcha-response"]) {
        $response = $reCaptcha->verifyResponse(
            $_SERVER["REMOTE_ADDR"],
            $_POST["g-recaptcha-response"]
        );
    }
    $capcha = false;
    if ($response != null && $response->success) {
        $capcha = true;
    }
    
    $inputs = $_POST;
    unset($inputs['g-recaptcha-response']);
    unset($inputs['contact_submit']);
    
    $inputs = collect($inputs)->map(function($item) {
        return trim($item);
    });
    $total = $inputs->count();
    $inputs = $inputs->filter(function($item) {
        return strlen($item) > 0 && !is_null($item);
    });
    return $inputs->count() == $total && $capcha;
}

function excludeDates()
{
    if(is_null(config('backend.tour_id'))) {
        return '[]';
    }
    $tour = getTour();
    if($tour->limit_by_day != 1) {
        $limit = collect($tour->tour_departures)->map(function($item) {
            return $item->limit;
        });
        $result = collect(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])->map(function($day) use ($limit) {
            return collect($limit)->map(function($item) use ($day) {
                return $item->$day;
            })->sum();
        })->filter(function($item) {
            return $item <= 0;
        })->keys();
        return '['.collect($result)->implode(', ').']';
    }
    $limit = $tour->limit;
    $result = collect(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])->map(function($item) use ($limit) {
        return $limit->$item;
    })->filter(function($item) {
        return $item <= 0;
    })->keys();
    return '['.collect($result)->implode(', ').']';
}

function versionCdn($file)
{
    return config('cdn.url').'/assets/js/v1.2/'.$file;
}

?>
