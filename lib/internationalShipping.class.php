<?php
/**
 *
 * @property-read array $rate_zone
 * @property-read string $rate_by
 * @property-read string $currency
 * @property-read string $weight_dimension
 * @property-read array $rate
 * @property-read string $delivery_time
 *
 */
class internationalShipping extends waShipping
{


    /**
     * Sort rates per orderWeight
     * @param &array $rates
     * @return void
     */
    private static function sortRates(&$rates)
    {
        uasort($rates, create_function('$a,$b', '
            $a=isset($a["limit"])?$a["limit"]:0;
            $b=isset($b["limit"])?$b["limit"]:0;
            return ($a>$b)?1:($a<$b?-1:0);
        '));
    }

    public function calculate()
    {

        $prices = array();
        $price = null;
        $limit = $this->getPackageProperty($this->rate_by);
        $rates = $this->rate;
        if (!$rates) {
            $rates = array();
        }
        self::sortRates($rates);
        $rates = array_reverse($rates);
        foreach ($rates as $rate) {
            $rate = array_map('floatval', $rate);
            if ($limit !== null && $rate['limit'] < $limit && $price === null) {
                $price = $rate['cost'];
            }
            $prices[] = $rate['cost'];
        }
        if ($this->delivery_time) {
            $delivery_date = array_map('strtotime', explode(',', $this->delivery_time, 2));
            foreach ($delivery_date as & $date) {
                $date = waDateTime::format('humandate', $date);
            }
            unset($date);
            $delivery_date = implode(' —', $delivery_date);
        } else {
            $delivery_date = null;
        }
        return array(
            'delivery' => array(
                'est_delivery' => $delivery_date,
                'currency'     => $this->currency,
                'rate'         => ($limit === null) ? ($prices ? array(min($prices), max($prices)) : null) : $price,
            ),
        );
    }

    public function allowedAddress()
    {
        return array();
    }

    public function allowedCurrency()
    {
        return $this->currency;
    }

    public function allowedWeightUnit()
    {
        return $this->weight_dimension;
    }

    public function requestedAddressFields()
    {
        return array(
            'zip' => false,
        );
    }

    public function getPrintForms(waOrder $order = null)
    {
        return array(
            'delivery_list' => array(
                'name'        => _wd('shipping_courier', 'Packing list'),
                'description' => _wd('shipping_courier', 'Order summary for courier shipping'),
            ),
        );
    }

    public function displayPrintForm($id, waOrder $order, $params = array())
    {
        if ($id = 'delivery_list') {
            $view = wa()->getView();
            $main_contact_info = array();
            foreach (array('email', 'phone', ) as $f) {
                if (($v = $order->contact->get($f, 'top,html'))) {
                    $main_contact_info[] = array(
                        'id'    => $f,
                        'name'  => waContactFields::get($f)->getName(),
                        'value' => is_array($v) ? implode(', ', $v) : $v,
                    );
                }
            }

            $formatter = new waContactAddressSeveralLinesFormatter();
            $shipping_address = array();
            foreach (waContactFields::get('address')->getFields() as $k => $v) {
                if (isset($order->params['shipping_address.'.$k])) {
                    $shipping_address[$k] = $order->params['shipping_address.'.$k];
                }
            }

            $shipping_address_text = array();
            foreach (array('country_name', 'region_name', 'zip', 'city', 'street') as $k) {
                if (isset($order->shipping_address[$k])) {
                    $shipping_address_text[] = $order->shipping_address[$k];
                }
            }
            $shipping_address_text = implode(', ', $shipping_address_text);
            $view->assign('shipping_address_text', $shipping_address_text);
            $shipping_address = $formatter->format(array('data' => $shipping_address));
            $shipping_address = $shipping_address['value'];

            $view->assign('shipping_address', $shipping_address);
            $view->assign('main_contact_info', $main_contact_info);
            $view->assign('order', $order);
            $view->assign('params', $params);
            $view->assign('p', $this);
            return $view->fetch($this->path.'/templates/form.html');
        } else {
            throw new waException('Print form not found');
        }
    }
}
