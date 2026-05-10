<?php
/**
 * Backend API integration helpers for the hybrid WordPress + Node setup.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TAM_BACKEND_API_BASE_URL' ) ) {
	define( 'TAM_BACKEND_API_BASE_URL', 'http://127.0.0.1:5000/api' );
}

if ( ! defined( 'TAM_BACKEND_API_TOKEN_COOKIE' ) ) {
	define( 'TAM_BACKEND_API_TOKEN_COOKIE', 'tam_api_token' );
}

if ( ! defined( 'TAM_BACKEND_API_USER_COOKIE' ) ) {
	define( 'TAM_BACKEND_API_USER_COOKIE', 'tam_api_user' );
}

/**
 * Return the configured backend API base URL.
 *
 * @return string
 */
function tam_backend_api_base_url() {
	$url = (string) apply_filters( 'tam_backend_api_base_url', TAM_BACKEND_API_BASE_URL );

	return untrailingslashit( trim( $url ) );
}

/**
 * Return the API origin URL without the /api suffix.
 *
 * @return string
 */
function tam_backend_api_origin_url() {
	$base_url = tam_backend_api_base_url();

	if ( ! $base_url ) {
		return '';
	}

	return preg_replace( '#/api/?$#', '', $base_url );
}

/**
 * Build a full backend API URL.
 *
 * @param string $path  Relative API path.
 * @param array  $query Optional query arguments.
 * @return string
 */
function tam_backend_api_build_url( $path = '', $query = array() ) {
	$path = ltrim( (string) $path, '/' );
	$url  = tam_backend_api_base_url();

	if ( $path ) {
		$url .= '/' . $path;
	}

	if ( ! empty( $query ) ) {
		$url = add_query_arg( $query, $url );
	}

	return $url;
}

/**
 * Resolve a backend asset URL, allowing relative /uploads paths.
 *
 * @param string $asset_path Relative or absolute path.
 * @return string
 */
function tam_backend_api_resolve_asset_url( $asset_path ) {
	$asset_path = trim( (string) $asset_path );

	if ( '' === $asset_path ) {
		return '';
	}

	if ( preg_match( '#^https?://#i', $asset_path ) ) {
		return $asset_path;
	}

	$origin = tam_backend_api_origin_url();

	if ( ! $origin ) {
		return '';
	}

	return $origin . '/' . ltrim( $asset_path, '/' );
}

/**
 * Normalize API error payloads.
 *
 * @param mixed $errors Raw error payload.
 * @return array
 */
function tam_backend_api_normalize_errors( $errors ) {
	if ( ! is_array( $errors ) ) {
		return array();
	}

	$normalized = array();

	foreach ( $errors as $key => $value ) {
		$normalized[ (string) $key ] = is_array( $value ) ? implode( ' ', array_map( 'strval', $value ) ) : (string) $value;
	}

	return $normalized;
}

/**
 * Perform an authenticated backend API request.
 *
 * @param string $method HTTP method.
 * @param string $path   Relative API path.
 * @param array  $args   Optional request arguments.
 * @return array
 */
function tam_backend_api_request( $method, $path, $args = array() ) {
	$url = tam_backend_api_build_url(
		$path,
		isset( $args['query'] ) && is_array( $args['query'] ) ? $args['query'] : array()
	);

	if ( ! $url ) {
		return array(
			'success' => false,
			'status'  => 0,
			'message' => 'Backend API is not configured.',
			'data'    => array(),
			'errors'  => array(),
		);
	}

	$headers = array(
		'Accept' => 'application/json',
	);

	if ( ! empty( $args['auth_token'] ) ) {
		$headers['Authorization'] = 'Bearer ' . trim( (string) $args['auth_token'] );
	}

	$request_args = array(
		'method'      => strtoupper( (string) $method ),
		'timeout'     => isset( $args['timeout'] ) ? (int) $args['timeout'] : 15,
		'redirection' => 2,
		'headers'     => $headers,
	);

	if ( array_key_exists( 'body', $args ) ) {
		$request_args['headers']['Content-Type'] = 'application/json; charset=utf-8';
		$request_args['body']                    = wp_json_encode( $args['body'] );
	}

	$response = wp_remote_request( $url, $request_args );

	if ( is_wp_error( $response ) ) {
		return array(
			'success' => false,
			'status'  => 0,
			'message' => $response->get_error_message(),
			'data'    => array(),
			'errors'  => array(),
		);
	}

	$status   = (int) wp_remote_retrieve_response_code( $response );
	$raw_body = (string) wp_remote_retrieve_body( $response );
	$payload  = json_decode( $raw_body, true );

	if ( ! is_array( $payload ) ) {
		$payload = array();
	}

	return array(
		'success' => $status >= 200 && $status < 300,
		'status'  => $status,
		'message' => isset( $payload['message'] ) ? (string) $payload['message'] : '',
		'data'    => $payload,
		'errors'  => tam_backend_api_normalize_errors( isset( $payload['errors'] ) ? $payload['errors'] : array() ),
	);
}

/**
 * Check whether the backend replied with its generic missing-route payload.
 *
 * @param array $response Normalized backend response.
 * @return bool
 */
function tam_backend_api_is_missing_route_response( $response ) {
	return 404 === (int) ( isset( $response['status'] ) ? $response['status'] : 0 )
		&& 'Route not found' === trim( (string) ( isset( $response['message'] ) ? $response['message'] : '' ) );
}

/**
 * Convert raw backend failures into user-facing checkout messages.
 *
 * @param array  $response        Normalized backend response.
 * @param string $default_message Fallback message.
 * @param string $path            Requested backend path.
 * @return string
 */
function tam_backend_api_get_error_message( $response, $default_message, $path = '' ) {
	if ( tam_backend_api_is_missing_route_response( $response ) ) {
		if ( 0 === strpos( (string) $path, 'checkout/' ) ) {
			return __( 'Backend checkout tam thoi chua san sang. Vui long khoi dong lai backend-api va tai lai trang.', 'travel-agency-modern' );
		}

		return __( 'Backend API tam thoi chua san sang. Vui long thu lai sau it phut.', 'travel-agency-modern' );
	}

	return ! empty( $response['message'] ) ? (string) $response['message'] : $default_message;
}

/**
 * Write a cookie in a WordPress-safe way.
 *
 * @param string  $name      Cookie name.
 * @param string  $value     Cookie value.
 * @param integer $expires   Unix timestamp.
 * @param boolean $http_only Whether the cookie should be HttpOnly.
 * @return void
 */
function tam_backend_api_write_cookie( $name, $value, $expires, $http_only = true ) {
	$path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
	$domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';

	if ( PHP_VERSION_ID >= 70300 ) {
		setcookie(
			$name,
			$value,
			array(
				'expires'  => (int) $expires,
				'path'     => $path,
				'domain'   => $domain,
				'secure'   => is_ssl(),
				'httponly' => (bool) $http_only,
				'samesite' => 'Lax',
			)
		);
	} else {
		setcookie( $name, $value, (int) $expires, $path . '; samesite=Lax', $domain, is_ssl(), (bool) $http_only );
	}
}

/**
 * Persist the backend auth session in cookies.
 *
 * @param string $token JWT token.
 * @param array  $user  Sanitized user payload.
 * @return void
 */
function tam_backend_api_set_session( $token, $user ) {
	$expires    = time() + WEEK_IN_SECONDS;
	$user_value = rawurlencode( wp_json_encode( is_array( $user ) ? $user : array() ) );

	if ( ! headers_sent() ) {
		tam_backend_api_write_cookie( TAM_BACKEND_API_TOKEN_COOKIE, trim( (string) $token ), $expires, true );
		tam_backend_api_write_cookie( TAM_BACKEND_API_USER_COOKIE, $user_value, $expires, true );
	}

	$_COOKIE[ TAM_BACKEND_API_TOKEN_COOKIE ] = trim( (string) $token );
	$_COOKIE[ TAM_BACKEND_API_USER_COOKIE ]  = $user_value;
}

/**
 * Clear the backend auth session cookies.
 *
 * @return void
 */
function tam_backend_api_clear_session() {
	if ( ! headers_sent() ) {
		tam_backend_api_write_cookie( TAM_BACKEND_API_TOKEN_COOKIE, '', time() - HOUR_IN_SECONDS, true );
		tam_backend_api_write_cookie( TAM_BACKEND_API_USER_COOKIE, '', time() - HOUR_IN_SECONDS, true );
	}

	unset( $_COOKIE[ TAM_BACKEND_API_TOKEN_COOKIE ], $_COOKIE[ TAM_BACKEND_API_USER_COOKIE ] );
}

/**
 * Return the current backend auth token.
 *
 * @return string
 */
function tam_backend_api_get_auth_token() {
	return isset( $_COOKIE[ TAM_BACKEND_API_TOKEN_COOKIE ] ) ? trim( (string) wp_unslash( $_COOKIE[ TAM_BACKEND_API_TOKEN_COOKIE ] ) ) : '';
}

/**
 * Return the current backend user payload from cookie storage.
 *
 * @return array|null
 */
function tam_backend_api_get_auth_user() {
	if ( empty( $_COOKIE[ TAM_BACKEND_API_USER_COOKIE ] ) ) {
		return null;
	}

	$raw_user = json_decode( rawurldecode( (string) wp_unslash( $_COOKIE[ TAM_BACKEND_API_USER_COOKIE ] ) ), true );

	if ( ! is_array( $raw_user ) ) {
		return null;
	}

	return array(
		'id'    => isset( $raw_user['id'] ) ? (int) $raw_user['id'] : 0,
		'name'  => isset( $raw_user['name'] ) ? sanitize_text_field( $raw_user['name'] ) : '',
		'email' => isset( $raw_user['email'] ) ? sanitize_email( $raw_user['email'] ) : '',
		'phone' => isset( $raw_user['phone'] ) ? sanitize_text_field( $raw_user['phone'] ) : '',
		'role'  => isset( $raw_user['role'] ) ? sanitize_text_field( $raw_user['role'] ) : '',
	);
}

/**
 * Whether the current request has a backend session.
 *
 * @return bool
 */
function tam_backend_api_is_authenticated() {
	return (bool) tam_backend_api_get_auth_token() && (bool) tam_backend_api_get_auth_user();
}

/**
 * Return the preferred frontend account URL.
 *
 * @return string
 */
function tam_backend_api_get_account_url() {
	if ( function_exists( 'tam_get_page_url_by_path' ) ) {
		return tam_get_page_url_by_path( 'tai-khoan', home_url( '/tai-khoan/' ) );
	}

	return home_url( '/tai-khoan/' );
}

/**
 * Refresh the backend user payload from /auth/me when needed.
 *
 * @param bool $force_refresh Whether to bypass the in-request cache.
 * @return array|null
 */
function tam_backend_api_get_current_user_profile( $force_refresh = false ) {
	static $cached_user = null;
	static $is_loaded   = false;

	if ( $is_loaded && ! $force_refresh ) {
		return $cached_user;
	}

	$token = tam_backend_api_get_auth_token();

	if ( ! $token ) {
		$cached_user = null;
		$is_loaded   = true;
		return null;
	}

	$cached_user = tam_backend_api_get_auth_user();
	$response    = tam_backend_api_request(
		'GET',
		'auth/me',
		array(
			'auth_token' => $token,
		)
	);

	if ( $response['success'] && ! empty( $response['data']['user'] ) && is_array( $response['data']['user'] ) ) {
		tam_backend_api_set_session( $token, $response['data']['user'] );
		$cached_user = tam_backend_api_get_auth_user();
		$is_loaded   = true;
		return $cached_user;
	}

	if ( 401 === (int) $response['status'] ) {
		tam_backend_api_clear_session();
		$cached_user = null;
		$is_loaded   = true;
		return null;
	}

	$is_loaded = true;

	return $cached_user;
}

/**
 * Redirect with a public status query-string flag.
 *
 * @param string $query_key    Query-string key to set.
 * @param string $status       Status value.
 * @param string $redirect_to  Redirect URL.
 * @param array  $extra_args   Extra query args to append.
 * @param array  $clear_keys   Keys to remove before redirecting.
 * @return void
 */
function tam_backend_api_redirect_public_status( $query_key, $status, $redirect_to, $extra_args = array(), $clear_keys = array() ) {
	$fragment = '';

	if ( false !== strpos( $redirect_to, '#' ) ) {
		list( $redirect_to, $fragment ) = explode( '#', $redirect_to, 2 );
		$fragment = '#' . $fragment;
	}

	$clear_keys  = array_filter( array_merge( array( $query_key ), $clear_keys ) );
	$redirect_to = remove_query_arg( $clear_keys, $redirect_to );
	$args        = array_merge(
		array(
			$query_key => $status,
		),
		$extra_args
	);

	$redirect_to = add_query_arg( $args, $redirect_to );
	wp_safe_redirect( $redirect_to . $fragment );
	exit;
}

/**
 * Append an optional backend message passed in the query string.
 *
 * @param string $markup      Existing notice markup.
 * @param string $message_key Message query-string key.
 * @return string
 */
function tam_backend_api_append_query_message( $markup, $message_key ) {
	if ( empty( $_GET[ $message_key ] ) ) {
		return $markup;
	}

	$message = sanitize_text_field( rawurldecode( wp_unslash( $_GET[ $message_key ] ) ) );

	if ( '' === $message ) {
		return $markup;
	}

	$markup .= sprintf(
		'<div class="tam-form-notice tam-form-notice--info">%s</div>',
		esc_html( $message )
	);

	return $markup;
}

/**
 * Return checkout notice markup that understands backend statuses.
 *
 * @return string
 */
function tam_backend_api_get_checkout_notice_markup() {
	if ( ! function_exists( 'tam_get_public_status_notice_markup' ) ) {
		return '';
	}

	$notice = tam_get_public_status_notice_markup(
		'checkout_status',
		array(
			'success'         => array(
				'class'   => 'tam-form-notice tam-form-notice--success',
				'message' => __( 'Yêu cầu thanh toán đã được ghi nhận. ADN Travel sẽ liên hệ để xác nhận và giữ chỗ cho bạn.', 'travel-agency-modern' ),
			),
			'saved_no_mail'   => array(
				'class'   => 'tam-form-notice tam-form-notice--success',
				'message' => __( 'Đơn đặt tour đã được lưu. Email tự động chưa gửi được nhưng yêu cầu vẫn có trong quản trị.', 'travel-agency-modern' ),
			),
			'payment_pending' => array(
				'class'   => 'tam-form-notice tam-form-notice--success',
				'message' => __( 'Booking đã được tạo trên backend. Hệ thống đang chờ xác nhận thanh toán cho đơn của bạn.', 'travel-agency-modern' ),
			),
			'missing'         => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Vui lòng nhập đầy đủ họ tên, email, số điện thoại và thông tin đặt tour.', 'travel-agency-modern' ),
			),
			'invalid_email'   => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Email chưa hợp lệ. Vui lòng kiểm tra lại trước khi tiếp tục.', 'travel-agency-modern' ),
			),
			'terms_missing'   => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Bạn cần đồng ý với điều khoản trước khi gửi đơn đặt tour.', 'travel-agency-modern' ),
			),
			'invalid_nonce'   => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Phiên thanh toán đã hết hạn. Vui lòng thử lại.', 'travel-agency-modern' ),
			),
			'mail_failed'     => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Hệ thống chưa gửi được email xác nhận. Vui lòng thử lại hoặc liên hệ hotline để được hỗ trợ nhanh.', 'travel-agency-modern' ),
			),
			'login_required'  => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Bạn cần đăng nhập tài khoản backend trước khi đặt tour.', 'travel-agency-modern' ),
			),
			'tour_not_synced' => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Tour này chưa được đồng bộ với backend API. Hãy chạy Sync Tours API trong quản trị trước.', 'travel-agency-modern' ),
			),
			'booking_failed'  => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Backend chưa tạo được booking cho tour này. Vui lòng thử lại sau.', 'travel-agency-modern' ),
			),
			'payment_failed'  => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Booking đã được tạo nhưng backend chưa tạo được yêu cầu thanh toán. Vui lòng kiểm tra lại ở trang tài khoản.', 'travel-agency-modern' ),
			),
		)
	);

	$notice = tam_backend_api_append_query_message( $notice, 'checkout_message' );

	if ( empty( $_GET['booking_ref'] ) ) {
		return $notice;
	}

	$booking_ref = sanitize_text_field( wp_unslash( $_GET['booking_ref'] ) );

	return $notice . sprintf(
		'<div class="tam-checkout__ref">%s <strong>%s</strong></div>',
		esc_html__( 'Mã giữ chỗ của bạn:', 'travel-agency-modern' ),
		esc_html( $booking_ref )
	);
}

/**
 * Return notice markup for the account dashboard.
 *
 * @return string
 */
function tam_backend_api_get_account_notice_markup() {
	if ( ! function_exists( 'tam_get_public_status_notice_markup' ) ) {
		return '';
	}

	$notice = tam_get_public_status_notice_markup(
		'account_status',
		array(
			'cancel_success' => array(
				'class'   => 'tam-form-notice tam-form-notice--success',
				'message' => __( 'Booking đã được huỷ thành công trên backend.', 'travel-agency-modern' ),
			),
			'login_required' => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Bạn cần đăng nhập để xem và quản lý booking.', 'travel-agency-modern' ),
			),
			'invalid_nonce'  => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Phiên thao tác đã hết hạn. Vui lòng thử lại.', 'travel-agency-modern' ),
			),
			'cancel_failed'  => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Không thể huỷ booking lúc này. Hãy kiểm tra lại trạng thái đơn.', 'travel-agency-modern' ),
			),
		)
	);

	return tam_backend_api_append_query_message( $notice, 'account_message' );
}

/**
 * Return notice markup for the public review form.
 *
 * @return string
 */
function tam_backend_api_get_review_notice_markup() {
	if ( ! function_exists( 'tam_get_public_status_notice_markup' ) ) {
		return '';
	}

	$notice = tam_get_public_status_notice_markup(
		'review_status',
		array(
			'success'        => array(
				'class'   => 'tam-form-notice tam-form-notice--success',
				'message' => __( 'Đánh giá của bạn đã được gửi lên backend thành công.', 'travel-agency-modern' ),
			),
			'login_required' => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Bạn cần đăng nhập trước khi gửi đánh giá.', 'travel-agency-modern' ),
			),
			'invalid_nonce'  => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Phiên gửi đánh giá đã hết hạn. Vui lòng thử lại.', 'travel-agency-modern' ),
			),
			'missing'        => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Vui lòng chọn booking, số sao và nội dung đánh giá.', 'travel-agency-modern' ),
			),
			'review_failed'  => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Backend chưa thể ghi nhận đánh giá lúc này.', 'travel-agency-modern' ),
			),
		)
	);

	return tam_backend_api_append_query_message( $notice, 'review_message' );
}

/**
 * Return the logout URL for the backend auth session.
 *
 * @param string $redirect_url Redirect destination after logout.
 * @return string
 */
function tam_backend_api_get_logout_url( $redirect_url = '' ) {
	if ( ! $redirect_url ) {
		$redirect_url = function_exists( 'tam_get_current_public_url' ) ? tam_get_current_public_url() : home_url( '/' );
	}

	return add_query_arg(
		array(
			'action'      => 'tam_api_logout',
			'redirect_to' => rawurlencode( $redirect_url ),
		),
		admin_url( 'admin-post.php' )
	);
}

/**
 * Handle backend logout requests.
 *
 * @return void
 */
function tam_backend_api_handle_logout() {
	$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( rawurldecode( wp_unslash( $_GET['redirect_to'] ) ) ) : home_url( '/' );

	tam_backend_api_clear_session();
	wp_safe_redirect( $redirect_to ? $redirect_to : home_url( '/' ) );
	exit;
}
add_action( 'admin_post_nopriv_tam_api_logout', 'tam_backend_api_handle_logout' );
add_action( 'admin_post_tam_api_logout', 'tam_backend_api_handle_logout' );

/**
 * Map backend register validation keys to the auth modal field names.
 *
 * @param array $errors Backend validation errors.
 * @return array
 */
function tam_backend_api_map_register_errors( $errors ) {
	$field_map = array(
		'fullName'        => 'register_name',
		'phone'           => 'register_phone',
		'email'           => 'register_email',
		'password'        => 'register_password',
		'confirmPassword' => 'register_confirm_password',
	);

	$mapped = array();

	foreach ( tam_backend_api_normalize_errors( $errors ) as $key => $message ) {
		$mapped_key          = isset( $field_map[ $key ] ) ? $field_map[ $key ] : $key;
		$mapped[ $mapped_key ] = $message;
	}

	return $mapped;
}

/**
 * Handle auth login requests before the legacy WordPress callback runs.
 *
 * @return void
 */
