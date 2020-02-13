<?php
/**
 * @Author: Timi Wahalahti
 * @Date:   2020-02-12 14:29:27
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2020-02-13 15:25:08
 *
 * @package air-helper
 */

add_action( 'admin_enqueue_scripts', 'air_helper_enqueue_helpscout_beacon' );
function air_helper_enqueue_helpscout_beacon() {
  // In which servers widget should be visible
  $hostnames_where_visible = apply_filters( 'air_helper_dashboard_widget_show_hostnames', [
    'craft' => true,
    'ghost' => true,
  ] );

  // Check that widget is allowed to be visible on this site, bail if not
  if ( 'development' !== getenv( 'WP_ENV' ) && ! array_key_exists( php_uname( 'n' ), $hostnames_where_visible ) ) {
    return;
  }

  wp_enqueue_script( 'helpscout-beacon', air_helper_base_url() . '/assets/js/helpscout-beacon.js', [], '2.0.0', true );

  $user_info = get_userdata( get_current_user_id() );
  wp_localize_script( 'helpscout-beacon', 'airhelperHelpscout', [
    'color'         => '#4d4aff',
    'userEmail'     => $user_info->user_email,
    'userName'      => $user_info->user_nicename,
    'site'          => get_bloginfo( 'name' ),
    'siteUrl'       => get_site_url(),
    'beaconId'      => getenv( 'HS_BEACON_ID' ),
    'signature'     => hash_hmac(
      'sha256',
      $user_info->user_email,
      getenv( 'NONCE_SALT' )
    ),
    'translations'  => [
      'prefilledSubject'          => __( 'Help request', 'air-helper' ),
      'text'                      => __( 'Do you need help?', 'air-helper' ),
      'sendAMessage'              => __( 'Dude user support', 'air-helper' ),
      'howCanWeHelp'              => __( 'How can we help?', 'air-helper' ),
      'responseTime'              => __( 'Our support team will respond to you on next working day at latest', 'air-helper' ),
      'continueEditing'           => __( 'Continue writing…', 'air-helper' ),
      'lastUpdated'               => __( 'Last updated', 'air-helper' ),
      'you'                       => __( 'You', 'air-helper' ),
      'nameLabel'                 => __( 'Name', 'air-helper' ),
      'subjectLabel'              => __( 'Subject', 'air-helper' ),
      'emailLabel'                => __( 'Email address', 'air-helper' ),
      'messageLabel'              => __( 'How can we help?', 'air-helper' ),
      'messageSubmitLabel'        => __( 'Send support request', 'air-helper' ),
      'next'                      => __( 'Next', 'air-helper' ),
      'weAreOnIt'                 => __( 'We’re on it!', 'air-helper' ),
      'messageConfirmationText'   => __( 'You’ll receive an reply shortly.', 'air-helper' ),
      'wereHereToHelp'            => __( 'Dude user support', 'air-helper' ),
      'viewAndUpdateMessage'      => __( 'You can view and update your message in', 'air-helper' ),
      'whatMethodWorks'           => __( 'Our support team will respond to you on next working day at latest', 'air-helper' ),
      'previousMessages'          => __( 'Previous Conversations', 'air-helper' ),
      'messageButtonLabel'        => __( 'Email', 'air-helper' ),
      'noTimeToWaitAround'        => __( 'Send message to our support team', 'air-helper' ),
      'addReply'                  => __( 'Add a reply', 'air-helper' ),
      'addYourMessageHere'        => __( 'Add your message here...', 'air-helper' ),
      'sendMessage'               => __( 'Send message', 'air-helper' ),
      'received'                  => __( 'Received', 'air-helper' ),
      'waitingForAnAnswer'        => __( 'Waiting for an answer', 'air-helper' ),
      'previousMessageErrorText'  => __( 'There was a problem retrieving this message. Please double-check your Internet connection and try again.', 'air-helper' ),
      'justNow'                   => __( 'Just Now', 'air-helper' ),
    ],
  ] );
}