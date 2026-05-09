<?php
/**
 * Custom wp-admin management shell for the hybrid WordPress + backend API setup.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TAM_BACKEND_ADMIN_TOKEN_META' ) ) {
	define( 'TAM_BACKEND_ADMIN_TOKEN_META', '_tam_backend_admin_token' );
}

if ( ! defined( 'TAM_BACKEND_ADMIN_USER_META' ) ) {
	define( 'TAM_BACKEND_ADMIN_USER_META', '_tam_backend_admin_user' );
}

if ( ! defined( 'TAM_BACKEND_ADMIN_FLASH_META' ) ) {
	define( 'TAM_BACKEND_ADMIN_FLASH_META', '_tam_backend_admin_flash' );
}

if ( ! defined( 'TAM_BACKEND_ADMIN_MENU_SLUG' ) ) {
	define( 'TAM_BACKEND_ADMIN_MENU_SLUG', 'tam-admin-dashboard' );
}

/**
 * Return page definitions for the custom admin shell.
 *
 * @return array<string, array<string, string|bool>>
 */
function tam_backend_admin_pages() {
	return array(
		'tam-admin-dashboard' => array(
			'label'      => __( 'Dashboard', 'travel-agency-modern' ),
			'menu_title' => __( 'ADN Admin', 'travel-agency-modern' ),
			'icon'       => 'dashicons-chart-pie',
			'protected'  => true,
		),
		'tam-admin-tours'     => array(
			'label'      => __( 'Tours', 'travel-agency-modern' ),
			'menu_title' => __( 'Tours', 'travel-agency-modern' ),
			'icon'       => 'dashicons-location-alt',
			'protected'  => true,
		),
		'tam-admin-bookings'  => array(
			'label'      => __( 'Bookings', 'travel-agency-modern' ),
			'menu_title' => __( 'Bookings', 'travel-agency-modern' ),
			'icon'       => 'dashicons-calendar-alt',
			'protected'  => true,
		),
		'tam-admin-users'     => array(
			'label'      => __( 'Users', 'travel-agency-modern' ),
			'menu_title' => __( 'Users', 'travel-agency-modern' ),
			'icon'       => 'dashicons-groups',
			'protected'  => true,
		),
		'tam-admin-reviews'   => array(
			'label'      => __( 'Reviews', 'travel-agency-modern' ),
			'menu_title' => __( 'Reviews', 'travel-agency-modern' ),
			'icon'       => 'dashicons-star-filled',
			'protected'  => true,
		),
		'tam-admin-payments'  => array(
			'label'      => __( 'Payments', 'travel-agency-modern' ),
			'menu_title' => __( 'Payments', 'travel-agency-modern' ),
			'icon'       => 'dashicons-money-alt',
			'protected'  => true,
		),
		'tam-admin-sync'      => array(
			'label'      => __( 'Sync Tours', 'travel-agency-modern' ),
			'menu_title' => __( 'Sync Tours', 'travel-agency-modern' ),
			'icon'       => 'dashicons-update',
			'protected'  => true,
		),
		'tam-admin-connect'   => array(
			'label'      => __( 'Backend Connect', 'travel-agency-modern' ),
			'menu_title' => __( 'Backend Connect', 'travel-agency-modern' ),
			'icon'       => 'dashicons-admin-network',
			'protected'  => false,
		),
	);
}

/**
 * Return page URL for the custom admin shell.
 *
 * @param string $slug Page slug.
 * @param array  $args Extra query args.
 * @return string
 */
function tam_backend_admin_page_url( $slug, $args = array() ) {
	$query_args = array_merge(
		array(
			'page' => $slug,
		),
		$args
	);

	return add_query_arg( $query_args, admin_url( 'admin.php' ) );
}

/**
 * Redirect to a custom admin page and stop execution.
 *
 * @param string $slug Page slug.
 * @param array  $args Extra query args.
 * @return void
 */
function tam_backend_admin_redirect( $slug, $args = array() ) {
	wp_safe_redirect( tam_backend_admin_page_url( $slug, $args ) );
	exit;
}

/**
 * Redirect to a custom admin page with a flash notice.
 *
 * @param string $slug    Page slug.
 * @param string $message Notice message.
 * @param string $type    success|warning|error|info.
 * @param array  $args    Extra query args.
 * @return void
 */
function tam_backend_admin_redirect_notice( $slug, $message, $type = 'success', $args = array() ) {
	$args['tam_notice']      = (string) $message;
	$args['tam_notice_type'] = sanitize_key( $type );

	tam_backend_admin_redirect( $slug, $args );
}

/**
 * Return the current custom admin page slug.
 *
 * @return string
 */
function tam_backend_admin_current_page() {
	return isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
}

/**
 * Whether the current screen belongs to the custom admin shell.
 *
 * @return bool
 */
function tam_backend_admin_is_current_screen() {
	return array_key_exists( tam_backend_admin_current_page(), tam_backend_admin_pages() );
}

/**
 * Return the current WP admin user ID.
 *
 * @return int
 */
function tam_backend_admin_current_user_id() {
	return get_current_user_id() ? (int) get_current_user_id() : 0;
}

/**
 * Persist a backend admin flash payload in user meta.
 *
 * @param string $key   Flash key.
 * @param mixed  $value Flash value.
 * @return void
 */
function tam_backend_admin_set_flash( $key, $value ) {
	$user_id = tam_backend_admin_current_user_id();

	if ( $user_id <= 0 ) {
		return;
	}

	$store = get_user_meta( $user_id, TAM_BACKEND_ADMIN_FLASH_META, true );
	$store = is_array( $store ) ? $store : array();
	$store[ sanitize_key( $key ) ] = $value;

	update_user_meta( $user_id, TAM_BACKEND_ADMIN_FLASH_META, $store );
}

/**
 * Pull and delete a backend admin flash payload from user meta.
 *
 * @param string $key     Flash key.
 * @param mixed  $default Default value.
 * @return mixed
 */
function tam_backend_admin_pull_flash( $key, $default = null ) {
	$user_id = tam_backend_admin_current_user_id();

	if ( $user_id <= 0 ) {
		return $default;
	}

	$store = get_user_meta( $user_id, TAM_BACKEND_ADMIN_FLASH_META, true );
	$store = is_array( $store ) ? $store : array();
	$key   = sanitize_key( $key );

	if ( ! array_key_exists( $key, $store ) ) {
		return $default;
	}

	$value = $store[ $key ];
	unset( $store[ $key ] );

	if ( empty( $store ) ) {
		delete_user_meta( $user_id, TAM_BACKEND_ADMIN_FLASH_META );
	} else {
		update_user_meta( $user_id, TAM_BACKEND_ADMIN_FLASH_META, $store );
	}

	return $value;
}

/**
 * Store the backend admin auth session for the current WP admin user.
 *
 * @param string $token Backend admin token.
 * @param array  $admin Backend admin profile.
 * @return void
 */
function tam_backend_admin_set_session( $token, $admin ) {
	$user_id = tam_backend_admin_current_user_id();

	if ( $user_id <= 0 ) {
		return;
	}

	update_user_meta( $user_id, TAM_BACKEND_ADMIN_TOKEN_META, trim( (string) $token ) );
	update_user_meta( $user_id, TAM_BACKEND_ADMIN_USER_META, is_array( $admin ) ? $admin : array() );
}

/**
 * Clear the backend admin auth session for the current WP admin user.
 *
 * @return void
 */
function tam_backend_admin_clear_session() {
	$user_id = tam_backend_admin_current_user_id();

	if ( $user_id <= 0 ) {
		return;
	}

	delete_user_meta( $user_id, TAM_BACKEND_ADMIN_TOKEN_META );
	delete_user_meta( $user_id, TAM_BACKEND_ADMIN_USER_META );
}

/**
 * Return the backend admin token for the current WP admin user.
 *
 * @return string
 */
function tam_backend_admin_get_token() {
	$user_id = tam_backend_admin_current_user_id();

	if ( $user_id <= 0 ) {
		return '';
	}

	return trim( (string) get_user_meta( $user_id, TAM_BACKEND_ADMIN_TOKEN_META, true ) );
}

/**
 * Return a sanitized backend admin profile.
 *
 * @return array|null
 */
function tam_backend_admin_get_profile() {
	$user_id = tam_backend_admin_current_user_id();

	if ( $user_id <= 0 ) {
		return null;
	}

	$profile = get_user_meta( $user_id, TAM_BACKEND_ADMIN_USER_META, true );

	if ( ! is_array( $profile ) || empty( $profile ) ) {
		return null;
	}

	return array(
		'id'    => isset( $profile['id'] ) ? (int) $profile['id'] : 0,
		'name'  => isset( $profile['name'] ) ? sanitize_text_field( $profile['name'] ) : '',
		'email' => isset( $profile['email'] ) ? sanitize_email( $profile['email'] ) : '',
		'role'  => isset( $profile['role'] ) ? sanitize_text_field( $profile['role'] ) : '',
	);
}

/**
 * Whether the current WP admin user has an active backend admin session.
 *
 * @return bool
 */
function tam_backend_admin_is_connected() {
	return (bool) tam_backend_admin_get_token() && (bool) tam_backend_admin_get_profile();
}

/**
 * Build a normalized response array for admin helper failures.
 *
 * @param string $message Error message.
 * @param int    $status  HTTP-like status code.
 * @param array  $errors  Field errors.
 * @return array
 */
function tam_backend_admin_error_response( $message, $status = 0, $errors = array() ) {
	return array(
		'success' => false,
		'status'  => (int) $status,
		'message' => (string) $message,
		'data'    => array(),
		'errors'  => is_array( $errors ) ? $errors : array(),
	);
}

/**
 * Normalize and trim newline-delimited text to an array of strings.
 *
 * @param string $value Raw textarea value.
 * @return string[]
 */
function tam_backend_admin_parse_lines( $value ) {
	$rows = preg_split( '/\r\n|\r|\n/', (string) $value );
	$rows = is_array( $rows ) ? $rows : array();

	return array_values(
		array_filter(
			array_map( 'trim', $rows ),
			static function ( $row ) {
				return '' !== $row;
			}
		)
	);
}

/**
 * Join a string array into a textarea-safe value.
 *
 * @param array $items Item list.
 * @return string
 */
function tam_backend_admin_format_lines( $items ) {
	$lines = array();

	foreach ( is_array( $items ) ? $items : array() as $item ) {
		$item = is_string( $item ) ? trim( $item ) : '';

		if ( '' !== $item ) {
			$lines[] = $item;
		}
	}

	return implode( "\n", $lines );
}

/**
 * Parse textarea rows using pipe-delimited columns.
 *
 * @param string   $value Raw textarea value.
 * @param string[] $keys  Keys for the output array.
 * @return array[]
 */
function tam_backend_admin_parse_pipe_rows( $value, $keys ) {
	$rows = array();

	foreach ( tam_backend_admin_parse_lines( $value ) as $line ) {
		$columns = array_map( 'trim', explode( '|', $line ) );
		$item    = array();

		foreach ( $keys as $index => $key ) {
			$item[ $key ] = isset( $columns[ $index ] ) ? $columns[ $index ] : '';
		}

		$rows[] = $item;
	}

	return $rows;
}

/**
 * Format structured rows into pipe-delimited textarea content.
 *
 * @param array    $items Structured rows.
 * @param string[] $keys  Keys to serialize.
 * @return string
 */
function tam_backend_admin_format_pipe_rows( $items, $keys ) {
	$lines = array();

	foreach ( is_array( $items ) ? $items : array() as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$columns = array();

		foreach ( $keys as $key ) {
			$columns[] = isset( $item[ $key ] ) ? trim( (string) $item[ $key ] ) : '';
		}

		if ( implode( '', $columns ) !== '' ) {
			$lines[] = implode( ' | ', $columns );
		}
	}

	return implode( "\n", $lines );
}

/**
 * Return a default tour form payload.
 *
 * @return array
 */
function tam_backend_admin_default_tour_form() {
	return array(
		'title'             => '',
		'description'       => '',
		'location'          => '',
		'duration'          => '',
		'durationText'      => '',
		'price'             => '',
		'maxPeople'         => '',
		'status'            => 'Draft',
		'transport'         => '',
		'departureNote'     => '',
		'tagline'           => '',
		'badge'             => '',
		'season'            => '',
		'departureSchedule' => '',
		'meetingPoint'      => '',
		'curatorNote'       => '',
		'curatorName'       => '',
		'includes_text'     => '',
		'promise_text'      => '',
		'overview_text'     => '',
		'highlights_text'   => '',
		'itinerary_text'    => '',
		'imageUrl'          => '',
	);
}

/**
 * Hydrate the tour form from a backend payload.
 *
 * @param array $tour Backend tour payload.
 * @return array
 */
function tam_backend_admin_tour_form_from_tour( $tour ) {
	$form = tam_backend_admin_default_tour_form();

	if ( ! is_array( $tour ) ) {
		return $form;
	}

	$form['title']             = isset( $tour['title'] ) ? (string) $tour['title'] : '';
	$form['description']       = isset( $tour['description'] ) ? (string) $tour['description'] : '';
	$form['location']          = isset( $tour['location'] ) ? (string) $tour['location'] : '';
	$form['duration']          = isset( $tour['duration'] ) ? (string) $tour['duration'] : '';
	$form['durationText']      = isset( $tour['durationText'] ) ? (string) $tour['durationText'] : '';
	$form['price']             = isset( $tour['price'] ) ? (string) $tour['price'] : '';
	$form['maxPeople']         = isset( $tour['maxPeople'] ) ? (string) $tour['maxPeople'] : '';
	$form['status']            = isset( $tour['status'] ) ? (string) $tour['status'] : 'Draft';
	$form['transport']         = isset( $tour['transport'] ) ? (string) $tour['transport'] : '';
	$form['departureNote']     = isset( $tour['departureNote'] ) ? (string) $tour['departureNote'] : '';
	$form['tagline']           = isset( $tour['tagline'] ) ? (string) $tour['tagline'] : '';
	$form['badge']             = isset( $tour['badge'] ) ? (string) $tour['badge'] : '';
	$form['season']            = isset( $tour['season'] ) ? (string) $tour['season'] : '';
	$form['departureSchedule'] = isset( $tour['departureSchedule'] ) ? (string) $tour['departureSchedule'] : '';
	$form['meetingPoint']      = isset( $tour['meetingPoint'] ) ? (string) $tour['meetingPoint'] : '';
	$form['curatorNote']       = isset( $tour['curatorNote'] ) ? (string) $tour['curatorNote'] : '';
	$form['curatorName']       = isset( $tour['curatorName'] ) ? (string) $tour['curatorName'] : '';
	$form['includes_text']     = tam_backend_admin_format_lines( isset( $tour['includes'] ) ? $tour['includes'] : array() );
	$form['promise_text']      = tam_backend_admin_format_lines( isset( $tour['promiseItems'] ) ? $tour['promiseItems'] : array() );
	$form['overview_text']     = tam_backend_admin_format_pipe_rows( isset( $tour['overviewCards'] ) ? $tour['overviewCards'] : array(), array( 'title', 'description', 'icon' ) );
	$form['highlights_text']   = tam_backend_admin_format_pipe_rows( isset( $tour['highlights'] ) ? $tour['highlights'] : array(), array( 'title', 'description', 'icon' ) );
	$form['itinerary_text']    = tam_backend_admin_format_pipe_rows( isset( $tour['itinerary'] ) ? $tour['itinerary'] : array(), array( 'label', 'title', 'description' ) );
	$form['imageUrl']          = isset( $tour['imageUrl'] ) ? (string) $tour['imageUrl'] : '';

	return $form;
}

/**
 * Build a backend tour write payload from submitted form data.
 *
 * @param array $input Submitted form data.
 * @return array
 */