function tam_backend_api_handle_ajax_login_request() {
	if ( tam_backend_api_is_authenticated() ) {
		wp_send_json_success(
			array(
				'message'     => __( 'Tai khoan backend da dang nhap san.', 'travel-agency-modern' ),
				'redirectUrl' => function_exists( 'tam_get_current_public_url' ) ? tam_get_current_public_url() : home_url( '/' ),
			)
		);
	}

	$nonce = isset( $_POST['tam_auth_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['tam_auth_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'tam_auth_login' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Phien dang nhap da het han. Vui long tai lai trang va thu lai.', 'travel-agency-modern' ),
			),
			403
		);
	}

	$email       = isset( $_POST['login_email'] ) ? sanitize_email( wp_unslash( $_POST['login_email'] ) ) : '';
	$password    = isset( $_POST['login_password'] ) ? (string) wp_unslash( $_POST['login_password'] ) : '';
	$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : ( function_exists( 'tam_get_current_public_url' ) ? tam_get_current_public_url() : home_url( '/' ) );
	$errors      = array();

	if ( '' === trim( $email ) ) {
		$errors['login_email'] = __( 'Vui long nhap email.', 'travel-agency-modern' );
	} elseif ( ! is_email( $email ) ) {
		$errors['login_email'] = __( 'Email chua dung dinh dang.', 'travel-agency-modern' );
	}

	if ( '' === $password ) {
		$errors['login_password'] = __( 'Vui long nhap mat khau.', 'travel-agency-modern' );
	}

	if ( ! empty( $errors ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Vui long kiem tra lai thong tin dang nhap.', 'travel-agency-modern' ),
				'errors'  => $errors,
			),
			422
		);
	}

	$response = tam_backend_api_login_user( $email, $password );

	if ( ! $response['success'] ) {
		wp_send_json_error(
			array(
				'message' => ! empty( $response['message'] ) ? $response['message'] : __( 'Dang nhap backend that bai.', 'travel-agency-modern' ),
			),
			$response['status'] ? $response['status'] : 401
		);
	}

	wp_send_json_success(
		array(
			'message'     => __( 'Dang nhap backend thanh cong. Dang tai lai trang...', 'travel-agency-modern' ),
			'redirectUrl' => $redirect_to ? $redirect_to : ( function_exists( 'tam_get_current_public_url' ) ? tam_get_current_public_url() : home_url( '/' ) ),
		)
	);
}

/**
 * Handle auth register requests before the legacy WordPress callback runs.
 *
 * @return void
 */
function tam_backend_api_handle_ajax_register_request() {
	if ( tam_backend_api_is_authenticated() ) {
		wp_send_json_success(
			array(
				'message'     => __( 'Tai khoan backend cua ban da san sang.', 'travel-agency-modern' ),
				'redirectUrl' => function_exists( 'tam_get_current_public_url' ) ? tam_get_current_public_url() : home_url( '/' ),
			)
		);
	}

	$nonce = isset( $_POST['tam_auth_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['tam_auth_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'tam_auth_register' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Phien dang ky da het han. Vui long tai lai trang va thu lai.', 'travel-agency-modern' ),
			),
			403
		);
	}

	$name             = isset( $_POST['register_name'] ) ? sanitize_text_field( wp_unslash( $_POST['register_name'] ) ) : '';
	$phone            = isset( $_POST['register_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['register_phone'] ) ) : '';
	$email            = isset( $_POST['register_email'] ) ? sanitize_email( wp_unslash( $_POST['register_email'] ) ) : '';
	$password         = isset( $_POST['register_password'] ) ? (string) wp_unslash( $_POST['register_password'] ) : '';
	$confirm_password = isset( $_POST['register_confirm_password'] ) ? (string) wp_unslash( $_POST['register_confirm_password'] ) : '';
	$redirect_to      = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : ( function_exists( 'tam_get_current_public_url' ) ? tam_get_current_public_url() : home_url( '/' ) );
	$errors           = array();

	if ( '' === trim( $name ) ) {
		$errors['register_name'] = __( 'Vui long nhap ho ten.', 'travel-agency-modern' );
	}

	if ( '' === trim( $phone ) ) {
		$errors['register_phone'] = __( 'Vui long nhap so dien thoai.', 'travel-agency-modern' );
	} elseif ( ! preg_match( '/^[0-9]{9,11}$/', $phone ) ) {
		$errors['register_phone'] = __( 'So dien thoai phai gom 9 den 11 chu so.', 'travel-agency-modern' );
	}

	if ( '' === trim( $email ) ) {
		$errors['register_email'] = __( 'Vui long nhap email.', 'travel-agency-modern' );
	} elseif ( ! is_email( $email ) ) {
		$errors['register_email'] = __( 'Email chua dung dinh dang.', 'travel-agency-modern' );
	}

	if ( '' === $password ) {
		$errors['register_password'] = __( 'Vui long nhap mat khau.', 'travel-agency-modern' );
	} elseif ( strlen( $password ) < 6 ) {
		$errors['register_password'] = __( 'Mat khau can toi thieu 6 ky tu.', 'travel-agency-modern' );
	}

	if ( '' === $confirm_password ) {
		$errors['register_confirm_password'] = __( 'Vui long xac nhan mat khau.', 'travel-agency-modern' );
	} elseif ( $password !== $confirm_password ) {
		$errors['register_confirm_password'] = __( 'Mat khau xac nhan chua khop.', 'travel-agency-modern' );
	}

	if ( ! empty( $errors ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Vui long kiem tra lai thong tin dang ky.', 'travel-agency-modern' ),
				'errors'  => $errors,
			),
			422
		);
	}

	$response = tam_backend_api_request(
		'POST',
		'auth/register',
		array(
			'body' => array(
				'fullName'        => $name,
				'phone'           => $phone,
				'email'           => $email,
				'password'        => $password,
				'confirmPassword' => $confirm_password,
			),
		)
	);

	if ( ! $response['success'] ) {
		wp_send_json_error(
			array(
				'message' => ! empty( $response['message'] ) ? $response['message'] : __( 'Dang ky backend that bai.', 'travel-agency-modern' ),
				'errors'  => tam_backend_api_map_register_errors( $response['errors'] ),
			),
			$response['status'] ? $response['status'] : 400
		);
	}

	$data  = isset( $response['data'] ) ? $response['data'] : array();
	$token = isset( $data['token'] ) ? (string) $data['token'] : '';
	$user  = isset( $data['user'] ) && is_array( $data['user'] ) ? $data['user'] : array();

	if ( ! $token || empty( $user ) ) {
		wp_send_json_success(
			array(
				'message'      => __( 'Dang ky thanh cong. Ban co the dang nhap ngay bay gio.', 'travel-agency-modern' ),
				'switchTab'    => 'login',
				'prefillEmail' => $email,
			)
		);
	}

	tam_backend_api_set_session( $token, $user );

	wp_send_json_success(
		array(
			'message'     => __( 'Tao tai khoan backend thanh cong. Dang dang nhap...', 'travel-agency-modern' ),
			'redirectUrl' => $redirect_to ? $redirect_to : ( function_exists( 'tam_get_current_public_url' ) ? tam_get_current_public_url() : home_url( '/' ) ),
		)
	);
}

/**
 * Login against the backend API and start a cookie session.
 *
 * @param string $email    User email.
 * @param string $password User password.
 * @return array
 */
function tam_backend_api_login_user( $email, $password ) {
	$response = tam_backend_api_request(
		'POST',
		'auth/login',
		array(
			'body' => array(
				'email'    => $email,
				'password' => $password,
			),
		)
	);

	if ( ! $response['success'] ) {
		return $response;
	}

	$data  = isset( $response['data'] ) ? $response['data'] : array();
	$token = isset( $data['token'] ) ? (string) $data['token'] : '';
	$user  = isset( $data['user'] ) && is_array( $data['user'] ) ? $data['user'] : array();

	if ( ! $token || empty( $user ) ) {
		return array(
			'success' => false,
			'status'  => 500,
			'message' => 'Backend login response did not include a valid token.',
			'data'    => $data,
			'errors'  => array(),
		);
	}

	tam_backend_api_set_session( $token, $user );

	return $response;
}

/**
 * Redirect the checkout flow with a backend-aware status code.
 *
 * @param string $status      Checkout status.
 * @param string $redirect_to Redirect URL.
 * @param string $booking_ref Optional booking reference.
 * @param string $message     Optional message override.
 * @return void
 */
function tam_backend_api_redirect_checkout_status( $status, $redirect_to, $booking_ref = '', $message = '' ) {
	$fragment = '';

	if ( false !== strpos( $redirect_to, '#' ) ) {
		list( $redirect_to, $fragment ) = explode( '#', $redirect_to, 2 );
		$fragment = '#' . $fragment;
	}

	$redirect_to = remove_query_arg( array( 'checkout_status', 'booking_ref', 'checkout_message' ), $redirect_to );
	$args        = array(
		'checkout_status' => $status,
	);

	if ( $booking_ref ) {
		$args['booking_ref'] = $booking_ref;
	}

	if ( $message ) {
		$args['checkout_message'] = rawurlencode( $message );
	}

	$redirect_to = add_query_arg( $args, $redirect_to );
	wp_safe_redirect( $redirect_to . $fragment );
	exit;
}

/**
 * Handle checkout requests against the backend booking and payment APIs.
 *
 * @return void
 */
function tam_backend_api_handle_checkout_submission() {
	$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/thanh-toan/' );

	if ( ! isset( $_POST['tam_checkout_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tam_checkout_nonce'] ) ), 'tam_checkout_form' ) ) {
		tam_backend_api_redirect_checkout_status( 'invalid_nonce', $redirect_to );
	}

	$name            = isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '';
	$email           = isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : '';
	$phone           = isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '';
	$note            = isset( $_POST['customer_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['customer_note'] ) ) : '';
	$wp_tour_id      = isset( $_POST['tour_id'] ) ? absint( wp_unslash( $_POST['tour_id'] ) ) : 0;
	$tour_title      = isset( $_POST['tour_title'] ) ? sanitize_text_field( wp_unslash( $_POST['tour_title'] ) ) : '';
	$departure_date  = isset( $_POST['departure_date'] ) ? sanitize_text_field( wp_unslash( $_POST['departure_date'] ) ) : '';
	$people          = isset( $_POST['people'] ) ? absint( wp_unslash( $_POST['people'] ) ) : 0;
	$payment_method  = isset( $_POST['payment_method'] ) ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : 'cod';
	$accepted_terms  = ! empty( $_POST['accept_terms'] );
	$auth_token      = tam_backend_api_get_auth_token();
	$current_user    = tam_backend_api_get_auth_user();
	$payment_methods = function_exists( 'tam_get_checkout_payment_methods' ) ? tam_get_checkout_payment_methods() : array();

	if ( '' === $name || '' === $email || '' === $phone || '' === $tour_title || '' === $departure_date || $people < 1 ) {
		tam_backend_api_redirect_checkout_status( 'missing', $redirect_to );
	}

	if ( ! is_email( $email ) ) {
		tam_backend_api_redirect_checkout_status( 'invalid_email', $redirect_to );
	}

	if ( ! $accepted_terms ) {
		tam_backend_api_redirect_checkout_status( 'terms_missing', $redirect_to );
	}

	if ( ! $auth_token || empty( $current_user ) ) {
		tam_backend_api_redirect_checkout_status( 'login_required', $redirect_to );
	}

	$api_tour_id = tam_backend_api_get_tour_id_for_post( $wp_tour_id );

	if ( $api_tour_id < 1 ) {
		tam_backend_api_redirect_checkout_status( 'tour_not_synced', $redirect_to );
	}

	if ( ! isset( $payment_methods[ $payment_method ] ) ) {
		$payment_method = 'cod';
	}

	$booking_response = tam_backend_api_request(
		'POST',
		'bookings',
		array(
			'auth_token' => $auth_token,
			'body'       => array(
				'tour_id'          => $api_tour_id,
				'travel_date'      => $departure_date,
				'number_of_people' => $people,
			),
		)
	);

	if ( ! $booking_response['success'] ) {
		if ( 401 === (int) $booking_response['status'] ) {
			tam_backend_api_clear_session();
			tam_backend_api_redirect_checkout_status( 'login_required', $redirect_to );
		}

		tam_backend_api_redirect_checkout_status(
			'booking_failed',
			$redirect_to,
			'',
			! empty( $booking_response['message'] ) ? $booking_response['message'] : ''
		);
	}

	$booking    = isset( $booking_response['data']['booking'] ) && is_array( $booking_response['data']['booking'] ) ? $booking_response['data']['booking'] : array();
	$booking_id = isset( $booking['id'] ) ? absint( $booking['id'] ) : 0;

	if ( $booking_id < 1 ) {
		tam_backend_api_redirect_checkout_status( 'booking_failed', $redirect_to );
	}

	$payment_response = tam_backend_api_request(
		'POST',
		'payments',
		array(
			'auth_token' => $auth_token,
			'body'       => array(
				'booking_id' => $booking_id,
				'method'     => tam_backend_api_map_payment_method( $payment_method ),
			),
		)
	);

	$is_pending_payment = $payment_response['success'] || 409 === (int) $payment_response['status'];

	if ( ! $is_pending_payment ) {
		tam_backend_api_redirect_checkout_status(
			'payment_failed',
			$redirect_to,
			tam_backend_api_build_booking_ref( $booking_id ),
			! empty( $payment_response['message'] ) ? $payment_response['message'] : ''
		);
	}

	if ( function_exists( 'tam_create_inquiry_request' ) ) {
		$internal_note = implode(
			"\n",
			array_filter(
				array(
					'API booking ref: ' . tam_backend_api_build_booking_ref( $booking_id ),
					'Khach dat tour: ' . $name,
					'Email: ' . $email,
					'So dien thoai: ' . $phone,
					'Tour: ' . $tour_title,
					'Ngay khoi hanh: ' . $departure_date,
					'So nguoi: ' . $people,
					'Backend payment: ' . tam_backend_api_map_payment_method( $payment_method ),
					$note ? 'Ghi chu: ' . $note : '',
				)
			)
		);

		tam_create_inquiry_request(
			array(
				'type'          => 'booking',
				'name'          => $name,
				'phone'         => $phone,
				'email'         => $email,
				'tour_interest' => $tour_title,
				'message'       => $internal_note,
				'source'        => $redirect_to,
				'status'        => 'new',
			)
		);
	}

	tam_backend_api_redirect_checkout_status(
		'payment_pending',
		$redirect_to,
		tam_backend_api_build_booking_ref( $booking_id ),
		! empty( $payment_response['message'] ) ? $payment_response['message'] : ''
	);
}

/**
 * Resolve the backend tour ID stored on a synced WordPress tour post.
 *
 * @param int $post_id WordPress tour post ID.
 * @return int
 */
function tam_backend_api_get_tour_id_for_post( $post_id ) {
	return (int) get_post_meta( $post_id, '_tam_api_tour_id', true );
}

/**
 * Return the synced backend gallery URLs for a WordPress tour post.
 *
 * @param int $post_id WordPress tour post ID.
 * @return string[]
 */
function tam_backend_api_get_tour_gallery_for_post( $post_id ) {
	$image_url = (string) get_post_meta( $post_id, '_tam_api_image_url', true );
	$image_url = tam_backend_api_resolve_asset_url( $image_url );

	if ( $image_url ) {
		return array( $image_url );
	}

	$gallery_meta = get_post_meta( $post_id, '_tam_api_gallery_images', true );
	$gallery_meta = is_array( $gallery_meta ) ? $gallery_meta : array();

	foreach ( $gallery_meta as $legacy_image_url ) {
		$legacy_image_url = tam_backend_api_resolve_asset_url( $legacy_image_url );

		if ( $legacy_image_url ) {
			return array( $legacy_image_url );
		}
	}

	return array();

	$gallery_meta = get_post_meta( $post_id, '_tam_api_gallery_images', true );
	$gallery_meta = is_array( $gallery_meta ) ? $gallery_meta : array();
	$gallery      = array();

	foreach ( $gallery_meta as $image_url ) {
		$image_url = tam_backend_api_resolve_asset_url( $image_url );

		if ( $image_url ) {
			$gallery[] = $image_url;
		}
	}

	if ( empty( $gallery ) ) {
		$image_url = (string) get_post_meta( $post_id, '_tam_api_image_url', true );
		$image_url = tam_backend_api_resolve_asset_url( $image_url );

		if ( $image_url ) {
			$gallery[] = $image_url;
		}
	}

	return array_values( array_unique( $gallery ) );
}

/**
 * Return the synced backend image URL for a WordPress tour post.
 *
 * @param int $post_id WordPress tour post ID.
 * @return string
 */
function tam_backend_api_get_tour_image_for_post( $post_id ) {
	$gallery = tam_backend_api_get_tour_gallery_for_post( $post_id );

	return ! empty( $gallery[0] ) ? (string) $gallery[0] : '';
}

/**
 * Return live reviews for a synced backend tour.
 *
 * @param int $post_id WordPress tour post ID.
 * @return array
 */
function tam_backend_api_get_reviews_for_post( $post_id ) {
	$api_tour_id = tam_backend_api_get_tour_id_for_post( $post_id );

	if ( $api_tour_id < 1 ) {
		return array();
	}

	$response = tam_backend_api_request( 'GET', 'reviews/tour/' . $api_tour_id );

	if ( ! $response['success'] ) {
		return array();
	}

	$payload = isset( $response['data']['reviews'] ) && is_array( $response['data']['reviews'] ) ? $response['data']['reviews'] : array();

	return array_map(
		static function ( $review ) {
			return array(
				'name'      => isset( $review['userName'] ) ? (string) $review['userName'] : 'Guest',
				'rating'    => isset( $review['rating'] ) ? (string) $review['rating'] : '5.0',
				'comment'   => isset( $review['comment'] ) ? (string) $review['comment'] : '',
				'route'     => isset( $review['tourTitle'] ) ? (string) $review['tourTitle'] : '',
				'createdAt' => isset( $review['createdAt'] ) ? (string) $review['createdAt'] : '',
			);
		},
		$payload
	);
}

/**
 * Find the synced WordPress tour post ID for a backend tour ID.
 *
 * @param int $api_tour_id Backend tour ID.
 * @return int
 */
function tam_backend_api_get_post_id_by_api_tour_id( $api_tour_id ) {
	$api_tour_id = absint( $api_tour_id );

	if ( $api_tour_id < 1 ) {
		return 0;
	}

	$posts = get_posts(
		array(
			'post_type'      => 'tour',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_tam_api_tour_id',
			'meta_value'     => $api_tour_id,
			'no_found_rows'  => true,
		)
	);

	return ! empty( $posts ) ? (int) $posts[0] : 0;
}

/**
 * Resolve a destination term label from the public filter slug.
 *
 * @param string $selected_dest Destination slug.
 * @return string
 */
function tam_backend_api_get_destination_name_from_slug( $selected_dest ) {
	$selected_dest = sanitize_title( $selected_dest );

	if ( '' === $selected_dest ) {
		return '';
	}

	$term = get_term_by( 'slug', $selected_dest, 'tour_destination' );

	if ( ! $term || is_wp_error( $term ) ) {
		return '';
	}

	return (string) $term->name;
}

/**
 * Return a human-readable tour results summary.
 *
 * @param int $found_posts Total matching tours.
 * @return string
 */
function tam_backend_api_get_tour_results_summary( $found_posts ) {
	$found_posts = max( 0, absint( $found_posts ) );

	if ( $found_posts < 1 ) {
		return __( 'Khong tim thay tour phu hop', 'travel-agency-modern' );
	}

	return sprintf(
		/* translators: %s is the number of matching tours. */
		_n( '%s tour phu hop', '%s tour phu hop', $found_posts, 'travel-agency-modern' ),
		number_format_i18n( $found_posts )
	);
}

/**
 * Build pagination markup for backend-driven tour archives.
 *
 * @param int    $current_page Current page number.
 * @param int    $total_pages  Total page count.
 * @param string $base_url     Base page URL.
 * @param array  $add_args     Extra query arguments.
 * @return string
 */
function tam_backend_api_get_pagination_markup( $current_page, $total_pages, $base_url, $add_args = array() ) {
	$current_page = max( 1, absint( $current_page ) );
	$total_pages  = max( 1, absint( $total_pages ) );
	$base_url     = $base_url ? $base_url : tam_get_page_url_by_path( 'tour' );
	$add_args     = is_array( $add_args ) ? $add_args : array();

	if ( $total_pages < 2 ) {
		return '';
	}

	$big  = 999999999;
	$base = str_replace( $big, '%#%', esc_url( add_query_arg( 'paged', $big, $base_url ) ) );
	$links = paginate_links(
		array(
			'base'      => $base,
			'format'    => '',
			'current'   => $current_page,
			'total'     => $total_pages,
			'type'      => 'list',
			'prev_text' => __( 'Truoc', 'travel-agency-modern' ),
			'next_text' => __( 'Sau', 'travel-agency-modern' ),
			'add_args'  => $add_args,
		)
	);

	if ( ! $links ) {
		return '';
	}

	return '<nav class="tam-pagination" aria-label="' . esc_attr__( 'Phan trang', 'travel-agency-modern' ) . '">' . wp_kses_post( $links ) . '</nav>';
}

/**
 * Normalize the display context for a backend tour card.
 *
 * @param array $tour Backend tour payload.
 * @return array
 */
function tam_backend_api_get_tour_card_context( $tour ) {
	$api_tour_id = isset( $tour['id'] ) ? absint( $tour['id'] ) : 0;
	$wp_post_id  = $api_tour_id ? tam_backend_api_get_post_id_by_api_tour_id( $api_tour_id ) : 0;

	if ( $wp_post_id < 1 && $api_tour_id > 0 ) {
		$maybe_post_id = tam_backend_api_upsert_tour_post( $tour );

		if ( ! is_wp_error( $maybe_post_id ) ) {
			$wp_post_id = (int) $maybe_post_id;
		}
	}

	$context = array(
		'permalink'     => '',
		'title'         => isset( $tour['title'] ) ? sanitize_text_field( (string) $tour['title'] ) : '',
		'excerpt'       => wp_trim_words( wp_strip_all_tags( (string) ( isset( $tour['description'] ) ? $tour['description'] : '' ) ), 24 ),
		'visual_url'    => isset( $tour['imageUrl'] ) ? tam_backend_api_resolve_asset_url( (string) $tour['imageUrl'] ) : '',
		'primary_term'  => ! empty( $tour['location'] ) ? sanitize_text_field( (string) $tour['location'] ) : __( 'Tour du lich', 'travel-agency-modern' ),
		'duration'      => ! empty( $tour['durationText'] ) ? sanitize_text_field( (string) $tour['durationText'] ) : '',
		'departure'     => ! empty( $tour['meetingPoint'] ) ? sanitize_text_field( (string) $tour['meetingPoint'] ) : '',
		'price_display' => function_exists( 'tam_format_tour_price' ) ? tam_format_tour_price( isset( $tour['price'] ) ? (string) absint( $tour['price'] ) : '' ) : '',
	);

	if ( empty( $context['departure'] ) && ! empty( $tour['departureNote'] ) ) {
		$context['departure'] = sanitize_text_field( (string) $tour['departureNote'] );
	}

	if ( $wp_post_id > 0 && 'tour' === get_post_type( $wp_post_id ) ) {
		$tour_meta    = function_exists( 'tam_get_tour_meta' ) ? tam_get_tour_meta( $wp_post_id ) : array();
		$destinations = function_exists( 'tam_get_tour_destinations' ) ? tam_get_tour_destinations( $wp_post_id ) : array();

		$context['permalink']     = (string) get_permalink( $wp_post_id );
		$context['title']         = get_the_title( $wp_post_id );
		$context['excerpt']       = wp_trim_words( get_the_excerpt( $wp_post_id ), 24 );
		$context['visual_url']    = function_exists( 'tam_get_tour_image_url' ) ? tam_get_tour_image_url( $wp_post_id, 'tam-tour-card' ) : $context['visual_url'];
		$context['primary_term']  = ! empty( $destinations ) ? $destinations[0]->name : $context['primary_term'];
		$context['duration']      = ! empty( $tour_meta['duration'] ) ? (string) $tour_meta['duration'] : $context['duration'];
		$context['departure']     = ! empty( $tour_meta['departure'] ) ? (string) $tour_meta['departure'] : $context['departure'];
		$context['price_display'] = function_exists( 'tam_format_tour_price' ) ? tam_format_tour_price( isset( $tour_meta['price_from'] ) ? (string) $tour_meta['price_from'] : '' ) : $context['price_display'];
	}

	return $context;
}

/**
 * Render one backend-driven tour card using the theme card UI.
 *
 * @param array $tour Backend tour payload.
 * @return string
 */
function tam_backend_api_get_tour_card_markup( $tour ) {
	$context = tam_backend_api_get_tour_card_context( $tour );
	$link    = $context['permalink'] ? $context['permalink'] : '#';

	ob_start();
	?>
	<article class="tam-card tam-content-card">
		<a class="tam-card__media" href="<?php echo esc_url( $link ); ?>">
			<?php if ( $context['visual_url'] ) : ?>
				<img src="<?php echo esc_url( $context['visual_url'] ); ?>" alt="<?php echo esc_attr( $context['title'] ); ?>" loading="lazy" />
			<?php else : ?>
				<div class="tam-card__placeholder"><?php echo esc_html( $context['primary_term'] ); ?></div>
			<?php endif; ?>
		</a>
		<div class="tam-card__body">
			<div class="tam-card__meta">
				<span><?php echo esc_html( $context['primary_term'] ); ?></span>
				<?php if ( $context['duration'] ) : ?>
					<span><?php echo esc_html( $context['duration'] ); ?></span>
				<?php endif; ?>
				<?php if ( $context['departure'] ) : ?>
					<span><?php echo esc_html( $context['departure'] ); ?></span>
				<?php endif; ?>
			</div>
			<h2 class="tam-card__title"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $context['title'] ); ?></a></h2>
			<p class="tam-card__excerpt"><?php echo esc_html( $context['excerpt'] ); ?></p>
			<div class="tam-card__footer">
				<div class="tam-price">
					<span><?php esc_html_e( 'Gia tham khao', 'travel-agency-modern' ); ?></span>
					<strong><?php echo esc_html( $context['price_display'] ); ?></strong>
				</div>
				<a class="tam-button tam-button--ghost" href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'Xem chi tiet', 'travel-agency-modern' ); ?></a>
			</div>
		</div>
	</article>
	<?php

	return trim( ob_get_clean() );
}

