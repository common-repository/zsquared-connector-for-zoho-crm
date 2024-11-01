<?php

namespace PCIS\ZSquared\CRM;

use WC_Order;
use DateTime;
use DateTimeZone;
use WC_Order_Item_Product;

class ZsqSlackNotifications
{
    public static function send($message)
    {
        $channel = get_option('zsq_crm_slack_channel');
        $url = get_option('zsq_crm_slack_url');
        if(!$channel || !$url) {
            self::fallbackMessage($message);
            return false;
        }
        $bot_name = 'Error Output';
        $icon = ':interrobang:';
        $data = array(
            'channel' => $channel,
            'username' => $bot_name,
            'text' => $message,
            'icon_emoji' => $icon
        );
        $data_string = json_encode($data);
        $result = wp_remote_post($url, ['body' => $data_string, 'sslverify' => FALSE]);
        return $result;
    }

    public static function orderComplete(WC_Order $order)
    {
        $tz = get_option('timezone_string');
        $timestamp = $order->get_date_created()->getTimestamp();
        try {
            $dt = new DateTime("now", new DateTimeZone($tz));
            $dt->setTimestamp($timestamp);
            $display_date = $dt->format('Y-m-d H:i');
        }
        catch (\Exception $e) {
            // catch missing timezone exception; format the timestamp and continue
            $display_date = date('Y-m-d H:i', $timestamp);
        }

        $message = "NEW ORDER RECEIVED - $display_date\n";
        $address = [];
        if($order->get_billing_city() != "") {
            $address[] = $order->get_billing_city();
        }
        if($order->get_billing_state() != "") {
            $address[] = $order->get_billing_state();
        }
        if($order->get_billing_country() != "") {
            $address[] = $order->get_billing_country();
        }
        if(!empty($address)){
            $message .= "From: ".implode(', ', $address)."\n";
        }
        $message .= "==================\n";
        foreach ($order->get_items() as $item) {
            $product = new WC_Order_Item_Product($item->get_id());
            $message .= $product->get_name()." (".$item->get_quantity().")\n";
        }
        $message .= "==================\nORDER TOTAL: $".$order->get_total()."\n==================";
        $channel = get_option('zsq_crm_slack_channel');
        $url = get_option('zsq_crm_slack_url');
        if(!$channel || !$url) {
            // do not report new orders if Slack is not enabled
            return false;
        }
        $bot_name = 'Order';
        $icon = ':sunglasses:';
        $data = array(
            'channel' => $channel,
            'username' => $bot_name,
            'text' => $message,
            'icon_emoji' => $icon
        );
        $data_string = json_encode($data);
        $result = wp_remote_post($url, ['body' => $data_string, 'sslverify' => FALSE]);
        return $result;
    }

    private static function fallbackMessage($message) {
        // final fallback, report to the error log
        error_log(date('Y-m-d H:i:s', time()) . ' :: ' . $message . PHP_EOL);
    }
}