function tam_backend_admin_build_tour_payload( $input ) {
	$input = is_array( $input ) ? $input : array();

	$overview_rows   = tam_backend_admin_parse_pipe_rows( isset( $input['overview_text'] ) ? $input['overview_text'] : '', array( 'title', 'description', 'icon' ) );
	$highlight_rows  = tam_backend_admin_parse_pipe_rows( isset( $input['highlights_text'] ) ? $input['highlights_text'] : '', array( 'title', 'description', 'icon' ) );
	$itinerary_rows  = tam_backend_admin_parse_pipe_rows( isset( $input['itinerary_text'] ) ? $input['itinerary_text'] : '', array( 'label', 'title', 'description' ) );
	$sanitize_rows   = static function ( $rows, $required_keys ) {
		$items = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$item      = array();
			$has_value = false;

			foreach ( $required_keys as $key ) {
				$value       = isset( $row[ $key ] ) ? sanitize_text_field( $row[ $key ] ) : '';
				$item[ $key ] = $value;
				$has_value    = $has_value || '' !== $value;
			}

			if ( $has_value ) {
				$items[] = $item;
			}
		}

		return $items;
	};

	return array(
		'title'             => isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : '',
		'description'       => isset( $input['description'] ) ? sanitize_textarea_field( $input['description'] ) : '',
		'location'          => isset( $input['location'] ) ? sanitize_text_field( $input['location'] ) : '',
		'duration'          => isset( $input['duration'] ) ? sanitize_text_field( $input['duration'] ) : '',
		'durationText'      => isset( $input['durationText'] ) ? sanitize_text_field( $input['durationText'] ) : '',
		'price'             => isset( $input['price'] ) ? sanitize_text_field( $input['price'] ) : '',
		'maxPeople'         => isset( $input['maxPeople'] ) ? sanitize_text_field( $input['maxPeople'] ) : '',
		'status'            => isset( $input['status'] ) ? sanitize_text_field( $input['status'] ) : 'Draft',
		'transport'         => isset( $input['transport'] ) ? sanitize_text_field( $input['transport'] ) : '',
		'departureNote'     => isset( $input['departureNote'] ) ? sanitize_text_field( $input['departureNote'] ) : '',
		'tagline'           => isset( $input['tagline'] ) ? sanitize_text_field( $input['tagline'] ) : '',
		'badge'             => isset( $input['badge'] ) ? sanitize_text_field( $input['badge'] ) : '',
		'season'            => isset( $input['season'] ) ? sanitize_text_field( $input['season'] ) : '',
		'departureSchedule' => isset( $input['departureSchedule'] ) ? sanitize_text_field( $input['departureSchedule'] ) : '',
		'meetingPoint'      => isset( $input['meetingPoint'] ) ? sanitize_text_field( $input['meetingPoint'] ) : '',
		'curatorNote'       => isset( $input['curatorNote'] ) ? sanitize_textarea_field( $input['curatorNote'] ) : '',
		'curatorName'       => isset( $input['curatorName'] ) ? sanitize_text_field( $input['curatorName'] ) : '',
		'includes'          => array_map( 'sanitize_text_field', tam_backend_admin_parse_lines( isset( $input['includes_text'] ) ? $input['includes_text'] : '' ) ),
		'promiseItems'      => array_map( 'sanitize_text_field', tam_backend_admin_parse_lines( isset( $input['promise_text'] ) ? $input['promise_text'] : '' ) ),
		'overviewCards'     => $sanitize_rows( $overview_rows, array( 'title', 'description', 'icon' ) ),
		'highlights'        => $sanitize_rows( $highlight_rows, array( 'title', 'description', 'icon' ) ),
		'itinerary'         => $sanitize_rows( $itinerary_rows, array( 'label', 'title', 'description' ) ),
		'imageUrl'          => isset( $input['imageUrl'] ) ? esc_url_raw( $input['imageUrl'] ) : '',
	);
}

/**
 * Return the first matching mirrored WordPress tour post for an API tour ID.
 *
 * @param int $api_tour_id Backend tour ID.
 * @return int
 */
function tam_backend_admin_get_mirror_post_id( $api_tour_id ) {
	$posts = get_posts(
		array(
			'post_type'      => 'tour',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'trash' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_tam_api_tour_id',
			'meta_value'     => (int) $api_tour_id,
			'no_found_rows'  => true,
		)
	);

	return ! empty( $posts[0] ) ? (int) $posts[0] : 0;
}

/**
 * Perform a JSON request against the backend admin API.
 *
 * @param string $method HTTP method.
 * @param string $path   API path relative to /api.
 * @param array  $args   Optional request arguments.
 * @return array
 */
function tam_backend_admin_request( $method, $path, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'body'              => null,
			'query'             => array(),
			'token'             => '',
			'require_auth'      => true,
			'clear_on_unauthorised' => true,
		)
	);

	$token = $args['token'] ? trim( (string) $args['token'] ) : tam_backend_admin_get_token();

	if ( $args['require_auth'] && '' === $token ) {
		return tam_backend_admin_error_response( __( 'Bạn chưa kết nối backend admin.', 'travel-agency-modern' ), 401 );
	}

	$request_args = array(
		'query' => is_array( $args['query'] ) ? $args['query'] : array(),
	);

	if ( '' !== $token ) {
		$request_args['auth_token'] = $token;
	}

	if ( null !== $args['body'] ) {
		$request_args['body'] = $args['body'];
	}

	$response = tam_backend_api_request( $method, $path, $request_args );

	if ( 401 === (int) $response['status'] && ! empty( $args['clear_on_unauthorised'] ) ) {
		tam_backend_admin_clear_session();
	}

	return $response;
}

/**
 * Perform a multipart request against the backend admin API.
 *
 * @param string $method HTTP method.
 * @param string $path   API path relative to /api.
 * @param array  $body   Payload array.
 * @param array  $file   Uploaded file info from $_FILES.
 * @return array
 */
function tam_backend_admin_request_multipart( $method, $path, $body, $file ) {
	$token = tam_backend_admin_get_token();

	if ( '' === $token ) {
		return tam_backend_admin_error_response( __( 'Bạn chưa kết nối backend admin.', 'travel-agency-modern' ), 401 );
	}

	$api_url = tam_backend_api_build_url( $path );

	if ( ! $api_url ) {
		return tam_backend_admin_error_response( __( 'Backend API chưa được cấu hình.', 'travel-agency-modern' ) );
	}

	$boundary = '----TamAdminBoundary' . wp_generate_password( 20, false, false );
	$eol      = "\r\n";
	$payload  = '';
	$body     = is_array( $body ) ? $body : array();
	$file     = is_array( $file ) ? $file : array();

	$payload .= '--' . $boundary . $eol;
	$payload .= 'Content-Disposition: form-data; name="payload"' . $eol . $eol;
	$payload .= wp_json_encode( $body ) . $eol;

	if ( ! empty( $file['tmp_name'] ) && is_readable( $file['tmp_name'] ) ) {
		$file_contents = file_get_contents( $file['tmp_name'] );

		if ( false === $file_contents ) {
			return tam_backend_admin_error_response( __( 'Không thể đọc file ảnh đã chọn.', 'travel-agency-modern' ) );
		}

		$file_name = ! empty( $file['name'] ) ? sanitize_file_name( wp_unslash( $file['name'] ) ) : 'tour-image.jpg';
		$file_type = ! empty( $file['type'] ) ? sanitize_text_field( $file['type'] ) : 'application/octet-stream';

		$payload .= '--' . $boundary . $eol;
		$payload .= 'Content-Disposition: form-data; name="image"; filename="' . $file_name . '"' . $eol;
		$payload .= 'Content-Type: ' . $file_type . $eol . $eol;
		$payload .= $file_contents . $eol;
	}

	$payload .= '--' . $boundary . '--' . $eol;

	$response = wp_remote_request(
		$api_url,
		array(
			'method'      => strtoupper( (string) $method ),
			'timeout'     => 20,
			'redirection' => 2,
			'headers'     => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
			),
			'body'        => $payload,
		)
	);

	if ( is_wp_error( $response ) ) {
		return tam_backend_admin_error_response( $response->get_error_message() );
	}

	$status   = (int) wp_remote_retrieve_response_code( $response );
	$raw_body = (string) wp_remote_retrieve_body( $response );
	$data     = json_decode( $raw_body, true );
	$data     = is_array( $data ) ? $data : array();

	if ( 401 === $status ) {
		tam_backend_admin_clear_session();
	}

	return array(
		'success' => $status >= 200 && $status < 300,
		'status'  => $status,
		'message' => isset( $data['message'] ) ? (string) $data['message'] : '',
		'data'    => $data,
		'errors'  => tam_backend_api_normalize_errors( isset( $data['errors'] ) ? $data['errors'] : array() ),
	);
}

/**
 * Refresh the backend admin profile via /admin-auth/me.
 *
 * @return array|null
 */
function tam_backend_admin_refresh_profile() {
	$response = tam_backend_admin_request(
		'GET',
		'admin-auth/me',
		array(
			'require_auth'         => true,
			'clear_on_unauthorised' => true,
		)
	);

	if ( ! $response['success'] || empty( $response['data']['admin'] ) || ! is_array( $response['data']['admin'] ) ) {
		return tam_backend_admin_get_profile();
	}

	$token = tam_backend_admin_get_token();
	tam_backend_admin_set_session( $token, $response['data']['admin'] );

	return tam_backend_admin_get_profile();
}

/**
 * Render a compact connection status pill.
 *
 * @param array|null $profile Backend admin profile.
 * @return string
 */
function tam_backend_admin_render_connection_pill( $profile ) {
	if ( empty( $profile['email'] ) ) {
		return '<span class="tam-admin-pill tam-admin-pill--warning">' . esc_html__( 'Chưa kết nối backend', 'travel-agency-modern' ) . '</span>';
	}

	return '<span class="tam-admin-pill tam-admin-pill--success">' . esc_html__( 'Backend đã kết nối', 'travel-agency-modern' ) . '</span>';
}

/**
 * Return a list of top-level page slugs shown in the custom sidebar.
 *
 * @return string[]
 */
function tam_backend_admin_sidebar_pages() {
	return array(
		'tam-admin-dashboard',
		'tam-admin-tours',
		'tam-admin-bookings',
		'tam-admin-users',
		'tam-admin-reviews',
		'tam-admin-payments',
		'tam-admin-sync',
		'tam-admin-connect',
	);
}

/**
 * Register the custom admin shell menu.
 *
 * @return void
 */
function tam_backend_admin_register_menu() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$pages = tam_backend_admin_pages();

	add_menu_page(
		__( 'ADN Travel Admin', 'travel-agency-modern' ),
		__( 'ADN Admin', 'travel-agency-modern' ),
		'manage_options',
		TAM_BACKEND_ADMIN_MENU_SLUG,
		'tam_backend_admin_render_dashboard_page',
		'dashicons-admin-multisite',
		3
	);

	add_submenu_page(
		TAM_BACKEND_ADMIN_MENU_SLUG,
		(string) $pages['tam-admin-dashboard']['label'],
		(string) $pages['tam-admin-dashboard']['label'],
		'manage_options',
		TAM_BACKEND_ADMIN_MENU_SLUG,
		'tam_backend_admin_render_dashboard_page'
	);

	foreach ( $pages as $slug => $config ) {
		if ( TAM_BACKEND_ADMIN_MENU_SLUG === $slug ) {
			continue;
		}

		add_submenu_page(
			TAM_BACKEND_ADMIN_MENU_SLUG,
			(string) $config['label'],
			(string) $config['menu_title'],
			'manage_options',
			$slug,
			'tam_backend_admin_route_page'
		);
	}
}
add_action( 'admin_menu', 'tam_backend_admin_register_menu', 25 );

/**
 * Hide the older custom management pages and the mirrored tour CPT menu.
 *
 * @return void
 */
function tam_backend_admin_hide_legacy_menus() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	remove_submenu_page( 'tools.php', 'tam-backend-bookings' );
	remove_submenu_page( 'tools.php', 'tam-sync-tours-api' );
	remove_menu_page( 'edit.php?post_type=tour' );
	remove_submenu_page( 'edit.php?post_type=tour', 'post-new.php?post_type=tour' );
	remove_submenu_page( 'edit.php?post_type=tour', 'edit-tags.php?taxonomy=tour_destination&post_type=tour' );
}
add_action( 'admin_menu', 'tam_backend_admin_hide_legacy_menus', 999 );

/**
 * Redirect direct access to the mirrored native tour screens back to the custom admin shell.
 *
 * @param WP_Screen $screen Current admin screen object.
 * @return void
 */
function tam_backend_admin_redirect_native_tour_screens( $screen ) {
	if ( ! current_user_can( 'manage_options' ) || ! ( $screen instanceof WP_Screen ) ) {
		return;
	}

	if ( 'tour' !== (string) $screen->post_type ) {
		return;
	}

	if ( in_array( $screen->base, array( 'edit', 'post', 'term' ), true ) ) {
		wp_safe_redirect( tam_backend_admin_page_url( 'tam-admin-tours' ) );
		exit;
	}
}
add_action( 'current_screen', 'tam_backend_admin_redirect_native_tour_screens' );

/**
 * Enqueue custom admin shell assets on custom pages.
 *
 * @param string $hook_suffix Current admin hook.
 * @return void
 */
function tam_backend_admin_enqueue_assets( $hook_suffix ) {
	if ( ! tam_backend_admin_is_current_screen() ) {
		return;
	}

	$base_path   = get_theme_file_path( '/assets/admin.css' );
	$script_path = get_theme_file_path( '/assets/admin.js' );
	$style_ver   = file_exists( $base_path ) ? (string) filemtime( $base_path ) : TAM_THEME_VERSION;
	$script_ver  = file_exists( $script_path ) ? (string) filemtime( $script_path ) : TAM_THEME_VERSION;

	wp_enqueue_style(
		'travel-agency-modern-admin',
		get_theme_file_uri( '/assets/admin.css' ),
		array(),
		$style_ver
	);

	wp_enqueue_script(
		'travel-agency-modern-admin',
		get_theme_file_uri( '/assets/admin.js' ),
		array(),
		$script_ver,
		true
	);

	wp_localize_script(
		'travel-agency-modern-admin',
		'tamAdminShell',
		array(
			'confirmTitle'   => __( 'Xác nhận thao tác', 'travel-agency-modern' ),
			'confirmButton'  => __( 'Tiếp tục', 'travel-agency-modern' ),
			'cancelButton'   => __( 'Hủy', 'travel-agency-modern' ),
			'defaultMessage' => __( 'Bạn có chắc muốn thực hiện thao tác này không?', 'travel-agency-modern' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'tam_backend_admin_enqueue_assets' );

/**
 * Remove global admin notices on custom admin shell pages so the layout stays clean.
 *
 * @return void
 */
function tam_backend_admin_cleanup_notice_hooks() {
	if ( ! tam_backend_admin_is_current_screen() ) {
		return;
	}

	remove_all_actions( 'admin_notices' );
	remove_all_actions( 'all_admin_notices' );
	remove_all_actions( 'network_admin_notices' );
	remove_all_actions( 'user_admin_notices' );
}
add_action( 'in_admin_header', 'tam_backend_admin_cleanup_notice_hooks', 1 );

/**
 * Return a backend admin profile for the shell header/sidebar.
 *
 * @return array
 */
function tam_backend_admin_shell_profile() {
	$profile = tam_backend_admin_refresh_profile();

	if ( ! empty( $profile['email'] ) ) {
		return $profile;
	}

	$wp_user = wp_get_current_user();

	return array(
		'id'    => $wp_user instanceof WP_User ? (int) $wp_user->ID : 0,
		'name'  => $wp_user instanceof WP_User ? (string) $wp_user->display_name : '',
		'email' => $wp_user instanceof WP_User ? (string) $wp_user->user_email : '',
		'role'  => __( 'WordPress Admin', 'travel-agency-modern' ),
	);
}

/**
 * Render the shell notice banner from query args.
 *
 * @return void
 */
function tam_backend_admin_render_notices() {
	if ( empty( $_GET['tam_notice'] ) ) {
		return;
	}

	$message = sanitize_text_field( wp_unslash( $_GET['tam_notice'] ) );
	$type    = isset( $_GET['tam_notice_type'] ) ? sanitize_key( wp_unslash( $_GET['tam_notice_type'] ) ) : 'info';

	if ( '' === $message ) {
		return;
	}

	$allowed = array(
		'success' => 'updated',
		'warning' => 'notice-warning',
		'error'   => 'notice-error',
		'info'    => 'notice-info',
	);

	$class = isset( $allowed[ $type ] ) ? $allowed[ $type ] : 'notice-info';

	printf(
		'<div class="notice %1$s is-dismissible tam-admin-notice"><p>%2$s</p></div>',
		esc_attr( $class ),
		esc_html( $message )
	);
}

/**
 * Route submenu callbacks to the correct renderer.
 *
 * @return void
 */
function tam_backend_admin_route_page() {
	$slug = tam_backend_admin_current_page();

	switch ( $slug ) {
		case 'tam-admin-tours':
			tam_backend_admin_render_tours_page();
			break;
		case 'tam-admin-bookings':
			tam_backend_admin_render_bookings_page();
			break;
		case 'tam-admin-users':
			tam_backend_admin_render_users_page();
			break;
		case 'tam-admin-reviews':
			tam_backend_admin_render_reviews_page();
			break;
		case 'tam-admin-payments':
			tam_backend_admin_render_payments_page();
			break;
		case 'tam-admin-sync':
			tam_backend_admin_render_sync_page();
			break;
		case 'tam-admin-connect':
			tam_backend_admin_render_connect_page();
			break;
		case 'tam-admin-dashboard':
		default:
			tam_backend_admin_render_dashboard_page();
			break;
	}
}

/**
 * Ensure the current screen can use the custom admin shell.
 *
 * @param string $page_slug Current page slug.
 * @return bool
 */
function tam_backend_admin_require_access( $page_slug ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập khu vực này.', 'travel-agency-modern' ) );
	}

	$pages      = tam_backend_admin_pages();
	$page_slug  = sanitize_key( $page_slug );
	$is_protected = isset( $pages[ $page_slug ]['protected'] ) ? (bool) $pages[ $page_slug ]['protected'] : false;

	if ( ! $is_protected ) {
		return true;
	}

	if ( tam_backend_admin_is_connected() ) {
		return true;
	}

	return false;
}

/**
 * Render the common admin shell wrapper.
 *
 * @param string   $page_slug Page slug.
 * @param callable $renderer  Page renderer callback.
 * @return void
 */
function tam_backend_admin_render_shell( $page_slug, $renderer ) {
	$has_access = tam_backend_admin_require_access( $page_slug );

	$pages   = tam_backend_admin_pages();
	$config  = isset( $pages[ $page_slug ] ) ? $pages[ $page_slug ] : $pages['tam-admin-dashboard'];
	$profile = tam_backend_admin_shell_profile();

	echo '<div class="wrap tam-admin-wrap">';
	echo '<div class="tam-admin-shell">';
	echo '<main class="tam-admin-main">';
	echo '<header class="tam-admin-topbar">';
	echo '<div>';
	echo '<p class="tam-admin-topbar__eyebrow">' . esc_html__( 'ADN Travel / WordPress Admin', 'travel-agency-modern' ) . '</p>';
	echo '<h2>' . esc_html( (string) $config['label'] ) . '</h2>';
	echo '</div>';
	echo '<div class="tam-admin-topbar__actions">';
	echo '<div class="tam-admin-topbar__profile">';
	echo '<strong>' . esc_html( $profile['name'] ? $profile['name'] : __( 'Admin', 'travel-agency-modern' ) ) . '</strong>';
	echo '<span>' . esc_html( $profile['email'] ? $profile['email'] : __( 'Chưa có email', 'travel-agency-modern' ) ) . '</span>';
	echo '</div>';
	echo wp_kses_post( tam_backend_admin_render_connection_pill( tam_backend_admin_get_profile() ) );
	echo '<a class="button button-secondary" href="' . esc_url( tam_backend_admin_page_url( 'tam-admin-connect' ) ) . '">' . esc_html__( 'Backend Connect', 'travel-agency-modern' ) . '</a>';
	echo '</div>';
	echo '</header>';

	tam_backend_admin_render_notices();

	if ( ! $has_access ) {
		tam_backend_admin_render_empty_state(
			__( 'Cần kết nối backend admin', 'travel-agency-modern' ),
			__( 'Trang quản trị này dùng dữ liệu thật từ backend API. Hãy đăng nhập backend admin trước để tải dashboard, tours, bookings, payments, reviews và users.', 'travel-agency-modern' ),
			tam_backend_admin_page_url( 'tam-admin-connect' ),
			__( 'Mở trang Backend Connect', 'travel-agency-modern' )
		);
		echo '</main>';
		echo '</div>';
		echo '</div>';
		return;
	}

	call_user_func( $renderer );

	echo '</main>';
	echo '</div>';
	echo '</div>';
}

/**
 * Return backend dashboard stats.
 *
 * @return array
 */
function tam_backend_admin_get_dashboard() {
	return tam_backend_admin_request( 'GET', 'stats/dashboard' );
}

/**
 * Return paginated tours from the backend.
 *
 * @param array $args Query args.
 * @return array
 */
function tam_backend_admin_get_tours( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'page'   => 1,
			'limit'  => 12,
			'search' => '',
			'status' => '',
		)
	);

	$query = array(
		'page'  => max( 1, (int) $args['page'] ),
		'limit' => max( 1, min( 50, (int) $args['limit'] ) ),
	);

	if ( '' !== trim( (string) $args['search'] ) ) {
		$query['search'] = trim( (string) $args['search'] );
	}

	if ( '' !== trim( (string) $args['status'] ) ) {
		$query['status'] = trim( (string) $args['status'] );
	}

	return tam_backend_admin_request(
		'GET',
		'tours',
		array(
			'query' => $query,
		)
	);
}