/**
 * Render the tour archive results area from backend payload data.
 *
 * @param array $tours Backend tour rows.
 * @param array $args  Rendering options.
 * @return string
 */
function tam_backend_api_get_tour_results_markup( $tours, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'found_posts'   => is_array( $tours ) ? count( $tours ) : 0,
			'current_page'  => 1,
			'total_pages'   => 1,
			'search_term'   => '',
			'selected_dest' => '',
			'base_url'      => tam_get_page_url_by_path( 'tour' ),
		)
	);

	$tours       = is_array( $tours ) ? array_values( $tours ) : array();
	$found_posts = max( 0, absint( $args['found_posts'] ) );
	$summary     = tam_backend_api_get_tour_results_summary( $found_posts );

	ob_start();

	if ( ! empty( $tours ) ) :
		?>
		<div class="tam-section-head tam-section-head--results">
			<div>
				<div class="tam-eyebrow"><?php esc_html_e( 'Ket qua hien co', 'travel-agency-modern' ); ?></div>
				<h2 class="tam-section-title"><?php echo esc_html( $summary ); ?></h2>
				<p class="tam-section-subtitle"><?php esc_html_e( 'Danh sach tour dang duoc tai tu backend API va phan trang theo tung nhom 10 tour de de theo doi hon.', 'travel-agency-modern' ); ?></p>
			</div>
		</div>
		<div class="tam-tour-grid">
			<?php foreach ( $tours as $tour ) : ?>
				<?php echo tam_backend_api_get_tour_card_markup( $tour ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endforeach; ?>
		</div>
		<?php
		echo tam_backend_api_get_pagination_markup(
			$args['current_page'],
			$args['total_pages'],
			$args['base_url'],
			array_filter(
				array(
					'search_tour' => sanitize_text_field( $args['search_term'] ),
					'destination' => sanitize_title( $args['selected_dest'] ),
				)
			)
		); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	else :
		?>
		<div class="tam-empty-state">
			<strong><?php esc_html_e( 'Khong tim thay tour phu hop', 'travel-agency-modern' ); ?></strong>
			<p><?php esc_html_e( 'Hay thu doi tu khoa hoac bo loc diem den de xem them hanh trinh khac.', 'travel-agency-modern' ); ?></p>
		</div>
		<?php
	endif;

	return trim( ob_get_clean() );
}

/**
 * Fetch a paginated tour archive payload from the backend API.
 *
 * @param string $search_term   Search keyword.
 * @param string $selected_dest Destination slug.
 * @param int    $paged         Current page.
 * @param string $base_url      Archive page URL.
 * @return array
 */
function tam_backend_api_get_tour_archive_payload( $search_term = '', $selected_dest = '', $paged = 1, $base_url = '' ) {
	$search_term      = sanitize_text_field( $search_term );
	$selected_dest    = sanitize_title( $selected_dest );
	$paged            = max( 1, absint( $paged ) );
	$base_url         = $base_url ? $base_url : tam_get_page_url_by_path( 'tour' );
	$destination_name = tam_backend_api_get_destination_name_from_slug( $selected_dest );
	$query_args       = array(
		'limit' => 10,
		'page'  => $paged,
		'status' => 'Active',
	);

	if ( '' !== $search_term ) {
		$query_args['search'] = $search_term;
	}

	if ( '' !== $destination_name ) {
		$query_args['location'] = $destination_name;
	}

	$response = tam_backend_api_request(
		'GET',
		'tours',
		array(
			'query'   => $query_args,
			'timeout' => 20,
		)
	);

	if ( ! $response['success'] ) {
		return array(
			'success'      => false,
			'message'      => ! empty( $response['message'] ) ? $response['message'] : __( 'Khong the tai du lieu tour tu backend.', 'travel-agency-modern' ),
			'html'         => '',
			'found_posts'  => 0,
			'current_page' => $paged,
			'summary'      => '',
		);
	}

	$payload      = isset( $response['data'] ) && is_array( $response['data'] ) ? $response['data'] : array();
	$tours        = ! empty( $payload['tours'] ) && is_array( $payload['tours'] ) ? array_values( $payload['tours'] ) : array();
	$pagination   = ! empty( $payload['pagination'] ) && is_array( $payload['pagination'] ) ? $payload['pagination'] : array();
	$found_posts  = isset( $pagination['total'] ) ? absint( $pagination['total'] ) : count( $tours );
	$current_page = isset( $pagination['page'] ) ? max( 1, absint( $pagination['page'] ) ) : $paged;
	$total_pages  = isset( $pagination['totalPages'] ) ? max( 1, absint( $pagination['totalPages'] ) ) : 1;
	$summary      = tam_backend_api_get_tour_results_summary( $found_posts );

	return array(
		'success'      => true,
		'message'      => isset( $payload['message'] ) ? (string) $payload['message'] : '',
		'html'         => tam_backend_api_get_tour_results_markup(
			$tours,
			array(
				'found_posts'   => $found_posts,
				'current_page'  => $current_page,
				'total_pages'   => $total_pages,
				'search_term'   => $search_term,
				'selected_dest' => $selected_dest,
				'base_url'      => $base_url,
			)
		),
		'found_posts'  => $found_posts,
		'current_page' => $current_page,
		'summary'      => $summary,
	);
}

/**
 * Translate booking statuses for public display.
 *
 * @param string $status Backend booking status.
 * @return string
 */
function tam_backend_api_get_booking_status_label( $status ) {
	$map = array(
		'PENDING'   => __( 'Chờ xác nhận', 'travel-agency-modern' ),
		'CONFIRMED' => __( 'Đã xác nhận', 'travel-agency-modern' ),
		'COMPLETED' => __( 'Đã hoàn thành', 'travel-agency-modern' ),
		'CANCELLED' => __( 'Đã huỷ', 'travel-agency-modern' ),
		'PAYMENT_FAILED' => __( 'Thanh toan that bai', 'travel-agency-modern' ),
	);

	$status = strtoupper( trim( (string) $status ) );

	if ( 'PENDING_PAYMENT' === $status ) {
		return __( 'Cho thanh toan', 'travel-agency-modern' );
	}

	if ( 'PENDING_CONFIRMATION' === $status ) {
		return __( 'Cho xac nhan', 'travel-agency-modern' );
	}

	if ( 'PAID' === $status ) {
		return __( 'Da thanh toan', 'travel-agency-modern' );
	}

	if ( 'REFUNDED' === $status ) {
		return __( 'Da hoan tien', 'travel-agency-modern' );
	}

	return isset( $map[ $status ] ) ? $map[ $status ] : $status;
}

/**
 * Translate payment methods for public display.
 *
 * @param string $method Backend payment method.
 * @return string
 */
function tam_backend_api_get_payment_method_label( $method ) {
	$map = array(
		'CASH'          => __( 'Tiền mặt', 'travel-agency-modern' ),
		'BANK_TRANSFER' => __( 'Chuyển khoản', 'travel-agency-modern' ),
		'MOMO'          => __( 'MoMo', 'travel-agency-modern' ),
		'VNPAY'         => __( 'VNPay', 'travel-agency-modern' ),
		'ZALOPAY'       => __( 'ZaloPay', 'travel-agency-modern' ),
		'CARD'          => __( 'The quoc te', 'travel-agency-modern' ),
	);

	$method = strtoupper( trim( (string) $method ) );

	return isset( $map[ $method ] ) ? $map[ $method ] : $method;
}

/**
 * Translate payment statuses for public display.
 *
 * @param string $status Backend payment status.
 * @return string
 */
function tam_backend_api_get_payment_status_label( $status ) {
	$map = array(
		'PENDING' => __( 'Đang chờ xử lý', 'travel-agency-modern' ),
		'SUCCESS' => __( 'Đã thanh toán', 'travel-agency-modern' ),
		'FAILED'  => __( 'Thất bại', 'travel-agency-modern' ),
	);

	$status = strtoupper( trim( (string) $status ) );

	if ( 'PARTIALLY_PAID' === $status ) {
		return __( 'Da dat coc', 'travel-agency-modern' );
	}

	if ( 'REFUNDED' === $status ) {
		return __( 'Da hoan tien', 'travel-agency-modern' );
	}

	if ( 'CANCELLED' === $status ) {
		return __( 'Da huy', 'travel-agency-modern' );
	}

	if ( 'EXPIRED' === $status ) {
		return __( 'Het han', 'travel-agency-modern' );
	}

	return isset( $map[ $status ] ) ? $map[ $status ] : $status;
}

/**
 * Format a backend date value with the site locale.
 *
 * @param string $value Raw date/time value.
 * @return string
 */
function tam_backend_api_format_date( $value ) {
	$timestamp = strtotime( (string) $value );

	if ( ! $timestamp ) {
		return (string) $value;
	}

	return wp_date( get_option( 'date_format' ), $timestamp );
}

/**
 * Format a backend datetime value with the site locale.
 *
 * @param string $value Raw date/time value.
 * @return string
 */
function tam_backend_api_format_datetime( $value ) {
	$timestamp = strtotime( (string) $value );

	if ( ! $timestamp ) {
		return (string) $value;
	}

	return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
}

/**
 * Fetch the current user's bookings from the backend API.
 *
 * @param bool $force_refresh Whether to bypass the in-request cache.
 * @return array
 */
function tam_backend_api_get_my_bookings( $force_refresh = false ) {
	static $cache = null;

	if ( ! $force_refresh && is_array( $cache ) ) {
		return $cache;
	}

	$token = tam_backend_api_get_auth_token();

	if ( ! $token ) {
		$cache = array();
		return $cache;
	}

	$response = tam_backend_api_request(
		'GET',
		'bookings/my',
		array(
			'auth_token' => $token,
		)
	);

	if ( 401 === (int) $response['status'] ) {
		tam_backend_api_clear_session();
		$cache = array();
		return $cache;
	}

	$cache = ( $response['success'] && ! empty( $response['data']['bookings'] ) && is_array( $response['data']['bookings'] ) )
		? $response['data']['bookings']
		: array();

	return $cache;
}

/**
 * Fetch a single booking detail for the authenticated user.
 *
 * @param int $booking_id Backend booking ID.
 * @return array|null
 */
function tam_backend_api_get_my_booking( $booking_id ) {
	static $cache = array();

	$booking_id = absint( $booking_id );

	if ( $booking_id < 1 ) {
		return null;
	}

	if ( array_key_exists( $booking_id, $cache ) ) {
		return $cache[ $booking_id ];
	}

	$token = tam_backend_api_get_auth_token();

	if ( ! $token ) {
		$cache[ $booking_id ] = null;
		return null;
	}

	$response = tam_backend_api_request(
		'GET',
		'bookings/my/' . $booking_id,
		array(
			'auth_token' => $token,
		)
	);

	if ( 401 === (int) $response['status'] ) {
		tam_backend_api_clear_session();
		$cache[ $booking_id ] = null;
		return null;
	}

	$cache[ $booking_id ] = ( $response['success'] && ! empty( $response['data']['booking'] ) && is_array( $response['data']['booking'] ) )
		? $response['data']['booking']
		: null;

	return $cache[ $booking_id ];
}

/**
 * Fetch payment history for a booking owned by the current user.
 *
 * @param int $booking_id Backend booking ID.
 * @return array
 */
function tam_backend_api_get_payments_for_booking( $booking_id ) {
	static $cache = array();

	$booking_id = absint( $booking_id );

	if ( $booking_id < 1 ) {
		return array();
	}

	if ( array_key_exists( $booking_id, $cache ) ) {
		return $cache[ $booking_id ];
	}

	$token = tam_backend_api_get_auth_token();

	if ( ! $token ) {
		$cache[ $booking_id ] = array();
		return $cache[ $booking_id ];
	}

	$response = tam_backend_api_request(
		'GET',
		'payments/booking/' . $booking_id,
		array(
			'auth_token' => $token,
		)
	);

	if ( 401 === (int) $response['status'] ) {
		tam_backend_api_clear_session();
		$cache[ $booking_id ] = array();
		return $cache[ $booking_id ];
	}

	$cache[ $booking_id ] = ( $response['success'] && ! empty( $response['data']['payments'] ) && is_array( $response['data']['payments'] ) )
		? $response['data']['payments']
		: array();

	return $cache[ $booking_id ];
}

/**
 * Fetch the current user's reviews from the backend API.
 *
 * @param bool $force_refresh Whether to bypass the in-request cache.
 * @return array
 */
function tam_backend_api_get_my_reviews( $force_refresh = false ) {
	static $cache = null;

	if ( ! $force_refresh && is_array( $cache ) ) {
		return $cache;
	}

	$token = tam_backend_api_get_auth_token();

	if ( ! $token ) {
		$cache = array();
		return $cache;
	}

	$response = tam_backend_api_request(
		'GET',
		'reviews/my',
		array(
			'auth_token' => $token,
		)
	);

	if ( 401 === (int) $response['status'] ) {
		tam_backend_api_clear_session();
		$cache = array();
		return $cache;
	}

	$cache = ( $response['success'] && ! empty( $response['data']['reviews'] ) && is_array( $response['data']['reviews'] ) )
		? $response['data']['reviews']
		: array();

	return $cache;
}

/**
 * Return bookings that can be reviewed on the current tour page.
 *
 * @param int $post_id WordPress tour post ID.
 * @return array
 */
function tam_backend_api_get_reviewable_bookings_for_post( $post_id ) {
	$api_tour_id = tam_backend_api_get_tour_id_for_post( $post_id );

	if ( $api_tour_id < 1 ) {
		return array();
	}

	$bookings = tam_backend_api_get_my_bookings();

	return array_values(
		array_filter(
			$bookings,
			static function ( $booking ) use ( $api_tour_id ) {
				return ! empty( $booking['can_review'] ) && isset( $booking['tour_id'] ) && absint( $booking['tour_id'] ) === $api_tour_id;
			}
		)
	);
}

/**
 * Handle frontend booking cancel requests.
 *
 * @return void
 */
function tam_backend_api_handle_cancel_booking() {
	$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : tam_backend_api_get_account_url();

	if ( ! isset( $_POST['tam_account_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tam_account_nonce'] ) ), 'tam_account_cancel_booking' ) ) {
		tam_backend_api_redirect_public_status( 'account_status', 'invalid_nonce', $redirect_to, array(), array( 'account_message' ) );
	}

	$token      = tam_backend_api_get_auth_token();
	$booking_id = isset( $_POST['booking_id'] ) ? absint( wp_unslash( $_POST['booking_id'] ) ) : 0;

	if ( ! $token ) {
		tam_backend_api_redirect_public_status( 'account_status', 'login_required', $redirect_to, array(), array( 'account_message' ) );
	}

	if ( $booking_id < 1 ) {
		tam_backend_api_redirect_public_status(
			'account_status',
			'cancel_failed',
			$redirect_to,
			array(
				'account_message' => rawurlencode( __( 'Booking không hợp lệ.', 'travel-agency-modern' ) ),
			),
			array( 'account_message' )
		);
	}

	$response = tam_backend_api_request(
		'PUT',
		'bookings/my/' . $booking_id . '/cancel',
		array(
			'auth_token' => $token,
		)
	);

	if ( ! $response['success'] ) {
		if ( 401 === (int) $response['status'] ) {
			tam_backend_api_clear_session();
			tam_backend_api_redirect_public_status( 'account_status', 'login_required', $redirect_to, array(), array( 'account_message' ) );
		}

		tam_backend_api_redirect_public_status(
			'account_status',
			'cancel_failed',
			$redirect_to,
			array(
				'account_message' => rawurlencode( ! empty( $response['message'] ) ? $response['message'] : __( 'Backend chưa thể huỷ booking này.', 'travel-agency-modern' ) ),
			),
			array( 'account_message' )
		);
	}

	tam_backend_api_redirect_public_status( 'account_status', 'cancel_success', $redirect_to, array(), array( 'account_message' ) );
}
add_action( 'admin_post_tam_cancel_booking', 'tam_backend_api_handle_cancel_booking' );
add_action( 'admin_post_nopriv_tam_cancel_booking', 'tam_backend_api_handle_cancel_booking' );

/**
 * Handle public review submissions against the backend API.
 *
 * @return void
 */
