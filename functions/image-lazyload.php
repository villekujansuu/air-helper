<?php
/**
 * Image lazyload helpers.
 *
 * @Author: 						Timi Wahalahti, Digitoimisto Dude Oy (https://dude.fi)
 * @Date:   						2019-08-07 14:38:34
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2019-11-29 16:46:42
 *
 * @package air-helper
 */

// function to output lazyload divs
if ( ! function_exists( 'image_lazyload_div' ) ) {
	function image_lazyload_div( $image_id = 0, $sizes = array() ) {
		echo get_image_lazyload_div( $image_id, $sizes );
	}
} // end if

// function to get lazyload divs
if ( ! function_exists( 'get_image_lazyload_div' ) ) {
	function get_image_lazyload_div( $image_id = 0, $sizes = array() ) {
		// Get image
		$image_urls = air_helper_get_image_lazyload_sizes( $image_id, $sizes );

		// Check if we have image
		if ( ! $image_urls || ! is_array( $image_urls ) ) {
			return;
		}

		// do preg match for our browser hack
		$browser_hack = false;
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			if ( preg_match( '/Windows Phone|Lumia|iPad|Safari/i', $_SERVER['HTTP_USER_AGENT'] ) ) {
				$browser_hack = true;
			}
		}

		ob_start();

		// div for preview image and data for js to use
		?>
		<div
			class="background-image preview lazyload"
			style="background-image: url('<?php echo $image_urls['tiny']; ?>');"
			data-src="<?php echo $image_urls['big']; ?>"
			data-src-mobile="<?php echo $image_urls['mobile']; ?>"></div>

		<?php // div for full image, hack for browsers that don't support our js well ?>
		<div
			class="background-image full-image"
			<?php if ( $browser_hack ) : ?>
				style="background-image: url('<?php echo $image_urls['big']; ?>');"
			<?php endif; ?>></div>

		<?php // div with full image for browsers without js ?>
		<noscript><div class="background-image full-image" style="background-image: url('<?php echo $image_urls['big']; ?>');"></div></noscript>

		<?php

		return ob_get_clean();
	}
} // end if

// function to output lazyload divs
if ( ! function_exists( 'image_lazyload_tag' ) ) {
	function image_lazyload_tag( $image_id = 0, $sizes = array() ) {
		echo get_image_lazyload_tag( $image_id, $sizes );
	}
} // end if

// function to get lazyload img tag
if ( ! function_exists( 'get_image_lazyload_tag' ) ) {
	function get_image_lazyload_tag( $image_id = 0, $sizes = array() ) {
		// Get image
		$image_urls = air_helper_get_image_lazyload_sizes( $image_id, $sizes );

		// Check if we have image
		if ( ! $image_urls || ! is_array( $image_urls ) ) {
			return;
		}

		// Get dimensions
		$dimensions = air_helper_get_image_lazyload_dimensions( $image_id, $sizes );

		if ( ! $dimensions ) {
			return;
		}

		// get the img tag
		ob_start(); ?>
		<img class="lazyload"
			src="<?php echo $image_urls['tiny']; ?>"
			data-src="<?php echo $image_urls['big']; ?>"
      data-src-mobile="<?php echo $image_urls['mobile']; ?>"
			width="<?php echo $dimensions['width']; ?>" height="<?php echo $dimensions['height']; ?>" />

    <?php

    return ob_get_clean();
	}
} // end if

// function to get proper image sizes
function air_helper_get_image_lazyload_sizes( $image_id = 0, $sizes = array() ) {
	$image_id = intval( $image_id );

  if ( ! $image_id ) {
    return false;
  }

  // Bail if ID is not attachment
  if ( 'attachment' !== get_post_type( $image_id ) ) {
    return false;
  }

  // Default image sizes for use cases
  $default_sizes = array(
    'tiny'    => 'tiny-lazyload-thumbnail',
    'mobile'  => 'large',
    'big'     => 'full',
  );

  $sizes = wp_parse_args( $sizes, $default_sizes );
  $intermediate_sizes = get_intermediate_image_sizes();

  // Loop use cases to get image url for it
  foreach ( $sizes as $size_for => $size ) {
    // Check that asked image size exists and fallback to full size
    if ( ! in_array( $size, $intermediate_sizes ) ) {
      $size = 'full';
    }

    // Get image url
    $url = wp_get_attachment_image_url( $image_id, $size );

    // Try to get thumbnail
    if ( ! $url && 'tiny-lazyload-thumbnail' === $size ) {
      $url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
    }

    // Get image url
    $url = wp_get_attachment_image_url( $image_id, $size );

    // For some reason, we don't have image so unset the size
    if ( ! $url ) {
      unset( $sizes[ $size_for ] );
    }

    // Replace the image size name with url to image
    $sizes[ $size_for ] = esc_url( $url );
  }

  // Check that all required default images exists
  if ( ! array_key_exists( 'tiny', $sizes ) ) {
    return false;
  }

  if ( ! array_key_exists( 'mobile', $sizes ) ) {
    return false;
  }

  if ( ! array_key_exists( 'big', $sizes ) ) {
    return false;
  }

  // Fallback to thumbnail if tiny is same as big
  if ( $sizes['tiny'] === $sizes['big'] ) {
    $url = wp_get_attachment_image_url( $image_id, 'thumbnail' );

    if ( $url ) {
      $sizes['tiny'] = esc_url( $url );
    }
  }

  return $sizes;
} // end function air_helper_get_image_lazyload_sizes

function air_helper_get_image_lazyload_dimensions( $image_id = 0, $sizes = array() ) {
	$image_id = intval( $image_id );

	if ( ! $image_id ) {
		return false;
	}

	// Bail if ID is not attachment
	if ( 'attachment' !== get_post_type( $image_id ) ) {
		return false;
	}

	// Default image sizes for use cases
	$default_sizes = array(
		'tiny'		=> 'tiny-lazyload-thumbnail',
		'mobile'	=> 'large',
		'big'			=> 'full',
	);

	$sizes = wp_parse_args( $sizes, $default_sizes );

	$dimensions = wp_get_attachment_image_src( $image_id, $sizes['big'] );

	if ( ! $dimensions ) {
		return false;
	}

	return array(
		'width'		=> $dimensions[1],
		'height'	=> $dimensions[2],
	);
}