/**
 * Return a single backend tour.
 *
 * @param int $tour_id Backend tour ID.
 * @return array
 */
function tam_backend_admin_get_tour( $tour_id ) {
	return tam_backend_admin_request( 'GET', 'tours/' . (int) $tour_id );
}

/**
 * Create a tour in the backend and sync the mirrored WordPress post.
 *
 * @param array $payload Backend payload.
 * @param array $file    Uploaded file info.
 * @return array
 */
function tam_backend_admin_create_tour( $payload, $file = array() ) {
	$response = ! empty( $file['tmp_name'] )
		? tam_backend_admin_request_multipart( 'POST', 'tours', $payload, $file )
		: tam_backend_admin_request( 'POST', 'tours', array( 'body' => $payload ) );

	if ( $response['success'] && ! empty( $response['data']['tour'] ) && is_array( $response['data']['tour'] ) ) {
		$sync_result = tam_backend_api_upsert_tour_post( $response['data']['tour'] );

		if ( is_wp_error( $sync_result ) ) {
			$response['sync_warning'] = true;
			$response['message']      = trim( (string) $response['message'] . ' ' . sprintf( __( 'Tour backend đã lưu nhưng mirror WordPress chưa sync được: %s', 'travel-agency-modern' ), $sync_result->get_error_message() ) );
		}
	}

	return $response;
}

/**
 * Update a backend tour and sync the mirrored WordPress post.
 *
 * @param int   $tour_id Backend tour ID.
 * @param array $payload Backend payload.
 * @param array $file    Uploaded file info.
 * @return array
 */
function tam_backend_admin_update_tour( $tour_id, $payload, $file = array() ) {
	$response = ! empty( $file['tmp_name'] )
		? tam_backend_admin_request_multipart( 'PUT', 'tours/' . (int) $tour_id, $payload, $file )
		: tam_backend_admin_request( 'PUT', 'tours/' . (int) $tour_id, array( 'body' => $payload ) );

	if ( $response['success'] && ! empty( $response['data']['tour'] ) && is_array( $response['data']['tour'] ) ) {
		$sync_result = tam_backend_api_upsert_tour_post( $response['data']['tour'] );

		if ( is_wp_error( $sync_result ) ) {
			$response['sync_warning'] = true;
			$response['message']      = trim( (string) $response['message'] . ' ' . sprintf( __( 'Tour backend đã lưu nhưng mirror WordPress chưa sync được: %s', 'travel-agency-modern' ), $sync_result->get_error_message() ) );
		}
	}

	return $response;
}

/**
 * Change a backend tour status and sync the mirrored WordPress post.
 *
 * @param int    $tour_id Backend tour ID.
 * @param string $status  Draft|Active|Closed.
 * @return array
 */
function tam_backend_admin_update_tour_status( $tour_id, $status ) {
	$current = tam_backend_admin_get_tour( $tour_id );

	if ( ! $current['success'] || empty( $current['data']['tour'] ) || ! is_array( $current['data']['tour'] ) ) {
		return $current;
	}

	$tour            = $current['data']['tour'];
	$tour['status']  = sanitize_text_field( $status );

	return tam_backend_admin_update_tour( $tour_id, $tour );
}

/**
 * Delete a backend tour and move its mirrored WordPress post to trash.
 *
 * @param int $tour_id Backend tour ID.
 * @return array
 */
function tam_backend_admin_delete_tour( $tour_id ) {
	$response = tam_backend_admin_request( 'DELETE', 'tours/' . (int) $tour_id );

	if ( $response['success'] ) {
		$post_id = tam_backend_admin_get_mirror_post_id( $tour_id );

		if ( $post_id > 0 ) {
			wp_trash_post( $post_id );
		}
	}

	return $response;
}

/**
 * Return backend bookings.
 *
 * @param array $args Query args.
 * @return array
 */
function tam_backend_admin_get_bookings( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'status' => '',
			'tour_id' => '',
		)
	);

	$query = array();

	if ( '' !== trim( (string) $args['status'] ) ) {
		$query['status'] = trim( (string) $args['status'] );
	}

	if ( '' !== trim( (string) $args['tour_id'] ) ) {
		$query['tour_id'] = trim( (string) $args['tour_id'] );
	}

	return tam_backend_admin_request(
		'GET',
		'bookings',
		array(
			'query' => $query,
		)
	);
}

/**
 * Return a booking detail payload from the backend.
 *
 * @param int $booking_id Backend booking ID.
 * @return array
 */
function tam_backend_admin_get_booking( $booking_id ) {
	return tam_backend_admin_request( 'GET', 'bookings/' . (int) $booking_id );
}

/**
 * Update a booking status in the backend.
 *
 * @param int    $booking_id Backend booking ID.
 * @param string $status     Booking status enum.
 * @return array
 */
function tam_backend_admin_update_booking_status( $booking_id, $status ) {
	return tam_backend_admin_request(
		'PUT',
		'bookings/' . (int) $booking_id . '/status',
		array(
			'body' => array(
				'status' => strtoupper( sanitize_text_field( $status ) ),
			),
		)
	);
}

/**
 * Return backend users.
 *
 * @param array $args Query args.
 * @return array
 */
function tam_backend_admin_get_users( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'search' => '',
		)
	);

	$query = array();

	if ( '' !== trim( (string) $args['search'] ) ) {
		$query['search'] = trim( (string) $args['search'] );
	}

	return tam_backend_admin_request(
		'GET',
		'users',
		array(
			'query' => $query,
		)
	);
}

/**
 * Return a backend user detail.
 *
 * @param int $user_id Backend user ID.
 * @return array
 */
function tam_backend_admin_get_user( $user_id ) {
	return tam_backend_admin_request( 'GET', 'users/' . (int) $user_id );
}

/**
 * Update a backend user role.
 *
 * @param int    $user_id Backend user ID.
 * @param string $role    USER|STAFF.
 * @return array
 */
function tam_backend_admin_update_user_role( $user_id, $role ) {
	return tam_backend_admin_request(
		'PUT',
		'users/' . (int) $user_id . '/role',
		array(
			'body' => array(
				'role' => strtoupper( sanitize_text_field( $role ) ),
			),
		)
	);
}

/**
 * Delete a backend user.
 *
 * @param int $user_id Backend user ID.
 * @return array
 */
function tam_backend_admin_delete_user( $user_id ) {
	return tam_backend_admin_request( 'DELETE', 'users/' . (int) $user_id );
}

/**
 * Return backend reviews.
 *
 * @param array $args Query args.
 * @return array
 */
function tam_backend_admin_get_reviews( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'status' => '',
			'search' => '',
		)
	);

	$query = array();

	if ( '' !== trim( (string) $args['status'] ) && 'ALL' !== strtoupper( trim( (string) $args['status'] ) ) ) {
		$query['status'] = trim( (string) $args['status'] );
	}

	if ( '' !== trim( (string) $args['search'] ) ) {
		$query['search'] = trim( (string) $args['search'] );
	}

	return tam_backend_admin_request(
		'GET',
		'reviews',
		array(
			'query' => $query,
		)
	);
}

/**
 * Update a backend review moderation status.
 *
 * @param int    $review_id Backend review ID.
 * @param string $status    VISIBLE|HIDDEN.
 * @return array
 */
function tam_backend_admin_update_review_status( $review_id, $status ) {
	return tam_backend_admin_request(
		'PUT',
		'reviews/' . (int) $review_id . '/status',
		array(
			'body' => array(
				'status' => strtoupper( sanitize_text_field( $status ) ),
			),
		)
	);
}

/**
 * Return backend payments.
 *
 * @param array $args Query args.
 * @return array
 */
function tam_backend_admin_get_payments( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'status' => '',
			'method' => '',
		)
	);

	$query = array();

	if ( '' !== trim( (string) $args['status'] ) ) {
		$query['status'] = trim( (string) $args['status'] );
	}

	if ( '' !== trim( (string) $args['method'] ) ) {
		$query['method'] = trim( (string) $args['method'] );
	}

	return tam_backend_admin_request(
		'GET',
		'payments',
		array(
			'query' => $query,
		)
	);
}

/**
 * Confirm a pending payment via the backend.
 *
 * @param int $payment_id Backend payment ID.
 * @return array
 */
function tam_backend_admin_confirm_payment( $payment_id ) {
	return tam_backend_admin_request( 'PUT', 'payments/' . (int) $payment_id . '/confirm' );
}

/**
 * Return a paginated slice from an array of items.
 *
 * @param array $items    Full item list.
 * @param int   $paged    Current page.
 * @param int   $per_page Items per page.
 * @return array
 */
function tam_backend_admin_paginate_array( $items, $paged, $per_page ) {
	$items      = is_array( $items ) ? array_values( $items ) : array();
	$per_page   = max( 1, (int) $per_page );
	$paged      = max( 1, (int) $paged );
	$total      = count( $items );
	$total_pages = max( 1, (int) ceil( $total / $per_page ) );
	$offset     = ( $paged - 1 ) * $per_page;

	return array(
		'items'       => array_slice( $items, $offset, $per_page ),
		'total_items' => $total,
		'total_pages' => $total_pages,
		'paged'       => min( $paged, $total_pages ),
		'per_page'    => $per_page,
	);
}

/**
 * Filter a booking array client-side by a generic search term.
 *
 * @param array  $bookings Booking rows.
 * @param string $search   Search term.
 * @return array
 */
function tam_backend_admin_filter_bookings( $bookings, $search ) {
	$search = trim( (string) $search );

	if ( '' === $search ) {
		return is_array( $bookings ) ? $bookings : array();
	}

	$needle = strtolower( $search );

	return array_values(
		array_filter(
			is_array( $bookings ) ? $bookings : array(),
			static function ( $booking ) use ( $needle ) {
				$haystack = implode(
					' ',
					array(
						isset( $booking['id'] ) ? (string) $booking['id'] : '',
						isset( $booking['user_name'] ) ? (string) $booking['user_name'] : '',
						isset( $booking['user_email'] ) ? (string) $booking['user_email'] : '',
						isset( $booking['tour_title'] ) ? (string) $booking['tour_title'] : '',
						isset( $booking['travel_date'] ) ? (string) $booking['travel_date'] : '',
					)
				);

				return false !== strpos( strtolower( $haystack ), $needle );
			}
		)
	);
}

/**
 * Filter payments client-side by a generic search term.
 *
 * @param array  $payments Payment rows.
 * @param string $search   Search term.
 * @return array
 */
function tam_backend_admin_filter_payments( $payments, $search ) {
	$search = trim( (string) $search );

	if ( '' === $search ) {
		return is_array( $payments ) ? $payments : array();
	}

	$needle = strtolower( $search );

	return array_values(
		array_filter(
			is_array( $payments ) ? $payments : array(),
			static function ( $payment ) use ( $needle ) {
				$haystack = implode(
					' ',
					array(
						isset( $payment['id'] ) ? (string) $payment['id'] : '',
						isset( $payment['user_name'] ) ? (string) $payment['user_name'] : '',
						isset( $payment['user_email'] ) ? (string) $payment['user_email'] : '',
						isset( $payment['tour_title'] ) ? (string) $payment['tour_title'] : '',
						isset( $payment['method'] ) ? (string) $payment['method'] : '',
						isset( $payment['status'] ) ? (string) $payment['status'] : '',
					)
				);

				return false !== strpos( strtolower( $haystack ), $needle );
			}
		)
	);
}

/**
 * Return a CSS tone name for badges and cards.
 *
 * @param string $status Status string.
 * @return string
 */
function tam_backend_admin_status_tone( $status ) {
	$status = strtoupper( trim( (string) $status ) );

	if ( in_array( $status, array( 'ACTIVE', 'CONFIRMED', 'COMPLETED', 'SUCCESS', 'VISIBLE', 'PAID' ), true ) ) {
		return 'success';
	}

	if ( in_array( $status, array( 'PENDING', 'PENDING_PAYMENT', 'PENDING_CONFIRMATION', 'DRAFT' ), true ) ) {
		return 'warning';
	}

	if ( in_array( $status, array( 'CANCELLED', 'CLOSED', 'FAILED', 'REFUNDED', 'HIDDEN' ), true ) ) {
		return 'danger';
	}

	return 'neutral';
}

/**
 * Format a currency amount for the admin shell.
 *
 * @param mixed $amount Amount.
 * @return string
 */
function tam_backend_admin_format_amount( $amount ) {
	$amount = is_numeric( $amount ) ? (float) $amount : 0;

	if ( function_exists( 'tam_backend_api_format_admin_amount' ) ) {
		return tam_backend_api_format_admin_amount( $amount );
	}

	return number_format_i18n( $amount ) . ' đ';
}

/**
 * Format a date for the admin shell.
 *
 * @param string $date Date string.
 * @return string
 */
function tam_backend_admin_format_date( $date ) {
	if ( function_exists( 'tam_backend_api_format_date' ) ) {
		return tam_backend_api_format_date( $date );
	}

	$timestamp = strtotime( (string) $date );

	return $timestamp ? wp_date( 'd/m/Y', $timestamp ) : '—';
}