function tam_backend_api_handle_review_submission() {
	$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/' );

	if ( ! isset( $_POST['tam_review_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tam_review_nonce'] ) ), 'tam_submit_review' ) ) {
		tam_backend_api_redirect_public_status( 'review_status', 'invalid_nonce', $redirect_to, array(), array( 'review_message' ) );
	}

	$token      = tam_backend_api_get_auth_token();
	$booking_id = isset( $_POST['booking_id'] ) ? absint( wp_unslash( $_POST['booking_id'] ) ) : 0;
	$rating     = isset( $_POST['rating'] ) ? absint( wp_unslash( $_POST['rating'] ) ) : 0;
	$comment    = isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) : '';

	if ( ! $token ) {
		tam_backend_api_redirect_public_status( 'review_status', 'login_required', $redirect_to, array(), array( 'review_message' ) );
	}

	if ( $booking_id < 1 || $rating < 1 || $rating > 5 || '' === trim( $comment ) ) {
		tam_backend_api_redirect_public_status( 'review_status', 'missing', $redirect_to, array(), array( 'review_message' ) );
	}

	$response = tam_backend_api_request(
		'POST',
		'reviews',
		array(
			'auth_token' => $token,
			'body'       => array(
				'booking_id' => $booking_id,
				'rating'     => $rating,
				'comment'    => $comment,
			),
		)
	);

	if ( ! $response['success'] ) {
		if ( 401 === (int) $response['status'] ) {
			tam_backend_api_clear_session();
			tam_backend_api_redirect_public_status( 'review_status', 'login_required', $redirect_to, array(), array( 'review_message' ) );
		}

		tam_backend_api_redirect_public_status(
			'review_status',
			'review_failed',
			$redirect_to,
			array(
				'review_message' => rawurlencode( ! empty( $response['message'] ) ? $response['message'] : __( 'Backend chưa ghi nhận được đánh giá của bạn.', 'travel-agency-modern' ) ),
			),
			array( 'review_message' )
		);
	}

	tam_backend_api_redirect_public_status( 'review_status', 'success', $redirect_to, array(), array( 'review_message' ) );
}
add_action( 'admin_post_tam_submit_tour_review', 'tam_backend_api_handle_review_submission' );
add_action( 'admin_post_nopriv_tam_submit_tour_review', 'tam_backend_api_handle_review_submission' );

/**
 * Map the theme checkout payment choice to the backend payment enum.
 *
 * @param string $method Theme payment key.
 * @return string
 */
function tam_backend_api_map_payment_method( $method ) {
	$method_map = array(
		'cod'     => 'CASH',
		'vnpay'   => 'VNPAY',
		'momo'    => 'MOMO',
		'zalopay' => 'ZALOPAY',
		'bank'    => 'BANK_TRANSFER',
		'wallet'  => 'MOMO',
		'card'    => 'CARD',
	);

	return isset( $method_map[ $method ] ) ? $method_map[ $method ] : 'VNPAY';
}

/**
 * Build a readable booking reference from the backend booking ID.
 *
 * @param int $booking_id Backend booking ID.
 * @return string
 */
function tam_backend_api_build_booking_ref( $booking_id ) {
	return 'API-' . str_pad( (string) absint( $booking_id ), 6, '0', STR_PAD_LEFT );
}

/**
 * Format a backend checkout summary into a short text line.
 *
 * @param array  $summary Checkout summary.
 * @param string $path    Dot path to the amount.
 * @return string
 */
function tam_backend_api_get_summary_amount_display( $summary, $path ) {
	$value = 0;
	$parts = explode( '.', (string) $path );
	$node  = $summary;

	foreach ( $parts as $part ) {
		if ( ! is_array( $node ) || ! array_key_exists( $part, $node ) ) {
			$node = 0;
			break;
		}

		$node = $node[ $part ];
	}

	$value = (int) round( (float) $node );

	if ( function_exists( 'tam_format_tour_price' ) ) {
		return tam_format_tour_price( (string) $value );
	}

	return number_format_i18n( $value ) . 'đ';
}

/**
 * Fetch a checkout transaction summary from the backend API.
 *
 * @param string $transaction_code Transaction code.
 * @return array|null
 */
function tam_backend_api_get_checkout_transaction_summary( $transaction_code ) {
	$transaction_code = sanitize_text_field( (string) $transaction_code );
	$auth_token       = tam_backend_api_get_auth_token();

	if ( '' === $transaction_code || '' === $auth_token ) {
		return null;
	}

	$response = tam_backend_api_request(
		'GET',
		'checkout/transaction/' . rawurlencode( $transaction_code ),
		array(
			'auth_token' => $auth_token,
		)
	);

	if ( 401 === (int) $response['status'] ) {
		tam_backend_api_clear_session();
	}

	if ( ! $response['success'] || empty( $response['data']['summary'] ) || ! is_array( $response['data']['summary'] ) ) {
		return null;
	}

	return $response['data']['summary'];
}

/**
 * Build the invoice download URL for a checkout transaction.
 *
 * @param string $transaction_code Transaction code.
 * @return string
 */
function tam_backend_api_get_checkout_invoice_url( $transaction_code ) {
	return add_query_arg(
		array(
			'action'      => 'tam_download_checkout_invoice',
			'checkout_tx' => rawurlencode( (string) $transaction_code ),
		),
		admin_url( 'admin-post.php' )
	);
}

/**
 * Render a QR-style booking pass as inline SVG.
 *
 * @param string $seed Unique booking seed.
 * @return string
 */
function tam_backend_api_get_booking_qr_markup( $seed ) {
	$seed = trim( (string) $seed );

	if ( '' === $seed ) {
		return '';
	}

	$grid_size = 29;
	$cell      = 6;
	$margin    = 10;
	$svg_size  = ( $grid_size * $cell ) + ( $margin * 2 );
	$hash      = hash( 'sha256', $seed );
	$bits      = '';

	for ( $index = 0; $index < strlen( $hash ); $index++ ) {
		$bits .= str_pad( base_convert( $hash[ $index ], 16, 2 ), 4, '0', STR_PAD_LEFT );
	}

	$finder_positions = array(
		array( 0, 0 ),
		array( $grid_size - 7, 0 ),
		array( 0, $grid_size - 7 ),
	);
	$rectangles       = '';
	$bit_index        = 0;

	for ( $row = 0; $row < $grid_size; $row++ ) {
		for ( $column = 0; $column < $grid_size; $column++ ) {
			$is_finder = false;

			foreach ( $finder_positions as $finder ) {
				if (
					$column >= $finder[0] &&
					$column < $finder[0] + 7 &&
					$row >= $finder[1] &&
					$row < $finder[1] + 7
				) {
					$is_finder = true;
					$local_x   = $column - $finder[0];
					$local_y   = $row - $finder[1];
					$on_outer  = 0 === $local_x || 0 === $local_y || 6 === $local_x || 6 === $local_y;
					$on_inner  = $local_x >= 2 && $local_x <= 4 && $local_y >= 2 && $local_y <= 4;

					if ( $on_outer || $on_inner ) {
						$x = $margin + ( $column * $cell );
						$y = $margin + ( $row * $cell );
						$rectangles .= '<rect x="' . (int) $x . '" y="' . (int) $y . '" width="' . (int) $cell . '" height="' . (int) $cell . '" rx="1.2" />';
					}

					break;
				}
			}

			if ( $is_finder ) {
				continue;
			}

			$should_fill = '1' === $bits[ $bit_index % strlen( $bits ) ];
			$bit_index++;

			if ( ! $should_fill ) {
				continue;
			}

			$x = $margin + ( $column * $cell );
			$y = $margin + ( $row * $cell );
			$rectangles .= '<rect x="' . (int) $x . '" y="' . (int) $y . '" width="' . (int) $cell . '" height="' . (int) $cell . '" rx="1.2" />';
		}
	}

	return '<svg class="tam-booking-flow__qr-svg" viewBox="0 0 ' . (int) $svg_size . ' ' . (int) $svg_size . '" role="img" aria-label="' . esc_attr__( 'Booking QR', 'travel-agency-modern' ) . '" xmlns="http://www.w3.org/2000/svg"><rect width="' . (int) $svg_size . '" height="' . (int) $svg_size . '" rx="20" fill="#ffffff"/><g fill="#10233c">' . $rectangles . '</g></svg>';
}

/**
 * Send a payment receipt email once after a successful transaction.
 *
 * @param array $summary Checkout summary.
 * @return void
 */
function tam_backend_api_maybe_send_checkout_payment_receipt_email( $summary ) {
	if ( empty( $summary['transaction']['code'] ) || empty( $summary['booking']['details']['contactEmail'] ) ) {
		return;
	}

	$transaction_code = sanitize_key( strtolower( (string) $summary['transaction']['code'] ) );
	$transient_key    = 'tam_checkout_mail_' . md5( $transaction_code );

	if ( get_transient( $transient_key ) ) {
		return;
	}

	$site_name   = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$booking     = isset( $summary['booking'] ) && is_array( $summary['booking'] ) ? $summary['booking'] : array();
	$tour        = isset( $summary['tour'] ) && is_array( $summary['tour'] ) ? $summary['tour'] : array();
	$details     = isset( $booking['details'] ) && is_array( $booking['details'] ) ? $booking['details'] : array();
	$payment     = isset( $summary['payment'] ) && is_array( $summary['payment'] ) ? $summary['payment'] : array();
	$total_label = tam_backend_api_get_summary_amount_display( $summary, 'pricing.totalAmount' );
	$subject     = sprintf(
		'[%1$s] Da nhan thanh toan cho booking %2$s',
		$site_name,
		isset( $booking['code'] ) ? (string) $booking['code'] : ''
	);
	$body        = implode(
		"\n",
		array_filter(
			array(
				'Xin chao ' . ( isset( $details['contactName'] ) ? (string) $details['contactName'] : '' ) . ',',
				'',
				'ADN Travel da nhan thanh toan cho booking cua ban.',
				'Booking hien dang cho nhan vien xac nhan thu cong truoc khi gui ve chinh thuc.',
				'Ma booking: ' . ( isset( $booking['code'] ) ? (string) $booking['code'] : '' ),
				'Tour: ' . ( isset( $tour['title'] ) ? (string) $tour['title'] : '' ),
				'Ngay khoi hanh: ' . ( isset( $booking['travelDate'] ) ? (string) $booking['travelDate'] : '' ),
				'Hanh khach: ' . ( isset( $booking['travellers'] ) ? (int) $booking['travellers'] : 0 ),
				'Phuong thuc thanh toan: ' . tam_backend_api_get_payment_method_label( isset( $payment['method'] ) ? $payment['method'] : '' ),
				'So tien da nhan: ' . tam_backend_api_get_summary_amount_display( $summary, 'pricing.payableNowAmount' ),
				'Tong gia tri booking: ' . $total_label,
				'Con lai: ' . tam_backend_api_get_summary_amount_display( $summary, 'pricing.remainingAmount' ),
				'Trang thai booking: Cho xac nhan',
				'',
				'Chung toi se lien he som nhat sau khi doi ngu van hanh kiem tra va xac nhan booking.',
				'Ban co the vao trang tai khoan de xem lich su booking va trang thai thanh toan.',
			)
		)
	);

	wp_mail(
		sanitize_email( (string) $details['contactEmail'] ),
		$subject,
		$body,
		array( 'Content-Type: text/plain; charset=UTF-8' )
	);

	set_transient( $transient_key, 1, DAY_IN_SECONDS );
}

/**
 * Build the result context for the step-4 success/failure screen.
 *
 * @return array|null
 */
function tam_backend_api_get_checkout_result_context() {
	$transaction_code = isset( $_GET['checkout_tx'] ) ? sanitize_text_field( wp_unslash( $_GET['checkout_tx'] ) ) : '';
	$result           = isset( $_GET['checkout_result'] ) ? sanitize_key( wp_unslash( $_GET['checkout_result'] ) ) : '';

	if ( '' === $transaction_code || '' === $result ) {
		return null;
	}

	$summary = tam_backend_api_get_checkout_transaction_summary( $transaction_code );

	if ( ! $summary ) {
		return array(
			'result'          => 'failed',
			'transactionCode' => $transaction_code,
			'checkoutToken'   => '',
			'message'         => __( 'Khong the tai lai ket qua giao dich nay. Vui long kiem tra lich su booking hoac thu lai sau.', 'travel-agency-modern' ),
			'summary'         => null,
			'invoiceUrl'      => '',
			'accountUrl'      => tam_backend_api_get_account_url(),
			'qrMarkup'        => '',
		);
	}

	if ( 'success' === $result ) {
		tam_backend_api_maybe_send_checkout_payment_receipt_email( $summary );
	}

	$booking_status = ! empty( $summary['booking']['status'] ) ? strtoupper( (string) $summary['booking']['status'] ) : '';
	$is_confirmed   = in_array( $booking_status, array( 'CONFIRMED', 'COMPLETED' ), true );

	return array(
		'result'          => 'success' === $result ? 'success' : 'failed',
		'transactionCode' => $transaction_code,
		'checkoutToken'   => '',
		'message'         => 'success' === $result
			? (
				$is_confirmed
					? __( 'Thanh toan thanh cong va booking cua ban da duoc xac nhan chinh thuc.', 'travel-agency-modern' )
					: __( 'Thanh toan thanh cong. Booking cua ban dang cho nhan vien xac nhan. Chung toi se lien he voi ban som nhat.', 'travel-agency-modern' )
			)
			: __( 'Giao dich chua thanh cong. Ban co the quay lai chon phuong thuc khac.', 'travel-agency-modern' ),
		'summary'         => $summary,
		'invoiceUrl'      => $is_confirmed ? tam_backend_api_get_checkout_invoice_url( $transaction_code ) : '',
		'accountUrl'      => tam_backend_api_get_account_url(),
		'qrMarkup'        => $is_confirmed ? tam_backend_api_get_booking_qr_markup( $transaction_code . '|' . ( isset( $summary['booking']['code'] ) ? (string) $summary['booking']['code'] : '' ) ) : '',
		'isConfirmed'     => $is_confirmed,
	);
}

/**
 * Escape text for the simple invoice PDF stream.
 *
 * @param string $text Raw text.
 * @return string
 */
function tam_backend_api_pdf_escape_text( $text ) {
	$text = remove_accents( wp_strip_all_tags( (string) $text ) );
	$text = preg_replace( '/[^\x20-\x7E]/', ' ', $text );
	$text = preg_replace( '/\s+/', ' ', (string) $text );
	$text = trim( (string) $text );
	$text = str_replace( '\\', '\\\\', $text );
	$text = str_replace( '(', '\(', $text );
	$text = str_replace( ')', '\)', $text );

	return $text;
}

/**
 * Build a lightweight PDF invoice without external libraries.
 *
 * @param array $summary Checkout summary.
 * @return string
 */
function tam_backend_api_build_invoice_pdf( $summary ) {
	$booking  = isset( $summary['booking'] ) && is_array( $summary['booking'] ) ? $summary['booking'] : array();
	$details  = isset( $booking['details'] ) && is_array( $booking['details'] ) ? $booking['details'] : array();
	$tour     = isset( $summary['tour'] ) && is_array( $summary['tour'] ) ? $summary['tour'] : array();
	$payment  = isset( $summary['payment'] ) && is_array( $summary['payment'] ) ? $summary['payment'] : array();
	$lines    = array(
		'ADN Travel Booking Invoice',
		'',
		'Booking code: ' . ( isset( $booking['code'] ) ? $booking['code'] : '' ),
		'Transaction code: ' . ( isset( $summary['transaction']['code'] ) ? $summary['transaction']['code'] : '' ),
		'Customer: ' . ( isset( $details['contactName'] ) ? $details['contactName'] : '' ),
		'Email: ' . ( isset( $details['contactEmail'] ) ? $details['contactEmail'] : '' ),
		'Phone: ' . ( isset( $details['contactPhone'] ) ? $details['contactPhone'] : '' ),
		'Country: ' . ( isset( $details['contactCountry'] ) ? $details['contactCountry'] : '' ),
		'',
		'Tour: ' . ( isset( $tour['title'] ) ? $tour['title'] : '' ),
		'Departure date: ' . ( isset( $booking['travelDate'] ) ? $booking['travelDate'] : '' ),
		'Travellers: ' . ( isset( $booking['travellers'] ) ? (string) $booking['travellers'] : '0' ),
		'Payment method: ' . tam_backend_api_get_payment_method_label( isset( $payment['method'] ) ? $payment['method'] : '' ),
		'Payment status: ' . ( isset( $payment['status'] ) ? $payment['status'] : '' ),
		'',
		'Subtotal: ' . tam_backend_api_get_summary_amount_display( $summary, 'pricing.baseAmount' ),
		'Discount: ' . tam_backend_api_get_summary_amount_display( $summary, 'pricing.discountAmount' ),
		'Tax: ' . tam_backend_api_get_summary_amount_display( $summary, 'pricing.taxAmount' ),
		'Service fee: ' . tam_backend_api_get_summary_amount_display( $summary, 'pricing.feeAmount' ),
		'Total amount: ' . tam_backend_api_get_summary_amount_display( $summary, 'pricing.totalAmount' ),
		'',
		'Generated at: ' . gmdate( 'Y-m-d H:i:s' ) . ' UTC',
	);
	$content  = "BT\n/F1 22 Tf\n1 0 0 1 54 800 Tm (" . tam_backend_api_pdf_escape_text( 'ADN Travel Invoice' ) . ") Tj\n";
	$content .= "/F1 11 Tf\n";

	$line_index = 0;

	foreach ( $lines as $line ) {
		$y = 768 - ( $line_index * 18 );
		$content .= '1 0 0 1 54 ' . (int) $y . ' Tm (' . tam_backend_api_pdf_escape_text( $line ) . ") Tj\n";
		$line_index++;
	}

	$content .= "ET\n";
	$objects = array(
		"1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
		"2 0 obj\n<< /Type /Pages /Count 1 /Kids [3 0 R] >>\nendobj\n",
		"3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>\nendobj\n",
		"4 0 obj\n<< /Length " . strlen( $content ) . " >>\nstream\n" . $content . "endstream\nendobj\n",
		"5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
	);
	$pdf     = "%PDF-1.4\n";
	$offsets = array( 0 );

	foreach ( $objects as $object ) {
		$offsets[] = strlen( $pdf );
		$pdf      .= $object;
	}

	$xref_offset = strlen( $pdf );
	$pdf        .= "xref\n0 " . ( count( $objects ) + 1 ) . "\n";
	$pdf        .= "0000000000 65535 f \n";

	for ( $index = 1; $index <= count( $objects ); $index++ ) {
		$pdf .= sprintf( "%010d 00000 n \n", $offsets[ $index ] );
	}

	$pdf .= "trailer\n<< /Size " . ( count( $objects ) + 1 ) . " /Root 1 0 R >>\nstartxref\n" . $xref_offset . "\n%%EOF";

	return $pdf;
}

/**
 * Download a generated invoice PDF for a successful booking.
 *
 * @return void
 */
function tam_backend_api_handle_checkout_invoice_download() {
	$transaction_code = isset( $_GET['checkout_tx'] ) ? sanitize_text_field( wp_unslash( $_GET['checkout_tx'] ) ) : '';
	$summary          = tam_backend_api_get_checkout_transaction_summary( $transaction_code );

	if ( ! $summary ) {
		wp_die(
			esc_html__( 'Khong tim thay invoice booking.', 'travel-agency-modern' ),
			'',
			array(
				'response' => 404,
			)
		);
	}

	$booking_status = ! empty( $summary['booking']['status'] ) ? strtoupper( (string) $summary['booking']['status'] ) : '';

	if ( ! in_array( $booking_status, array( 'CONFIRMED', 'COMPLETED' ), true ) ) {
		wp_die(
			esc_html__( 'Invoice chinh thuc chi kha dung sau khi booking duoc nhan vien xac nhan.', 'travel-agency-modern' ),
			'',
			array(
				'response' => 403,
			)
		);
	}

	$pdf      = tam_backend_api_build_invoice_pdf( $summary );
	$filename = 'invoice-' . sanitize_file_name( strtolower( (string) $summary['booking']['code'] ) ) . '.pdf';

	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Length: ' . strlen( $pdf ) );

	echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}
add_action( 'admin_post_tam_download_checkout_invoice', 'tam_backend_api_handle_checkout_invoice_download' );
add_action( 'admin_post_nopriv_tam_download_checkout_invoice', 'tam_backend_api_handle_checkout_invoice_download' );

/**
 * Return a normalized checkout quote payload over wp-admin/admin-ajax.php.
 *
 * @return void
 */
