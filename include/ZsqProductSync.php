<?php

namespace PCIS\ZSquared\CRM;

use WP_Error;
use WC_Product;

class ZsqProductSync
{
    public function manualSync($page = 1) {
        $api_key = get_option('zsq_crm_api_key');
        if(!empty($api_key) && $api_key != "") {
            $url = ZSQ_CRM_API_ENDPOINT."product/manual?api_key=".$api_key."&zsq_conn_host=".ZSQ_CRM_HOST."&page=".$page;
            $response = wp_remote_get($url,
                array('sslverify' => FALSE));
            if(is_a($response, WP_Error::class)) {
                echo "ERROR: " . $response->get_error_message();
                die;
            }
            $output = json_decode($response['body'], true);
            if (isset($output['data']['items']) && !empty($output['data']['items'])) {
                foreach ($output['data']['items'] as $product) {
                    $product_id = wc_get_product_id_by_sku($product['sku']);
                    if($product_id > 0) {
                        $wc_product = new WC_Product($product_id);
                        $this->updateProduct($wc_product, $product);
                    }
                    else {
                        $this->addProduct($product);
                    }
                }
                if($output['data']['more'] === 'true') {
                    return 'more';
                }
                return "Product sync complete";
            }
            else if (isset($output['message']) && $output['message'] != 'done') {
                return $output['message'];
            }
            else if (isset($output['message']) && $output['message'] == 'done') {
                return "Product sync complete";
            }
            else {
                return "No products found! Please make sure that your API key is correct. Could not sync at this time.";
            }
        }
        return "No connection set up";
    }

    public function dailySync($page = 1) {
        $api_key = get_option('zsq_crm_api_key');
        $price_sync = get_option('zsq_crm_daily_sync_price');
        $qty_sync = get_option('zsq_crm_daily_sync_qty');
        if($price_sync == 1 || $qty_sync == 1) {
            if (!empty($api_key) && $api_key != "") {
                $url = ZSQ_CRM_API_ENDPOINT . "product/daily?api_key=" . $api_key . "&zsq_conn_host=" . ZSQ_CRM_HOST . "&page=" . $page;
                $response = wp_remote_get($url,
                    array('sslverify' => FALSE));
                if (is_a($response, WP_Error::class)) {
                    echo "ERROR: " . $response->get_error_message();
                    die;
                }
                $output = json_decode($response['body'], true);
                if (isset($output['data']['items']) && !empty($output['data']['items'])) {
                    foreach ($output['data']['items'] as $product) {
                        $product_id = wc_get_product_id_by_sku($product['sku']);
                        if ($product_id > 0) {
                            $wc_product = new WC_Product($product_id);
                            $this->updateProduct($wc_product, $product);
                        }
                    }
                    if ($output['data']['more'] === 'true') {
                        $page++;
                        return $this->dailySync($page);
                    }
                    return "Daily product sync complete";
                }
                else if (isset($output['message']) && $output != 'done') {
                    return $output['message'];
                }
                else if (isset($output['message']) && $output['message'] == 'done') {
                    return "Daily product sync complete";
                }
                else {
                    return "No products found! Could not sync at this time";
                }
            }
            return "No connection set up";
        }
        return "No daily product sync active";
    }

    private function updateProduct(WC_Product $product, $product_data) {
        if($product_data['sku'] === get_option('zsq_crm_shipping_item')) {
            // never sync the shipping charge item
            return;
        }
        if(get_option('zsq_crm_daily_sync_price') == 1) {
            $product->set_regular_price($product_data['rate']);
            $product->set_price($product_data['rate']);
        }
        if(get_option('zsq_crm_daily_sync_qty') == 1) {
            $product->set_stock_quantity($product_data['actual_available_stock']);
        }
        $product->save();
    }

    private function addProduct($product_data) {
        if($product_data['sku'] === get_option('zsq_crm_shipping_item')) {
            // never sync the shipping charge item
            return;
        }
        try {
            $product = new WC_Product();
            $product->set_regular_price($product_data['rate']);
            $product->set_price($product_data['rate']);
            $product->set_name($product_data['name']);
            $product->set_sku($product_data['sku']);
            $product->set_stock_quantity($product_data['actual_available_stock']);
            $product->set_manage_stock(true);
            $product->set_catalog_visibility('hidden');
            $product->save();
        }
        catch (\Exception $e) {
            ZsqSlackNotifications::send('Error on adding WP_Product, SKU = '.$product_data['sku'].': '.$e->getMessage());
            error_log('Error on adding WP_Product, SKU = '.$product_data['sku'].': '.$e->getMessage());
        }
    }
}