/**
 * Format a datetime for the admin shell.
 *
 * @param string $date Date string.
 * @return string
 */
function tam_backend_admin_format_datetime( $date ) {
	if ( function_exists( 'tam_backend_api_format_datetime' ) ) {
		return tam_backend_api_format_datetime( $date );
	}

	$timestamp = strtotime( (string) $date );

	return $timestamp ? wp_date( 'd/m/Y H:i', $timestamp ) : '—';
}

/**
 * Render a modern status badge.
 *
 * @param string $status Status label.
 * @return string
 */
function tam_backend_admin_badge( $status ) {
	$tone   = tam_backend_admin_status_tone( $status );
	$label  = trim( str_replace( '_', ' ', (string) $status ) );
	$label  = '' !== $label ? $label : __( 'Unknown', 'travel-agency-modern' );

	return sprintf(
		'<span class="tam-admin-badge tam-admin-badge--%1$s">%2$s</span>',
		esc_attr( $tone ),
		esc_html( ucwords( strtolower( $label ) ) )
	);
}

/**
 * Render a reusable empty state card.
 *
 * @param string $title       Card title.
 * @param string $description Supporting text.
 * @param string $action_url  Optional CTA URL.
 * @param string $action_text Optional CTA label.
 * @return void
 */
function tam_backend_admin_render_empty_state( $title, $description, $action_url = '', $action_text = '' ) {
	echo '<section class="tam-admin-card tam-admin-empty">';
	echo '<h3>' . esc_html( $title ) . '</h3>';
	echo '<p>' . esc_html( $description ) . '</p>';

	if ( $action_url && $action_text ) {
		echo '<a class="button button-primary" href="' . esc_url( $action_url ) . '">' . esc_html( $action_text ) . '</a>';
	}

	echo '</section>';
}

/**
 * Render a generic API error card.
 *
 * @param array  $response Backend response.
 * @param string $action   Optional action URL.
 * @param string $label    Optional action label.
 * @return void
 */
function tam_backend_admin_render_api_error( $response, $action = '', $label = '' ) {
	$message = ! empty( $response['message'] ) ? (string) $response['message'] : __( 'Không thể tải dữ liệu backend lúc này.', 'travel-agency-modern' );

	tam_backend_admin_render_empty_state(
		__( 'Kết nối backend chưa sẵn sàng', 'travel-agency-modern' ),
		$message,
		$action,
		$label
	);
}

/**
 * Render a summary stat card.
 *
 * @param string $label Card label.
 * @param string $value Card value.
 * @param string $meta  Optional helper text.
 * @return void
 */
function tam_backend_admin_render_stat_card( $label, $value, $meta = '' ) {
	echo '<article class="tam-admin-stat">';
	echo '<span>' . esc_html( $label ) . '</span>';
	echo '<strong>' . esc_html( $value ) . '</strong>';

	if ( '' !== $meta ) {
		echo '<small>' . esc_html( $meta ) . '</small>';
	}

	echo '</article>';
}

/**
 * Render custom admin pagination links.
 *
 * @param int   $current   Current page.
 * @param int   $total     Total pages.
 * @param array $base_args Base query args.
 * @return void
 */
function tam_backend_admin_render_pagination( $current, $total, $base_args ) {
	$current = max( 1, (int) $current );
	$total   = max( 1, (int) $total );

	if ( $total <= 1 ) {
		return;
	}

	echo '<div class="tam-admin-pagination">';

	for ( $page = 1; $page <= $total; $page++ ) {
		$url = tam_backend_admin_page_url(
			tam_backend_admin_current_page(),
			array_merge(
				$base_args,
				array(
					'paged' => $page,
				)
			)
		);

		printf(
			'<a class="tam-admin-pagination__link %1$s" href="%2$s">%3$s</a>',
			$page === $current ? 'is-active' : '',
			esc_url( $url ),
			esc_html( number_format_i18n( $page ) )
		);
	}

	echo '</div>';
}

/**
 * Render one admin field with optional error text.
 *
 * @param string $name    Field name.
 * @param string $label   Field label.
 * @param string $value   Field value.
 * @param array  $args    Render args.
 * @param array  $errors  Error map.
 * @return void
 */
function tam_backend_admin_render_field( $name, $label, $value, $args = array(), $errors = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'type'        => 'text',
			'rows'        => 4,
			'placeholder' => '',
			'options'     => array(),
			'help'        => '',
			'full'        => false,
		)
	);

	$error = isset( $errors[ $name ] ) ? (string) $errors[ $name ] : '';
	$classes = array( 'tam-admin-field' );

	if ( ! empty( $args['full'] ) ) {
		$classes[] = 'tam-admin-field--full';
	}

	if ( '' !== $error ) {
		$classes[] = 'tam-admin-field--error';
	}

	printf(
		'<label class="%1$s">',
		esc_attr( implode( ' ', $classes ) )
	);
	echo '<span>' . esc_html( $label ) . '</span>';

	if ( 'textarea' === $args['type'] ) {
		printf(
			'<textarea class="%5$s" name="tour[%1$s]" rows="%2$s" placeholder="%3$s">%4$s</textarea>',
			esc_attr( $name ),
			esc_attr( (int) $args['rows'] ),
			esc_attr( (string) $args['placeholder'] ),
			esc_textarea( $value ),
			'' !== $error ? 'is-error' : ''
		);
	} elseif ( 'select' === $args['type'] ) {
		printf( '<select class="%2$s" name="tour[%1$s]">', esc_attr( $name ), esc_attr( '' !== $error ? 'is-error' : '' ) );

		foreach ( (array) $args['options'] as $option_value => $option_label ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $option_value ),
				selected( (string) $value, (string) $option_value, false ),
				esc_html( $option_label )
			);
		}

		echo '</select>';
	} else {
		printf(
			'<input class="%5$s" type="%1$s" name="tour[%2$s]" value="%3$s" placeholder="%4$s" />',
			esc_attr( (string) $args['type'] ),
			esc_attr( $name ),
			esc_attr( $value ),
			esc_attr( (string) $args['placeholder'] ),
			esc_attr( '' !== $error ? 'is-error' : '' )
		);
	}

	if ( '' !== $error ) {
		echo '<small class="tam-admin-field__error">' . esc_html( $error ) . '</small>';
	} elseif ( ! empty( $args['help'] ) ) {
		echo '<small>' . esc_html( (string) $args['help'] ) . '</small>';
	}

	echo '</label>';
}

/**
 * Render the backend connect page.
 *
 * @return void
 */
function tam_backend_admin_render_connect_page() {
	tam_backend_admin_render_shell(
		'tam-admin-connect',
		static function () {
			$profile = tam_backend_admin_get_profile();
			$view    = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'login';

			echo '<section class="tam-admin-grid tam-admin-grid--2">';
			echo '<article class="tam-admin-card">';
			echo '<div class="tam-admin-card__head"><h3>' . esc_html__( 'Trạng thái kết nối backend', 'travel-agency-modern' ) . '</h3></div>';

			if ( ! empty( $profile['email'] ) ) {
				echo '<div class="tam-admin-kv">';
				echo '<div><span>' . esc_html__( 'Tên admin backend', 'travel-agency-modern' ) . '</span><strong>' . esc_html( $profile['name'] ) . '</strong></div>';
				echo '<div><span>' . esc_html__( 'Email', 'travel-agency-modern' ) . '</span><strong>' . esc_html( $profile['email'] ) . '</strong></div>';
				echo '<div><span>' . esc_html__( 'Vai trò', 'travel-agency-modern' ) . '</span><strong>' . esc_html( $profile['role'] ? $profile['role'] : 'admin' ) . '</strong></div>';
				echo '</div>';
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="tam-admin-inline-form">';
				wp_nonce_field( 'tam_backend_admin_auth', 'tam_backend_admin_nonce' );
				echo '<input type="hidden" name="action" value="tam_backend_admin_auth" />';
				echo '<input type="hidden" name="intent" value="disconnect" />';
				echo '<button type="submit" class="button button-secondary">' . esc_html__( 'Ngắt kết nối backend', 'travel-agency-modern' ) . '</button>';
				echo '</form>';
			} else {
				echo '<p class="tam-admin-muted">' . esc_html__( 'Mỗi WordPress admin sẽ có một phiên backend riêng để gọi các route quản trị như tours, bookings, payments, reviews và users.', 'travel-agency-modern' ) . '</p>';
			}

			echo '</article>';
			echo '<article class="tam-admin-card">';
			echo '<div class="tam-admin-card__head">';
			echo '<h3>' . esc_html__( 'Tài khoản backend admin', 'travel-agency-modern' ) . '</h3>';
			echo '<div class="tam-admin-tabs">';
			echo '<a class="' . esc_attr( 'login' === $view ? 'is-active' : '' ) . '" href="' . esc_url( tam_backend_admin_page_url( 'tam-admin-connect', array( 'view' => 'login' ) ) ) . '">' . esc_html__( 'Đăng nhập', 'travel-agency-modern' ) . '</a>';
			echo '<a class="' . esc_attr( 'register' === $view ? 'is-active' : '' ) . '" href="' . esc_url( tam_backend_admin_page_url( 'tam-admin-connect', array( 'view' => 'register' ) ) ) . '">' . esc_html__( 'Đăng ký', 'travel-agency-modern' ) . '</a>';
			echo '</div>';
			echo '</div>';

			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="tam-admin-stack">';
			wp_nonce_field( 'tam_backend_admin_auth', 'tam_backend_admin_nonce' );
			echo '<input type="hidden" name="action" value="tam_backend_admin_auth" />';
			echo '<input type="hidden" name="intent" value="' . esc_attr( 'register' === $view ? 'register' : 'login' ) . '" />';

			if ( 'register' === $view ) {
				echo '<label class="tam-admin-field tam-admin-field--full"><span>' . esc_html__( 'Tên admin', 'travel-agency-modern' ) . '</span><input type="text" name="name" required /></label>';
			}

			echo '<label class="tam-admin-field tam-admin-field--full"><span>' . esc_html__( 'Email backend admin', 'travel-agency-modern' ) . '</span><input type="email" name="email" required /></label>';
			echo '<label class="tam-admin-field tam-admin-field--full"><span>' . esc_html__( 'Mật khẩu', 'travel-agency-modern' ) . '</span><input type="password" name="password" required /></label>';

			if ( 'register' === $view ) {
				echo '<label class="tam-admin-field tam-admin-field--full"><span>' . esc_html__( 'Nhập lại mật khẩu', 'travel-agency-modern' ) . '</span><input type="password" name="confirmPassword" required /></label>';
			}

			echo '<button type="submit" class="button button-primary">' . esc_html( 'register' === $view ? __( 'Tạo tài khoản và kết nối', 'travel-agency-modern' ) : __( 'Đăng nhập backend', 'travel-agency-modern' ) ) . '</button>';
			echo '</form>';
			echo '</article>';
			echo '</section>';
		}
	);
}

/**
 * Render the dashboard page.
 *
 * @return void
 */