function tam_backend_api_handle_ajax_checkout_quote_request() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'tam_checkout_quote' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Phien checkout da het han. Vui long tai lai trang.', 'travel-agency-modern' ),
			),
			403
		);
	}

	$wp_tour_id      = isset( $_POST['tour_id'] ) ? absint( wp_unslash( $_POST['tour_id'] ) ) : 0;
	$api_tour_id     = tam_backend_api_get_tour_id_for_post( $wp_tour_id );
	$travel_date     = isset( $_POST['travel_date'] ) ? sanitize_text_field( wp_unslash( $_POST['travel_date'] ) ) : '';
	$adults_count    = isset( $_POST['adults_count'] ) ? absint( wp_unslash( $_POST['adults_count'] ) ) : 1;
	$children_count  = isset( $_POST['children_count'] ) ? absint( wp_unslash( $_POST['children_count'] ) ) : 0;
	$coupon_code     = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';
	$payment_plan    = isset( $_POST['payment_plan'] ) ? sanitize_key( wp_unslash( $_POST['payment_plan'] ) ) : 'full';

	if ( $api_tour_id < 1 ) {
		wp_send_json_error(
			array(
				'message' => __( 'Tour nay chua duoc dong bo sang backend.', 'travel-agency-modern' ),
			),
			400
		);
	}

	$response = tam_backend_api_request(
		'POST',
		'checkout/quote',
		array(
			'body' => array(
				'tour_id'        => $api_tour_id,
				'travel_date'    => $travel_date,
				'adults_count'   => max( 1, $adults_count ),
				'children_count' => max( 0, $children_count ),
				'coupon_code'    => $coupon_code,
				'payment_plan'   => strtoupper( 'deposit' === strtolower( $payment_plan ) ? 'DEPOSIT' : 'FULL' ),
			),
		)
	);

	if ( ! $response['success'] ) {
		wp_send_json_error(
			array(
				'message' => tam_backend_api_get_error_message( $response, __( 'Khong the tinh tong thanh toan luc nay.', 'travel-agency-modern' ), 'checkout/quote' ),
				'errors'  => $response['errors'],
				'code'    => tam_backend_api_is_missing_route_response( $response ) ? 'backend_route_missing' : 'quote_failed',
			),
			$response['status'] ? $response['status'] : 400
		);
	}

	wp_send_json_success(
		array(
			'message'     => ! empty( $response['message'] ) ? $response['message'] : __( 'Da cap nhat tong thanh toan.', 'travel-agency-modern' ),
			'summary'     => isset( $response['data']['summary'] ) ? $response['data']['summary'] : array(),
			'couponValid' => ! empty( $response['data']['couponValid'] ),
		)
	);
}
add_action( 'wp_ajax_tam_checkout_quote', 'tam_backend_api_handle_ajax_checkout_quote_request' );
add_action( 'wp_ajax_nopriv_tam_checkout_quote', 'tam_backend_api_handle_ajax_checkout_quote_request' );

/**
 * Create a secure backend checkout session via admin-ajax.
 *
 * @return void
 */
