<?php

class internationalShipping extends waShipping {

    protected $currency = 'RUB';

    public function calculate() {

        return array(
            'delivery' => array(
                'est_delivery' => $this->est_delivery,
                'currency' => $this->currency,
                'rate' => null,
            ),
        );
    }

    public function allowedAddress() {
        return array();
    }

    public function allowedCurrency() {
        return $this->currency;
    }

    public function allowedWeightUnit() {
        return $this->weight_dimension;
    }

}