function tam_backend_admin_render_dashboard_page() {
	tam_backend_admin_render_shell(
		'tam-admin-dashboard',
		static function () {
			$response = tam_backend_admin_get_dashboard();

			if ( ! $response['success'] ) {
				tam_backend_admin_render_api_error( $response, tam_backend_admin_page_url( 'tam-admin-connect' ), __( 'Kiểm tra kết nối backend', 'travel-agency-modern' ) );
				return;
			}

			$stats           = isset( $response['data']['stats'] ) && is_array( $response['data']['stats'] ) ? $response['data']['stats'] : array();
			$recent_bookings = isset( $response['data']['recentBookings'] ) && is_array( $response['data']['recentBookings'] ) ? $response['data']['recentBookings'] : array();
			$top_tours       = isset( $response['data']['topTours'] ) && is_array( $response['data']['topTours'] ) ? $response['data']['topTours'] : array();
			$monthly         = isset( $response['data']['monthlyRevenue'] ) && is_array( $response['data']['monthlyRevenue'] ) ? $response['data']['monthlyRevenue'] : array();

			echo '<section class="tam-admin-stats">';
			tam_backend_admin_render_stat_card( __( 'Tổng tours', 'travel-agency-modern' ), number_format_i18n( isset( $stats['tours']['total'] ) ? (int) $stats['tours']['total'] : 0 ), __( 'Nguồn dữ liệu từ backend', 'travel-agency-modern' ) );
			tam_backend_admin_render_stat_card( __( 'Tours đang mở bán', 'travel-agency-modern' ), number_format_i18n( isset( $stats['tours']['active'] ) ? (int) $stats['tours']['active'] : 0 ), __( 'Status Active', 'travel-agency-modern' ) );
			tam_backend_admin_render_stat_card( __( 'Tổng booking', 'travel-agency-modern' ), number_format_i18n( isset( $stats['bookings']['total'] ) ? (int) $stats['bookings']['total'] : 0 ), sprintf( __( '%s booking chờ xử lý', 'travel-agency-modern' ), number_format_i18n( isset( $stats['bookings']['pending'] ) ? (int) $stats['bookings']['pending'] : 0 ) ) );
			tam_backend_admin_render_stat_card( __( 'Doanh thu đã thu', 'travel-agency-modern' ), tam_backend_admin_format_amount( isset( $stats['revenue']['total'] ) ? $stats['revenue']['total'] : 0 ), sprintf( __( '%s khách hàng', 'travel-agency-modern' ), number_format_i18n( isset( $stats['users']['total'] ) ? (int) $stats['users']['total'] : 0 ) ) );
			echo '</section>';

			echo '<section class="tam-admin-grid tam-admin-grid--2">';
			echo '<article class="tam-admin-card">';
			echo '<div class="tam-admin-card__head"><h3>' . esc_html__( 'Doanh thu 6 tháng gần đây', 'travel-agency-modern' ) . '</h3></div>';

			if ( empty( $monthly ) ) {
				echo '<p class="tam-admin-muted">' . esc_html__( 'Chưa có dữ liệu thanh toán thành công để hiển thị.', 'travel-agency-modern' ) . '</p>';
			} else {
				$max = 0;

				foreach ( $monthly as $row ) {
					$max = max( $max, (float) ( isset( $row['revenue'] ) ? $row['revenue'] : 0 ) );
				}

				echo '<div class="tam-admin-bars">';

				foreach ( $monthly as $row ) {
					$revenue = (float) ( isset( $row['revenue'] ) ? $row['revenue'] : 0 );
					$height  = $max > 0 ? max( 16, (int) round( ( $revenue / $max ) * 160 ) ) : 16;

					echo '<div class="tam-admin-bars__item">';
					echo '<strong style="height:' . esc_attr( $height ) . 'px"></strong>';
					echo '<span>' . esc_html( isset( $row['month'] ) ? (string) $row['month'] : '' ) . '</span>';
					echo '<small>' . esc_html( tam_backend_admin_format_amount( $revenue ) ) . '</small>';
					echo '</div>';
				}

				echo '</div>';
			}

			echo '</article>';
			echo '<article class="tam-admin-card">';
			echo '<div class="tam-admin-card__head"><h3>' . esc_html__( 'Top tours được đặt nhiều nhất', 'travel-agency-modern' ) . '</h3></div>';

			if ( empty( $top_tours ) ) {
				echo '<p class="tam-admin-muted">' . esc_html__( 'Chưa có tour nào phát sinh booking.', 'travel-agency-modern' ) . '</p>';
			} else {
				echo '<div class="tam-admin-list">';

				foreach ( $top_tours as $tour ) {
					echo '<div class="tam-admin-list__item">';
					echo '<div><strong>' . esc_html( isset( $tour['title'] ) ? (string) $tour['title'] : '' ) . '</strong><span>' . esc_html( isset( $tour['location'] ) ? (string) $tour['location'] : '' ) . '</span></div>';
					echo '<div><strong>' . esc_html( number_format_i18n( isset( $tour['total_bookings'] ) ? (int) $tour['total_bookings'] : 0 ) ) . '</strong><span>' . esc_html__( 'bookings', 'travel-agency-modern' ) . '</span></div>';
					echo '</div>';
				}

				echo '</div>';
			}

			echo '</article>';
			echo '</section>';

			echo '<section class="tam-admin-card">';
			echo '<div class="tam-admin-card__head"><h3>' . esc_html__( 'Booking mới nhất', 'travel-agency-modern' ) . '</h3><a class="button button-secondary" href="' . esc_url( tam_backend_admin_page_url( 'tam-admin-bookings' ) ) . '">' . esc_html__( 'Xem tất cả bookings', 'travel-agency-modern' ) . '</a></div>';
			echo '<table class="widefat striped tam-admin-table">';
			echo '<thead><tr><th>' . esc_html__( 'Booking', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Khách hàng', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Tour', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Ngày đi', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Tổng tiền', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Trạng thái', 'travel-agency-modern' ) . '</th></tr></thead><tbody>';

			if ( empty( $recent_bookings ) ) {
				echo '<tr><td colspan="6">' . esc_html__( 'Chưa có booking nào.', 'travel-agency-modern' ) . '</td></tr>';
			} else {
				foreach ( $recent_bookings as $booking ) {
					echo '<tr>';
					echo '<td>#' . esc_html( isset( $booking['id'] ) ? (int) $booking['id'] : 0 ) . '<br /><small>' . esc_html( tam_backend_admin_format_datetime( isset( $booking['created_at'] ) ? (string) $booking['created_at'] : '' ) ) . '</small></td>';
					echo '<td>' . esc_html( isset( $booking['user_name'] ) ? (string) $booking['user_name'] : '' ) . '</td>';
					echo '<td>' . esc_html( isset( $booking['tour_title'] ) ? (string) $booking['tour_title'] : '' ) . '</td>';
					echo '<td>' . esc_html( tam_backend_admin_format_date( isset( $booking['travel_date'] ) ? (string) $booking['travel_date'] : '' ) ) . '</td>';
					echo '<td>' . esc_html( tam_backend_admin_format_amount( isset( $booking['total_price'] ) ? $booking['total_price'] : 0 ) ) . '</td>';
					echo '<td>' . wp_kses_post( tam_backend_admin_badge( isset( $booking['status'] ) ? $booking['status'] : '' ) ) . '</td>';
					echo '</tr>';
				}
			}

			echo '</tbody></table>';
			echo '</section>';
		}
	);
}

/**
 * Render the tour create/edit form.
 *
 * @param array $form    Form state.
 * @param array $errors  Validation errors.
 * @param int    $tour_id       Tour ID being edited.
 * @param string $general_error Optional non-field error.
 * @return void
 */
function tam_backend_admin_render_tour_form( $form, $errors, $tour_id = 0, $general_error = '' ) {
	$form   = wp_parse_args( is_array( $form ) ? $form : array(), tam_backend_admin_default_tour_form() );
	$errors = is_array( $errors ) ? $errors : array();
	$general_error = trim( (string) $general_error );

	echo '<section class="tam-admin-card">';
	echo '<div class="tam-admin-card__head"><h3>' . esc_html( $tour_id > 0 ? __( 'Chỉnh sửa tour', 'travel-agency-modern' ) : __( 'Tạo tour mới', 'travel-agency-modern' ) ) . '</h3><a class="button button-secondary" href="' . esc_url( tam_backend_admin_page_url( 'tam-admin-tours' ) ) . '">' . esc_html__( 'Quay lại danh sách', 'travel-agency-modern' ) . '</a></div>';

	if ( ! empty( $errors ) ) {
		echo '<div class="tam-admin-form-alert tam-admin-form-alert--error">';
		echo '<strong>' . esc_html__( 'Hãy kiểm tra các trường được tô đỏ.', 'travel-agency-modern' ) . '</strong>';
		echo '<span>' . esc_html__( 'Những dữ liệu chưa hợp lệ đã được đánh dấu ngay trong form để bạn sửa nhanh hơn.', 'travel-agency-modern' ) . '</span>';
		echo '</div>';
	} elseif ( '' !== $general_error ) {
		echo '<div class="tam-admin-form-alert tam-admin-form-alert--error">';
		echo '<strong>' . esc_html( $general_error ) . '</strong>';
		echo '</div>';
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" enctype="multipart/form-data" class="tam-admin-stack">';
	wp_nonce_field( 'tam_backend_admin_tour_save', 'tam_backend_admin_tour_nonce' );
	echo '<input type="hidden" name="action" value="tam_backend_admin_tour_save" />';
	echo '<input type="hidden" name="tour_id" value="' . esc_attr( $tour_id ) . '" />';
	echo '<input type="hidden" name="tour[imageUrl]" value="' . esc_attr( $form['imageUrl'] ) . '" />';
	echo '<div class="tam-admin-form-grid">';
	tam_backend_admin_render_field( 'title', __( 'Tên tour', 'travel-agency-modern' ), $form['title'], array( 'placeholder' => __( 'Ví dụ: Vịnh Hạ Long Premium', 'travel-agency-modern' ) ), $errors );
	tam_backend_admin_render_field( 'location', __( 'Địa điểm', 'travel-agency-modern' ), $form['location'], array( 'placeholder' => __( 'Ví dụ: Quảng Ninh', 'travel-agency-modern' ) ), $errors );
	tam_backend_admin_render_field( 'status', __( 'Trạng thái', 'travel-agency-modern' ), $form['status'], array( 'type' => 'select', 'options' => array( 'Draft' => 'Draft', 'Active' => 'Active', 'Closed' => 'Closed' ) ), $errors );
	tam_backend_admin_render_field( 'duration', __( 'Số ngày', 'travel-agency-modern' ), $form['duration'], array( 'type' => 'number', 'placeholder' => '3' ), $errors );
	tam_backend_admin_render_field( 'durationText', __( 'Nhãn thời lượng', 'travel-agency-modern' ), $form['durationText'], array( 'placeholder' => __( 'Ví dụ: 3 ngày 2 đêm', 'travel-agency-modern' ) ), $errors );
	tam_backend_admin_render_field( 'price', __( 'Giá', 'travel-agency-modern' ), $form['price'], array( 'type' => 'number', 'placeholder' => '4500000' ), $errors );
	tam_backend_admin_render_field( 'maxPeople', __( 'Số khách tối đa', 'travel-agency-modern' ), $form['maxPeople'], array( 'type' => 'number', 'placeholder' => '18' ), $errors );
	tam_backend_admin_render_field( 'transport', __( 'Phương tiện', 'travel-agency-modern' ), $form['transport'], array( 'placeholder' => __( 'Máy bay / xe du lịch', 'travel-agency-modern' ) ), $errors );
	tam_backend_admin_render_field( 'departureNote', __( 'Ghi chú khởi hành', 'travel-agency-modern' ), $form['departureNote'], array( 'placeholder' => __( 'Khởi hành từ Hà Nội', 'travel-agency-modern' ) ), $errors );
	tam_backend_admin_render_field( 'departureSchedule', __( 'Lịch khởi hành', 'travel-agency-modern' ), $form['departureSchedule'], array( 'placeholder' => __( 'Mỗi thứ Sáu hàng tuần', 'travel-agency-modern' ) ), $errors );
	tam_backend_admin_render_field( 'meetingPoint', __( 'Điểm tập trung', 'travel-agency-modern' ), $form['meetingPoint'], array( 'placeholder' => __( 'Sân bay Nội Bài', 'travel-agency-modern' ) ), $errors );
	tam_backend_admin_render_field( 'season', __( 'Mùa đẹp', 'travel-agency-modern' ), $form['season'], array( 'placeholder' => __( 'Tháng 10 - 3', 'travel-agency-modern' ) ), $errors );
	tam_backend_admin_render_field( 'badge', __( 'Badge nổi bật', 'travel-agency-modern' ), $form['badge'], array( 'placeholder' => __( 'Bestseller / Limited', 'travel-agency-modern' ) ), $errors );
	tam_backend_admin_render_field( 'tagline', __( 'Tagline ngắn', 'travel-agency-modern' ), $form['tagline'], array( 'placeholder' => __( 'Ngắm vịnh trên du thuyền 5 sao', 'travel-agency-modern' ) ), $errors );
	tam_backend_admin_render_field( 'curatorName', __( 'Người phụ trách', 'travel-agency-modern' ), $form['curatorName'], array( 'placeholder' => __( 'Tên chuyên viên', 'travel-agency-modern' ) ), $errors );
	tam_backend_admin_render_field( 'description', __( 'Mô tả tour', 'travel-agency-modern' ), $form['description'], array( 'type' => 'textarea', 'rows' => 6, 'full' => true ), $errors );
	tam_backend_admin_render_field( 'curatorNote', __( 'Ghi chú curator', 'travel-agency-modern' ), $form['curatorNote'], array( 'type' => 'textarea', 'rows' => 4, 'full' => true ), $errors );
	tam_backend_admin_render_field( 'includes_text', __( 'Bao gồm', 'travel-agency-modern' ), $form['includes_text'], array( 'type' => 'textarea', 'rows' => 5, 'help' => __( 'Mỗi dòng là một hạng mục bao gồm.', 'travel-agency-modern' ), 'full' => true ), $errors );
	tam_backend_admin_render_field( 'promise_text', __( 'Giá trị nhận được', 'travel-agency-modern' ), $form['promise_text'], array( 'type' => 'textarea', 'rows' => 5, 'help' => __( 'Mỗi dòng là một promise item.', 'travel-agency-modern' ), 'full' => true ), $errors );
	tam_backend_admin_render_field( 'overview_text', __( 'Overview cards', 'travel-agency-modern' ), $form['overview_text'], array( 'type' => 'textarea', 'rows' => 5, 'help' => __( 'Mỗi dòng: tiêu đề | mô tả | icon', 'travel-agency-modern' ), 'full' => true ), $errors );
	tam_backend_admin_render_field( 'highlights_text', __( 'Highlights', 'travel-agency-modern' ), $form['highlights_text'], array( 'type' => 'textarea', 'rows' => 5, 'help' => __( 'Mỗi dòng: tiêu đề | mô tả | icon', 'travel-agency-modern' ), 'full' => true ), $errors );
	tam_backend_admin_render_field( 'itinerary_text', __( 'Lịch trình', 'travel-agency-modern' ), $form['itinerary_text'], array( 'type' => 'textarea', 'rows' => 6, 'help' => __( 'Mỗi dòng: nhãn ngày | tiêu đề | mô tả', 'travel-agency-modern' ), 'full' => true ), $errors );
	echo '<label class="tam-admin-field tam-admin-field--full"><span>' . esc_html__( 'Ảnh tour', 'travel-agency-modern' ) . '</span><input type="file" name="tour_image" accept=".jpg,.jpeg,.png,.webp" />';

	if ( ! empty( $form['imageUrl'] ) ) {
		echo '<small>' . esc_html__( 'Ảnh hiện tại từ backend:', 'travel-agency-modern' ) . ' <a href="' . esc_url( tam_backend_api_resolve_asset_url( $form['imageUrl'] ) ) . '" target="_blank" rel="noopener">' . esc_html( $form['imageUrl'] ) . '</a></small>';
	}

	echo '</label>';
	echo '</div>';
	echo '<div class="tam-admin-actions"><button type="submit" class="button button-primary">' . esc_html( $tour_id > 0 ? __( 'Lưu tour', 'travel-agency-modern' ) : __( 'Tạo tour', 'travel-agency-modern' ) ) . '</button></div>';
	echo '</form>';
	echo '</section>';
}

/**
 * Render the tours page.
 *
 * @return void
 */
function tam_backend_admin_render_tours_page() {
	tam_backend_admin_render_shell(
		'tam-admin-tours',
		static function () {
			$view       = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list';
			$search     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
			$status     = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
			$paged      = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
			$tour_id    = isset( $_GET['tour_id'] ) ? absint( $_GET['tour_id'] ) : 0;
			$form_state    = tam_backend_admin_pull_flash( 'tour_form', null );
			$form_errors   = tam_backend_admin_pull_flash( 'tour_errors', array() );
			$general_error = (string) tam_backend_admin_pull_flash( 'tour_general_error', '' );

			if ( 'create' === $view || 'edit' === $view ) {
				$form = is_array( $form_state ) ? $form_state : tam_backend_admin_default_tour_form();

				if ( 'edit' === $view && $tour_id > 0 && empty( $form_state ) ) {
					$detail_response = tam_backend_admin_get_tour( $tour_id );

					if ( ! $detail_response['success'] ) {
						tam_backend_admin_render_api_error( $detail_response, tam_backend_admin_page_url( 'tam-admin-tours' ), __( 'Quay lại danh sách', 'travel-agency-modern' ) );
						return;
					}

					$form = tam_backend_admin_tour_form_from_tour( $detail_response['data']['tour'] );
				}

				tam_backend_admin_render_tour_form( $form, $form_errors, $tour_id, $general_error );
				return;
			}

			$response = tam_backend_admin_get_tours(
				array(
					'page'   => $paged,
					'limit'  => 10,
					'search' => $search,
					'status' => $status,
				)
			);

			if ( ! $response['success'] ) {
				tam_backend_admin_render_api_error( $response, tam_backend_admin_page_url( 'tam-admin-connect' ), __( 'Kiểm tra kết nối backend', 'travel-agency-modern' ) );
				return;
			}

			$tours      = isset( $response['data']['tours'] ) && is_array( $response['data']['tours'] ) ? $response['data']['tours'] : array();
			$pagination = isset( $response['data']['pagination'] ) && is_array( $response['data']['pagination'] ) ? $response['data']['pagination'] : array();
			$summary_response = tam_backend_admin_get_tours(
				array(
					'page'  => 1,
					'limit' => 200,
				)
			);
			$summary_tours = $summary_response['success'] && ! empty( $summary_response['data']['tours'] ) && is_array( $summary_response['data']['tours'] ) ? $summary_response['data']['tours'] : array();
			$active_count  = 0;
			$closed_count  = 0;

			foreach ( $summary_tours as $tour ) {
				$tour_status = strtoupper( isset( $tour['status'] ) ? (string) $tour['status'] : '' );

				if ( 'ACTIVE' === $tour_status ) {
					++$active_count;
				} elseif ( 'CLOSED' === $tour_status ) {
					++$closed_count;
				}
			}

			echo '<section class="tam-admin-stats">';
			tam_backend_admin_render_stat_card( __( 'Tổng tours', 'travel-agency-modern' ), number_format_i18n( isset( $pagination['total'] ) ? (int) $pagination['total'] : count( $tours ) ), __( 'Từ backend tours API', 'travel-agency-modern' ) );
			tam_backend_admin_render_stat_card( __( 'Tours Active', 'travel-agency-modern' ), number_format_i18n( $active_count ), __( 'Đang mở bán', 'travel-agency-modern' ) );
			tam_backend_admin_render_stat_card( __( 'Tours Closed', 'travel-agency-modern' ), number_format_i18n( $closed_count ), __( 'Đã ẩn khỏi bán hàng', 'travel-agency-modern' ) );
			tam_backend_admin_render_stat_card( __( 'Tạo mới', 'travel-agency-modern' ), __( 'Sẵn sàng', 'travel-agency-modern' ), __( 'Có form backend sync đầy đủ', 'travel-agency-modern' ) );
			echo '</section>';

			echo '<section class="tam-admin-card">';
			echo '<div class="tam-admin-card__head"><h3>' . esc_html__( 'Quản lý tours', 'travel-agency-modern' ) . '</h3><a class="button button-primary" href="' . esc_url( tam_backend_admin_page_url( 'tam-admin-tours', array( 'view' => 'create' ) ) ) . '">' . esc_html__( 'Tạo tour mới', 'travel-agency-modern' ) . '</a></div>';
			echo '<form method="get" class="tam-admin-toolbar">';
			echo '<input type="hidden" name="page" value="tam-admin-tours" />';
			echo '<input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Tìm theo tên hoặc địa điểm...', 'travel-agency-modern' ) . '" />';
			echo '<select name="status"><option value="">' . esc_html__( 'Tất cả trạng thái', 'travel-agency-modern' ) . '</option><option value="Active" ' . selected( $status, 'Active', false ) . '>Active</option><option value="Draft" ' . selected( $status, 'Draft', false ) . '>Draft</option><option value="Closed" ' . selected( $status, 'Closed', false ) . '>Closed</option></select>';
			echo '<button type="submit" class="button button-secondary">' . esc_html__( 'Lọc', 'travel-agency-modern' ) . '</button>';
			echo '</form>';
			echo '<table class="widefat striped tam-admin-table">';
			echo '<thead><tr><th>' . esc_html__( 'Tour', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Địa điểm', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Thời lượng', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Giá', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Review', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Trạng thái', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Hành động', 'travel-agency-modern' ) . '</th></tr></thead><tbody>';

			if ( empty( $tours ) ) {
				echo '<tr><td colspan="7">' . esc_html__( 'Không tìm thấy tour phù hợp.', 'travel-agency-modern' ) . '</td></tr>';
			} else {
				foreach ( $tours as $tour ) {
					$current_status = isset( $tour['status'] ) ? (string) $tour['status'] : '';
					$image_url      = ! empty( $tour['imageUrl'] ) ? tam_backend_api_resolve_asset_url( $tour['imageUrl'] ) : '';

					echo '<tr>';
					echo '<td><div class="tam-admin-media">';

					if ( $image_url ) {
						echo '<img src="' . esc_url( $image_url ) . '" alt="" />';
					} else {
						echo '<span class="tam-admin-media__placeholder dashicons dashicons-format-image"></span>';
					}

					echo '<div><strong>' . esc_html( isset( $tour['title'] ) ? (string) $tour['title'] : '' ) . '</strong><span>#' . esc_html( isset( $tour['id'] ) ? (int) $tour['id'] : 0 ) . '</span></div></div></td>';
					echo '<td>' . esc_html( isset( $tour['location'] ) ? (string) $tour['location'] : '' ) . '</td>';
					echo '<td>' . esc_html( isset( $tour['durationText'] ) && $tour['durationText'] ? (string) $tour['durationText'] : ( isset( $tour['duration'] ) ? (int) $tour['duration'] . ' ngày' : '' ) ) . '</td>';
					echo '<td>' . esc_html( tam_backend_admin_format_amount( isset( $tour['price'] ) ? $tour['price'] : 0 ) ) . '</td>';
					echo '<td>' . esc_html( number_format_i18n( isset( $tour['reviews'] ) ? (int) $tour['reviews'] : 0 ) ) . ' · ' . esc_html( isset( $tour['rating'] ) ? number_format_i18n( (float) $tour['rating'], 1 ) : '0.0' ) . '</td>';
					echo '<td>' . wp_kses_post( tam_backend_admin_badge( $current_status ) ) . '</td>';
					echo '<td><div class="tam-admin-table__actions">';
					echo '<a class="button button-small" href="' . esc_url( tam_backend_admin_page_url( 'tam-admin-tours', array( 'view' => 'edit', 'tour_id' => isset( $tour['id'] ) ? (int) $tour['id'] : 0 ) ) ) . '">' . esc_html__( 'Sửa', 'travel-agency-modern' ) . '</a>';

					if ( 'Active' === $current_status ) {
						echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-tam-confirm="' . esc_attr__( 'Ẩn tour này khỏi trạng thái mở bán?', 'travel-agency-modern' ) . '">';
						wp_nonce_field( 'tam_backend_admin_tour_status', 'tam_backend_admin_status_nonce' );
						echo '<input type="hidden" name="action" value="tam_backend_admin_tour_status" /><input type="hidden" name="tour_id" value="' . esc_attr( (int) $tour['id'] ) . '" /><input type="hidden" name="status" value="Closed" /><button type="submit" class="button button-small">' . esc_html__( 'Close', 'travel-agency-modern' ) . '</button></form>';
					} else {
						echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
						wp_nonce_field( 'tam_backend_admin_tour_status', 'tam_backend_admin_status_nonce' );
						echo '<input type="hidden" name="action" value="tam_backend_admin_tour_status" /><input type="hidden" name="tour_id" value="' . esc_attr( (int) $tour['id'] ) . '" /><input type="hidden" name="status" value="Active" /><button type="submit" class="button button-small button-primary">' . esc_html__( 'Activate', 'travel-agency-modern' ) . '</button></form>';
					}

					echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-tam-confirm="' . esc_attr__( 'Xóa tour này khỏi backend? Nếu đã có booking, backend sẽ từ chối thao tác.', 'travel-agency-modern' ) . '">';
					wp_nonce_field( 'tam_backend_admin_tour_delete', 'tam_backend_admin_delete_nonce' );
					echo '<input type="hidden" name="action" value="tam_backend_admin_tour_delete" /><input type="hidden" name="tour_id" value="' . esc_attr( (int) $tour['id'] ) . '" /><button type="submit" class="button button-small button-link-delete">' . esc_html__( 'Xóa', 'travel-agency-modern' ) . '</button></form>';
					echo '</div></td>';
					echo '</tr>';
				}
			}

			echo '</tbody></table>';
			tam_backend_admin_render_pagination(
				isset( $pagination['page'] ) ? (int) $pagination['page'] : $paged,
				isset( $pagination['totalPages'] ) ? (int) $pagination['totalPages'] : 1,
				array(
					's'      => $search,
					'status' => $status,
				)
			);
			echo '</section>';
		}
	);
}

/**
 * Render the bookings page.
 *
 * @return void
 */
function tam_backend_admin_render_bookings_page() {
	tam_backend_admin_render_shell(
		'tam-admin-bookings',
		static function () {
			$status     = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
			$search     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
			$paged      = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
			$booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;
			$response   = tam_backend_admin_get_bookings( array( 'status' => $status ) );

			if ( ! $response['success'] ) {
				tam_backend_admin_render_api_error( $response, tam_backend_admin_page_url( 'tam-admin-connect' ), __( 'Kiểm tra kết nối backend', 'travel-agency-modern' ) );
				return;
			}

			$bookings = isset( $response['data']['bookings'] ) && is_array( $response['data']['bookings'] ) ? $response['data']['bookings'] : array();
			$bookings = tam_backend_admin_filter_bookings( $bookings, $search );
			$summary  = array(
				'total'     => count( $bookings ),
				'pending'   => 0,
				'confirmed' => 0,
				'completed' => 0,
			);

			foreach ( $bookings as $booking ) {
				$booking_status = strtoupper( isset( $booking['booking_status'] ) ? (string) $booking['booking_status'] : ( isset( $booking['status'] ) ? (string) $booking['status'] : '' ) );

				if ( in_array( $booking_status, array( 'PENDING', 'PENDING_PAYMENT', 'PENDING_CONFIRMATION', 'PAID', 'PAYMENT_FAILED' ), true ) ) {
					++$summary['pending'];
				} elseif ( 'CONFIRMED' === $booking_status ) {
					++$summary['confirmed'];
				} elseif ( 'COMPLETED' === $booking_status ) {
					++$summary['completed'];
				}
			}

			$pagination = tam_backend_admin_paginate_array( $bookings, $paged, 10 );
			$detail     = null;

			if ( $booking_id > 0 ) {
				$detail_response = tam_backend_admin_get_booking( $booking_id );

				if ( $detail_response['success'] && ! empty( $detail_response['data']['booking'] ) ) {
					$detail = $detail_response['data']['booking'];
				}
			}

			echo '<section class="tam-admin-stats">';
			tam_backend_admin_render_stat_card( __( 'Tổng booking', 'travel-agency-modern' ), number_format_i18n( $summary['total'] ) );
			tam_backend_admin_render_stat_card( __( 'Đang chờ xử lý', 'travel-agency-modern' ), number_format_i18n( $summary['pending'] ) );
			tam_backend_admin_render_stat_card( __( 'Đã xác nhận', 'travel-agency-modern' ), number_format_i18n( $summary['confirmed'] ) );
			tam_backend_admin_render_stat_card( __( 'Đã hoàn tất', 'travel-agency-modern' ), number_format_i18n( $summary['completed'] ) );
			echo '</section>';

			if ( ! empty( $detail ) ) {
				echo '<section class="tam-admin-card">';
				echo '<div class="tam-admin-card__head"><h3>' . esc_html__( 'Chi tiết booking', 'travel-agency-modern' ) . '</h3><a class="button button-secondary" href="' . esc_url( tam_backend_admin_page_url( 'tam-admin-bookings', array( 'status' => $status, 's' => $search, 'paged' => $paged ) ) ) . '">' . esc_html__( 'Đóng chi tiết', 'travel-agency-modern' ) . '</a></div>';
				echo '<div class="tam-admin-kv">';
				echo '<div><span>ID</span><strong>#' . esc_html( isset( $detail['id'] ) ? (int) $detail['id'] : 0 ) . '</strong></div>';
				echo '<div><span>' . esc_html__( 'Khách hàng', 'travel-agency-modern' ) . '</span><strong>' . esc_html( isset( $detail['user_name'] ) ? (string) $detail['user_name'] : '' ) . '</strong></div>';
				echo '<div><span>' . esc_html__( 'Email', 'travel-agency-modern' ) . '</span><strong>' . esc_html( isset( $detail['user_email'] ) ? (string) $detail['user_email'] : '' ) . '</strong></div>';
				echo '<div><span>' . esc_html__( 'Tour', 'travel-agency-modern' ) . '</span><strong>' . esc_html( isset( $detail['tour_title'] ) ? (string) $detail['tour_title'] : '' ) . '</strong></div>';
				echo '<div><span>' . esc_html__( 'Ngày đi', 'travel-agency-modern' ) . '</span><strong>' . esc_html( tam_backend_admin_format_date( isset( $detail['travel_date'] ) ? (string) $detail['travel_date'] : '' ) ) . '</strong></div>';
				echo '<div><span>' . esc_html__( 'Số khách', 'travel-agency-modern' ) . '</span><strong>' . esc_html( isset( $detail['number_of_people'] ) ? (int) $detail['number_of_people'] : 0 ) . '</strong></div>';
				echo '<div><span>' . esc_html__( 'Tổng tiền', 'travel-agency-modern' ) . '</span><strong>' . esc_html( tam_backend_admin_format_amount( isset( $detail['total_price'] ) ? $detail['total_price'] : 0 ) ) . '</strong></div>';
				echo '<div><span>' . esc_html__( 'Booking status', 'travel-agency-modern' ) . '</span><strong>' . wp_kses_post( tam_backend_admin_badge( isset( $detail['booking_status'] ) ? $detail['booking_status'] : ( isset( $detail['status'] ) ? $detail['status'] : '' ) ) ) . '</strong></div>';
				echo '</div>';

				if ( ! empty( $detail['payments'] ) && is_array( $detail['payments'] ) ) {
					echo '<h4>' . esc_html__( 'Lịch sử payment', 'travel-agency-modern' ) . '</h4>';
					echo '<table class="widefat striped tam-admin-table"><thead><tr><th>ID</th><th>' . esc_html__( 'Method', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Số tiền', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Trạng thái', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Paid at', 'travel-agency-modern' ) . '</th></tr></thead><tbody>';

					foreach ( $detail['payments'] as $payment ) {
						echo '<tr><td>#' . esc_html( isset( $payment['id'] ) ? (int) $payment['id'] : 0 ) . '</td><td>' . esc_html( isset( $payment['method'] ) ? (string) $payment['method'] : '' ) . '</td><td>' . esc_html( tam_backend_admin_format_amount( isset( $payment['amount'] ) ? $payment['amount'] : 0 ) ) . '</td><td>' . wp_kses_post( tam_backend_admin_badge( isset( $payment['status'] ) ? $payment['status'] : '' ) ) . '</td><td>' . esc_html( tam_backend_admin_format_datetime( isset( $payment['paid_at'] ) ? (string) $payment['paid_at'] : '' ) ) . '</td></tr>';
					}

					echo '</tbody></table>';
				}

				echo '</section>';
			}

			echo '<section class="tam-admin-card">';
			echo '<div class="tam-admin-card__head"><h3>' . esc_html__( 'Danh sách bookings', 'travel-agency-modern' ) . '</h3></div>';
			echo '<form method="get" class="tam-admin-toolbar">';
			echo '<input type="hidden" name="page" value="tam-admin-bookings" />';
			echo '<input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Tìm theo mã booking, tour, khách hàng...', 'travel-agency-modern' ) . '" />';
			echo '<select name="status"><option value="">' . esc_html__( 'Tất cả trạng thái', 'travel-agency-modern' ) . '</option><option value="PENDING_CONFIRMATION" ' . selected( $status, 'PENDING_CONFIRMATION', false ) . '>Pending confirmation</option><option value="CONFIRMED" ' . selected( $status, 'CONFIRMED', false ) . '>Confirmed</option><option value="COMPLETED" ' . selected( $status, 'COMPLETED', false ) . '>Completed</option><option value="CANCELLED" ' . selected( $status, 'CANCELLED', false ) . '>Cancelled</option></select>';
			echo '<button type="submit" class="button button-secondary">' . esc_html__( 'Lọc', 'travel-agency-modern' ) . '</button>';
			echo '</form>';
			echo '<table class="widefat striped tam-admin-table"><thead><tr><th>' . esc_html__( 'Booking', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Khách hàng', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Tour', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Ngày đi', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Tổng tiền', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Trạng thái', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Hành động', 'travel-agency-modern' ) . '</th></tr></thead><tbody>';

			if ( empty( $pagination['items'] ) ) {
				echo '<tr><td colspan="7">' . esc_html__( 'Không có booking nào phù hợp.', 'travel-agency-modern' ) . '</td></tr>';
			} else {
				foreach ( $pagination['items'] as $booking ) {
					$booking_status = isset( $booking['booking_status'] ) ? (string) $booking['booking_status'] : ( isset( $booking['status'] ) ? (string) $booking['status'] : '' );
					$payment_status = isset( $booking['payment_status'] ) ? (string) $booking['payment_status'] : '';
					echo '<tr>';
					echo '<td><strong>#' . esc_html( isset( $booking['id'] ) ? (int) $booking['id'] : 0 ) . '</strong><small>' . esc_html( tam_backend_admin_format_datetime( isset( $booking['created_at'] ) ? (string) $booking['created_at'] : '' ) ) . '</small></td>';
					echo '<td>' . esc_html( isset( $booking['user_name'] ) ? (string) $booking['user_name'] : '' ) . '<small>' . esc_html( isset( $booking['user_email'] ) ? (string) $booking['user_email'] : '' ) . '</small></td>';
					echo '<td>' . esc_html( isset( $booking['tour_title'] ) ? (string) $booking['tour_title'] : '' ) . '</td>';
					echo '<td>' . esc_html( tam_backend_admin_format_date( isset( $booking['travel_date'] ) ? (string) $booking['travel_date'] : '' ) ) . '</td>';
					echo '<td>' . esc_html( tam_backend_admin_format_amount( isset( $booking['total_price'] ) ? $booking['total_price'] : 0 ) ) . '<small>' . esc_html( $payment_status ? 'Payment: ' . $payment_status : '' ) . '</small></td>';
					echo '<td>' . wp_kses_post( tam_backend_admin_badge( $booking_status ) ) . '</td>';
					echo '<td><div class="tam-admin-table__actions">';
					echo '<a class="button button-small" href="' . esc_url( tam_backend_admin_page_url( 'tam-admin-bookings', array( 'booking_id' => isset( $booking['id'] ) ? (int) $booking['id'] : 0, 'status' => $status, 's' => $search, 'paged' => $paged ) ) ) . '">' . esc_html__( 'Xem', 'travel-agency-modern' ) . '</a>';

					if ( in_array( strtoupper( $booking_status ), array( 'PENDING', 'PENDING_PAYMENT', 'PENDING_CONFIRMATION', 'PAID' ), true ) ) {
						echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
						wp_nonce_field( 'tam_backend_admin_booking_status', 'tam_backend_admin_booking_nonce' );
						echo '<input type="hidden" name="action" value="tam_backend_admin_booking_status" /><input type="hidden" name="booking_id" value="' . esc_attr( (int) $booking['id'] ) . '" /><input type="hidden" name="status" value="CONFIRMED" /><button type="submit" class="button button-small button-primary">' . esc_html__( 'Confirm', 'travel-agency-modern' ) . '</button></form>';
					} elseif ( 'CONFIRMED' === strtoupper( $booking_status ) ) {
						echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
						wp_nonce_field( 'tam_backend_admin_booking_status', 'tam_backend_admin_booking_nonce' );
						echo '<input type="hidden" name="action" value="tam_backend_admin_booking_status" /><input type="hidden" name="booking_id" value="' . esc_attr( (int) $booking['id'] ) . '" /><input type="hidden" name="status" value="COMPLETED" /><button type="submit" class="button button-small">' . esc_html__( 'Complete', 'travel-agency-modern' ) . '</button></form>';
					}

					if ( ! in_array( strtoupper( $booking_status ), array( 'CANCELLED', 'COMPLETED', 'REFUNDED' ), true ) ) {
						echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-tam-confirm="' . esc_attr__( 'Hủy booking này trên backend?', 'travel-agency-modern' ) . '">';
						wp_nonce_field( 'tam_backend_admin_booking_status', 'tam_backend_admin_booking_nonce' );
						echo '<input type="hidden" name="action" value="tam_backend_admin_booking_status" /><input type="hidden" name="booking_id" value="' . esc_attr( (int) $booking['id'] ) . '" /><input type="hidden" name="status" value="CANCELLED" /><button type="submit" class="button button-small button-link-delete">' . esc_html__( 'Cancel', 'travel-agency-modern' ) . '</button></form>';
					}

					echo '</div></td></tr>';
				}
			}

			echo '</tbody></table>';
			tam_backend_admin_render_pagination( $pagination['paged'], $pagination['total_pages'], array( 'status' => $status, 's' => $search ) );
			echo '</section>';
		}
	);
}

/**
 * Render the users page.
 *
 * @return void
 */
function tam_backend_admin_render_users_page() {
	tam_backend_admin_render_shell(
		'tam-admin-users',
		static function () {
			$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
			$paged   = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
			$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
			$response = tam_backend_admin_get_users( array( 'search' => $search ) );

			if ( ! $response['success'] ) {
				tam_backend_admin_render_api_error( $response, tam_backend_admin_page_url( 'tam-admin-connect' ), __( 'Kiểm tra kết nối backend', 'travel-agency-modern' ) );
				return;
			}

			$users      = isset( $response['data']['users'] ) && is_array( $response['data']['users'] ) ? $response['data']['users'] : array();
			$pagination = tam_backend_admin_paginate_array( $users, $paged, 10 );
			$detail     = null;

			if ( $user_id > 0 ) {
				$detail_response = tam_backend_admin_get_user( $user_id );

				if ( $detail_response['success'] && ! empty( $detail_response['data']['user'] ) ) {
					$detail = $detail_response['data']['user'];
				}
			}

			echo '<section class="tam-admin-stats">';
			tam_backend_admin_render_stat_card( __( 'Tổng users', 'travel-agency-modern' ), number_format_i18n( count( $users ) ) );
			tam_backend_admin_render_stat_card( __( 'USER role', 'travel-agency-modern' ), number_format_i18n( count( array_filter( $users, static function ( $user ) { return isset( $user['role'] ) && 'USER' === strtoupper( (string) $user['role'] ); } ) ) ) );
			tam_backend_admin_render_stat_card( __( 'STAFF role', 'travel-agency-modern' ), number_format_i18n( count( array_filter( $users, static function ( $user ) { return isset( $user['role'] ) && 'STAFF' === strtoupper( (string) $user['role'] ); } ) ) ) );
			tam_backend_admin_render_stat_card( __( 'Đã lọc', 'travel-agency-modern' ), $search ? __( 'Có tìm kiếm', 'travel-agency-modern' ) : __( 'Toàn bộ', 'travel-agency-modern' ) );
			echo '</section>';

			if ( ! empty( $detail ) ) {
				echo '<section class="tam-admin-card">';
				echo '<div class="tam-admin-card__head"><h3>' . esc_html__( 'Chi tiết user', 'travel-agency-modern' ) . '</h3><a class="button button-secondary" href="' . esc_url( tam_backend_admin_page_url( 'tam-admin-users', array( 's' => $search, 'paged' => $paged ) ) ) . '">' . esc_html__( 'Đóng chi tiết', 'travel-agency-modern' ) . '</a></div>';
				echo '<div class="tam-admin-kv">';
				echo '<div><span>ID</span><strong>#' . esc_html( isset( $detail['id'] ) ? (int) $detail['id'] : 0 ) . '</strong></div>';
				echo '<div><span>' . esc_html__( 'Tên', 'travel-agency-modern' ) . '</span><strong>' . esc_html( isset( $detail['name'] ) ? (string) $detail['name'] : '' ) . '</strong></div>';
				echo '<div><span>Email</span><strong>' . esc_html( isset( $detail['email'] ) ? (string) $detail['email'] : '' ) . '</strong></div>';
				echo '<div><span>' . esc_html__( 'Số điện thoại', 'travel-agency-modern' ) . '</span><strong>' . esc_html( isset( $detail['phone'] ) ? (string) $detail['phone'] : '' ) . '</strong></div>';
				echo '<div><span>' . esc_html__( 'Role', 'travel-agency-modern' ) . '</span><strong>' . wp_kses_post( tam_backend_admin_badge( isset( $detail['role'] ) ? $detail['role'] : '' ) ) . '</strong></div>';
				echo '<div><span>' . esc_html__( 'Ngày tạo', 'travel-agency-modern' ) . '</span><strong>' . esc_html( tam_backend_admin_format_datetime( isset( $detail['created_at'] ) ? (string) $detail['created_at'] : '' ) ) . '</strong></div>';
				echo '</div>';

				if ( ! empty( $detail['bookings'] ) && is_array( $detail['bookings'] ) ) {
					echo '<h4>' . esc_html__( 'Lịch sử booking', 'travel-agency-modern' ) . '</h4>';
					echo '<table class="widefat striped tam-admin-table"><thead><tr><th>ID</th><th>' . esc_html__( 'Tour', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Ngày đi', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Tổng', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Trạng thái', 'travel-agency-modern' ) . '</th></tr></thead><tbody>';

					foreach ( $detail['bookings'] as $booking ) {
						echo '<tr><td>#' . esc_html( isset( $booking['id'] ) ? (int) $booking['id'] : 0 ) . '</td><td>' . esc_html( isset( $booking['tour_title'] ) ? (string) $booking['tour_title'] : '' ) . '</td><td>' . esc_html( tam_backend_admin_format_date( isset( $booking['travel_date'] ) ? (string) $booking['travel_date'] : '' ) ) . '</td><td>' . esc_html( tam_backend_admin_format_amount( isset( $booking['total_price'] ) ? $booking['total_price'] : 0 ) ) . '</td><td>' . wp_kses_post( tam_backend_admin_badge( isset( $booking['status'] ) ? $booking['status'] : '' ) ) . '</td></tr>';
					}

					echo '</tbody></table>';
				}

				echo '</section>';
			}

			echo '<section class="tam-admin-card">';
			echo '<div class="tam-admin-card__head"><h3>' . esc_html__( 'Danh sách users', 'travel-agency-modern' ) . '</h3></div>';
			echo '<form method="get" class="tam-admin-toolbar"><input type="hidden" name="page" value="tam-admin-users" /><input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Tìm theo tên, email hoặc số điện thoại...', 'travel-agency-modern' ) . '" /><button type="submit" class="button button-secondary">' . esc_html__( 'Tìm', 'travel-agency-modern' ) . '</button></form>';
			echo '<table class="widefat striped tam-admin-table"><thead><tr><th>' . esc_html__( 'User', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Role', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Bookings', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Ngày tạo', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Hành động', 'travel-agency-modern' ) . '</th></tr></thead><tbody>';

			if ( empty( $pagination['items'] ) ) {
				echo '<tr><td colspan="5">' . esc_html__( 'Không tìm thấy user phù hợp.', 'travel-agency-modern' ) . '</td></tr>';
			} else {
				foreach ( $pagination['items'] as $user ) {
					$role = isset( $user['role'] ) ? (string) $user['role'] : 'USER';
					echo '<tr><td><strong>' . esc_html( isset( $user['name'] ) ? (string) $user['name'] : '' ) . '</strong><small>' . esc_html( isset( $user['email'] ) ? (string) $user['email'] : '' ) . '</small></td><td>' . wp_kses_post( tam_backend_admin_badge( $role ) ) . '</td><td>' . esc_html( number_format_i18n( isset( $user['total_bookings'] ) ? (int) $user['total_bookings'] : 0 ) ) . '</td><td>' . esc_html( tam_backend_admin_format_datetime( isset( $user['created_at'] ) ? (string) $user['created_at'] : '' ) ) . '</td><td><div class="tam-admin-table__actions"><a class="button button-small" href="' . esc_url( tam_backend_admin_page_url( 'tam-admin-users', array( 'user_id' => isset( $user['id'] ) ? (int) $user['id'] : 0, 's' => $search, 'paged' => $paged ) ) ) . '">' . esc_html__( 'Xem', 'travel-agency-modern' ) . '</a>';
					echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
					wp_nonce_field( 'tam_backend_admin_user_role', 'tam_backend_admin_user_role_nonce' );
					echo '<input type="hidden" name="action" value="tam_backend_admin_user_role" /><input type="hidden" name="user_id" value="' . esc_attr( (int) $user['id'] ) . '" /><input type="hidden" name="role" value="' . esc_attr( 'USER' === strtoupper( $role ) ? 'STAFF' : 'USER' ) . '" /><button type="submit" class="button button-small">' . esc_html( 'USER' === strtoupper( $role ) ? __( 'Đổi sang STAFF', 'travel-agency-modern' ) : __( 'Đổi sang USER', 'travel-agency-modern' ) ) . '</button></form>';
					echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-tam-confirm="' . esc_attr__( 'Xóa user này khỏi backend? Backend sẽ chặn nếu còn booking đang hoạt động.', 'travel-agency-modern' ) . '">';
					wp_nonce_field( 'tam_backend_admin_user_delete', 'tam_backend_admin_user_delete_nonce' );
					echo '<input type="hidden" name="action" value="tam_backend_admin_user_delete" /><input type="hidden" name="user_id" value="' . esc_attr( (int) $user['id'] ) . '" /><button type="submit" class="button button-small button-link-delete">' . esc_html__( 'Xóa', 'travel-agency-modern' ) . '</button></form></div></td></tr>';
				}
			}

			echo '</tbody></table>';
			tam_backend_admin_render_pagination( $pagination['paged'], $pagination['total_pages'], array( 's' => $search ) );
			echo '</section>';
		}
	);
}

/**
 * Render the reviews page.
 *
 * @return void
 */
function tam_backend_admin_render_reviews_page() {
	tam_backend_admin_render_shell(
		'tam-admin-reviews',
		static function () {
			$status   = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
			$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
			$paged    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
			$response = tam_backend_admin_get_reviews( array( 'status' => $status, 'search' => $search ) );

			if ( ! $response['success'] ) {
				tam_backend_admin_render_api_error( $response, tam_backend_admin_page_url( 'tam-admin-connect' ), __( 'Kiểm tra kết nối backend', 'travel-agency-modern' ) );
				return;
			}

			$reviews     = isset( $response['data']['reviews'] ) && is_array( $response['data']['reviews'] ) ? $response['data']['reviews'] : array();
			$pagination  = tam_backend_admin_paginate_array( $reviews, $paged, 10 );
			$visible     = count( array_filter( $reviews, static function ( $review ) { return isset( $review['status'] ) && 'VISIBLE' === strtoupper( (string) $review['status'] ); } ) );
			$hidden      = count( array_filter( $reviews, static function ( $review ) { return isset( $review['status'] ) && 'HIDDEN' === strtoupper( (string) $review['status'] ); } ) );
			$rating_sum  = 0;

			foreach ( $reviews as $review ) {
				$rating_sum += isset( $review['rating'] ) ? (float) $review['rating'] : 0;
			}

			echo '<section class="tam-admin-stats">';
			tam_backend_admin_render_stat_card( __( 'Tổng reviews', 'travel-agency-modern' ), number_format_i18n( count( $reviews ) ) );
			tam_backend_admin_render_stat_card( __( 'Visible', 'travel-agency-modern' ), number_format_i18n( $visible ) );
			tam_backend_admin_render_stat_card( __( 'Hidden', 'travel-agency-modern' ), number_format_i18n( $hidden ) );
			tam_backend_admin_render_stat_card( __( 'Điểm trung bình', 'travel-agency-modern' ), count( $reviews ) > 0 ? number_format_i18n( $rating_sum / count( $reviews ), 1 ) : '0.0' );
			echo '</section>';

			echo '<section class="tam-admin-card">';
			echo '<div class="tam-admin-card__head"><h3>' . esc_html__( 'Moderation reviews', 'travel-agency-modern' ) . '</h3></div>';
			echo '<form method="get" class="tam-admin-toolbar"><input type="hidden" name="page" value="tam-admin-reviews" /><input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Tìm theo khách, email, tour hoặc nội dung...', 'travel-agency-modern' ) . '" /><select name="status"><option value="ALL">' . esc_html__( 'Tất cả trạng thái', 'travel-agency-modern' ) . '</option><option value="VISIBLE" ' . selected( $status, 'VISIBLE', false ) . '>Visible</option><option value="HIDDEN" ' . selected( $status, 'HIDDEN', false ) . '>Hidden</option></select><button type="submit" class="button button-secondary">' . esc_html__( 'Lọc', 'travel-agency-modern' ) . '</button></form>';
			echo '<table class="widefat striped tam-admin-table"><thead><tr><th>' . esc_html__( 'Khách hàng', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Tour', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Đánh giá', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Ngày đi', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Trạng thái', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Hành động', 'travel-agency-modern' ) . '</th></tr></thead><tbody>';

			if ( empty( $pagination['items'] ) ) {
				echo '<tr><td colspan="6">' . esc_html__( 'Không có review phù hợp.', 'travel-agency-modern' ) . '</td></tr>';
			} else {
				foreach ( $pagination['items'] as $review ) {
					$review_status = isset( $review['status'] ) ? (string) $review['status'] : 'VISIBLE';
					echo '<tr><td><strong>' . esc_html( isset( $review['userName'] ) ? (string) $review['userName'] : '' ) . '</strong><small>' . esc_html( isset( $review['userEmail'] ) ? (string) $review['userEmail'] : '' ) . '</small></td><td>' . esc_html( isset( $review['tourTitle'] ) ? (string) $review['tourTitle'] : '' ) . '</td><td><strong>' . esc_html( number_format_i18n( isset( $review['rating'] ) ? (float) $review['rating'] : 0, 1 ) ) . ' / 5</strong><small>' . esc_html( wp_trim_words( isset( $review['comment'] ) ? (string) $review['comment'] : '', 18, '...' ) ) . '</small></td><td>' . esc_html( tam_backend_admin_format_date( isset( $review['travelDate'] ) ? (string) $review['travelDate'] : '' ) ) . '</td><td>' . wp_kses_post( tam_backend_admin_badge( $review_status ) ) . '</td><td><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
					wp_nonce_field( 'tam_backend_admin_review_status', 'tam_backend_admin_review_nonce' );
					echo '<input type="hidden" name="action" value="tam_backend_admin_review_status" /><input type="hidden" name="review_id" value="' . esc_attr( isset( $review['id'] ) ? (int) $review['id'] : 0 ) . '" /><input type="hidden" name="status" value="' . esc_attr( 'VISIBLE' === strtoupper( $review_status ) ? 'HIDDEN' : 'VISIBLE' ) . '" /><button type="submit" class="button button-small">' . esc_html( 'VISIBLE' === strtoupper( $review_status ) ? __( 'Ẩn review', 'travel-agency-modern' ) : __( 'Hiện review', 'travel-agency-modern' ) ) . '</button></form></td></tr>';
				}
			}

			echo '</tbody></table>';
			tam_backend_admin_render_pagination( $pagination['paged'], $pagination['total_pages'], array( 's' => $search, 'status' => $status ) );
			echo '</section>';
		}
	);
}

/**
 * Render the payments page.
 *
 * @return void
 */
function tam_backend_admin_render_payments_page() {
	tam_backend_admin_render_shell(
		'tam-admin-payments',
		static function () {
			$status   = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
			$method   = isset( $_GET['method'] ) ? sanitize_text_field( wp_unslash( $_GET['method'] ) ) : '';
			$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
			$paged    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
			$response = tam_backend_admin_get_payments( array( 'status' => $status, 'method' => $method ) );

			if ( ! $response['success'] ) {
				tam_backend_admin_render_api_error( $response, tam_backend_admin_page_url( 'tam-admin-connect' ), __( 'Kiểm tra kết nối backend', 'travel-agency-modern' ) );
				return;
			}

			$payments   = isset( $response['data']['payments'] ) && is_array( $response['data']['payments'] ) ? $response['data']['payments'] : array();
			$payments   = tam_backend_admin_filter_payments( $payments, $search );
			$pagination = tam_backend_admin_paginate_array( $payments, $paged, 10 );
			$total      = 0;
			$pending    = 0;
			$success    = 0;

			foreach ( $payments as $payment ) {
				$total += isset( $payment['amount'] ) ? (float) $payment['amount'] : 0;

				if ( isset( $payment['status'] ) && 'PENDING' === strtoupper( (string) $payment['status'] ) ) {
					++$pending;
				}

				if ( isset( $payment['status'] ) && 'SUCCESS' === strtoupper( (string) $payment['status'] ) ) {
					++$success;
				}
			}

			echo '<section class="tam-admin-stats">';
			tam_backend_admin_render_stat_card( __( 'Tổng payment', 'travel-agency-modern' ), number_format_i18n( count( $payments ) ) );
			tam_backend_admin_render_stat_card( __( 'Đang chờ xác nhận', 'travel-agency-modern' ), number_format_i18n( $pending ) );
			tam_backend_admin_render_stat_card( __( 'Đã thành công', 'travel-agency-modern' ), number_format_i18n( $success ) );
			tam_backend_admin_render_stat_card( __( 'Tổng giá trị', 'travel-agency-modern' ), tam_backend_admin_format_amount( $total ) );
			echo '</section>';

			echo '<section class="tam-admin-card">';
			echo '<div class="tam-admin-card__head"><h3>' . esc_html__( 'Payments & transactions', 'travel-agency-modern' ) . '</h3></div>';
			echo '<form method="get" class="tam-admin-toolbar"><input type="hidden" name="page" value="tam-admin-payments" /><input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Tìm theo khách, tour, method, trạng thái...', 'travel-agency-modern' ) . '" /><select name="status"><option value="">' . esc_html__( 'Tất cả trạng thái', 'travel-agency-modern' ) . '</option><option value="PENDING" ' . selected( $status, 'PENDING', false ) . '>Pending</option><option value="SUCCESS" ' . selected( $status, 'SUCCESS', false ) . '>Success</option><option value="FAILED" ' . selected( $status, 'FAILED', false ) . '>Failed</option></select><select name="method"><option value="">' . esc_html__( 'Tất cả phương thức', 'travel-agency-modern' ) . '</option><option value="CASH" ' . selected( $method, 'CASH', false ) . '>Cash</option><option value="BANK_TRANSFER" ' . selected( $method, 'BANK_TRANSFER', false ) . '>Bank transfer</option><option value="MOMO" ' . selected( $method, 'MOMO', false ) . '>MoMo</option><option value="VNPAY" ' . selected( $method, 'VNPAY', false ) . '>VNPay</option><option value="CARD" ' . selected( $method, 'CARD', false ) . '>Card</option></select><button type="submit" class="button button-secondary">' . esc_html__( 'Lọc', 'travel-agency-modern' ) . '</button></form>';
			echo '<table class="widefat striped tam-admin-table"><thead><tr><th>ID</th><th>' . esc_html__( 'Khách hàng', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Tour', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Method', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Số tiền', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Trạng thái', 'travel-agency-modern' ) . '</th><th>' . esc_html__( 'Hành động', 'travel-agency-modern' ) . '</th></tr></thead><tbody>';

			if ( empty( $pagination['items'] ) ) {
				echo '<tr><td colspan="7">' . esc_html__( 'Không có payment phù hợp.', 'travel-agency-modern' ) . '</td></tr>';
			} else {
				foreach ( $pagination['items'] as $payment ) {
					$current_status = isset( $payment['status'] ) ? (string) $payment['status'] : '';
					echo '<tr><td>#' . esc_html( isset( $payment['id'] ) ? (int) $payment['id'] : 0 ) . '<small>' . esc_html( tam_backend_admin_format_datetime( isset( $payment['paid_at'] ) ? (string) $payment['paid_at'] : '' ) ) . '</small></td><td><strong>' . esc_html( isset( $payment['user_name'] ) ? (string) $payment['user_name'] : '' ) . '</strong><small>' . esc_html( isset( $payment['user_email'] ) ? (string) $payment['user_email'] : '' ) . '</small></td><td>' . esc_html( isset( $payment['tour_title'] ) ? (string) $payment['tour_title'] : '' ) . '</td><td>' . esc_html( isset( $payment['method'] ) ? (string) $payment['method'] : '' ) . '</td><td>' . esc_html( tam_backend_admin_format_amount( isset( $payment['amount'] ) ? $payment['amount'] : 0 ) ) . '</td><td>' . wp_kses_post( tam_backend_admin_badge( $current_status ) ) . '</td><td>';

					if ( 'PENDING' === strtoupper( $current_status ) ) {
						echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-tam-confirm="' . esc_attr__( 'Xác nhận payment này là đã nhận tiền thành công?', 'travel-agency-modern' ) . '">';
						wp_nonce_field( 'tam_backend_admin_payment_confirm', 'tam_backend_admin_payment_nonce' );
						echo '<input type="hidden" name="action" value="tam_backend_admin_payment_confirm" /><input type="hidden" name="payment_id" value="' . esc_attr( (int) $payment['id'] ) . '" /><button type="submit" class="button button-small button-primary">' . esc_html__( 'Confirm payment', 'travel-agency-modern' ) . '</button></form>';
					} else {
						echo '<span class="tam-admin-muted">' . esc_html__( 'Không có thao tác', 'travel-agency-modern' ) . '</span>';
					}

					echo '</td></tr>';
				}
			}

			echo '</tbody></table>';
			tam_backend_admin_render_pagination( $pagination['paged'], $pagination['total_pages'], array( 's' => $search, 'status' => $status, 'method' => $method ) );
			echo '</section>';
		}
	);
}

