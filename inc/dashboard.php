<?php
/**
 * @Author: 						Timi Wahalahti, Digitoimisto Dude Oy (https://dude.fi)
 * @Date:   						2018-11-13 18:06:44
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2018-12-11 12:31:14
 *
 * @package development
 */

/**
 * Remove welcome panel
 *
 * @since  1.7.0
 */
remove_action( 'welcome_panel', 'wp_welcome_panel' );

/**
 *  Remove some boxes from dashboard.
 *
 *  Turn off by using `remove_action( 'wp_dashboard_setup', 'air_helper_clear_admin_dashboard' )`
 *
 *  @since 1.7.0
 */
function air_helper_clear_admin_dashboard() {
	$remove_boxes = array(
		'normal'	=> array(
			'dashboard_right_now',
			'dashboard_recent_comments',
			'dashboard_incoming_links',
			'dashboard_activity',
			'dashboard_plugins',
			'sendgrid_statistics_widget',
			'wpseo-dashboard-overview', // yoast
			'rg_forms_dashboard', // gravity forms
		),
		'side'		=> array(
			'dashboard_quick_press',
			'dashboard_recent_drafts',
			'dashboard_primary',
			'dashboard_secondary',
		),
	);

	// Allow filtering which boxes to hide
	$remove_boxes = apply_filters( 'air_helper_clear_admin_dashboard_boxes', $remove_boxes );

	if ( ! empty( $remove_boxes ) ) {

		// Hide normal boxes
		if ( isset( $remove_boxes['normal'] ) ) {
			foreach ( $remove_boxes['normal'] as $box ) {
				remove_meta_box( $box, 'dashboard', 'normal' );
			}
		}

		// Hide side boxes
		if ( isset( $remove_boxes['side'] ) ) {
			foreach ( $remove_boxes['side'] as $box ) {
				remove_meta_box( $box, 'dashboard', 'side' );
			}
		}
	}
}
add_action( 'wp_dashboard_setup', 'air_helper_clear_admin_dashboard', 99 );

/**
 *  Remoev some notices from dashboard.
 *
 *  Turn off by using `remove_action( 'admin_init', 'air_helper_clean_admin_notices' )`
 *
 *  @since  1.7.1
 */
function air_helper_clean_admin_notices() {
  $remove_notices = array(
    'sg_subscription_widget_admin_notice',
    'eae_page_scanner_notice',
  );

  // Allow filtering which notices to remove
  $remove_notices = apply_filters( 'air_helper_clear_admin_notices', $remove_notices );

  // remove notices
  if ( ! empty( $remove_notices ) ) {
    foreach ( $remove_notices as $notice ) {
      remove_action( 'admin_notices', $notice );
    }
  }

  // GADWP notice is better to remove by updating a option
  if ( ! get_option( 'exactmetrics_tracking_notice' ) ) {
    update_option( 'exactmetrics_tracking_notice', true );
  }
}
add_action( 'admin_init', 'air_helper_clean_admin_notices', 999 );

/**
 *  Add our news and support widget to dashboard. Also make sure that it is always first in
 *  order.
 *
 *  Turn off by using `remove_action( 'wp_dashboard_setup', 'air_helper_admin_dashboard_widgets_setup' )`
 *
 *  @since  1.7.0
 */
function air_helper_admin_dashboard_widgets_setup() {
	// In which servers widget should be visible
	$hostnames_where_visible = apply_filters( 'air_helper_dashboard_widget_show_hostnames', array(
		'craft'	=> true,
		'ghost'	=> true,
	) );

	// Check that widget is allowed to be visible on this site, bail if not
	if ( 'development' !== getenv( 'WP_ENV' ) && ! array_key_exists( php_uname( 'n' ), $hostnames_where_visible ) ) {
		return;
	}

	// Add the dashboard widget
 	wp_add_dashboard_widget(
 		'air-helper-help', // id
 		__( 'Updates from Dude & send support requests', 'air-helper' ), // name
 		'air_helper_admin_dashboard_widget_callback' // callbac
 	);

 	// Alter the widget order and make our widget always first
 	global $wp_meta_boxes;
	$widget = $wp_meta_boxes['dashboard']['normal']['core']['air-helper-help'];
	unset( $wp_meta_boxes['dashboard']['normal']['core']['air-helper-help'] );
	$wp_meta_boxes['dashboard']['side']['core']['air-helper-help'] = $widget;
}
add_action( 'wp_dashboard_setup', 'air_helper_admin_dashboard_widgets_setup' );

/**
 *  Enqueue styles and scripts for dashboard widget.
 *
 *  @since  1.7.0
 */
