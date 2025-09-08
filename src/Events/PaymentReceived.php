<?php

namespace PayGate\LaravelPayGateGlobal\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $txReference;
    public $identifier;
    public $paymentReference;
    public $amount;
    public $datetime;
    public $paymentMethod;
    public $phoneNumber;

    public function __construct(
        string $txReference,
        string $identifier,
        ?string $paymentReference,
        float $amount,
        string $datetime,
        string $paymentMethod,
        string $phoneNumber
    ) {
        $this->txReference = $txReference;
        $this->identifier = $identifier;
        $this->paymentReference = $paymentReference;
        $this->amount = $amount;
        $this->datetime = $datetime;
        $this->paymentMethod = $paymentMethod;
        $this->phoneNumber = $phoneNumber;
    }
}