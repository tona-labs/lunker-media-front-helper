<?php
if(isset($_POST["item_number"]) && isset($_POST["txn_id"])) {
    $reservation = confirmReservation($_POST);
} else if(isset($_GET["item_number"]) && isset($_GET["tx"])) {
    $reservation = confirmReservation($_GET);
} else if(isset($_GET['id'])) {
    $reservation = confirmReservation($_GET);
} else {
    echo 'Error';
    return;
}
if(!isset($reservation)) {
    $url = config('app.url');
    header('location:'.$url);
}
?>
<div class="container pt-4 pb60">
    <div>
        <div class="row justify-content-center mb42">
            <div class="col-lg-10 blue text-center mb-4">
                <h1>Thank you, <?= $reservation->client->name ?>!</h1>
                <h2 class="grey">Your reservation was completed successfully.</h2>
            </div>
            <div class="col-lg-10">
                <div class="thank-you-container shadow"> 
                    <div class="alert alert-success text-center">
                        Your reservation details are below:
                    </div>
                    <div class="blue text-center mb-4">
                        <h2>Confirmation #<?= $reservation->payment_code ?></h2>
                    </div>
                    <div class="row justify-content-center flex-wrap-reverse">
                        <div class="col-lg-6 col-12 grey text-justify">
                            <ul class="list lh-2">
                                <li class="d-flex align-items-center">
                                    <div>
                                        <strong>Tour:</strong> <?= $reservation->tour->name ?>
                                    </div>
                                </li>
                                <li class="d-flex align-items-center">
                                    <div>
                                        <strong>Client Name:</strong> <?= $reservation->client->name ?>
                                    </div>
                                </li>
                                <li class="d-flex align-items-center">
                                    <div>
                                        <strong>Tour Date:</strong> <?= explode(' ',$reservation->date)[0] ?>
                                    </div>
                                </li>
                                <li class="d-flex align-items-center">
                                    <div>
                                    <strong>Tour Option:</strong> <?= $reservation->tour_option->name ?>
                                    </div>
                                </li>
                                <li class="d-flex align-items-center">
                                    <div>
                                        <strong>Depart Time:</strong> <?= $reservation->tour_departure->name ?>
                                    </div>
                                </li>
                                <li class="d-flex align-items-center">
                                    <div>
                                        <strong>Total Passengers:</strong> <?= $reservation->total_passengers ?>
                                    </div>
                                </li>
                                <li class="d-flex align-items-center">
                                    <div>
                                        <strong>Total Purchase:</strong> $ <?= $reservation->total ?>
                                    </div>                                  
                                </li>
                                <li class="d-flex align-items-center">
                                    <div>
                                        <strong>Paid Today:</strong> $ <?= $reservation->total_payed ?>
                                    </div>                                  
                                </li>
                                <?php if($reservation->total != $reservation->total_payed): ?>
                                    <li class="d-flex align-items-center">
                                        <div>
                                            <strong>Balance:</strong> $ <?= $reservation->total - $reservation->total_payed?>
                                        </div>                                  
                                    </li>
                                <?php endif; ?>
                                <?php foreach($reservation->tour->additional_fields as $key => $additional_field) : ?>
                                    <?php $values = collect(json_decode($reservation->additional_fields))->values(); ?>
                                    <!--<li class="d-flex align-items-center">
                                        <div>
                                            <strong><?= $additional_field->title; ?>:</strong> <?= $values[$key] ?>
                                        </div>
                                    </li>-->
                                <?php endforeach; ?>
                            </ul>
                        </div> 
                        <div class="col-lg-6 col-12 d-flex align-items-center justify-content-center mb42">
                            <img alt="Image" class="lazyload" src="<?= config('cdn.url') ?>/assets/img/checked.svg" width="120" height="120">
                        </div>
                    </div>
                    <div class="col-lg-12 d-flex justify-content-center py-4 mt-4">
                        <button class="btn btn-success btn-lg shadow-lg" onclick="window.print();">Print your Reservation here<img alt="Image" data-src="<?= config('cdn.url') ?>/assets/img/print.svg" class="lazyload img-fluid ml-2" width="16" height="16"></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-10 text-center mb-4">
                <div class="alert alert-warning d-flex flex-column align-items-center justify-content-center p-4">
                    <h3 class="bold">Can't you print your reservation right now?</h3>
                    <span>No worries. We've sent the Reservation to your email at...<br>
                    <span class="grey text-center"><?= $reservation->client->email ?></span></span>
                    <img alt="Image" src="<?= config('cdn.url') ?>/assets/img/email.svg" class="img-fluid mt-4" width="60" height="60">
                </div>
            </div>
        </div>
    </div>
</div>