function air_helper_dashboard_widget_styles() {
	wp_register_style( 'air-helper-dashboard-widget', air_helper_base_url() . '/assets/css/dashboard-widget.css', false, air_helper_version() );
	wp_enqueue_style( 'air-helper-dashboard-widget' );

	wp_register_script( 'air-helper-dashboard-widget', air_helper_base_url() . '/assets/js/dashboard-widget.js', false, air_helper_version() );
	wp_enqueue_script( 'air-helper-dashboard-widget' );
}
add_action( 'admin_enqueue_scripts', 'air_helper_dashboard_widget_styles' );

/**
 *  Output dashboard widget content.
 *
 *  @since  1.7.0
 *  @param  mixed   $post          where widget is shown
 *  @param  array   $callback_args arguments passed into callback function
 *  @return [type]                 [description]
 */
function air_helper_admin_dashboard_widget_callback( $post, $callback_args ) {
	// get data for widget
	$data = _air_helper_admin_dashboard_widget_get_data();

	// if no data, show error message and bail
	if ( empty( $data ) ) {
		echo wpautop( __( 'Error while fetching data.', 'air-helper' ) );
		return;
	} ?>

	<div class="air-helper-help-wrapper">

		<div class="news-wrapper">

			<?php // check if we have sheculed maintenances
			if ( ! empty( $data->maintenances ) ) :
				// show only first maintennace
				$maintenance = $data->maintenances[0];
				$statuspage_url = apply_filters( 'air_helper_dashboard_widget_statuspage_url', 'https://status.dude.fi' );

				if ( isset( $maintenance->start ) &&
					isset( $maintenance->end ) &&
					isset( $maintenance->title ) &&
					isset( $maintenance->desc )
				) :
					// make maintenance start and end times to human readbale string
					$day_str = _air_helper_admin_dashboard_widget_get_time_str( $maintenance->start, $maintenance->end ); ?>
					<div class="maintenance">
						<h3><?php echo sanitize_text_field( $maintenance->title ) ?></h3>

						<p class="time">
							<?php echo $day_str ?>
						</p>

						<?php echo wpautop( wp_kses_post( $maintenance->desc ) ) ?>

						<p class="read-more">
							<a href="<?php echo $statuspage_url ?>" target="_blank"><?php _e( 'Server status on real time', 'air-helper' ) ?> &rarr;</a>
						</p>
					</div>
				<?php endif; // maintennace content isset
			endif; // ! empty( $data->maintenances )

			// check if we have news to show
			if ( ! empty( $data->news ) ) :

				// loop news
				foreach( $data->news as $news ) :

					// if no essential content, skip this and continue to next
					if ( ! isset( $news->content ) ||
						! isset( $news->time ) ||
						! isset( $news->title ) ||
						! isset( $news->content )
					) {
						continue;
					}

					// strip tags from content.
					$content = strip_tags( $news->content, '<a><i><b><br><strong><italic>' ); ?>
					<div class="news">
						<p class="time">
							<?php echo date_i18n( 'j.n.Y H:i', strtotime( $news->time ) ) ?>
						</p>

						<h3><?php echo sanitize_text_field( $news->title ) ?></h3>

						<?php echo wpautop( wp_kses_post( $content ) );

						if ( isset( $news->link ) ) :
							if ( ! empty( $news->link->href ) && ! empty( $news->link->title ) ) : ?>
								<p class="read-more">
									<a href="<?php echo esc_url( $news->link->href ) ?>" target="_blank"><?php echo sanitize_text_field( $news->link->title ) ?> &rarr;</a>
								</p>
							<?php endif;
						endif; ?>

					</div>
				<?php endforeach; // loop news
			endif; // ! empty( $data->news ) ?>
		</div>

		<div class="support-wrapper">
			<div class="support-form">
				<h2><?php _e( 'Send support ticket', 'air-helper' ) ?></h2>

				<p><?php _e( 'You can send a new message to Dudes support with this form, which serves on weekdays 9-17. We will respond to you on next working day at latest.', 'air-helper' ) ?></p>

				<form>
					<label><?php _e( 'Subject', 'air-helper' ) ?></label>
					<input type="text" name="subject" autocomplete="off">

					<label><?php _e( 'Message', 'air-helper' ) ?></label>
					<textarea rows="8" name="content"></textarea>
					<button class="button"><?php _e( 'Send support request', 'air-helper' ) ?></button>

					<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'air_helper_dashboard_widget_ticket_nonce' ) ?>">
				</form>

				<p class="message message-field-error"><?php _e( 'Fill all fields.', 'air-helper' ) ?></p>
				<p class="message message-error"><?php sprintf( __( 'There was an error while sending the form, please contact support by email %s', 'air-helper' ), apply_filters( 'air_helper_dashboard_widget_support_email', 'apuva@dude.fi' ) ) ?></p>
				<p class="message message-success"><?php _e( 'Support request received!', 'air-helper' ) ?></p>
			</div>
		</div>

	</div>
<?php } // end function air_helper_admin_dashboard_widget_help_callback