function tam_backend_api_handle_ajax_checkout_session_request() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'tam_checkout_create_session' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Phien checkout da het han. Vui long tai lai trang.', 'travel-agency-modern' ),
			),
			403
		);
	}

	$auth_token = tam_backend_api_get_auth_token();

	if ( ! $auth_token ) {
		wp_send_json_error(
			array(
				'message' => __( 'Ban can dang nhap truoc khi thanh toan.', 'travel-agency-modern' ),
				'code'    => 'login_required',
			),
			401
		);
	}

	$wp_tour_id        = isset( $_POST['tour_id'] ) ? absint( wp_unslash( $_POST['tour_id'] ) ) : 0;
	$api_tour_id       = tam_backend_api_get_tour_id_for_post( $wp_tour_id );
	$request_id        = isset( $_POST['request_id'] ) ? sanitize_text_field( wp_unslash( $_POST['request_id'] ) ) : '';
	$travel_date       = isset( $_POST['travel_date'] ) ? sanitize_text_field( wp_unslash( $_POST['travel_date'] ) ) : '';
	$contact_name      = isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '';
	$contact_phone     = isset( $_POST['contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) ) : '';
	$contact_email     = isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '';
	$contact_country   = isset( $_POST['contact_country'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_country'] ) ) : '';
	$special_requests  = isset( $_POST['special_requests'] ) ? sanitize_textarea_field( wp_unslash( $_POST['special_requests'] ) ) : '';
	$payment_method    = isset( $_POST['payment_method'] ) ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : 'vnpay';
	$payment_plan      = isset( $_POST['payment_plan'] ) ? sanitize_key( wp_unslash( $_POST['payment_plan'] ) ) : 'full';
	$coupon_code       = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';
	$adults_count      = isset( $_POST['adults_count'] ) ? absint( wp_unslash( $_POST['adults_count'] ) ) : 1;
	$children_count    = isset( $_POST['children_count'] ) ? absint( wp_unslash( $_POST['children_count'] ) ) : 0;
	$retry_transaction = isset( $_POST['retry_transaction_code'] ) ? sanitize_text_field( wp_unslash( $_POST['retry_transaction_code'] ) ) : '';

	if ( $api_tour_id < 1 || '' === $request_id || '' === $travel_date ) {
		wp_send_json_error(
			array(
				'message' => __( 'Thieu thong tin de tao phien thanh toan.', 'travel-agency-modern' ),
			),
			400
		);
	}

	$response = tam_backend_api_request(
		'POST',
		'checkout/session',
		array(
			'auth_token' => $auth_token,
			'body'       => array(
				'tour_id'          => $api_tour_id,
				'request_id'       => $request_id,
				'travel_date'      => $travel_date,
				'contact_name'     => $contact_name,
				'contact_phone'    => $contact_phone,
				'contact_email'    => $contact_email,
				'contact_country'  => $contact_country,
				'special_requests' => $special_requests,
				'payment_method'   => tam_backend_api_map_payment_method( $payment_method ),
				'payment_plan'     => strtoupper( 'deposit' === strtolower( $payment_plan ) ? 'DEPOSIT' : 'FULL' ),
				'coupon_code'      => $coupon_code,
				'adults_count'     => max( 1, $adults_count ),
				'children_count'   => max( 0, $children_count ),
				'frontend_tour_id' => $wp_tour_id,
				'retry_transaction_code' => $retry_transaction,
			),
		)
	);

	if ( 401 === (int) $response['status'] ) {
		tam_backend_api_clear_session();
	}

	if ( ! $response['success'] ) {
		wp_send_json_error(
			array(
				'message' => tam_backend_api_get_error_message( $response, __( 'Khong the tao booking luc nay.', 'travel-agency-modern' ), 'checkout/session' ),
				'errors'  => $response['errors'],
				'code'    => 401 === (int) $response['status']
					? 'login_required'
					: ( tam_backend_api_is_missing_route_response( $response ) ? 'backend_route_missing' : 'checkout_failed' ),
			),
			$response['status'] ? $response['status'] : 400
		);
	}

	wp_send_json_success(
		array(
			'message'      => ! empty( $response['message'] ) ? $response['message'] : __( 'Da tao phien thanh toan.', 'travel-agency-modern' ),
			'redirectUrl'  => isset( $response['data']['redirectUrl'] ) ? esc_url_raw( $response['data']['redirectUrl'] ) : '',
			'booking'      => isset( $response['data']['booking'] ) ? $response['data']['booking'] : array(),
			'payment'      => isset( $response['data']['payment'] ) ? $response['data']['payment'] : array(),
			'transaction'  => isset( $response['data']['transaction'] ) ? $response['data']['transaction'] : array(),
			'pricing'      => isset( $response['data']['pricing'] ) ? $response['data']['pricing'] : array(),
		)
	);
}
add_action( 'wp_ajax_tam_checkout_create_session', 'tam_backend_api_handle_ajax_checkout_session_request' );
add_action( 'wp_ajax_nopriv_tam_checkout_create_session', 'tam_backend_api_handle_ajax_checkout_session_request' );

/**
 * Fetch every tour from the backend API.
 *
 * @return array
 */
function tam_backend_api_fetch_all_tours() {
	$page       = 1;
	$all_tours  = array();
	$max_loops  = 25;
	$loop_count = 0;

	while ( $loop_count < $max_loops ) {
		$response = tam_backend_api_request(
			'GET',
			'tours',
			array(
				'query' => array(
					'limit' => 100,
					'page'  => $page,
				),
				'timeout' => 20,
			)
		);

		if ( ! $response['success'] ) {
			return array(
				'success' => false,
				'message' => $response['message'] ? $response['message'] : 'Could not fetch tours from backend.',
				'tours'   => array(),
			);
		}

		$payload = isset( $response['data'] ) ? $response['data'] : array();
		$tours   = isset( $payload['tours'] ) && is_array( $payload['tours'] ) ? $payload['tours'] : array();

		$all_tours = array_merge( $all_tours, $tours );

		$has_next_page = ! empty( $payload['pagination']['hasNextPage'] );

		if ( ! $has_next_page ) {
			break;
		}

		++$page;
		++$loop_count;
	}

	return array(
		'success' => true,
		'message' => '',
		'tours'   => $all_tours,
	);
}

/**
 * Convert an API list into one item per line for the existing theme meta fields.
 *
 * @param array  $items       Source array.
 * @param string $value_field Object field to use.
 * @return string
 */
function tam_backend_api_join_lines( $items, $value_field = '' ) {
	$lines = array();

	if ( ! is_array( $items ) ) {
		return '';
	}

	foreach ( $items as $item ) {
		if ( is_string( $item ) ) {
			$item = trim( $item );
			if ( '' !== $item ) {
				$lines[] = $item;
			}
			continue;
		}

		if ( ! is_array( $item ) ) {
			continue;
		}

		$value = '';

		if ( $value_field && ! empty( $item[ $value_field ] ) ) {
			$value = (string) $item[ $value_field ];
		} elseif ( ! empty( $item['description'] ) ) {
			$value = (string) $item['description'];
		} elseif ( ! empty( $item['title'] ) ) {
			$value = (string) $item['title'];
		}

		$value = trim( $value );

		if ( '' !== $value ) {
			$lines[] = $value;
		}
	}

	return implode( "\n", $lines );
}

/**
 * Serialize departure date rows into the WordPress meta format used by the theme.
 *
 * @param array $items API departure date rows.
 * @return string
 */
function tam_backend_api_format_departure_dates( $items ) {
	$rows = array();

	foreach ( is_array( $items ) ? $items : array() as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$value = isset( $item['value'] ) ? trim( (string) $item['value'] ) : '';
		$label = isset( $item['label'] ) ? trim( (string) $item['label'] ) : '';

		if ( '' === $value ) {
			continue;
		}

		$rows[] = implode(
			'|',
			array(
				$value,
				$label ? $label : $value,
			)
		);
	}

	return implode( "\n", $rows );
}

/**
 * Convert API itinerary rows to the theme textarea format.
 *
 * @param array $items API itinerary rows.
 * @return string
 */
function tam_backend_api_format_itinerary( $items ) {
	$rows = array();

	if ( ! is_array( $items ) ) {
		return '';
	}

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$label       = isset( $item['label'] ) ? trim( (string) $item['label'] ) : '';
		$title       = isset( $item['title'] ) ? trim( (string) $item['title'] ) : '';
		$description = isset( $item['description'] ) ? trim( (string) $item['description'] ) : '';

		if ( '' === $description ) {
			continue;
		}

		$rows[] = implode(
			'|',
			array(
				$label ? $label : 'Day',
				$title ? $title : 'Itinerary',
				$description,
			)
		);
	}

	return implode( "\n", $rows );
}

/**
 * Build a richer WordPress post body from the backend payload.
 *
 * @param array $tour API tour payload.
 * @return string
 */
function tam_backend_api_build_tour_content( $tour ) {
	return '';

	$parts = array();

	if ( ! empty( $tour['description'] ) ) {
		$parts[] = wpautop( wp_kses_post( $tour['description'] ) );
	}

	if ( ! empty( $tour['overviewCards'] ) && is_array( $tour['overviewCards'] ) ) {
		$parts[] = '<h2>Tong quan hanh trinh</h2>';

		foreach ( $tour['overviewCards'] as $card ) {
			if ( ! is_array( $card ) ) {
				continue;
			}

			$title       = ! empty( $card['title'] ) ? esc_html( $card['title'] ) : '';
			$description = ! empty( $card['description'] ) ? esc_html( $card['description'] ) : '';

			if ( $title ) {
				$parts[] = '<h3>' . $title . '</h3>';
			}

			if ( $description ) {
				$parts[] = '<p>' . $description . '</p>';
			}
		}
	}

	if ( ! empty( $tour['promiseItems'] ) && is_array( $tour['promiseItems'] ) ) {
		$items = array();

		foreach ( $tour['promiseItems'] as $item ) {
			$item = is_string( $item ) ? trim( $item ) : '';

			if ( '' !== $item ) {
				$items[] = '<li>' . esc_html( $item ) . '</li>';
			}
		}

		if ( ! empty( $items ) ) {
			$parts[] = '<h2>Gia tri ban nhan duoc</h2><ul>' . implode( '', $items ) . '</ul>';
		}
	}

	if ( ! empty( $tour['curatorNote'] ) ) {
		$parts[] = '<blockquote><p>' . esc_html( $tour['curatorNote'] ) . '</p></blockquote>';
	}

	return implode( "\n\n", array_filter( $parts ) );
}

/**
 * Upsert a WordPress tour post from an API tour payload.
 *
 * @param array $tour API tour payload.
 * @return int|WP_Error
 */
function tam_backend_api_upsert_tour_post( $tour ) {
	$api_tour_id = isset( $tour['id'] ) ? absint( $tour['id'] ) : 0;

	if ( $api_tour_id < 1 || empty( $tour['title'] ) ) {
		return new WP_Error( 'tam_api_invalid_tour', 'Invalid tour payload.' );
	}

	$existing_posts = get_posts(
		array(
			'post_type'      => 'tour',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_tam_api_tour_id',
			'meta_value'     => $api_tour_id,
			'no_found_rows'  => true,
		)
	);

	$post_id    = ! empty( $existing_posts ) ? (int) $existing_posts[0] : 0;
	$post_array = array(
		'post_type'    => 'tour',
		'post_status'  => ! empty( $tour['status'] ) && 'Active' === $tour['status'] ? 'publish' : 'draft',
		'post_title'   => sanitize_text_field( $tour['title'] ),
		'post_excerpt' => wp_trim_words( wp_strip_all_tags( (string) $tour['description'] ), 28 ),
		'post_content' => tam_backend_api_build_tour_content( $tour ),
	);

	if ( $post_id > 0 ) {
		$post_array['ID'] = $post_id;
		$result           = wp_update_post( wp_slash( $post_array ), true );
	} else {
		$result  = wp_insert_post( wp_slash( $post_array ), true );
		$post_id = ! is_wp_error( $result ) ? (int) $result : 0;
	}

	if ( is_wp_error( $result ) || $post_id < 1 ) {
		return is_wp_error( $result ) ? $result : new WP_Error( 'tam_api_post_insert_failed', 'Could not create tour post.' );
	}

	$group_size = ! empty( $tour['maxPeople'] ) ? 'Toi da ' . absint( $tour['maxPeople'] ) . ' khach' : '';
	$departure  = '';

	if ( ! empty( $tour['meetingPoint'] ) ) {
		$departure = (string) $tour['meetingPoint'];
	} elseif ( ! empty( $tour['departureNote'] ) ) {
		$departure = (string) $tour['departureNote'];
	} elseif ( ! empty( $tour['departureSchedule'] ) ) {
		$departure = (string) $tour['departureSchedule'];
	}

	update_post_meta( $post_id, '_tam_api_tour_id', $api_tour_id );
	update_post_meta( $post_id, '_tam_api_tour_status', isset( $tour['status'] ) ? (string) $tour['status'] : '' );
	$primary_image = isset( $tour['imageUrl'] ) ? (string) $tour['imageUrl'] : '';

	if ( '' === $primary_image && ! empty( $tour['galleryImages'] ) && is_array( $tour['galleryImages'] ) ) {
		$primary_image = (string) reset( $tour['galleryImages'] );
	}

	update_post_meta( $post_id, '_tam_api_image_url', $primary_image );
	update_post_meta( $post_id, '_tam_api_gallery_images', $primary_image ? array( $primary_image ) : array() );
	update_post_meta( $post_id, '_tam_tour_duration', isset( $tour['durationText'] ) ? (string) $tour['durationText'] : '' );
	update_post_meta( $post_id, '_tam_tour_departure', $departure );
	update_post_meta( $post_id, '_tam_tour_price_from', isset( $tour['price'] ) ? (string) absint( $tour['price'] ) : '' );
	update_post_meta( $post_id, '_tam_tour_group_size', $group_size );
	update_post_meta( $post_id, '_tam_tour_rating', isset( $tour['rating'] ) ? (string) $tour['rating'] : '' );
	update_post_meta( $post_id, '_tam_tour_review_count', isset( $tour['reviews'] ) ? (string) absint( $tour['reviews'] ) : '0' );
	update_post_meta( $post_id, '_tam_tour_season', isset( $tour['season'] ) ? (string) $tour['season'] : '' );
	update_post_meta( $post_id, '_tam_tour_transport', isset( $tour['transport'] ) ? (string) $tour['transport'] : '' );
	update_post_meta( $post_id, '_tam_tour_departure_dates', tam_backend_api_format_departure_dates( isset( $tour['departureDates'] ) ? $tour['departureDates'] : array() ) );
	update_post_meta( $post_id, '_tam_tour_highlights', tam_backend_api_join_lines( isset( $tour['highlights'] ) ? $tour['highlights'] : array(), 'description' ) );
	update_post_meta( $post_id, '_tam_tour_itinerary', tam_backend_api_format_itinerary( isset( $tour['itinerary'] ) ? $tour['itinerary'] : array() ) );
	update_post_meta( $post_id, '_tam_tour_includes', tam_backend_api_join_lines( isset( $tour['includes'] ) ? $tour['includes'] : array() ) );
	update_post_meta( $post_id, '_tam_tour_excludes', tam_backend_api_join_lines( isset( $tour['excludes'] ) ? $tour['excludes'] : array() ) );
	// Sync m&#244; t&#7843; &#273;&#7847;y &#273;&#7911; v&#224;o WP meta &#8212; d&#249;ng cho Ph&#432;&#417;ng &#225;n C (&#432;u ti&#234;n backend description).
	update_post_meta( $post_id, '_tam_api_description', isset( $tour['description'] ) ? (string) $tour['description'] : '' );
	update_post_meta( $post_id, '_tam_api_average_rating', isset( $tour['rating'] ) ? (string) $tour['rating'] : '' );
	update_post_meta( $post_id, '_tam_api_total_reviews', isset( $tour['reviews'] ) ? absint( $tour['reviews'] ) : 0 );
	update_post_meta( $post_id, '_tam_tour_review_snippets', '' );
	update_post_meta( $post_id, '_tam_tour_featured', ! empty( $tour['featured'] ) ? '1' : '' );

	if ( ! empty( $tour['location'] ) ) {
		wp_set_object_terms( $post_id, sanitize_text_field( $tour['location'] ), 'tour_destination', false );
	}

	return $post_id;
}

/**
 * Synchronize backend tours into the WordPress custom post type.
 *
 * @return array
 */
function tam_backend_api_sync_tours() {
	$result = tam_backend_api_fetch_all_tours();

	if ( ! $result['success'] ) {
		return array(
			'success' => false,
			'message' => $result['message'] ? $result['message'] : 'Could not fetch tours for sync.',
			'count'   => 0,
		);
	}

	$synced_count = 0;
	$errors       = array();

	foreach ( $result['tours'] as $tour ) {
		$post_id = tam_backend_api_upsert_tour_post( $tour );

		if ( is_wp_error( $post_id ) ) {
			$errors[] = $tour['title'] . ': ' . $post_id->get_error_message();
			continue;
		}

		++$synced_count;
	}

	return array(
		'success' => empty( $errors ),
		'message' => empty( $errors ) ? 'Sync completed successfully.' : implode( ' | ', $errors ),
		'count'   => $synced_count,
	);
}

/**
 * Read backend .env values so wp-admin can inspect the checkout database directly.
 *
 * @return array
 */
function tam_backend_api_get_env_values() {
	static $values = null;

	if ( is_array( $values ) ) {
		return $values;
	}

	$values   = array();
	$env_path = trailingslashit( ABSPATH ) . 'backend-api/.env';

	if ( ! is_readable( $env_path ) ) {
		return $values;
	}

	$lines = file( $env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

	if ( ! is_array( $lines ) ) {
		return $values;
	}

	foreach ( $lines as $line ) {
		$line = trim( (string) $line );

		if ( '' === $line || '#' === substr( $line, 0, 1 ) || false === strpos( $line, '=' ) ) {
			continue;
		}

		list( $name, $raw_value ) = array_map( 'trim', explode( '=', $line, 2 ) );
		$name                     = preg_replace( '/[^A-Z0-9_]/i', '', (string) $name );

		if ( '' === $name ) {
			continue;
		}

		$raw_value = trim( (string) $raw_value, "\"'" );
		$values[ $name ] = $raw_value;
	}

	return $values;
}

/**
 * Return the backend checkout database name.
 *
 * @return string
 */
function tam_backend_api_get_storage_database_name() {
	$env_values = tam_backend_api_get_env_values();
	$name       = isset( $env_values['DB_NAME'] ) ? (string) $env_values['DB_NAME'] : '';

	$name = preg_replace( '/[^A-Za-z0-9_$]/', '', $name );

	return (string) apply_filters( 'tam_backend_api_storage_database_name', $name );
}

/**
 * Build a fully qualified backend table name for cross-database queries.
 *
 * @param string $table_name Raw table name.
 * @return string
 */
function tam_backend_api_get_storage_table_name( $table_name ) {
	$database   = tam_backend_api_get_storage_database_name();
	$table_name = preg_replace( '/[^A-Za-z0-9_$]/', '', (string) $table_name );

	if ( '' === $database || '' === $table_name ) {
		return '';
	}

	return sprintf( '`%s`.`%s`', $database, $table_name );
}

/**
 * Ensure WordPress can read the backend checkout database.
 *
 * @return true|WP_Error
 */
function tam_backend_api_get_storage_access_state() {
	static $state = null;

	if ( null !== $state ) {
		return $state;
	}

	global $wpdb;

	$bookings_table = tam_backend_api_get_storage_table_name( 'bookings' );

	if ( '' === $bookings_table ) {
		$state = new WP_Error(
			'tam_backend_db_missing',
			__( 'Khong tim thay cau hinh DB cua backend-api. Hay kiem tra file backend-api/.env.', 'travel-agency-modern' )
		);

		return $state;
	}

	$wpdb->get_var( "SELECT 1 FROM {$bookings_table} LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	if ( ! empty( $wpdb->last_error ) ) {
		$state = new WP_Error(
			'tam_backend_db_unreachable',
			sprintf(
				/* translators: %s: MySQL error from WordPress connection. */
				__( 'WordPress khong doc duoc DB checkout: %s', 'travel-agency-modern' ),
				$wpdb->last_error
			)
		);

		$wpdb->last_error = '';

		return $state;
	}

	$state = true;

	return $state;
}

/**
 * Format backend checkout amounts inside wp-admin.
 *
 * @param float|int|string $amount Raw amount.
 * @return string
 */
function tam_backend_api_format_admin_amount( $amount ) {
	$value = (int) round( (float) $amount );

	if ( function_exists( 'tam_format_tour_price' ) ) {
		return tam_format_tour_price( (string) $value );
	}

	return number_format_i18n( $value ) . 'đ';
}

/**
 * Translate backend statuses for wp-admin.
 *
 * @param string $type   booking|payment|transaction.
 * @param string $status Raw status value.
 * @return string
 */
function tam_backend_api_get_admin_status_label( $type, $status ) {
	$status = strtoupper( trim( (string) $status ) );
	$map    = array();

	if ( 'booking' === $type ) {
		$map = array(
			'PENDING'         => __( 'Cho xac nhan', 'travel-agency-modern' ),
			'PENDING_PAYMENT' => __( 'Cho thanh toan', 'travel-agency-modern' ),
			'PAID'            => __( 'Da thanh toan', 'travel-agency-modern' ),
			'PENDING_CONFIRMATION' => __( 'Cho nhan vien xac nhan', 'travel-agency-modern' ),
			'CONFIRMED'       => __( 'Da xac nhan', 'travel-agency-modern' ),
			'COMPLETED'       => __( 'Da hoan thanh', 'travel-agency-modern' ),
			'CANCELLED'       => __( 'Da huy', 'travel-agency-modern' ),
			'PAYMENT_FAILED'  => __( 'Thanh toan that bai', 'travel-agency-modern' ),
			'REFUNDED'        => __( 'Da hoan tien', 'travel-agency-modern' ),
		);
	} else {
		$map = array(
			'PENDING'        => __( 'Dang cho xu ly', 'travel-agency-modern' ),
			'PAID'           => __( 'Da thanh toan', 'travel-agency-modern' ),
			'PARTIALLY_PAID' => __( 'Da dat coc', 'travel-agency-modern' ),
			'SUCCESS'        => __( 'Da thanh toan', 'travel-agency-modern' ),
			'FAILED'         => __( 'That bai', 'travel-agency-modern' ),
			'CANCELLED'      => __( 'Da huy', 'travel-agency-modern' ),
			'EXPIRED'        => __( 'Het han', 'travel-agency-modern' ),
			'REFUNDED'       => __( 'Da hoan tien', 'travel-agency-modern' ),
		);
	}

	return isset( $map[ $status ] ) ? $map[ $status ] : $status;
}

/**
 * Render a colored status badge for wp-admin.
 *
 * @param string $type   booking|payment|transaction.
 * @param string $status Raw status value.
 * @return string
 */
function tam_backend_api_get_admin_status_badge( $type, $status ) {
	$status      = strtoupper( trim( (string) $status ) );
	$label       = tam_backend_api_get_admin_status_label( $type, $status );
	$class_parts = array( 'tam-backend-bookings__badge' );

	if ( in_array( $status, array( 'SUCCESS', 'PAID', 'CONFIRMED', 'COMPLETED' ), true ) ) {
		$class_parts[] = 'is-success';
	} elseif ( in_array( $status, array( 'FAILED', 'PAYMENT_FAILED', 'CANCELLED', 'EXPIRED', 'REFUNDED' ), true ) ) {
		$class_parts[] = 'is-danger';
	} else {
		$class_parts[] = 'is-pending';
	}

	return sprintf(
		'<span class="%1$s">%2$s</span>',
		esc_attr( implode( ' ', $class_parts ) ),
		esc_html( $label )
	);
}

/**
 * Translate a payment plan code into human-friendly text.
 *
 * @param string $plan Raw payment plan.
 * @return string
 */
function tam_backend_api_get_payment_plan_label( $plan ) {
	$plan = strtoupper( trim( (string) $plan ) );

	return 'DEPOSIT' === $plan
		? __( 'Dat coc truoc', 'travel-agency-modern' )
		: __( 'Thanh toan toan bo', 'travel-agency-modern' );
}

/**
 * Return the current admin actor label for audit trails.
 *
 * @return string
 */
function tam_backend_api_get_admin_actor_label() {
	$user = wp_get_current_user();

	if ( ! $user || ! $user->exists() ) {
		return 'WordPress admin';
	}

	$name  = trim( (string) $user->display_name );
	$email = trim( (string) $user->user_email );

	if ( '' !== $name && '' !== $email ) {
		return $name . ' <' . $email . '>';
	}

	return '' !== $name ? $name : ( '' !== $email ? $email : 'WordPress admin' );
}

/**
 * Ensure the backend checkout audit table exists for admin actions.
 *
 * @return true|WP_Error
 */
function tam_backend_api_ensure_admin_audit_table() {
	global $wpdb;

	$table_name = tam_backend_api_get_storage_table_name( 'booking_audit_logs' );

	if ( '' === $table_name ) {
		return new WP_Error( 'tam_backend_audit_table_missing', __( 'Khong tim thay bang booking_audit_logs trong DB backend.', 'travel-agency-modern' ) );
	}

	$create_sql = "
		CREATE TABLE IF NOT EXISTS {$table_name} (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			booking_id INT UNSIGNED NOT NULL,
			action VARCHAR(100) NOT NULL,
			actor_type VARCHAR(50) NOT NULL,
			actor_id VARCHAR(191) NULL,
			actor_name VARCHAR(191) NULL,
			note TEXT NULL,
			payload_json LONGTEXT NULL,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY booking_id (booking_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	";

	$wpdb->query( $create_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	if ( ! empty( $wpdb->last_error ) ) {
		$error = new WP_Error( 'tam_backend_audit_table_failed', $wpdb->last_error );
		$wpdb->last_error = '';

		return $error;
	}

	return true;
}

/**
 * Write an admin booking audit log row into the backend DB.
 *
 * @param int    $booking_id Booking ID.
 * @param string $action     Audit action name.
 * @param array  $payload    Optional payload.
 * @return true|WP_Error
 */
function tam_backend_api_insert_admin_booking_audit_log( $booking_id, $action, $payload = array() ) {
	global $wpdb;

	$state = tam_backend_api_ensure_admin_audit_table();

	if ( is_wp_error( $state ) ) {
		return $state;
	}

	$table_name = tam_backend_api_get_storage_table_name( 'booking_audit_logs' );
	$user       = wp_get_current_user();
	$result     = $wpdb->insert(
		$table_name,
		array(
			'booking_id'    => (int) $booking_id,
			'action'        => sanitize_key( $action ),
			'actor_type'    => 'wordpress_admin',
			'actor_id'      => $user && $user->exists() ? (string) $user->ID : '',
			'actor_name'    => tam_backend_api_get_admin_actor_label(),
			'note'          => isset( $payload['note'] ) ? sanitize_text_field( (string) $payload['note'] ) : '',
			'payload_json'  => wp_json_encode( $payload ),
			'created_at'    => current_time( 'mysql' ),
		),
		array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);

	if ( false === $result ) {
		$error = new WP_Error( 'tam_backend_audit_insert_failed', $wpdb->last_error ? $wpdb->last_error : __( 'Khong the ghi audit log cho booking.', 'travel-agency-modern' ) );
		$wpdb->last_error = '';

		return $error;
	}

	return true;
}

/**
 * Send the official booking confirmation email after manual admin approval.
 *
 * @param array $detail_payload Booking detail payload.
 * @return bool
 */
function tam_backend_api_send_manual_confirmation_email( $detail_payload ) {
	if ( empty( $detail_payload['booking'] ) || ! is_array( $detail_payload['booking'] ) ) {
		return false;
	}

	$booking       = $detail_payload['booking'];
	$payments      = ! empty( $detail_payload['payments'] ) && is_array( $detail_payload['payments'] ) ? $detail_payload['payments'] : array();
	$transactions  = ! empty( $detail_payload['transactions'] ) && is_array( $detail_payload['transactions'] ) ? $detail_payload['transactions'] : array();
	$contact_email = ! empty( $booking['contact_email'] ) ? sanitize_email( $booking['contact_email'] ) : '';

	if ( '' === $contact_email ) {
		return false;
	}

	$site_name         = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$contact_name      = ! empty( $booking['contact_name'] ) ? (string) $booking['contact_name'] : ( ! empty( $booking['user_name'] ) ? (string) $booking['user_name'] : '' );
	$latest_payment    = ! empty( $payments[0] ) ? $payments[0] : array();
	$latest_transaction = ! empty( $transactions[0] ) ? $transactions[0] : array();
	$transaction_code  = ! empty( $latest_transaction['transaction_code'] ) ? (string) $latest_transaction['transaction_code'] : '';
	$invoice_url       = $transaction_code ? tam_backend_api_get_checkout_invoice_url( $transaction_code ) : '';
	$subject           = sprintf(
		'[%1$s] Booking %2$s da duoc xac nhan',
		$site_name,
		tam_backend_api_build_booking_ref( isset( $booking['id'] ) ? (int) $booking['id'] : 0 )
	);
	$body              = implode(
		"\n",
		array_filter(
			array(
				'Xin chao ' . $contact_name . ',',
				'',
				'Booking tour cua ban da duoc ADN Travel xac nhan chinh thuc.',
				'Ma booking: ' . tam_backend_api_build_booking_ref( isset( $booking['id'] ) ? (int) $booking['id'] : 0 ),
				'Tour: ' . ( isset( $booking['tour_title'] ) ? (string) $booking['tour_title'] : '' ),
				'Ngay khoi hanh: ' . tam_backend_api_format_date( isset( $booking['travel_date'] ) ? $booking['travel_date'] : '' ),
				'So khach: ' . ( isset( $booking['number_of_people'] ) ? (int) $booking['number_of_people'] : 0 ),
				'Hinh thuc thanh toan: ' . tam_backend_api_get_payment_plan_label( isset( $booking['payment_plan'] ) ? $booking['payment_plan'] : '' ),
				'Da thanh toan: ' . tam_backend_api_format_admin_amount( isset( $booking['paid_amount'] ) ? $booking['paid_amount'] : 0 ),
				'Con lai: ' . tam_backend_api_format_admin_amount( isset( $booking['remaining_amount'] ) ? $booking['remaining_amount'] : 0 ),
				'Phuong thuc thanh toan: ' . tam_backend_api_get_payment_method_label( isset( $latest_payment['method'] ) ? $latest_payment['method'] : '' ),
				$invoice_url ? 'Invoice: ' . $invoice_url : '',
				'',
				'Ve/QR va hoa don se co san trong trang tai khoan cua ban ngay sau khi dang nhap.',
				'Chi tiet lich trinh cuoi cung va thong tin huong dan vien se duoc doi ngu van hanh gui bo sung neu tour co cap nhat rieng.',
				'Tai khoan: ' . tam_backend_api_get_account_url(),
			)
		)
	);

	return (bool) wp_mail(
		$contact_email,
		$subject,
		$body,
		array( 'Content-Type: text/plain; charset=UTF-8' )
	);
}

/**
 * Return aggregate stats for the admin bookings dashboard.
 *
 * @return array|WP_Error
 */
function tam_backend_api_get_admin_booking_stats() {
	global $wpdb;

	$state = tam_backend_api_get_storage_access_state();

	if ( is_wp_error( $state ) ) {
		return $state;
	}

	$bookings_table = tam_backend_api_get_storage_table_name( 'bookings' );
	$payments_table = tam_backend_api_get_storage_table_name( 'payments' );

	$query = "
		SELECT
			COUNT(*) AS total_bookings,
			SUM(CASE WHEN COALESCE(NULLIF(b.booking_status, ''), b.status) IN ('PENDING', 'PENDING_PAYMENT', 'PENDING_CONFIRMATION', 'PAYMENT_FAILED') THEN 1 ELSE 0 END) AS pending_bookings,
			SUM(CASE WHEN COALESCE(NULLIF(b.booking_status, ''), b.status) IN ('CONFIRMED', 'COMPLETED') THEN 1 ELSE 0 END) AS confirmed_bookings,
			SUM(CASE WHEN p.status = 'SUCCESS' THEN p.amount ELSE 0 END) AS successful_revenue
		FROM {$bookings_table} b
		LEFT JOIN {$payments_table} p
			ON p.id = (
				SELECT p2.id
				FROM {$payments_table} p2
				WHERE p2.booking_id = b.id
				ORDER BY p2.id DESC
				LIMIT 1
			)
	";

	$row = $wpdb->get_row( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	if ( ! empty( $wpdb->last_error ) ) {
		$error = new WP_Error( 'tam_backend_db_query_failed', $wpdb->last_error );
		$wpdb->last_error = '';

		return $error;
	}

	return array(
		'total_bookings'    => isset( $row['total_bookings'] ) ? (int) $row['total_bookings'] : 0,
		'pending_bookings'  => isset( $row['pending_bookings'] ) ? (int) $row['pending_bookings'] : 0,
		'confirmed_bookings'=> isset( $row['confirmed_bookings'] ) ? (int) $row['confirmed_bookings'] : 0,
		'successful_revenue'=> isset( $row['successful_revenue'] ) ? (float) $row['successful_revenue'] : 0,
	);
}

/**
 * Fetch paginated booking rows from the backend checkout database.
 *
 * @param array $args Query args.
 * @return array|WP_Error
 */
function tam_backend_api_get_admin_bookings( $args = array() ) {
	global $wpdb;

	$state = tam_backend_api_get_storage_access_state();

	if ( is_wp_error( $state ) ) {
		return $state;
	}

	$args = wp_parse_args(
		$args,
		array(
			'paged'          => 1,
			'per_page'       => 20,
			'search'         => '',
			'status'         => '',
			'payment_status' => '',
		)
	);

	$paged    = max( 1, (int) $args['paged'] );
	$per_page = max( 1, min( 50, (int) $args['per_page'] ) );
	$offset   = ( $paged - 1 ) * $per_page;
	$search   = trim( (string) $args['search'] );
	$status   = strtoupper( trim( (string) $args['status'] ) );
	$payment_status = strtoupper( trim( (string) $args['payment_status'] ) );

	$bookings_table     = tam_backend_api_get_storage_table_name( 'bookings' );
	$users_table        = tam_backend_api_get_storage_table_name( 'users' );
	$tours_table        = tam_backend_api_get_storage_table_name( 'tours' );
	$details_table      = tam_backend_api_get_storage_table_name( 'booking_details' );
	$payments_table     = tam_backend_api_get_storage_table_name( 'payments' );
	$transactions_table = tam_backend_api_get_storage_table_name( 'payment_transactions' );

	$where_sql = array();
	$params    = array();

	if ( '' !== $status ) {
		$where_sql[] = "COALESCE(NULLIF(b.booking_status, ''), b.status) = %s";
		$params[]    = $status;
	}

	if ( '' !== $payment_status ) {
		$where_sql[] = "COALESCE(NULLIF(b.payment_status, ''), COALESCE(p.status, '')) = %s";
		$params[]    = $payment_status;
	}

	if ( '' !== $search ) {
		$like = '%' . $wpdb->esc_like( $search ) . '%';

		$where_sql[] = '(CAST(b.id AS CHAR) LIKE %s OR CAST(b.tour_id AS CHAR) LIKE %s OR COALESCE(u.name, \'\') LIKE %s OR COALESCE(u.email, \'\') LIKE %s OR COALESCE(bd.contact_name, \'\') LIKE %s OR COALESCE(bd.contact_email, \'\') LIKE %s OR COALESCE(t.title, \'\') LIKE %s OR COALESCE(pt.transaction_code, \'\') LIKE %s)';
		$params      = array_merge(
			$params,
			array_fill( 0, 8, $like )
		);
	}

	$where_clause = empty( $where_sql ) ? '' : 'WHERE ' . implode( ' AND ', $where_sql );

	$count_query = "
		SELECT COUNT(*)
		FROM {$bookings_table} b
		LEFT JOIN {$users_table} u ON u.id = b.user_id
		LEFT JOIN {$tours_table} t ON t.id = b.tour_id
		LEFT JOIN {$details_table} bd ON bd.booking_id = b.id
		LEFT JOIN {$payments_table} p
			ON p.id = (
				SELECT p2.id
				FROM {$payments_table} p2
				WHERE p2.booking_id = b.id
				ORDER BY p2.id DESC
				LIMIT 1
			)
		LEFT JOIN {$transactions_table} pt
			ON pt.id = (
				SELECT pt2.id
				FROM {$transactions_table} pt2
				WHERE pt2.booking_id = b.id
				ORDER BY pt2.id DESC
				LIMIT 1
			)
		{$where_clause}
	";

	$total_items = (int) $wpdb->get_var(
		empty( $params ) ? $count_query : $wpdb->prepare( $count_query, $params ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	);

	if ( ! empty( $wpdb->last_error ) ) {
		$error = new WP_Error( 'tam_backend_bookings_count_failed', $wpdb->last_error );
		$wpdb->last_error = '';

		return $error;
	}

	$list_query = "
		SELECT
			b.id,
			b.user_id,
			b.tour_id,
			b.travel_date,
			b.number_of_people,
			b.total_price,
			b.status,
			COALESCE(NULLIF(b.booking_status, ''), b.status) AS booking_status,
			COALESCE(NULLIF(b.payment_status, ''), COALESCE(p.status, '')) AS booking_payment_status,
			COALESCE(NULLIF(b.payment_plan, ''), COALESCE(p.payment_plan, 'FULL')) AS payment_plan,
			b.paid_amount,
			b.remaining_amount,
			b.confirmed_by,
			b.confirmed_at,
			b.created_at,
			u.name AS user_name,
			u.email AS user_email,
			u.phone AS user_phone,
			t.title AS tour_title,
			t.location AS tour_location,
			bd.contact_name,
			bd.contact_phone,
			bd.contact_email,
			bd.contact_country,
			bd.adults_count,
			bd.children_count,
			p.id AS payment_id,
			p.method AS payment_method,
			p.status AS payment_status,
			p.amount AS payment_amount,
			p.paid_amount AS payment_paid_amount,
			p.remaining_amount AS payment_remaining_amount,
			p.paid_at,
			pt.transaction_code,
			pt.provider AS transaction_provider,
			pt.status AS transaction_status,
			pt.total_amount AS transaction_total,
			pt.coupon_code,
			pt.completed_at AS transaction_completed_at
		FROM {$bookings_table} b
		LEFT JOIN {$users_table} u ON u.id = b.user_id
		LEFT JOIN {$tours_table} t ON t.id = b.tour_id
		LEFT JOIN {$details_table} bd ON bd.booking_id = b.id
		LEFT JOIN {$payments_table} p
			ON p.id = (
				SELECT p2.id
				FROM {$payments_table} p2
				WHERE p2.booking_id = b.id
				ORDER BY p2.id DESC
				LIMIT 1
			)
		LEFT JOIN {$transactions_table} pt
			ON pt.id = (
				SELECT pt2.id
				FROM {$transactions_table} pt2
				WHERE pt2.booking_id = b.id
				ORDER BY pt2.id DESC
				LIMIT 1
			)
		{$where_clause}
		ORDER BY b.created_at DESC
		LIMIT %d OFFSET %d
	";

	$query_params = array_merge( $params, array( $per_page, $offset ) );
	$items        = $wpdb->get_results( $wpdb->prepare( $list_query, $query_params ), ARRAY_A );

	if ( ! empty( $wpdb->last_error ) ) {
		$error = new WP_Error( 'tam_backend_bookings_query_failed', $wpdb->last_error );
		$wpdb->last_error = '';

		return $error;
	}

	return array(
		'items'       => is_array( $items ) ? $items : array(),
		'total_items' => $total_items,
		'total_pages' => max( 1, (int) ceil( $total_items / $per_page ) ),
		'paged'       => $paged,
		'per_page'    => $per_page,
	);
}

/**
 * Fetch one booking with related contact, payment and transaction summary.
 *
 * @param int $booking_id Booking ID.
 * @return array|WP_Error|null
 */
function tam_backend_api_get_admin_booking_detail( $booking_id ) {
	global $wpdb;

	$state = tam_backend_api_get_storage_access_state();

	if ( is_wp_error( $state ) ) {
		return $state;
	}

	$booking_id = (int) $booking_id;

	if ( $booking_id <= 0 ) {
		return null;
	}

	$bookings_table     = tam_backend_api_get_storage_table_name( 'bookings' );
	$users_table        = tam_backend_api_get_storage_table_name( 'users' );
	$tours_table        = tam_backend_api_get_storage_table_name( 'tours' );
	$details_table      = tam_backend_api_get_storage_table_name( 'booking_details' );
	$payments_table     = tam_backend_api_get_storage_table_name( 'payments' );
	$transactions_table = tam_backend_api_get_storage_table_name( 'payment_transactions' );
	$customers_table    = tam_backend_api_get_storage_table_name( 'booking_customers' );

	$detail_query = "
		SELECT
			b.*,
			u.name AS user_name,
			u.email AS user_email,
			u.phone AS user_phone,
			t.title AS tour_title,
			t.location AS tour_location,
			t.image_url AS tour_image_url,
			t.duration_text,
			t.meeting_point,
			t.transport,
			bd.contact_name,
			bd.contact_phone,
			bd.contact_email,
			bd.contact_country,
			bd.adults_count,
			bd.children_count,
			bd.special_requests
		FROM {$bookings_table} b
		LEFT JOIN {$users_table} u ON u.id = b.user_id
		LEFT JOIN {$tours_table} t ON t.id = b.tour_id
		LEFT JOIN {$details_table} bd ON bd.booking_id = b.id
		WHERE b.id = %d
		LIMIT 1
	";

	$detail = $wpdb->get_row( $wpdb->prepare( $detail_query, $booking_id ), ARRAY_A );

	if ( ! empty( $wpdb->last_error ) ) {
		$error = new WP_Error( 'tam_backend_booking_detail_failed', $wpdb->last_error );
		$wpdb->last_error = '';

		return $error;
	}

	if ( empty( $detail ) ) {
		return null;
	}

	$payments_query = "
		SELECT *
		FROM {$payments_table}
		WHERE booking_id = %d
		ORDER BY id DESC
	";

	$transactions_query = "
		SELECT
			pt.*,
			p.method AS payment_method,
			p.status AS payment_status
		FROM {$transactions_table} pt
		LEFT JOIN {$payments_table} p ON p.id = pt.payment_id
		WHERE pt.booking_id = %d
		ORDER BY pt.id DESC
	";

	$customers_query = "
		SELECT *
		FROM {$customers_table}
		WHERE booking_id = %d
		ORDER BY id ASC
	";

	$payments     = $wpdb->get_results( $wpdb->prepare( $payments_query, $booking_id ), ARRAY_A );
	$transactions = $wpdb->get_results( $wpdb->prepare( $transactions_query, $booking_id ), ARRAY_A );
	$customers    = $wpdb->get_results( $wpdb->prepare( $customers_query, $booking_id ), ARRAY_A );

	if ( ! empty( $wpdb->last_error ) ) {
		$error = new WP_Error( 'tam_backend_booking_related_failed', $wpdb->last_error );
		$wpdb->last_error = '';

		return $error;
	}

	return array(
		'booking'      => $detail,
		'payments'     => is_array( $payments ) ? $payments : array(),
		'transactions' => is_array( $transactions ) ? $transactions : array(),
		'customers'    => is_array( $customers ) ? $customers : array(),
	);
}

/**
 * Execute a manual admin action against a backend checkout booking.
 *
 * @param int    $booking_id   Booking ID.
 * @param string $action_type  confirm_booking|cancel_booking|refund_payment.
 * @return array|WP_Error
 */
function tam_backend_api_run_admin_booking_action( $booking_id, $action_type ) {
	global $wpdb;

	$state = tam_backend_api_get_storage_access_state();

	if ( is_wp_error( $state ) ) {
		return $state;
	}

	$booking_id   = (int) $booking_id;
	$action_type  = sanitize_key( $action_type );
	$bookings     = tam_backend_api_get_storage_table_name( 'bookings' );
	$payments     = tam_backend_api_get_storage_table_name( 'payments' );
	$transactions = tam_backend_api_get_storage_table_name( 'payment_transactions' );

	if ( $booking_id <= 0 ) {
		return new WP_Error( 'tam_backend_action_invalid_booking', __( 'Booking khong hop le.', 'travel-agency-modern' ) );
	}

	$row_query = "
		SELECT
			b.id,
			b.user_id,
			b.total_price,
			b.status,
			COALESCE(NULLIF(b.booking_status, ''), b.status) AS booking_status,
			COALESCE(NULLIF(b.payment_status, ''), COALESCE(p.status, '')) AS booking_payment_status,
			COALESCE(NULLIF(b.payment_plan, ''), COALESCE(p.payment_plan, 'FULL')) AS payment_plan,
			b.paid_amount,
			b.remaining_amount,
			p.id AS payment_id,
			p.status AS payment_record_status,
			p.method AS payment_method,
			p.amount AS payment_amount,
			p.paid_amount AS payment_paid_amount,
			p.remaining_amount AS payment_remaining_amount,
			pt.id AS transaction_id,
			pt.transaction_code
		FROM {$bookings} b
		LEFT JOIN {$payments} p
			ON p.id = (
				SELECT p2.id
				FROM {$payments} p2
				WHERE p2.booking_id = b.id
				ORDER BY p2.id DESC
				LIMIT 1
			)
		LEFT JOIN {$transactions} pt
			ON pt.id = (
				SELECT pt2.id
				FROM {$transactions} pt2
				WHERE pt2.booking_id = b.id
				ORDER BY pt2.id DESC
				LIMIT 1
			)
		WHERE b.id = %d
		LIMIT 1
	";

	$current = $wpdb->get_row( $wpdb->prepare( $row_query, $booking_id ), ARRAY_A );

	if ( ! empty( $wpdb->last_error ) ) {
		$error = new WP_Error( 'tam_backend_action_lookup_failed', $wpdb->last_error );
		$wpdb->last_error = '';

		return $error;
	}

	if ( empty( $current ) ) {
		return new WP_Error( 'tam_backend_action_missing_booking', __( 'Khong tim thay booking can xu ly.', 'travel-agency-modern' ) );
	}

	$booking_status = strtoupper( (string) $current['booking_status'] );
	$payment_status = strtoupper( (string) $current['booking_payment_status'] );
	$payment_plan   = strtoupper( (string) $current['payment_plan'] );
	$paid_amount    = (float) ( isset( $current['paid_amount'] ) ? $current['paid_amount'] : 0 );
	$actor_label    = tam_backend_api_get_admin_actor_label();
	$did_commit     = false;

	$wpdb->query( 'START TRANSACTION' );

	try {
		if ( 'confirm_booking' === $action_type ) {
			if ( in_array( $booking_status, array( 'CONFIRMED', 'COMPLETED' ), true ) ) {
				throw new Exception( __( 'Booking nay da duoc xac nhan truoc do.', 'travel-agency-modern' ) );
			}

			if ( ! in_array( $booking_status, array( 'PENDING', 'PENDING_CONFIRMATION', 'PAID' ), true ) ) {
				throw new Exception( __( 'Chi co the xac nhan booking dang cho xu ly sau thanh toan.', 'travel-agency-modern' ) );
			}

			if ( ! in_array( $payment_status, array( 'PAID', 'PARTIALLY_PAID', 'SUCCESS' ), true ) ) {
				throw new Exception( __( 'Booking nay chua co thanh toan hop le de xac nhan.', 'travel-agency-modern' ) );
			}

			$wpdb->update(
				$bookings,
				array(
					'status'         => 'CONFIRMED',
					'booking_status' => 'CONFIRMED',
					'confirmed_by'   => $actor_label,
					'confirmed_at'   => current_time( 'mysql' ),
				),
				array( 'id' => $booking_id ),
				array( '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			$audit = tam_backend_api_insert_admin_booking_audit_log(
				$booking_id,
				'BOOKING_CONFIRMED',
				array(
					'by'             => $actor_label,
					'bookingStatus'  => 'CONFIRMED',
					'paymentStatus'  => $payment_status,
					'paymentPlan'    => $payment_plan,
					'paidAmount'     => $paid_amount,
				)
			);

			if ( is_wp_error( $audit ) ) {
				throw new Exception( $audit->get_error_message() );
			}

			$wpdb->query( 'COMMIT' );
			$did_commit = true;

			$detail_payload = tam_backend_api_get_admin_booking_detail( $booking_id );
			$mail_sent      = is_array( $detail_payload ) ? tam_backend_api_send_manual_confirmation_email( $detail_payload ) : false;

			if ( $mail_sent ) {
				$wpdb->update(
					$bookings,
					array( 'confirmation_sent_at' => current_time( 'mysql' ) ),
					array( 'id' => $booking_id ),
					array( '%s' ),
					array( '%d' )
				);
			}

			return array(
				'status'  => $mail_sent ? 'success' : 'warning',
				'message' => $mail_sent
					? __( 'Booking da duoc xac nhan va email chinh thuc da gui cho khach.', 'travel-agency-modern' )
					: __( 'Booking da duoc xac nhan, nhung email chinh thuc chua gui duoc.', 'travel-agency-modern' ),
			);
		}

		if ( 'cancel_booking' === $action_type ) {
			if ( in_array( $booking_status, array( 'CANCELLED', 'REFUNDED' ), true ) ) {
				throw new Exception( __( 'Booking nay da o trang thai huy/hoan tien.', 'travel-agency-modern' ) );
			}

			if ( 'COMPLETED' === $booking_status ) {
				throw new Exception( __( 'Khong the huy booking da hoan thanh.', 'travel-agency-modern' ) );
			}

			$wpdb->update(
				$bookings,
				array(
					'status'         => 'CANCELLED',
					'booking_status' => 'CANCELLED',
				),
				array( 'id' => $booking_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			$audit = tam_backend_api_insert_admin_booking_audit_log(
				$booking_id,
				'BOOKING_CANCELLED',
				array(
					'by'             => $actor_label,
					'previousStatus' => $booking_status,
					'paymentStatus'  => $payment_status,
				)
			);

			if ( is_wp_error( $audit ) ) {
				throw new Exception( $audit->get_error_message() );
			}

			$wpdb->query( 'COMMIT' );
			$did_commit = true;

			return array(
				'status'  => 'success',
				'message' => __( 'Booking da duoc chuyen sang trang thai da huy.', 'travel-agency-modern' ),
			);
		}

		if ( 'refund_payment' === $action_type ) {
			if ( ! in_array( $payment_status, array( 'PAID', 'PARTIALLY_PAID', 'SUCCESS' ), true ) ) {
				throw new Exception( __( 'Booking nay chua co thanh toan hop le de hoan tien.', 'travel-agency-modern' ) );
			}

			if ( empty( $current['payment_id'] ) ) {
				throw new Exception( __( 'Khong tim thay payment de xu ly hoan tien.', 'travel-agency-modern' ) );
			}

			$refund_amount = (float) ( ! empty( $current['payment_paid_amount'] ) ? $current['payment_paid_amount'] : ( ! empty( $current['payment_amount'] ) ? $current['payment_amount'] : $paid_amount ) );

			$wpdb->update(
				$payments,
				array(
					'status'        => 'REFUNDED',
					'refund_amount' => $refund_amount,
					'refunded_at'   => current_time( 'mysql' ),
				),
				array( 'id' => (int) $current['payment_id'] ),
				array( '%s', '%f', '%s' ),
				array( '%d' )
			);

			if ( ! empty( $current['transaction_id'] ) ) {
				$wpdb->update(
					$transactions,
					array(
						'status'       => 'REFUNDED',
						'completed_at' => current_time( 'mysql' ),
					),
					array( 'id' => (int) $current['transaction_id'] ),
					array( '%s', '%s' ),
					array( '%d' )
				);
			}

			$wpdb->update(
				$bookings,
				array(
					'status'         => 'REFUNDED',
					'booking_status' => 'REFUNDED',
					'payment_status' => 'REFUNDED',
				),
				array( 'id' => $booking_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);

			$audit = tam_backend_api_insert_admin_booking_audit_log(
				$booking_id,
				'PAYMENT_REFUNDED',
				array(
					'by'            => $actor_label,
					'refundAmount'  => $refund_amount,
					'paymentPlan'   => $payment_plan,
					'transaction'   => isset( $current['transaction_code'] ) ? $current['transaction_code'] : '',
				)
			);

			if ( is_wp_error( $audit ) ) {
				throw new Exception( $audit->get_error_message() );
			}

			$wpdb->query( 'COMMIT' );
			$did_commit = true;

			return array(
				'status'  => 'success',
				'message' => __( 'Da cap nhat trang thai hoan tien cho booking nay.', 'travel-agency-modern' ),
			);
		}

		throw new Exception( __( 'Hanh dong khong hop le.', 'travel-agency-modern' ) );
	} catch ( Exception $exception ) {
		if ( ! $did_commit ) {
			$wpdb->query( 'ROLLBACK' );
		}

		return new WP_Error( 'tam_backend_admin_action_failed', $exception->getMessage() );
	}
}

/**
 * Handle wp-admin booking actions from the Booking API page.
 *
 * @return void
 */
function tam_backend_api_handle_admin_booking_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'travel-agency-modern' ) );
	}

	check_admin_referer( 'tam_backend_booking_action', 'tam_backend_booking_nonce' );

	$booking_id   = isset( $_POST['booking_id'] ) ? absint( wp_unslash( $_POST['booking_id'] ) ) : 0;
	$action_type  = isset( $_POST['booking_admin_action'] ) ? sanitize_key( wp_unslash( $_POST['booking_admin_action'] ) ) : '';
	$redirect_to  = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : admin_url( 'tools.php?page=tam-backend-bookings' );
	$result       = tam_backend_api_run_admin_booking_action( $booking_id, $action_type );
	$status       = is_wp_error( $result ) ? 'error' : ( ! empty( $result['status'] ) ? $result['status'] : 'success' );
	$message      = is_wp_error( $result ) ? $result->get_error_message() : ( ! empty( $result['message'] ) ? $result['message'] : __( 'Da cap nhat booking.', 'travel-agency-modern' ) );
	$redirect_to  = add_query_arg(
		array(
			'booking_admin_status'  => $status,
			'booking_admin_message' => $message,
		),
		$redirect_to
	);

	wp_safe_redirect( $redirect_to );
	exit;
}
add_action( 'admin_post_tam_backend_booking_action', 'tam_backend_api_handle_admin_booking_action' );

/**
 * Render the wp-admin page that mirrors checkout bookings from backend DB.
 *
 * @return void
 */
function tam_backend_api_render_bookings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'travel-agency-modern' ) );
	}

	$base_url       = admin_url( 'tools.php?page=tam-backend-bookings' );
	$booking_id     = isset( $_GET['booking_id'] ) ? absint( wp_unslash( $_GET['booking_id'] ) ) : 0;
	$current_page   = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
	$search_term    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$status_filter  = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
	$payment_filter = isset( $_GET['payment_status'] ) ? sanitize_text_field( wp_unslash( $_GET['payment_status'] ) ) : '';
	$admin_notice_status  = isset( $_GET['booking_admin_status'] ) ? sanitize_key( wp_unslash( $_GET['booking_admin_status'] ) ) : '';
	$admin_notice_message = isset( $_GET['booking_admin_message'] ) ? sanitize_text_field( wp_unslash( $_GET['booking_admin_message'] ) ) : '';

	$storage_state = tam_backend_api_get_storage_access_state();
	$stats         = tam_backend_api_get_admin_booking_stats();
	$list_payload  = tam_backend_api_get_admin_bookings(
		array(
			'paged'          => $current_page,
			'search'         => $search_term,
			'status'         => $status_filter,
			'payment_status' => $payment_filter,
		)
	);
	$detail_payload = $booking_id ? tam_backend_api_get_admin_booking_detail( $booking_id ) : null;
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Booking API', 'travel-agency-modern' ); ?></h1>
		<p><?php esc_html_e( 'Trang nay doc truc tiep du lieu checkout tu DB backend-api de ban quan ly booking va thanh toan ngay trong wp-admin.', 'travel-agency-modern' ); ?></p>
		<p>
			<strong><?php esc_html_e( 'Backend API', 'travel-agency-modern' ); ?>:</strong>
			<?php echo esc_html( tam_backend_api_base_url() ); ?>
			<br />
			<strong><?php esc_html_e( 'Database', 'travel-agency-modern' ); ?>:</strong>
			<?php echo esc_html( tam_backend_api_get_storage_database_name() ); ?>
		</p>

		<style>
			.tam-backend-bookings__grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; margin:20px 0 24px; }
			.tam-backend-bookings__card { background:#fff; border:1px solid #dcdcde; border-radius:16px; padding:18px 20px; box-shadow:0 8px 24px rgba(15,23,42,.05); }
			.tam-backend-bookings__card h2, .tam-backend-bookings__card h3 { margin:0 0 8px; }
			.tam-backend-bookings__metric { font-size:28px; font-weight:700; line-height:1.1; }
			.tam-backend-bookings__muted { color:#646970; }
			.tam-backend-bookings__filters { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin:20px 0; }
			.tam-backend-bookings__filters .field { min-width:180px; }
			.tam-backend-bookings__filters label { display:block; margin-bottom:6px; font-weight:600; }
			.tam-backend-bookings__badge { display:inline-flex; align-items:center; justify-content:center; min-height:24px; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; line-height:1.2; }
			.tam-backend-bookings__badge.is-success { background:#e7f7ed; color:#137333; }
			.tam-backend-bookings__badge.is-danger { background:#fdebec; color:#b42318; }
			.tam-backend-bookings__badge.is-pending { background:#eef4ff; color:#1d4ed8; }
			.tam-backend-bookings__stack { display:grid; gap:16px; }
			.tam-backend-bookings__meta { margin:0; display:grid; gap:8px; }
			.tam-backend-bookings__meta li { display:flex; justify-content:space-between; gap:16px; padding:10px 0; border-bottom:1px solid #f0f0f1; }
			.tam-backend-bookings__meta li:last-child { border-bottom:0; }
			.tam-backend-bookings__table td strong { display:block; }
			.tam-backend-bookings__table td small { color:#646970; }
		</style>

		<?php if ( is_wp_error( $storage_state ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $storage_state->get_error_message() ); ?></p></div>
		<?php endif; ?>

		<?php if ( '' !== $admin_notice_status && '' !== $admin_notice_message ) : ?>
			<?php $notice_class = 'warning' === $admin_notice_status ? 'notice-warning' : ( 'error' === $admin_notice_status ? 'notice-error' : 'notice-success' ); ?>
			<div class="notice <?php echo esc_attr( $notice_class ); ?> is-dismissible"><p><?php echo esc_html( $admin_notice_message ); ?></p></div>
		<?php endif; ?>

		<?php if ( ! is_wp_error( $stats ) ) : ?>
			<div class="tam-backend-bookings__grid">
				<div class="tam-backend-bookings__card">
					<h2><?php esc_html_e( 'Tong booking', 'travel-agency-modern' ); ?></h2>
					<div class="tam-backend-bookings__metric"><?php echo esc_html( number_format_i18n( $stats['total_bookings'] ) ); ?></div>
				</div>
				<div class="tam-backend-bookings__card">
					<h2><?php esc_html_e( 'Cho xu ly', 'travel-agency-modern' ); ?></h2>
					<div class="tam-backend-bookings__metric"><?php echo esc_html( number_format_i18n( $stats['pending_bookings'] ) ); ?></div>
				</div>
				<div class="tam-backend-bookings__card">
					<h2><?php esc_html_e( 'Da xac nhan', 'travel-agency-modern' ); ?></h2>
					<div class="tam-backend-bookings__metric"><?php echo esc_html( number_format_i18n( $stats['confirmed_bookings'] ) ); ?></div>
				</div>
				<div class="tam-backend-bookings__card">
					<h2><?php esc_html_e( 'Doanh thu da thanh toan', 'travel-agency-modern' ); ?></h2>
					<div class="tam-backend-bookings__metric"><?php echo esc_html( tam_backend_api_format_admin_amount( $stats['successful_revenue'] ) ); ?></div>
				</div>
			</div>
		<?php endif; ?>

		<form method="get" class="tam-backend-bookings__filters">
			<input type="hidden" name="page" value="tam-backend-bookings" />
			<div class="field">
				<label for="tam-backend-search"><?php esc_html_e( 'Tim kiem', 'travel-agency-modern' ); ?></label>
				<input id="tam-backend-search" type="search" name="s" class="regular-text" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Booking, tour, email, transaction...', 'travel-agency-modern' ); ?>" />
			</div>
			<div class="field">
				<label for="tam-backend-status"><?php esc_html_e( 'Trang thai booking', 'travel-agency-modern' ); ?></label>
				<select id="tam-backend-status" name="status">
					<option value=""><?php esc_html_e( 'Tat ca', 'travel-agency-modern' ); ?></option>
					<?php foreach ( array( 'PENDING', 'PENDING_PAYMENT', 'PAYMENT_FAILED', 'PAID', 'PENDING_CONFIRMATION', 'CONFIRMED', 'COMPLETED', 'CANCELLED', 'REFUNDED' ) as $status_option ) : ?>
						<option value="<?php echo esc_attr( $status_option ); ?>" <?php selected( strtoupper( $status_filter ), $status_option ); ?>><?php echo esc_html( tam_backend_api_get_admin_status_label( 'booking', $status_option ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field">
				<label for="tam-backend-payment-status"><?php esc_html_e( 'Trang thai thanh toan', 'travel-agency-modern' ); ?></label>
				<select id="tam-backend-payment-status" name="payment_status">
					<option value=""><?php esc_html_e( 'Tat ca', 'travel-agency-modern' ); ?></option>
					<?php foreach ( array( 'PENDING', 'PAID', 'PARTIALLY_PAID', 'FAILED', 'REFUNDED', 'CANCELLED', 'EXPIRED' ) as $status_option ) : ?>
						<option value="<?php echo esc_attr( $status_option ); ?>" <?php selected( strtoupper( $payment_filter ), $status_option ); ?>><?php echo esc_html( tam_backend_api_get_admin_status_label( 'payment', $status_option ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Loc du lieu', 'travel-agency-modern' ); ?></button>
				<a href="<?php echo esc_url( $base_url ); ?>" class="button"><?php esc_html_e( 'Reset', 'travel-agency-modern' ); ?></a>
			</div>
		</form>

		<?php if ( $booking_id ) : ?>
			<p><a href="<?php echo esc_url( remove_query_arg( 'booking_id', $_SERVER['REQUEST_URI'] ) ); ?>">&larr; <?php esc_html_e( 'Quay lai danh sach booking', 'travel-agency-modern' ); ?></a></p>
			<?php if ( is_wp_error( $detail_payload ) ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( $detail_payload->get_error_message() ); ?></p></div>
			<?php elseif ( empty( $detail_payload['booking'] ) ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'Khong tim thay booking trong DB backend.', 'travel-agency-modern' ); ?></p></div>
			<?php else : ?>
				<?php
				$booking                = $detail_payload['booking'];
				$current_booking_status = ! empty( $booking['booking_status'] ) ? $booking['booking_status'] : $booking['status'];
				$current_payment_status = ! empty( $booking['payment_status'] ) ? $booking['payment_status'] : '';
				?>
				<div class="tam-backend-bookings__stack">
					<div class="tam-backend-bookings__card">
						<h2><?php echo esc_html( tam_backend_api_build_booking_ref( $booking['id'] ) ); ?></h2>
						<p>
							<?php echo wp_kses_post( tam_backend_api_get_admin_status_badge( 'booking', $current_booking_status ) ); ?>
							<span class="tam-backend-bookings__muted"><?php echo esc_html( tam_backend_api_format_datetime( $booking['created_at'] ) ); ?></span>
						</p>
						<ul class="tam-backend-bookings__meta">
							<li><strong><?php esc_html_e( 'Tour', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( $booking['tour_title'] ); ?></span></li>
							<li><strong><?php esc_html_e( 'Khoi hanh', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( tam_backend_api_format_date( $booking['travel_date'] ) ); ?></span></li>
							<li><strong><?php esc_html_e( 'So khach', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( (int) $booking['number_of_people'] ); ?></span></li>
							<li><strong><?php esc_html_e( 'Tong tien', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( tam_backend_api_format_admin_amount( $booking['total_price'] ) ); ?></span></li>
							<li><strong><?php esc_html_e( 'Da thanh toan', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( tam_backend_api_format_admin_amount( $booking['paid_amount'] ) ); ?></span></li>
							<li><strong><?php esc_html_e( 'Con lai', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( tam_backend_api_format_admin_amount( $booking['remaining_amount'] ) ); ?></span></li>
							<li><strong><?php esc_html_e( 'Hinh thuc', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( tam_backend_api_get_payment_plan_label( isset( $booking['payment_plan'] ) ? $booking['payment_plan'] : '' ) ); ?></span></li>
							<li><strong><?php esc_html_e( 'Payment status', 'travel-agency-modern' ); ?></strong><span><?php echo wp_kses_post( tam_backend_api_get_admin_status_badge( 'payment', $current_payment_status ) ); ?></span></li>
							<li><strong><?php esc_html_e( 'Confirmed by', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( ! empty( $booking['confirmed_by'] ) ? $booking['confirmed_by'] : '—' ); ?></span></li>
							<li><strong><?php esc_html_e( 'Confirmed at', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( ! empty( $booking['confirmed_at'] ) ? tam_backend_api_format_datetime( $booking['confirmed_at'] ) : '—' ); ?></span></li>
							<li><strong><?php esc_html_e( 'Nguoi dung backend', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( trim( (string) $booking['user_name'] ) ? $booking['user_name'] . ' · ' . $booking['user_email'] : $booking['user_email'] ); ?></span></li>
							<li><strong><?php esc_html_e( 'Lien he checkout', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( trim( (string) $booking['contact_name'] ) ? $booking['contact_name'] . ' · ' . $booking['contact_email'] : $booking['contact_email'] ); ?></span></li>
							<li><strong><?php esc_html_e( 'So dien thoai', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( $booking['contact_phone'] ? $booking['contact_phone'] : $booking['user_phone'] ); ?></span></li>
							<li><strong><?php esc_html_e( 'Quoc gia', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( $booking['contact_country'] ); ?></span></li>
						</ul>
						<?php if ( ! empty( $booking['special_requests'] ) ) : ?>
							<p><strong><?php esc_html_e( 'Ghi chu', 'travel-agency-modern' ); ?>:</strong> <?php echo esc_html( $booking['special_requests'] ); ?></p>
						<?php endif; ?>
						<div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:16px;">
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'tam_backend_booking_action', 'tam_backend_booking_nonce' ); ?>
								<input type="hidden" name="action" value="tam_backend_booking_action" />
								<input type="hidden" name="booking_id" value="<?php echo esc_attr( (int) $booking['id'] ); ?>" />
								<input type="hidden" name="redirect_to" value="<?php echo esc_url( add_query_arg( 'booking_id', (int) $booking['id'], $base_url ) ); ?>" />
								<input type="hidden" name="booking_admin_action" value="confirm_booking" />
								<button type="submit" class="button button-primary" <?php disabled( ! in_array( strtoupper( (string) $current_booking_status ), array( 'PENDING', 'PENDING_CONFIRMATION', 'PAID' ), true ) || ! in_array( strtoupper( (string) $current_payment_status ), array( 'PAID', 'PARTIALLY_PAID', 'SUCCESS' ), true ) ); ?>><?php esc_html_e( 'CONFIRM BOOKING', 'travel-agency-modern' ); ?></button>
							</form>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'tam_backend_booking_action', 'tam_backend_booking_nonce' ); ?>
								<input type="hidden" name="action" value="tam_backend_booking_action" />
								<input type="hidden" name="booking_id" value="<?php echo esc_attr( (int) $booking['id'] ); ?>" />
								<input type="hidden" name="redirect_to" value="<?php echo esc_url( add_query_arg( 'booking_id', (int) $booking['id'], $base_url ) ); ?>" />
								<input type="hidden" name="booking_admin_action" value="cancel_booking" />
								<button type="submit" class="button" <?php disabled( in_array( strtoupper( (string) $current_booking_status ), array( 'CANCELLED', 'REFUNDED', 'COMPLETED' ), true ) ); ?>><?php esc_html_e( 'CANCEL BOOKING', 'travel-agency-modern' ); ?></button>
							</form>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'tam_backend_booking_action', 'tam_backend_booking_nonce' ); ?>
								<input type="hidden" name="action" value="tam_backend_booking_action" />
								<input type="hidden" name="booking_id" value="<?php echo esc_attr( (int) $booking['id'] ); ?>" />
								<input type="hidden" name="redirect_to" value="<?php echo esc_url( add_query_arg( 'booking_id', (int) $booking['id'], $base_url ) ); ?>" />
								<input type="hidden" name="booking_admin_action" value="refund_payment" />
								<button type="submit" class="button button-secondary" <?php disabled( ! in_array( strtoupper( (string) $current_payment_status ), array( 'PAID', 'PARTIALLY_PAID', 'SUCCESS' ), true ) ); ?>><?php esc_html_e( 'REFUND PAYMENT', 'travel-agency-modern' ); ?></button>
							</form>
						</div>
					</div>

					<div class="tam-backend-bookings__card">
						<h3><?php esc_html_e( 'Lich su thanh toan', 'travel-agency-modern' ); ?></h3>
						<?php if ( empty( $detail_payload['payments'] ) ) : ?>
							<p class="tam-backend-bookings__muted"><?php esc_html_e( 'Chua co payment nao cho booking nay.', 'travel-agency-modern' ); ?></p>
						<?php else : ?>
							<table class="widefat striped tam-backend-bookings__table">
								<thead><tr><th>ID</th><th><?php esc_html_e( 'Phuong thuc', 'travel-agency-modern' ); ?></th><th><?php esc_html_e( 'Hinh thuc', 'travel-agency-modern' ); ?></th><th><?php esc_html_e( 'Da thu', 'travel-agency-modern' ); ?></th><th><?php esc_html_e( 'Con lai', 'travel-agency-modern' ); ?></th><th><?php esc_html_e( 'Trang thai', 'travel-agency-modern' ); ?></th><th><?php esc_html_e( 'Paid at', 'travel-agency-modern' ); ?></th></tr></thead>
								<tbody>
									<?php foreach ( $detail_payload['payments'] as $payment ) : ?>
										<tr>
											<td><?php echo esc_html( (int) $payment['id'] ); ?></td>
											<td><?php echo esc_html( tam_backend_api_get_payment_method_label( $payment['method'] ) ); ?></td>
											<td><?php echo esc_html( tam_backend_api_get_payment_plan_label( isset( $payment['payment_plan'] ) ? $payment['payment_plan'] : '' ) ); ?></td>
											<td><?php echo esc_html( tam_backend_api_format_admin_amount( ! empty( $payment['paid_amount'] ) ? $payment['paid_amount'] : $payment['amount'] ) ); ?></td>
											<td><?php echo esc_html( tam_backend_api_format_admin_amount( isset( $payment['remaining_amount'] ) ? $payment['remaining_amount'] : 0 ) ); ?></td>
											<td><?php echo wp_kses_post( tam_backend_api_get_admin_status_badge( 'payment', $payment['status'] ) ); ?></td>
											<td><?php echo esc_html( ! empty( $payment['paid_at'] ) ? tam_backend_api_format_datetime( $payment['paid_at'] ) : '—' ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>

					<div class="tam-backend-bookings__card">
						<h3><?php esc_html_e( 'Lich su giao dich', 'travel-agency-modern' ); ?></h3>
						<?php if ( empty( $detail_payload['transactions'] ) ) : ?>
							<p class="tam-backend-bookings__muted"><?php esc_html_e( 'Chua co transaction nao cho booking nay.', 'travel-agency-modern' ); ?></p>
						<?php else : ?>
							<table class="widefat striped tam-backend-bookings__table">
								<thead><tr><th><?php esc_html_e( 'Transaction', 'travel-agency-modern' ); ?></th><th><?php esc_html_e( 'Provider', 'travel-agency-modern' ); ?></th><th><?php esc_html_e( 'Hinh thuc', 'travel-agency-modern' ); ?></th><th><?php esc_html_e( 'Da thu', 'travel-agency-modern' ); ?></th><th><?php esc_html_e( 'Tong', 'travel-agency-modern' ); ?></th><th><?php esc_html_e( 'Trang thai', 'travel-agency-modern' ); ?></th><th><?php esc_html_e( 'Coupon', 'travel-agency-modern' ); ?></th><th><?php esc_html_e( 'Completed', 'travel-agency-modern' ); ?></th></tr></thead>
								<tbody>
									<?php foreach ( $detail_payload['transactions'] as $transaction ) : ?>
										<tr>
											<td><strong><?php echo esc_html( $transaction['transaction_code'] ); ?></strong><br /><small><?php echo esc_html( $transaction['request_id'] ); ?></small></td>
											<td><?php echo esc_html( tam_backend_api_get_payment_method_label( $transaction['provider'] ) ); ?></td>
											<td><?php echo esc_html( tam_backend_api_get_payment_plan_label( isset( $transaction['payment_plan'] ) ? $transaction['payment_plan'] : '' ) ); ?></td>
											<td><?php echo esc_html( tam_backend_api_format_admin_amount( isset( $transaction['paid_amount'] ) ? $transaction['paid_amount'] : $transaction['total_amount'] ) ); ?></td>
											<td><?php echo esc_html( tam_backend_api_format_admin_amount( $transaction['total_amount'] ) ); ?></td>
											<td><?php echo wp_kses_post( tam_backend_api_get_admin_status_badge( 'transaction', $transaction['status'] ) ); ?></td>
											<td><?php echo esc_html( $transaction['coupon_code'] ? $transaction['coupon_code'] : '—' ); ?></td>
											<td><?php echo esc_html( ! empty( $transaction['completed_at'] ) ? tam_backend_api_format_datetime( $transaction['completed_at'] ) : '—' ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( ! is_wp_error( $list_payload ) ) : ?>
			<div class="tam-backend-bookings__card">
				<h2><?php esc_html_e( 'Danh sach booking backend', 'travel-agency-modern' ); ?></h2>
				<table class="widefat striped tam-backend-bookings__table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Booking', 'travel-agency-modern' ); ?></th>
							<th><?php esc_html_e( 'Khach hang', 'travel-agency-modern' ); ?></th>
							<th><?php esc_html_e( 'Tour', 'travel-agency-modern' ); ?></th>
							<th><?php esc_html_e( 'Trang thai', 'travel-agency-modern' ); ?></th>
							<th><?php esc_html_e( 'Thanh toan', 'travel-agency-modern' ); ?></th>
							<th><?php esc_html_e( 'Tong tien', 'travel-agency-modern' ); ?></th>
							<th><?php esc_html_e( 'Hanh dong', 'travel-agency-modern' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $list_payload['items'] ) ) : ?>
							<tr><td colspan="7"><?php esc_html_e( 'Chua co booking nao trong DB backend.', 'travel-agency-modern' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $list_payload['items'] as $item ) : ?>
								<tr>
									<td>
										<strong><?php echo esc_html( tam_backend_api_build_booking_ref( $item['id'] ) ); ?></strong>
										<small><?php echo esc_html( tam_backend_api_format_datetime( $item['created_at'] ) ); ?></small>
									</td>
									<td>
										<strong><?php echo esc_html( $item['contact_name'] ? $item['contact_name'] : $item['user_name'] ); ?></strong>
										<small><?php echo esc_html( $item['contact_email'] ? $item['contact_email'] : $item['user_email'] ); ?></small>
									</td>
									<td>
										<strong><?php echo esc_html( $item['tour_title'] ); ?></strong>
										<small><?php echo esc_html( tam_backend_api_format_date( $item['travel_date'] ) . ' · ' . (int) $item['number_of_people'] . ' khach' ); ?></small>
									</td>
									<td>
										<?php echo wp_kses_post( tam_backend_api_get_admin_status_badge( 'booking', ! empty( $item['booking_status'] ) ? $item['booking_status'] : $item['status'] ) ); ?>
										<small><?php echo esc_html( tam_backend_api_get_payment_plan_label( isset( $item['payment_plan'] ) ? $item['payment_plan'] : '' ) ); ?></small>
									</td>
									<td>
										<?php if ( ! empty( $item['payment_id'] ) ) : ?>
											<?php echo wp_kses_post( tam_backend_api_get_admin_status_badge( 'payment', ! empty( $item['booking_payment_status'] ) ? $item['booking_payment_status'] : $item['payment_status'] ) ); ?>
											<small><?php echo esc_html( tam_backend_api_get_payment_method_label( $item['payment_method'] ) ); ?></small>
											<small><?php echo esc_html( tam_backend_api_format_admin_amount( ! empty( $item['paid_amount'] ) ? $item['paid_amount'] : ( ! empty( $item['payment_paid_amount'] ) ? $item['payment_paid_amount'] : $item['payment_amount'] ) ) ); ?></small>
											<?php if ( ! empty( $item['transaction_code'] ) ) : ?>
												<small><?php echo esc_html( $item['transaction_code'] ); ?></small>
											<?php endif; ?>
										<?php else : ?>
											<small><?php esc_html_e( 'Chua tao payment', 'travel-agency-modern' ); ?></small>
										<?php endif; ?>
									</td>
									<td><strong><?php echo esc_html( tam_backend_api_format_admin_amount( $item['total_price'] ) ); ?></strong></td>
									<td>
										<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'booking_id', (int) $item['id'], $base_url ) ); ?>"><?php esc_html_e( 'Xem chi tiet', 'travel-agency-modern' ); ?></a>
										<?php if ( in_array( strtoupper( (string) ( ! empty( $item['booking_status'] ) ? $item['booking_status'] : $item['status'] ) ), array( 'PENDING', 'PENDING_CONFIRMATION', 'PAID' ), true ) && in_array( strtoupper( (string) ( ! empty( $item['booking_payment_status'] ) ? $item['booking_payment_status'] : $item['payment_status'] ) ), array( 'PAID', 'PARTIALLY_PAID', 'SUCCESS' ), true ) ) : ?>
											<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:6px;">
												<?php wp_nonce_field( 'tam_backend_booking_action', 'tam_backend_booking_nonce' ); ?>
												<input type="hidden" name="action" value="tam_backend_booking_action" />
												<input type="hidden" name="booking_id" value="<?php echo esc_attr( (int) $item['id'] ); ?>" />
												<input type="hidden" name="redirect_to" value="<?php echo esc_url( $base_url ); ?>" />
												<input type="hidden" name="booking_admin_action" value="confirm_booking" />
												<button type="submit" class="button button-primary button-small"><?php esc_html_e( 'Confirm', 'travel-agency-modern' ); ?></button>
											</form>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>

				<?php
				if ( $list_payload['total_pages'] > 1 ) {
					echo '<div class="tablenav"><div class="tablenav-pages">';
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg(
									array(
										'page'           => 'tam-backend-bookings',
										'paged'          => '%#%',
										's'              => $search_term,
										'status'         => $status_filter,
										'payment_status' => $payment_filter,
									),
									admin_url( 'tools.php' )
								),
								'format'    => '',
								'current'   => $list_payload['paged'],
								'total'     => $list_payload['total_pages'],
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
							)
						)
					);
					echo '</div></div>';
				}
				?>
			</div>
		<?php elseif ( is_wp_error( $list_payload ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $list_payload->get_error_message() ); ?></p></div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Add a lightweight admin page to sync tours from the backend API.
 *
 * @return void
 */
function tam_backend_api_register_sync_page() {
	add_management_page(
		'Booking API',
		'Booking API',
		'manage_options',
		'tam-backend-bookings',
		'tam_backend_api_render_bookings_page'
	);

	add_management_page(
		'Sync Tours API',
		'Sync Tours API',
		'manage_options',
		'tam-sync-tours-api',
		'tam_backend_api_render_sync_page'
	);
}
add_action( 'admin_menu', 'tam_backend_api_register_sync_page' );

/**
 * Render the sync page and handle the sync form postback.
 *
 * @return void
 */
function tam_backend_api_render_sync_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'travel-agency-modern' ) );
	}

	$sync_result = null;

	if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['tam_sync_tours_nonce'] ) ) {
		check_admin_referer( 'tam_sync_tours_action', 'tam_sync_tours_nonce' );
		$sync_result = tam_backend_api_sync_tours();
	}
	?>
	<div class="wrap">
		<h1>Sync tours from backend API</h1>
		<p>Use this tool after the Node backend is running to import or refresh tour posts in WordPress.</p>
		<p><strong>Backend:</strong> <?php echo esc_html( tam_backend_api_base_url() ); ?></p>

		<?php if ( is_array( $sync_result ) ) : ?>
			<div class="notice <?php echo ! empty( $sync_result['success'] ) ? 'notice-success' : 'notice-error'; ?>">
				<p>
					<?php echo esc_html( $sync_result['message'] ); ?>
					<?php if ( isset( $sync_result['count'] ) ) : ?>
						<?php echo esc_html( ' Synced tours: ' . (int) $sync_result['count'] . '.' ); ?>
					<?php endif; ?>
				</p>
			</div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'tam_sync_tours_action', 'tam_sync_tours_nonce' ); ?>
			<p>
				<button type="submit" class="button button-primary">Sync tours now</button>
			</p>
		</form>
	</div>
	<?php
}

/**
 * Intercept legacy WordPress transport endpoints and proxy them to the backend API.
 *
 * @return void
 */
function tam_backend_api_intercept_requests() {
	if ( empty( $_REQUEST['action'] ) ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_REQUEST['action'] ) );

	if ( wp_doing_ajax() ) {
		if ( 'tam_auth_login' === $action ) {
			tam_backend_api_handle_ajax_login_request();
		}

		if ( 'tam_auth_register' === $action ) {
			tam_backend_api_handle_ajax_register_request();
		}
	}

	if ( 'tam_submit_checkout_form' === $action ) {
		tam_backend_api_handle_checkout_submission();
	}
}
add_action( 'admin_init', 'tam_backend_api_intercept_requests', 0 );