/**
 * Render the sync page.
 *
 * @return void
 */
function tam_backend_admin_render_sync_page() {
	tam_backend_admin_render_shell(
		'tam-admin-sync',
		static function () {
			$tour_count = wp_count_posts( 'tour' );

			echo '<section class="tam-admin-grid tam-admin-grid--2">';
			echo '<article class="tam-admin-card">';
			echo '<div class="tam-admin-card__head"><h3>' . esc_html__( 'Đồng bộ mirror tour sang WordPress', 'travel-agency-modern' ) . '</h3></div>';
			echo '<p class="tam-admin-muted">' . esc_html__( 'Frontend public vẫn đang đọc tour từ custom post type WordPress. Trang này giúp đối soát lại dữ liệu khi bạn đã tạo hoặc chỉnh sửa tour từ backend admin shell.', 'travel-agency-modern' ) . '</p>';
			echo '<div class="tam-admin-kv"><div><span>' . esc_html__( 'Mirror tour posts hiện có', 'travel-agency-modern' ) . '</span><strong>' . esc_html( number_format_i18n( isset( $tour_count->publish ) ? (int) $tour_count->publish : 0 ) ) . '</strong></div><div><span>' . esc_html__( 'Draft mirrors', 'travel-agency-modern' ) . '</span><strong>' . esc_html( number_format_i18n( isset( $tour_count->draft ) ? (int) $tour_count->draft : 0 ) ) . '</strong></div></div>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="tam-admin-inline-form">';
			wp_nonce_field( 'tam_backend_admin_sync', 'tam_backend_admin_sync_nonce' );
			echo '<input type="hidden" name="action" value="tam_backend_admin_sync" /><button type="submit" class="button button-primary">' . esc_html__( 'Sync tours ngay', 'travel-agency-modern' ) . '</button></form>';
			echo '</article>';
			echo '<article class="tam-admin-card">';
			echo '<div class="tam-admin-card__head"><h3>' . esc_html__( 'Nguyên tắc đồng bộ', 'travel-agency-modern' ) . '</h3></div>';
			echo '<ul class="tam-admin-bullets"><li>' . esc_html__( 'Backend là nguồn dữ liệu chuẩn cho tours, bookings, payments, reviews và users.', 'travel-agency-modern' ) . '</li><li>' . esc_html__( 'Tour Active sẽ mirror sang post publish, Draft hoặc Closed sẽ mirror sang draft.', 'travel-agency-modern' ) . '</li><li>' . esc_html__( 'Khi xóa tour ở backend, post mirror tương ứng trong WordPress sẽ được đưa vào thùng rác.', 'travel-agency-modern' ) . '</li></ul>';
			echo '</article>';
			echo '</section>';
		}
	);
}