/**
 *  Get data for the widget from helpwidget api.
 *
 *  @since  1.7.0
 *  @return mixed  false if no data, otherwise data object
 */
function _air_helper_admin_dashboard_widget_get_data() {
	// Get data from transient
	if ( $data = get_site_transient( 'air_helpwidget_data' ) ) {
		return $data;
	}

	// Make api reauest if data isn't in cache
	$api_base = _air_helper_admin_dashboard_widget_get_api_url();
	$api_access_token = _air_helper_admin_dashboard_widget_get_api_key();
	$request = wp_remote_request( "{$api_base}/v1/newsfeed?access_token={$api_access_token}" );

	if ( is_wp_error( $request ) ) {
		return false;
	}

	if ( empty( $request['body'] ) ) {
		return false;
	}

	// decode returned data
	$data = json_decode( $request['body'] );

	// Set data to cache
	set_site_transient( 'air_helpwidget_data', $data, apply_filters( 'air_helper_dashboard_widget_data_cache_lifetime', HOUR_IN_SECONDS ) );

	return $data;
} // end function _air_helper_admin_dashboard_widget_get_data

function _air_helper_admin_dashboard_widget_get_time_str( $start = null, $end = null ) {
	$day_str = '';

	// bail if not time provided
	if ( empty( $start ) ) {
		return $day_str;
	}

	// no end time provided or same as start, make simple string
  if ( empty( $end ) || $start === $end ) {
    $day_str = ucfirst( date_i18n( 'l j.n.Y H:i:s', strtotime( get_date_from_gmt( $start ) ) ) );
  } else {
  	// get months and dates for comparison
    $start_month = date_i18n( 'M', strtotime( get_date_from_gmt( $start ) ) );
    $end_month = date_i18n( 'M', strtotime( get_date_from_gmt( $end ) ) );
    $start_day = date_i18n( 'D', strtotime( get_date_from_gmt( $start ) ) );
    $end_day = date_i18n( 'D', strtotime( get_date_from_gmt( $end ) ) );

    // make str start based on if start month and end month are same or if dates are same
    if ( $start_month !== $end_month ) {
      $day_str = ucfirst( date_i18n( 'l\n\a j.n. -', strtotime( get_date_from_gmt( $start ) ) ) );
    } elseif ( $start_day !== $end_day ) {
      $day_str = ucfirst( date_i18n( 'l\n\a j. -', strtotime( get_date_from_gmt( $start ) ) ) );
    }

    // add end day to str
    $day_str .= ucfirst( date_i18n( 'l\n\a j.n.Y', strtotime( get_date_from_gmt( $end ) ) ) );

    // and times to str
    $day_str .= date_i18n( ' H:i', strtotime( get_date_from_gmt( $start ) ) );
    $day_str .= date_i18n( ' - H:i', strtotime( get_date_from_gmt( $end ) ) );
  }

  // return str
  return $day_str;
} // end function _air_helper_admin_dashboard_widget_get_time_str

/**
 *  Handle new ticket creation ajax call from dashboard widget.
 *
 *  @since  1.7.0
 */
function _air_helper_admin_dashboard_widget_send_ticket() {
	// check nonce
	check_ajax_referer( 'air_helper_dashboard_widget_ticket_nonce', 'ticket_nonce' );

	// get content
	$subject = sanitize_text_field( $_POST['subject'] );
	$content = sanitize_text_field( $_POST['content'] );

	// make post call
	$api_base = _air_helper_admin_dashboard_widget_get_api_url();
	$response = wp_remote_post( "{$api_base}/v1/tickets/new", array(
		'method'	=> 'POST',
		'body'		=> array(
			'access_token'	=> _air_helper_admin_dashboard_widget_get_api_key(),
			'site'					=> get_bloginfo( 'name' ) . ' / ' . get_site_url(),
			'user'					=> wp_get_current_user()->user_email,
			'subject'				=> $subject,
			'content'				=> $content,
		),
	) );

	// send error if post call failed
	if ( is_wp_error( $response ) ) {
		wp_send_json_error();
	}

	// if api didn't return OK message, send error
	if ( 200 !== (int) $response['response']['code'] ) {
		wp_send_json_error();
	}

	// send success message
	wp_send_json_success( array( $response ) );
}
add_action( 'wp_ajax_air_helper_send_ticket', '_air_helper_admin_dashboard_widget_send_ticket' );

/**
 *  Get API base url for dashboard widget API calls.
 *
 *  @since  1.7.0
 *  @return string  api base url
 */
function _air_helper_admin_dashboard_widget_get_api_url() {
	return 'https://api.dude.fi/helpwidget';
}

/**
 *  Get key for dashbard widget API calls.
 *
 *  @since  1.7.0
 *  @return string  api key
 */
function _air_helper_admin_dashboard_widget_get_api_key() {
	return getenv( 'SENDGRID_API_KEY' );
}
