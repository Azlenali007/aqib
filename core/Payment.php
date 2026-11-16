<?php
declare(strict_types=1);

class Payment
{
    public static function razorpay(): RazorpayGateway
    {
        return new RazorpayGateway();
    }

    public static function manual(): ManualGateway
    {
        return new ManualGateway();
    }
}