/**
 * Handle backend connect, register and disconnect actions.
 *
 * @return void
 */
function tam_backend_admin_handle_auth_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'travel-agency-modern' ) );
	}

	check_admin_referer( 'tam_backend_admin_auth', 'tam_backend_admin_nonce' );

	$intent = isset( $_POST['intent'] ) ? sanitize_key( wp_unslash( $_POST['intent'] ) ) : 'login';

	if ( 'disconnect' === $intent ) {
		tam_backend_admin_clear_session();
		tam_backend_admin_redirect_notice( 'tam-admin-connect', __( 'Đã ngắt kết nối backend admin.', 'travel-agency-modern' ), 'success' );
	}

	$body = array(
		'email'    => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		'password' => isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '',
	);

	if ( 'register' === $intent ) {
		$body['name']            = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$body['confirmPassword'] = isset( $_POST['confirmPassword'] ) ? (string) wp_unslash( $_POST['confirmPassword'] ) : '';
	}

	$response = tam_backend_admin_request(
		'POST',
		'admin-auth/' . ( 'register' === $intent ? 'register' : 'login' ),
		array(
			'require_auth' => false,
			'body'         => $body,
		)
	);

	if ( ! $response['success'] || empty( $response['data']['token'] ) || empty( $response['data']['admin'] ) ) {
		tam_backend_admin_redirect_notice( 'tam-admin-connect', ! empty( $response['message'] ) ? $response['message'] : __( 'Không thể kết nối backend admin.', 'travel-agency-modern' ), 'error', array( 'view' => 'register' === $intent ? 'register' : 'login' ) );
	}

	tam_backend_admin_set_session( (string) $response['data']['token'], (array) $response['data']['admin'] );
	tam_backend_admin_redirect_notice( 'tam-admin-dashboard', __( 'Kết nối backend admin thành công.', 'travel-agency-modern' ), 'success' );
}
add_action( 'admin_post_tam_backend_admin_auth', 'tam_backend_admin_handle_auth_action' );

