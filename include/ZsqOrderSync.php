<?php

namespace PCIS\ZSquared\CRM;

use WC_Order;
use WP_Error;
use WC_Order_Item_Product;

class ZsqOrderSync
{
    private $api_key = false;

    private $processOutput = [];
    public $error_message;

    private $state = null;

    public function __construct(WC_Order $order)
    {
        $this->processOutput[] = "Starting process on order ".$order->get_id();
        try {
            $this->api_key = get_option('zsq_crm_api_key');
            $this->processOutput[] = "API Key => " . $this->api_key;
            $order_array = [];
            $order_prefix = get_option('zsq_crm_order_prefix');
            if (!$order_prefix) {
                $order_prefix = "ZC";
            }
            if ($order->get_total_discount() > 0) {
                $order_array['discount_total'] = (string)$order->get_discount_total();
                $order_array['coupon_array'] = $order->get_coupon_codes();
            }
            $order_array['date'] = date('Y-m-d', $order->get_date_created()->getTimestamp());
            $taxes = $order->get_taxes();
            $tax_array = [];
            foreach ($taxes as $t) {
                $rate_id = $t->get_rate_id();
                $tax_id = get_option('zsq_crm_ex_to_woo_tax_map_'.$rate_id);
                if(empty($tax_id)) {
                    $this->processOutput[] = "ERROR: No Zoho CRM tax ID set for the following tax: ".print_r($t, true);
                    $this->error_message = "ERROR: No Zoho CRM tax ID set for the following tax: ".print_r($t, true)."; cannot transfer at this time.";
                    $this->sendProcessOutput();
                    return false;
                }
                $tax_array[] = $tax_id;
            }
            if(!empty($tax_array)) {
                $order_array['taxes'] = $tax_array;
            }
            $order_array['shipping_charge'] = (float)$order->get_shipping_total();
            $order_array['line_items'] = $this->compileLineItems($order);
            $order_array['customer'] = $this->getCustomer($order);
            $order_array['notes'] = $order->get_customer_note();
            $order_array['currency'] = $order->get_currency();
            $order_array['custom_fields'] = [];
            $order_array['type'] = 'crm';
            $order_array['shipping_item'] = get_option('zsq_crm_shipping_item');
            $order_array['salesorder_number'] = $order_prefix . "-" . $order->get_id() . "-" . date('dmy', $order->get_date_created()->getTimestamp());
            $order_array['total'] = $order->get_total();
            if ($this->api_key) {
                $url = ZSQ_CRM_API_ENDPOINT."salesorder/send";
                $response = wp_remote_post($url, ['body' => ['salesorder' => $order_array, 'api_key' => $this->api_key, 'zsq_conn_host' => ZSQ_CRM_HOST], 'sslverify' => FALSE]);
                if(is_a($response, WP_Error::class)) {
                    $this->processOutput[] = "WP ERROR on sales order send: " . print_r($response->get_error_message(), true);
                    $this->error_message = "WP ERROR on sales order send: " . print_r($response->get_error_message(), true);
                    $this->sendProcessOutput();
                    $this->state = false;
                    return false;
                }
                $body = json_decode($response['body'], true);
                if (isset($body['message']) && $body['message'] == "Done") {
                    ZsqSlackNotifications::orderComplete($order);
                    $this->state = true;
                    return true;
                } else if (isset($body['message'])) {
                    $this->processOutput[] = "ERROR on sales order send: " . $body['message'];
                    $this->error_message = "ERROR on sales order send: " . $body['message'];
                    $this->state = false;
                } else {
                    $this->processOutput[] = "ERROR on sales order send; response from ZSquared: " . print_r($response['body'], true);
                    $this->error_message = "ERROR on sales order send; response from ZSquared: " . print_r($response['body'], true);
                    $this->state = false;
                }
            } else {
                $this->processOutput[] = "ERROR: API key missing";
                $this->error_message = "ERROR: API key missing";
            }
            $this->sendProcessOutput();
            $this->state = false;
        } catch (\Exception $e) {
            $this->processOutput[] = "ERROR: " . $e->getMessage() . ' on order ID ' . $order->get_id();
            $this->error_message = "ERROR: " . $e->getMessage() . ' on order ID ' . $order->get_id();
            $this->sendProcessOutput();
            $this->state = false;
        }
        return false;
    }

    public function getState() {
        return $this->state;
    }

    private function compileLineItems(WC_Order $order)
    {
        $return_array = [];
        foreach ($order->get_items() as $item) {
            $product = new WC_Order_Item_Product($item->get_id());
            $realproduct = wc_get_product($product->get_product_id());
            if($realproduct) {
                $new_item = [
                    "rate" => number_format(($product->get_subtotal() / $item->get_quantity()), 2),
                    "quantity" => $item->get_quantity(),
                    "unit" => "qty",
                    "item_total" => $product->get_total(),
                    "sku" => $realproduct->get_sku()
                ];
                $return_array[] = $new_item;
            }
            else {
                throw new \Exception("Could not get product info from order item ".$product->get_name());
            }
        }
        return $return_array;
    }

    private function getCustomer(WC_Order $order)
    {
        $contact = [
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
    
            'billing_1' => $order->get_billing_address_1(),
            'billing_2' => $order->get_billing_address_2(),
            'billing_city' => $order->get_billing_city(),
            'billing_state' => $order->get_billing_state(),
            'billing_postcode' => $order->get_billing_postcode(),
            'billing_country' => $order->get_billing_country(),

            'attention' => "F.A.O. ".$order->get_shipping_first_name(). " ". $order->get_shipping_last_name(),
            'shipping_1' => $order->get_shipping_address_1(),
            'shipping_2' => $order->get_shipping_address_2(),
            'shipping_city' => $order->get_shipping_city(),
            'shipping_state' => $order->get_shipping_state(),
            'shipping_postcode' => $order->get_shipping_postcode(),
            'shipping_country' => $order->get_shipping_country(),
        ];
        return $contact;
    }

    private function sendProcessOutput()
    {
        if (!empty($this->processOutput)) {
            $message = "";
            foreach ($this->processOutput as $p) {
                $message .= $p . "\n";
            }
            ZsqSlackNotifications::send($message);
        }
    }
}