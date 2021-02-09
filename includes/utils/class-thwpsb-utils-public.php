<?php

/**
 * The file that defines the plugin utility functions
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       themehigh.com
 * @since      1.0.0
 *
 * @package    THWPSB
 * @subpackage THWPSB/includes
 */

/**
 * The helper class.
 *
 * This is used to define utility functions.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    THWPSB
 * @subpackage THWPSB/includes
 * @author     ThemeHigh <info@themehigh.com>
 */
class THWPSB_Utils_Public
{
    const SB_MKEY_EXPIRED = 'th_expired';
    const SB_MKEY_PARENT = 'th_parent';

    public static function render_new_sandbox_bar()
    {
        ob_start(); ?>
        <div class="thwpsb-bar">
        <p id="new-wpsb">
            <?php
            $message = esc_html__('Do you need an admin demo?', 'themehigh-wp-sandbox');
            $message .= sprintf(
                ' <a href="%s" class="">%s</a>',
                esc_url('#'),
                esc_html__('Create Sandbox', 'themehigh-wp-sandbox')
            );
            echo $message;
            ?>
        </p>
        </div>

        <!-- The Modal -->
        <div id="th-wpsb-modal" class="modal">

          <!-- Modal content -->
          <div class="modal-content">
            <span class="close">&times;</span>
            <div class="sb-details">
                <div class="preparing">
                    <p>Preparing your WordPress...</p>
                    <div class="sb-loader"></div>
                </div>
                <div class="ready" style="display:none">
                    Redirecting to new WordPress...
                </div>
            </div>
          </div>
        </div>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    public static function render_sandbox_expiry_warning($id){
        $expired  = THWPSB_Utils::get_site_expiry($id);
        $now = THWPSB_Utils::get_current_time();
        $time_diff = THWPSB_Utils::get_time_diff($now, $expired);
        if($time_diff < 1){
            $time_diff = 1;
        }
        ?>
        <div class="thwpsb-countdown-wrapper">
                <p> <span class="close">>></span>
                    <?php
                    printf(
                        /* translators: %s: Name of a city */
                        __( 'Your application will shutdown in <span id="thwpsb-countdown">%s</span> minutes.', 'themehig-wp-sandbox' ),
                        $time_diff
                    );
                    ?>
                </p>
        </div>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }



}