/**
 * Handle create/update tour submissions.
 *
 * @return void
 */
function tam_backend_admin_handle_tour_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'travel-agency-modern' ) );
	}

	check_admin_referer( 'tam_backend_admin_tour_save', 'tam_backend_admin_tour_nonce' );

	$tour_id = isset( $_POST['tour_id'] ) ? absint( $_POST['tour_id'] ) : 0;
	$input   = isset( $_POST['tour'] ) ? (array) wp_unslash( $_POST['tour'] ) : array();
	$file    = isset( $_FILES['tour_image'] ) ? $_FILES['tour_image'] : array();
	$payload = tam_backend_admin_build_tour_payload( $input );
	$view    = $tour_id > 0 ? 'edit' : 'create';

	$response = $tour_id > 0
		? tam_backend_admin_update_tour( $tour_id, $payload, $file )
		: tam_backend_admin_create_tour( $payload, $file );

	if ( ! $response['success'] ) {
		tam_backend_admin_set_flash( 'tour_form', wp_parse_args( $input, tam_backend_admin_default_tour_form() ) );
		$field_errors = isset( $response['errors'] ) && is_array( $response['errors'] ) ? $response['errors'] : array();
		tam_backend_admin_set_flash( 'tour_errors', $field_errors );

		if ( empty( $field_errors ) ) {
			tam_backend_admin_set_flash( 'tour_general_error', ! empty( $response['message'] ) ? $response['message'] : __( 'Không thể lưu tour lúc này.', 'travel-agency-modern' ) );
		}

		tam_backend_admin_redirect(
			'tam-admin-tours',
			array(
				'view'    => $view,
				'tour_id' => $tour_id,
			)
		);
	}

	$notice_type = ! empty( $response['sync_warning'] ) ? 'warning' : 'success';
	$message     = ! empty( $response['message'] ) ? (string) $response['message'] : ( $tour_id > 0 ? __( 'Cập nhật tour thành công.', 'travel-agency-modern' ) : __( 'Tạo tour thành công.', 'travel-agency-modern' ) );

	tam_backend_admin_redirect_notice( 'tam-admin-tours', $message, $notice_type );
}
add_action( 'admin_post_tam_backend_admin_tour_save', 'tam_backend_admin_handle_tour_save' );

/**
 * Handle quick tour status updates.
 *
 * @return void
 */
function tam_backend_admin_handle_tour_status() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'travel-agency-modern' ) );
	}

	check_admin_referer( 'tam_backend_admin_tour_status', 'tam_backend_admin_status_nonce' );

	$tour_id = isset( $_POST['tour_id'] ) ? absint( $_POST['tour_id'] ) : 0;
	$status  = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
	$result  = tam_backend_admin_update_tour_status( $tour_id, $status );

	tam_backend_admin_redirect_notice( 'tam-admin-tours', ! empty( $result['message'] ) ? $result['message'] : ( $result['success'] ? __( 'Đã cập nhật trạng thái tour.', 'travel-agency-modern' ) : __( 'Không thể cập nhật trạng thái tour.', 'travel-agency-modern' ) ), $result['success'] ? 'success' : 'error' );
}
add_action( 'admin_post_tam_backend_admin_tour_status', 'tam_backend_admin_handle_tour_status' );

/**
 * Handle backend tour deletion.
 *
 * @return void
 */
function tam_backend_admin_handle_tour_delete() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'travel-agency-modern' ) );
	}

	check_admin_referer( 'tam_backend_admin_tour_delete', 'tam_backend_admin_delete_nonce' );

	$tour_id = isset( $_POST['tour_id'] ) ? absint( $_POST['tour_id'] ) : 0;
	$result  = tam_backend_admin_delete_tour( $tour_id );

	tam_backend_admin_redirect_notice( 'tam-admin-tours', ! empty( $result['message'] ) ? $result['message'] : ( $result['success'] ? __( 'Đã xóa tour khỏi backend.', 'travel-agency-modern' ) : __( 'Không thể xóa tour.', 'travel-agency-modern' ) ), $result['success'] ? 'success' : 'error' );
}
add_action( 'admin_post_tam_backend_admin_tour_delete', 'tam_backend_admin_handle_tour_delete' );

/**
 * Handle manual WordPress mirror sync from backend tours.
 *
 * @return void
 */
function tam_backend_admin_handle_sync() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'travel-agency-modern' ) );
	}

	check_admin_referer( 'tam_backend_admin_sync', 'tam_backend_admin_sync_nonce' );

	$result = tam_backend_api_sync_tours();

	tam_backend_admin_redirect_notice( 'tam-admin-sync', isset( $result['message'] ) ? (string) $result['message'] : ( ! empty( $result['success'] ) ? __( 'Đồng bộ tours thành công.', 'travel-agency-modern' ) : __( 'Không thể đồng bộ tours.', 'travel-agency-modern' ) ), ! empty( $result['success'] ) ? 'success' : 'error' );
}
add_action( 'admin_post_tam_backend_admin_sync', 'tam_backend_admin_handle_sync' );

/**
 * Handle backend booking status actions.
 *
 * @return void
 */
function tam_backend_admin_handle_booking_status() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'travel-agency-modern' ) );
	}

	check_admin_referer( 'tam_backend_admin_booking_status', 'tam_backend_admin_booking_nonce' );

	$booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
	$status     = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
	$result     = tam_backend_admin_update_booking_status( $booking_id, $status );

	tam_backend_admin_redirect_notice( 'tam-admin-bookings', ! empty( $result['message'] ) ? $result['message'] : ( $result['success'] ? __( 'Đã cập nhật booking.', 'travel-agency-modern' ) : __( 'Không thể cập nhật booking.', 'travel-agency-modern' ) ), $result['success'] ? 'success' : 'error' );
}
add_action( 'admin_post_tam_backend_admin_booking_status', 'tam_backend_admin_handle_booking_status' );

/**
 * Handle backend payment confirmation.
 *
 * @return void
 */
function tam_backend_admin_handle_payment_confirm() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'travel-agency-modern' ) );
	}

	check_admin_referer( 'tam_backend_admin_payment_confirm', 'tam_backend_admin_payment_nonce' );

	$payment_id = isset( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;
	$result     = tam_backend_admin_confirm_payment( $payment_id );

	tam_backend_admin_redirect_notice( 'tam-admin-payments', ! empty( $result['message'] ) ? $result['message'] : ( $result['success'] ? __( 'Đã xác nhận payment.', 'travel-agency-modern' ) : __( 'Không thể xác nhận payment.', 'travel-agency-modern' ) ), $result['success'] ? 'success' : 'error' );
}
add_action( 'admin_post_tam_backend_admin_payment_confirm', 'tam_backend_admin_handle_payment_confirm' );

/**
 * Handle backend review moderation updates.
 *
 * @return void
 */
function tam_backend_admin_handle_review_status() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'travel-agency-modern' ) );
	}

	check_admin_referer( 'tam_backend_admin_review_status', 'tam_backend_admin_review_nonce' );

	$review_id = isset( $_POST['review_id'] ) ? absint( $_POST['review_id'] ) : 0;
	$status    = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
	$result    = tam_backend_admin_update_review_status( $review_id, $status );

	tam_backend_admin_redirect_notice( 'tam-admin-reviews', ! empty( $result['message'] ) ? $result['message'] : ( $result['success'] ? __( 'Đã cập nhật review.', 'travel-agency-modern' ) : __( 'Không thể cập nhật review.', 'travel-agency-modern' ) ), $result['success'] ? 'success' : 'error' );
}
add_action( 'admin_post_tam_backend_admin_review_status', 'tam_backend_admin_handle_review_status' );

/**
 * Handle backend user role updates.
 *
 * @return void
 */
function tam_backend_admin_handle_user_role() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'travel-agency-modern' ) );
	}

	check_admin_referer( 'tam_backend_admin_user_role', 'tam_backend_admin_user_role_nonce' );

	$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
	$role    = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
	$result  = tam_backend_admin_update_user_role( $user_id, $role );

	tam_backend_admin_redirect_notice( 'tam-admin-users', ! empty( $result['message'] ) ? $result['message'] : ( $result['success'] ? __( 'Đã cập nhật role user.', 'travel-agency-modern' ) : __( 'Không thể cập nhật role user.', 'travel-agency-modern' ) ), $result['success'] ? 'success' : 'error' );
}
add_action( 'admin_post_tam_backend_admin_user_role', 'tam_backend_admin_handle_user_role' );

/**
 * Handle backend user deletion.
 *
 * @return void
 */
function tam_backend_admin_handle_user_delete() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'travel-agency-modern' ) );
	}

	check_admin_referer( 'tam_backend_admin_user_delete', 'tam_backend_admin_user_delete_nonce' );

	$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
	$result  = tam_backend_admin_delete_user( $user_id );

	tam_backend_admin_redirect_notice( 'tam-admin-users', ! empty( $result['message'] ) ? $result['message'] : ( $result['success'] ? __( 'Đã xóa user.', 'travel-agency-modern' ) : __( 'Không thể xóa user.', 'travel-agency-modern' ) ), $result['success'] ? 'success' : 'error' );
}
add_action( 'admin_post_tam_backend_admin_user_delete', 'tam_backend_admin_handle_user_delete' );
