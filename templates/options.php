<?php $showtab = esc_attr($_REQUEST['show']);
if ($showtab == "") $showtab = null; ?>
<div class="wrap zspl-wrap">
    <div class="zspl-header">
        <img src="<?php echo ZSQ_CRM_PLUGIN_ASSETS; ?>assets/img/z-squared-logo.png">
        <h1>ZSquared Connector to Zoho CRM</h1>
    </div>

    <div class="zspl-tab-parent">
        <div class="zspl-tab-wrapper">
            <a href="options-general.php?page=zsq_crm.php&show=settings" id="zspl-nav-settings"
               class="zspl-nav<?php echo $showtab == 'settings' || is_null($showtab) ? " active" : ''; ?>"
               onclick="zsq_switch_tabs('settings')">Configuration</a>
            <a href="options-general.php?page=zsq_crm.php&show=inventory" id="zspl-nav-inventory" class="zspl-nav<?php echo $showtab == 'inventory' ? " active" : ''; ?>"
               onclick="zsq_switch_tabs('inventory')">Inventory Management</a>
            <a href="options-general.php?page=zsq_crm.php&show=replay" id="zspl-nav-replay" class="zspl-nav<?php echo $showtab == 'replay' ? " active" : ''; ?>"
               onclick="zsq_switch_tabs('replay')">Replay Sales Orders</a>
            <a href="options-general.php?page=zsq_crm.php&show=tax" id="zspl-nav-tax" class="zspl-nav<?php echo $showtab == 'tax' ? " active" : ''; ?>"
               onclick="zsq_switch_tabs('tax')">Tax Settings</a>
            <a href="options-general.php?page=zsq_crm.php&show=solist" id="zspl-nav-solist" class="zspl-nav<?php echo $showtab == 'solist' ? " active" : ''; ?>"
               onclick="zsq_switch_tabs('solist')">Activity Log</a>
        </div>
    </div>

    <div class="zspl-tab"
         id="zspl-tab-settings"<?php echo $showtab == 'settings' || is_null($showtab) ? "" : ' style="display:none;"'; ?>>
        <div class="zspl-conn-info">
            <?php if (strlen(get_option('zsq_crm_api_key')) > 0) : ?>
                <h2>Login to the ZSquared service <a target="_blank"
                                                     href="<?php echo ZSQ_CRM_APP_HOME; ?>dcf/login">here</a> to see
                    more information about your connection.</h2>
            <?php else : ?>
                <h2>To get started, please sign up for a Connector API Key on the <a
                            href="<?php echo ZSQ_CRM_APP_HOME; ?>dcf/signup?returnpath=<?php echo esc_url('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">ZSquared
                        Data Connection Service.</a></h2>
            <?php endif; ?>
        </div>

        <div class="zspl-status-parent">
            <div class="zspl-conn-status">
                <h2 class="zspl-title-white">Current ZSquared server status:
                    <span><?php echo esc_html($server); ?></span></h2>
            </div>
            <div class="zspl-account-status">
                <h2 class="zspl-title-white">Current account status: <span><?php echo esc_html($status); ?></span></h2>
            </div>
        </div>

        <form class="zspl-form" method="post" action="options-general.php?page=zsq_crm.php&show=settings">
            <input type="hidden" name="zsq_crm_setting_supdate" value="1"/>
            <?php
            settings_fields('zsq_crm_fields');
            do_settings_sections('zsq_crm_fields');
            settings_fields('zsq_crm_slack_fields');
            do_settings_sections('zsq_crm_slack_fields');
            submit_button();
            ?>
        </form>
    </div>

    <div class="zspl-tab" id="zspl-tab-inventory"<?php echo $showtab == 'inventory' ? "" : ' style="display:none;"'; ?>>
        <form class="zspl-form zspl-sync-settings-form" method="post"
              action="options-general.php?page=zsq_crm.php&show=inventory">
            <input type="hidden" name="zsq_crm_setting_supdate" value="1"/>
            <?php
            settings_fields('zsq_crm_inv_sync');
            do_settings_sections('zsq_crm_inv_sync');
            submit_button("Save Sync Settings");
            ?>
        </form>
        <div class="zspl-sync-settings">
            <h2>Add/change CRM Items (Optional)</h2>
            <p>Use the button below to add/update all products from Zoho CRM to Woocommerce.</p>
            <p><strong>
                    Note: Products will be created if their SKU is not already assigned to an existing Woocommerce
                    Product;
                    otherwise the Product price and quantity will be updated if you have chosen to keep them synced
                    using the settings above.
                </strong>
            </p>
            <p>
                <button class="button button-primary" id="zsq_crm_manual_sync">Add/Change now</button>
            </p>
        </div>
        <p id="zsq_crm_manual_sync_message"></p>
    </div>


    <div class="zspl-tab" id="zspl-tab-replay"<?php echo $showtab == 'replay' ? "" : ' style="display:none;"'; ?>>
        <div class="zspl-manual-sync">
            <h2>Replay order submission</h2>
            <p>Sometimes an order may not make it into Zoho for a variety of reasons. When this happens, you can replay
                the order submission to try again.</p>
            <p>Enter the Woocommerce sales order number to attempt to send it again to Zoho CRM</p>
            <form method="post" action="options-general.php?page=zsq_crm.php&replay=1&show=replay">
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row">Sales Order ID Number</th>
                        <td><input name="zsq_crm_sales_order_id" id="zsq_crm_sales_order_id" type="text"
                                   placeholder="XXX">
                        </td>
                    </tr>
                    <tr>
                        <td class="remove-padding"><p class="submit"><input type="submit" name="submit" id="submit"
                                                                            class="button button-primary"
                                                                            value="Replay"></p></td>
                    </tr>
                    </tbody>
                </table>
            </form>
        </div>
    </div>

    <div class="zspl-tab" id="zspl-tab-tax"<?php echo $showtab == 'tax' ? "" : ' style="display:none;"'; ?>>
        <div class="zspl-tax-mapping">
            <h2>Tax Information Mapping</h2>
            <?php if (empty($woo_taxes)) : ?>
                <p>No Woocommerce or Zoho tax settings found; sales orders will be transferred to Zoho CRM without any
                    tax information applied.</p>
            <?php elseif (!empty($woo_taxes) && empty($ex_taxes)) : ?>
                <p>Woocommerce tax settings detected but no Zoho CRM taxes detected. Please check your tax settings in
                    Zoho CRM, and confirm that your ZSquared API key has been added here. If you have set your taxes in
                    Woocommerce and Zoho CRM and you still see this message, please contact the ZSquared team.</p>
            <?php elseif (empty($woo_taxes) && !empty($ex_taxes)) : ?>
                <p>No Woocommerce tax settings detected, but Zoho CRM taxes detected. Please check your tax settings in
                    Woocommerce. If you have set your taxes in Woocommerce and Zoho CRM and you still see this message,
                    please contact the ZSquared team.</p>
            <?php else : ?>
                <p>Zoho CRM may use tax information that must be mapped to the same taxes in Woocommerce. Please
                    review below:</p>
                <form method="post" action="options-general.php?page=zsq_crm.php&taxupdate=1&show=tax">
                    <table class="form-table">
                        <thead>
                        <tr class="zspl-dekstop-subtitles">
                            <th>Woocommerce Tax Entry</th>
                            <td>External Tax Entry</td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($woo_taxes as $w) :
                            $value = get_option('zsq_crm_ex_to_woo_tax_map_' . $w->tax_rate_id);
                            $tax_name = $w->tax_rate_country . "-" . $w->tax_rate_state . " (" . number_format($w->tax_rate, 2) . "%) " . $w->tax_rate_name;
                            ?>
                            <tr>
                                <th><?php echo esc_html($tax_name); ?></th>
                                <td><select name="zsq_crm_ex_to_woo_tax_map_<?php echo esc_attr($w->tax_rate_id); ?>">
                                        <option value="0">-</option>
                                        <?php foreach ($ex_taxes as $z) : ?>
                                            <option value="<?php echo esc_attr($z['ex_tax_id']); ?>"<?php if ($z['ex_tax_id'] == $value) : ?> selected<?php endif; ?>><?php echo esc_html($z['tax_name'] . " (" . number_format($z['ex_tax_percent'], 2) . "%)"); ?></option>
                                        <?php endforeach; ?>
                                    </select></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p><input type="submit" name="tax-submit" id="tax-submit" class="button button-primary"
                              value="Save Tax Settings"></p>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <div class="zspl-tab" id="zspl-tab-solist"<?php echo $showtab == 'solist' ? "" : ' style="display:none;"'; ?>>
        <h2>Recently processed sales orders</h2>
        <?php if(!empty($orders)) : ?>
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
            <tr>
                <th scope="col" class="manage-column">#</th>
                <th scope="col" class="manage-column">Processed at</th>
                <th scope="col" class="manage-column">Customer</th>
                <th scope="col" class="manage-column">Total</th>
                <th scope="col" class="manage-column">Status</th>
            </tr>
            </thead>

            <tbody id="the-list">
            <?php foreach ($orders as $o) : ?>
                <tr class="type-post format-standard">
                    <td><?php echo esc_html($o['so_number']); ?></td>
                    <td><?php echo esc_html($o['time']); ?></td>
                    <td><?php echo esc_html($o['customer']); ?></td>
                    <td><?php echo esc_html($o['total']); ?></td>
                    <td><?php echo esc_html($o['status']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>

            <tfoot>
            <tr>
                <th scope="col" class="manage-column">#</th>
                <th scope="col" class="manage-column">Processed at</th>
                <th scope="col" class="manage-column">Customer</th>
                <th scope="col" class="manage-column">Total</th>
                <th scope="col" class="manage-column">Status</th>
            </tr>
            </tfoot>
        </table>
        <?php else : ?>
        <p>No orders found</p>
        <?php endif; ?>
    </div>
</div>
