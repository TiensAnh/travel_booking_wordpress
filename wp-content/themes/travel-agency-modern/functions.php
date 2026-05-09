<?php
/**
 * Travel Agency Modern child theme functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TAM_THEME_VERSION', '1.0.0' );

require_once get_theme_file_path( '/inc/api-integration.php' );

/**
 * Return a cache-busting version based on file modified time.
 *
 * @param string $relative_path Path relative to child theme root.
 */
function tam_asset_version( $relative_path ) {
	$file_path = get_theme_file_path( $relative_path );

	if ( file_exists( $file_path ) ) {
		return (string) filemtime( $file_path );
	}

	return TAM_THEME_VERSION;
}

require_once get_theme_file_path( '/inc/admin-backoffice.php' );

/**
 * Child theme setup.
 */
function tam_theme_setup() {
	add_image_size( 'tam-tour-card', 720, 520, true );
	add_image_size( 'tam-blog-card', 720, 440, true );
	add_image_size( 'tam-hero-large', 1600, 900, true );

	register_nav_menus(
		array(
			'primary' => __( 'Menu chính', 'travel-agency-modern' ),
			'footer'  => __( 'Menu chân trang', 'travel-agency-modern' ),
		)
	);
}
add_action( 'after_setup_theme', 'tam_theme_setup', 20 );

/**
 * Add a stable body class for theme-specific styling.
 *
 * @param string[] $classes Body classes.
 */
function tam_body_classes( $classes ) {
	$classes[] = 'travel-agency-modern';

	return $classes;
}
add_filter( 'body_class', 'tam_body_classes' );

/**
 * Remove parent theme assets so the new design can own the UI cleanly.
 */
function tam_dequeue_parent_assets() {
	$style_handles = array(
		'animate',
		'travel-agency-google-fonts',
		'travel-agency-elementor',
		'travel-agency-style',
	);

	$script_handles = array(
		'wow',
		'travel-agency-modal-accessibility',
		'all',
		'v4-shims',
		'travel-agency-custom',
	);

	foreach ( $style_handles as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}

	foreach ( $script_handles as $handle ) {
		wp_dequeue_script( $handle );
		wp_deregister_script( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'tam_dequeue_parent_assets', 100 );

/**
 * Enqueue child theme assets.
 */
function tam_enqueue_assets() {
	wp_enqueue_style(
		'travel-agency-modern-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'travel-agency-modern-icons',
		'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
		array(),
		'6.5.2'
	);

	wp_enqueue_style(
		'travel-agency-modern-style',
		get_stylesheet_uri(),
		array( 'travel-agency-modern-fonts', 'travel-agency-modern-icons' ),
		tam_asset_version( '/style.css' )
	);

	wp_enqueue_script(
		'travel-agency-modern-script',
		get_theme_file_uri( '/assets/theme.js' ),
		array(),
		tam_asset_version( '/assets/theme.js' ),
		true
	);

	wp_localize_script(
		'travel-agency-modern-script',
		'tamTheme',
		array(
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'redirectUrl'       => tam_get_current_public_url(),
			'successDelay'      => 900,
			'tourFilterAction'  => 'tam_filter_tours',
			'tourFilterNonce'   => wp_create_nonce( 'tam_tour_filter' ),
			'tourSearchDelay'   => 380,
			'tourSearchMinLoad' => 650,
			'checkoutQuoteAction'   => 'tam_checkout_quote',
			'checkoutQuoteNonce'    => wp_create_nonce( 'tam_checkout_quote' ),
			'checkoutSessionAction' => 'tam_checkout_create_session',
			'checkoutSessionNonce'  => wp_create_nonce( 'tam_checkout_create_session' ),
			'checkoutInvoiceAction' => 'tam_download_checkout_invoice',
			'checkoutMessages'      => array(
				'genericError'   => __( 'He thong dang ban, vui long thu lai sau it phut.', 'travel-agency-modern' ),
				'quoteLoading'   => __( 'Dang cap nhat tong thanh toan...', 'travel-agency-modern' ),
				'paymentLoading' => __( 'Dang tao booking va chuyen sang cong thanh toan...', 'travel-agency-modern' ),
				'loginRequired'  => __( 'Ban can dang nhap truoc khi thanh toan.', 'travel-agency-modern' ),
				'backendUnavailable' => __( 'Backend checkout tam thoi chua san sang. Vui long khoi dong lai backend-api va tai lai trang.', 'travel-agency-modern' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'tam_enqueue_assets', 110 );

/**
 * Register tour content model.
 */
function tam_register_tour_content_type() {
	register_post_type(
		'tour',
		array(
			'labels'       => array(
				'name'               => __( 'Tour', 'travel-agency-modern' ),
				'singular_name'      => __( 'Tour', 'travel-agency-modern' ),
				'add_new'            => __( 'Thêm tour', 'travel-agency-modern' ),
				'add_new_item'       => __( 'Thêm tour mới', 'travel-agency-modern' ),
				'edit_item'          => __( 'Chỉnh sửa tour', 'travel-agency-modern' ),
				'new_item'           => __( 'Tour mới', 'travel-agency-modern' ),
				'view_item'          => __( 'Xem tour', 'travel-agency-modern' ),
				'search_items'       => __( 'Tìm kiếm tour', 'travel-agency-modern' ),
				'not_found'          => __( 'Chưa có tour nào.', 'travel-agency-modern' ),
				'not_found_in_trash' => __( 'Không có tour trong thùng rác.', 'travel-agency-modern' ),
				'menu_name'          => __( 'Tour', 'travel-agency-modern' ),
			),
			'public'       => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-location-alt',
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			'rewrite'      => array(
				'slug'       => 'tour',
				'with_front' => false,
			),
			'has_archive'  => false,
		)
	);

	register_taxonomy(
		'tour_destination',
		'tour',
		array(
			'labels'            => array(
				'name'              => __( 'Điểm đến', 'travel-agency-modern' ),
				'singular_name'     => __( 'Điểm đến', 'travel-agency-modern' ),
				'search_items'      => __( 'Tìm điểm đến', 'travel-agency-modern' ),
				'all_items'         => __( 'Tất cả điểm đến', 'travel-agency-modern' ),
				'edit_item'         => __( 'Sửa điểm đến', 'travel-agency-modern' ),
				'update_item'       => __( 'Cập nhật điểm đến', 'travel-agency-modern' ),
				'add_new_item'      => __( 'Thêm điểm đến', 'travel-agency-modern' ),
				'new_item_name'     => __( 'Tên điểm đến mới', 'travel-agency-modern' ),
				'menu_name'         => __( 'Điểm đến', 'travel-agency-modern' ),
				'not_found'         => __( 'Chưa có điểm đến.', 'travel-agency-modern' ),
				'back_to_items'     => __( 'Quay lại danh sách điểm đến', 'travel-agency-modern' ),
				'choose_from_most_used' => __( 'Chọn điểm đến', 'travel-agency-modern' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'diem-den',
				'with_front' => false,
			),
		)
	);
}
add_action( 'init', 'tam_register_tour_content_type' );

/**
 * Register a lightweight inquiry content type for consultation and newsletter leads.
 */
function tam_register_inquiry_content_type() {
	register_post_type(
		'tour_inquiry',
		array(
			'labels'              => array(
				'name'               => __( 'Yêu cầu', 'travel-agency-modern' ),
				'singular_name'      => __( 'Yêu cầu', 'travel-agency-modern' ),
				'add_new_item'       => __( 'Thêm yêu cầu', 'travel-agency-modern' ),
				'edit_item'          => __( 'Chi tiết yêu cầu', 'travel-agency-modern' ),
				'new_item'           => __( 'Yêu cầu mới', 'travel-agency-modern' ),
				'view_item'          => __( 'Xem yêu cầu', 'travel-agency-modern' ),
				'search_items'       => __( 'Tìm yêu cầu', 'travel-agency-modern' ),
				'not_found'          => __( 'Chưa có yêu cầu nào.', 'travel-agency-modern' ),
				'not_found_in_trash' => __( 'Không có yêu cầu trong thùng rác.', 'travel-agency-modern' ),
				'menu_name'          => __( 'Yêu cầu', 'travel-agency-modern' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-email-alt2',
			'supports'            => array( 'title' ),
			'exclude_from_search' => true,
			'show_in_nav_menus'   => false,
			'show_in_rest'        => false,
		)
	);
}
add_action( 'init', 'tam_register_inquiry_content_type' );

/**
 * Return public status labels for inquiry records.
 */
function tam_get_inquiry_status_map() {
	return array(
		'new'       => array(
			'label' => __( 'Mới nhận', 'travel-agency-modern' ),
			'class' => 'new',
		),
		'working'   => array(
			'label' => __( 'Đang xử lý', 'travel-agency-modern' ),
			'class' => 'working',
		),
		'contacted' => array(
			'label' => __( 'Đã liên hệ', 'travel-agency-modern' ),
			'class' => 'contacted',
		),
		'closed'    => array(
			'label' => __( 'Đã chốt', 'travel-agency-modern' ),
			'class' => 'closed',
		),
	);
}

/**
 * Add an admin meta box for inquiry records.
 */
function tam_add_inquiry_meta_box() {
	add_meta_box(
		'tam-inquiry-details',
		__( 'Thông tin yêu cầu', 'travel-agency-modern' ),
		'tam_render_inquiry_meta_box',
		'tour_inquiry',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'tam_add_inquiry_meta_box' );

/**
 * Render the inquiry admin meta box.
 *
 * @param WP_Post $post The current inquiry post.
 */
function tam_render_inquiry_meta_box( $post ) {
	$status_map = tam_get_inquiry_status_map();
	$type       = get_post_meta( $post->ID, '_tam_inquiry_type', true );
	$status     = get_post_meta( $post->ID, '_tam_inquiry_status', true );
	$name       = get_post_meta( $post->ID, '_tam_inquiry_name', true );
	$phone      = get_post_meta( $post->ID, '_tam_inquiry_phone', true );
	$email      = get_post_meta( $post->ID, '_tam_inquiry_email', true );
	$tour       = get_post_meta( $post->ID, '_tam_inquiry_tour_interest', true );
	$message    = get_post_meta( $post->ID, '_tam_inquiry_message', true );
	$source     = get_post_meta( $post->ID, '_tam_inquiry_source', true );

	if ( ! isset( $status_map[ $status ] ) ) {
		$status = 'new';
	}

	wp_nonce_field( 'tam_save_inquiry_meta', 'tam_inquiry_meta_nonce' );
	?>
	<div class="tam-admin-grid" style="display:grid;gap:18px;">
		<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">
			<p style="margin:0;">
				<label style="display:block;font-weight:600;margin-bottom:8px;"><?php esc_html_e( 'Loại yêu cầu', 'travel-agency-modern' ); ?></label>
				<input type="text" readonly value="<?php echo esc_attr( 'newsletter' === $type ? __( 'Đăng ký nhận tin', 'travel-agency-modern' ) : __( 'Tư vấn tour', 'travel-agency-modern' ) ); ?>" style="width:100%;" />
			</p>
			<p style="margin:0;">
				<label for="tam-inquiry-status" style="display:block;font-weight:600;margin-bottom:8px;"><?php esc_html_e( 'Trạng thái', 'travel-agency-modern' ); ?></label>
				<select id="tam-inquiry-status" name="tam_inquiry_status" style="width:100%;">
					<?php foreach ( $status_map as $key => $status_data ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>>
							<?php echo esc_html( $status_data['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p style="margin:0;">
				<label style="display:block;font-weight:600;margin-bottom:8px;"><?php esc_html_e( 'Khách hàng', 'travel-agency-modern' ); ?></label>
				<input type="text" readonly value="<?php echo esc_attr( $name ? $name : __( 'Không có tên', 'travel-agency-modern' ) ); ?>" style="width:100%;" />
			</p>
			<p style="margin:0;">
				<label style="display:block;font-weight:600;margin-bottom:8px;"><?php esc_html_e( 'Điện thoại', 'travel-agency-modern' ); ?></label>
				<input type="text" readonly value="<?php echo esc_attr( $phone ); ?>" style="width:100%;" />
			</p>
			<p style="margin:0;">
				<label style="display:block;font-weight:600;margin-bottom:8px;"><?php esc_html_e( 'Email', 'travel-agency-modern' ); ?></label>
				<input type="text" readonly value="<?php echo esc_attr( $email ); ?>" style="width:100%;" />
			</p>
			<p style="margin:0;">
				<label style="display:block;font-weight:600;margin-bottom:8px;"><?php esc_html_e( 'Nguồn gửi', 'travel-agency-modern' ); ?></label>
				<input type="text" readonly value="<?php echo esc_attr( $source ); ?>" style="width:100%;" />
			</p>
		</div>
		<?php if ( $tour ) : ?>
			<p style="margin:0;">
				<label style="display:block;font-weight:600;margin-bottom:8px;"><?php esc_html_e( 'Tour quan tâm', 'travel-agency-modern' ); ?></label>
				<input type="text" readonly value="<?php echo esc_attr( $tour ); ?>" style="width:100%;" />
			</p>
		<?php endif; ?>
		<p style="margin:0;">
			<label style="display:block;font-weight:600;margin-bottom:8px;"><?php esc_html_e( 'Nội dung', 'travel-agency-modern' ); ?></label>
			<textarea readonly style="width:100%;min-height:140px;"><?php echo esc_textarea( $message ); ?></textarea>
		</p>
	</div>
	<?php
}

/**
 * Save editable inquiry fields in wp-admin.
 *
 * @param int $post_id Post ID.
 */
function tam_save_inquiry_meta( $post_id ) {
	if ( ! isset( $_POST['tam_inquiry_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tam_inquiry_meta_nonce'] ) ), 'tam_save_inquiry_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) || 'tour_inquiry' !== get_post_type( $post_id ) ) {
		return;
	}

	$status_map = tam_get_inquiry_status_map();
	$status     = isset( $_POST['tam_inquiry_status'] ) ? sanitize_key( wp_unslash( $_POST['tam_inquiry_status'] ) ) : 'new';

	if ( ! isset( $status_map[ $status ] ) ) {
		$status = 'new';
	}

	update_post_meta( $post_id, '_tam_inquiry_status', $status );
}
add_action( 'save_post_tour_inquiry', 'tam_save_inquiry_meta' );

/**
 * Tour meta field configuration.
 */
function tam_tour_meta_fields() {
	return array(
		'duration'        => array(
			'label'       => __( 'Thời lượng', 'travel-agency-modern' ),
			'placeholder' => __( 'Ví dụ: 3 ngày 2 đêm', 'travel-agency-modern' ),
		),
		'departure'       => array(
			'label'       => __( 'Điểm khởi hành', 'travel-agency-modern' ),
			'placeholder' => __( 'Ví dụ: Hà Nội', 'travel-agency-modern' ),
		),
		'price_from'      => array(
			'label'       => __( 'Giá từ', 'travel-agency-modern' ),
			'placeholder' => __( 'Ví dụ: 3490000', 'travel-agency-modern' ),
		),
		'group_size'      => array(
			'label'       => __( 'Quy mô đoàn', 'travel-agency-modern' ),
			'placeholder' => __( 'Ví dụ: 10 - 15 khách', 'travel-agency-modern' ),
		),
		'rating'          => array(
			'label'       => __( 'Rating trung bình', 'travel-agency-modern' ),
			'placeholder' => __( 'Ví dụ: 4.9', 'travel-agency-modern' ),
		),
		'review_count'    => array(
			'label'       => __( 'Số lượng đánh giá', 'travel-agency-modern' ),
			'placeholder' => __( 'Ví dụ: 128', 'travel-agency-modern' ),
		),
		'season'          => array(
			'label'       => __( 'Mùa đẹp', 'travel-agency-modern' ),
			'placeholder' => __( 'Ví dụ: Tháng 10 - 3', 'travel-agency-modern' ),
		),
		'transport'       => array(
			'label'       => __( 'Phương tiện', 'travel-agency-modern' ),
			'placeholder' => __( 'Ví dụ: Xe du lịch / Máy bay', 'travel-agency-modern' ),
		),
		'departure_dates' => array(
			'label'       => __( 'Ngày khởi hành', 'travel-agency-modern' ),
			'type'        => 'textarea',
			'rows'        => 4,
			'placeholder' => __( "Mỗi dòng 1 ngày.\nVí dụ:\n2026-06-12|Khởi hành cuối tuần\n2026-06-19|Khởi hành thứ sáu", 'travel-agency-modern' ),
			'help'        => __( 'Mỗi dòng theo định dạng: ngày|nhãn hiển thị. Có thể chỉ nhập ngày hoặc câu chữ ngắn.', 'travel-agency-modern' ),
		),
		'highlights'      => array(
			'label'       => __( 'Điểm nổi bật', 'travel-agency-modern' ),
			'type'        => 'textarea',
			'rows'        => 5,
			'placeholder' => __( "Mỗi dòng 1 ý nổi bật.\nVí dụ:\nCung đường đẹp, nhiều điểm check-in\nLịch trình gọn, dễ đi cho nhóm bạn", 'travel-agency-modern' ),
			'help'        => __( 'Danh sách bullet ở phần mô tả tour.', 'travel-agency-modern' ),
		),
		'itinerary'       => array(
			'label'       => __( 'Lịch trình theo ngày', 'travel-agency-modern' ),
			'type'        => 'textarea',
			'rows'        => 6,
			'placeholder' => __( "Mỗi dòng theo định dạng:\nDay 1|Khởi hành và làm quen hành trình|Đón khách, di chuyển, check-in và trải nghiệm điểm nhấn đầu tiên.", 'travel-agency-modern' ),
			'help'        => __( 'Mỗi dòng gồm 3 phần: nhãn ngày | tiêu đề | mô tả.', 'travel-agency-modern' ),
		),
		'includes'        => array(
			'label'       => __( 'Bao gồm', 'travel-agency-modern' ),
			'type'        => 'textarea',
			'rows'        => 5,
			'placeholder' => __( "Mỗi dòng 1 mục.\nVí dụ:\nXe đưa đón theo lịch trình\nKhách sạn tiêu chuẩn 3-4 sao", 'travel-agency-modern' ),
		),
		'excludes'        => array(
			'label'       => __( 'Không bao gồm', 'travel-agency-modern' ),
			'type'        => 'textarea',
			'rows'        => 5,
			'placeholder' => __( "Mỗi dòng 1 mục.\nVí dụ:\nChi phí cá nhân\nThuế VAT", 'travel-agency-modern' ),
		),
		'review_snippets' => array(
			'label'       => __( 'Review khách hàng', 'travel-agency-modern' ),
			'type'        => 'textarea',
			'rows'        => 5,
			'placeholder' => __( "Mỗi dòng theo định dạng:\nLan Anh|5.0|Lịch trình rõ, hướng dẫn viên nhiệt tình và khách sạn sạch đẹp.", 'travel-agency-modern' ),
			'help'        => __( 'Mỗi dòng gồm 3 phần: tên khách | rating | nội dung review.', 'travel-agency-modern' ),
		),
		'featured'        => array(
			'label' => __( 'Đánh dấu tour nổi bật', 'travel-agency-modern' ),
			'type'  => 'checkbox',
		),
	);
}

/**
 * Register the tour meta box.
 */
function tam_add_tour_meta_box() {
	add_meta_box(
		'tam-tour-details',
		__( 'Thông tin tour', 'travel-agency-modern' ),
		'tam_render_tour_meta_box',
		'tour',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'tam_add_tour_meta_box' );

/**
 * Render the meta box fields.
 *
 * @param WP_Post $post The current post.
 */
function tam_render_tour_meta_box( $post ) {
	wp_nonce_field( 'tam_save_tour_meta', 'tam_tour_meta_nonce' );

	foreach ( tam_tour_meta_fields() as $key => $field ) {
		$meta_key = '_tam_tour_' . $key;
		$value    = get_post_meta( $post->ID, $meta_key, true );
		?>
		<p>
			<label for="<?php echo esc_attr( 'tam-tour-' . $key ); ?>" style="display:block;font-weight:600;margin-bottom:8px;">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<?php if ( isset( $field['type'] ) && 'checkbox' === $field['type'] ) : ?>
				<label for="<?php echo esc_attr( 'tam-tour-' . $key ); ?>">
					<input
						type="checkbox"
						id="<?php echo esc_attr( 'tam-tour-' . $key ); ?>"
						name="tam_tour_meta[<?php echo esc_attr( $key ); ?>]"
						value="1"
						<?php checked( '1', $value ); ?>
					/>
					<?php esc_html_e( 'Hiển thị tour này trong khu vực nổi bật.', 'travel-agency-modern' ); ?>
				</label>
			<?php elseif ( isset( $field['type'] ) && 'textarea' === $field['type'] ) : ?>
				<textarea
					id="<?php echo esc_attr( 'tam-tour-' . $key ); ?>"
					name="tam_tour_meta[<?php echo esc_attr( $key ); ?>]"
					rows="<?php echo esc_attr( isset( $field['rows'] ) ? (int) $field['rows'] : 5 ); ?>"
					placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>"
					style="width:100%;max-width:720px;"
				><?php echo esc_textarea( $value ); ?></textarea>
			<?php else : ?>
				<input
					type="text"
					id="<?php echo esc_attr( 'tam-tour-' . $key ); ?>"
					name="tam_tour_meta[<?php echo esc_attr( $key ); ?>]"
					value="<?php echo esc_attr( $value ); ?>"
					placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
					style="width:100%;max-width:520px;"
				/>
			<?php endif; ?>
			<?php if ( ! empty( $field['help'] ) ) : ?>
				<span style="display:block;margin-top:8px;color:#64748b;font-size:13px;"><?php echo esc_html( $field['help'] ); ?></span>
			<?php endif; ?>
		</p>
		<?php
	}
}

/**
 * Save tour meta fields.
 *
 * @param int $post_id Post ID.
 */
function tam_save_tour_meta( $post_id ) {
	if ( ! isset( $_POST['tam_tour_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tam_tour_meta_nonce'] ) ), 'tam_save_tour_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( 'tour' !== get_post_type( $post_id ) ) {
		return;
	}

	$submitted = isset( $_POST['tam_tour_meta'] ) ? (array) wp_unslash( $_POST['tam_tour_meta'] ) : array();

	foreach ( tam_tour_meta_fields() as $key => $field ) {
		$meta_key = '_tam_tour_' . $key;

		if ( isset( $field['type'] ) && 'checkbox' === $field['type'] ) {
			update_post_meta( $post_id, $meta_key, ! empty( $submitted[ $key ] ) ? '1' : '0' );
			continue;
		}

		if ( isset( $submitted[ $key ] ) && '' !== trim( $submitted[ $key ] ) ) {
			$sanitized_value = isset( $field['type'] ) && 'textarea' === $field['type']
				? sanitize_textarea_field( $submitted[ $key ] )
				: sanitize_text_field( $submitted[ $key ] );
			update_post_meta( $post_id, $meta_key, $sanitized_value );
		} else {
			delete_post_meta( $post_id, $meta_key );
		}
	}
}
add_action( 'save_post', 'tam_save_tour_meta' );

/**
 * Register Customizer settings for business contact information.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function tam_register_customizer_settings( $wp_customize ) {
	$wp_customize->add_section(
		'tam_contact_settings',
		array(
			'title'       => __( 'Liên hệ Travel Agency Modern', 'travel-agency-modern' ),
			'description' => __( 'Thông tin liên hệ sử dụng trên header, footer và form tư vấn.', 'travel-agency-modern' ),
			'priority'    => 32,
		)
	);

	$fields = array(
		'tam_phone'          => array(
			'label'   => __( 'Số điện thoại', 'travel-agency-modern' ),
			'default' => '+84 977 998 776',
		),
		'tam_contact_email'  => array(
			'label'   => __( 'Email nhận tư vấn', 'travel-agency-modern' ),
			'default' => get_option( 'admin_email' ),
		),
		'tam_chat_url'       => array(
			'label'   => __( 'URL chat nhanh', 'travel-agency-modern' ),
			'default' => 'https://wa.me/84977998776',
		),
		'tam_office_address' => array(
			'label'   => __( 'Địa chỉ văn phòng', 'travel-agency-modern' ),
			'default' => __( '123 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh', 'travel-agency-modern' ),
			'type'    => 'textarea',
		),
	);

	foreach ( $fields as $setting_id => $field ) {
		$wp_customize->add_setting(
			$setting_id,
			array(
				'default'           => $field['default'],
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		if ( 'tam_contact_email' === $setting_id ) {
			$wp_customize->get_setting( $setting_id )->sanitize_callback = 'sanitize_email';
		}

		if ( 'tam_chat_url' === $setting_id ) {
			$wp_customize->get_setting( $setting_id )->sanitize_callback = 'esc_url_raw';
		}

		$wp_customize->add_control(
			$setting_id,
			array(
				'label'   => $field['label'],
				'section' => 'tam_contact_settings',
				'type'    => isset( $field['type'] ) ? $field['type'] : 'text',
			)
		);
	}
}
add_action( 'customize_register', 'tam_register_customizer_settings', 30 );

/**
 * Get contact details from theme mods.
 */
function tam_get_contact_details() {
	$phone        = get_theme_mod( 'tam_phone', '+84 977 998 776' );
	$email        = get_theme_mod( 'tam_contact_email', get_option( 'admin_email' ) );
	$address      = get_theme_mod( 'tam_office_address', __( '123 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh', 'travel-agency-modern' ) );
	$digits       = preg_replace( '/[^\d+]/', '', (string) $phone );
	$wa_digits    = ltrim( preg_replace( '/[^\d]/', '', (string) $phone ), '0' );
	$default_chat = $wa_digits ? 'https://wa.me/' . $wa_digits : '#';
	$chat_url     = get_theme_mod( 'tam_chat_url', $default_chat );

	return array(
		'phone'      => $phone,
		'email'      => is_email( $email ) ? $email : get_option( 'admin_email' ),
		'address'    => $address,
		'tel_url'    => $digits ? 'tel:' . $digits : '#',
		'chat_url'   => $chat_url ? esc_url( $chat_url ) : '#',
		'chat_label' => __( 'Chat nhanh', 'travel-agency-modern' ),
	);
}

/**
 * Normalize a phone number for storage and lookup.
 *
 * @param string $phone Raw phone value.
 */
function tam_normalize_phone( $phone ) {
	return preg_replace( '/\D+/', '', (string) $phone );
}

/**
 * Return initials for avatar-like UI elements.
 *
 * @param string $name Full name.
 */
function tam_get_initials( $name ) {
	$initials = '';
	$parts    = preg_split( '/\s+/', trim( (string) $name ) );

	if ( empty( $parts ) ) {
		return 'TV';
	}

	foreach ( $parts as $part ) {
		$initials .= function_exists( 'mb_substr' ) ? mb_substr( $part, 0, 1 ) : substr( $part, 0, 1 );

		if ( strlen( $initials ) >= 2 ) {
			break;
		}
	}

	return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $initials ) : strtoupper( $initials );
}

/**
 * Shared content for the home promotions section.
 */
function tam_get_home_promo_content() {
	return array(
		'headline'    => 'Ưu đãi và trải nghiệm đang kéo khách quan tâm nhiều nhất',
		'description' => 'Dựa theo cấu trúc ở dự án tham chiếu, trang chủ được bổ sung thêm các khối ưu đãi nổi bật và lý do tin tưởng để website có cảm giác “đang bán thật” thay vì chỉ là catalog tĩnh.',
		'cards'       => array(
			array(
				'badge'       => 'Mới giảm 15%',
				'title'       => 'Tour biển cho nhóm gia đình',
				'description' => 'Ưu đãi theo nhóm 4 khách trở lên, hợp cho các tuyến nghỉ dưỡng Phú Quốc, Nha Trang và Đà Nẵng.',
				'button_text' => 'Xem tour nghỉ dưỡng',
				'button_url'  => tam_get_page_url_by_path( 'tour' ),
				'tone'        => 'sea',
			),
			array(
				'badge'       => 'Tư vấn 1:1',
				'title'       => 'Chốt hành trình theo ngân sách',
				'description' => 'Đội ngũ tư vấn có thể gợi ý tuyến, thời lượng và điểm khởi hành phù hợp mà không cần khách phải tự lọc quá nhiều.',
				'button_text' => 'Nhận tư vấn nhanh',
				'button_url'  => tam_get_page_url_by_path( 'lien-he' ),
				'tone'        => 'sunrise',
			),
		),
		'features'    => array(
			array(
				'title'       => 'Hành trình rõ ràng ngay từ card tour',
				'description' => 'Khách thấy được điểm đến, thời lượng và mức giá tham khảo chỉ trong một vùng nhìn.',
			),
			array(
				'title'       => 'Lead đi về một đầu mối',
				'description' => 'Form tư vấn, nút gọi và chat đều đang gom về cùng một quy trình để đội ngũ dễ xử lý.',
			),
			array(
				'title'       => 'Có thể mở rộng thành booking thật',
				'description' => 'Cấu trúc hiện tại đã tách riêng dữ liệu tour và luồng tiếp nhận lead để dễ gắn thêm booking online sau này.',
			),
		),
	);
}

/**
 * Shared content for newsletter panels.
 */
function tam_get_newsletter_content() {
	return array(
		'title'       => 'Nhận ưu đãi kín và tuyến tour mới qua email',
		'description' => 'Khối newsletter được thêm theo bản tham chiếu để giữ nhịp chăm sóc sau lần ghé đầu tiên. Với V1, đăng ký sẽ được lưu ngay trong WordPress để đội ngũ có thể theo dõi lại sau.',
		'tags'        => array(
			'Ưu đãi theo mùa',
			'Lịch khởi hành mới',
			'Combo gia đình',
			'Gợi ý tour cuối tuần',
		),
	);
}

/**
 * Shared review content for the home page.
 */
function tam_get_home_reviews_content() {
	return array(
		'eyebrow'     => 'Review khách hàng',
		'title'       => 'Khách quay lại vì trải nghiệm thật sự gọn và rõ',
		'description' => 'Phần review được đặt gần cuối homepage để tạo niềm tin nhanh trước khi khách chuyển qua trang Tour hoặc để lại yêu cầu tư vấn.',
		'rating'      => '4.9/5',
		'rating_note' => 'Đánh giá từ khách đã đi tour, nhóm gia đình và khách quay lại theo mùa.',
		'metrics'     => array(
			array(
				'value' => '2.8k+',
				'label' => 'lượt review tích lũy',
			),
			array(
				'value' => '93%',
				'label' => 'khách sẵn sàng giới thiệu',
			),
			array(
				'value' => '24h',
				'label' => 'phản hồi tư vấn trung bình',
			),
		),
		'items'       => array(
			array(
				'name'   => 'Lan Anh',
				'route'  => 'Tour Hà Giang 3N2D',
				'quote'  => 'Lịch trình rõ, tư vấn nhanh và lúc chăm sóc sau khi gửi form cảm giác rất có người theo sát.',
				'rating' => '5.0',
			),
			array(
				'name'   => 'Minh Quân',
				'route'  => 'Phú Quốc 4N3D',
				'quote'  => 'Trang web nhìn sạch, card tour dễ hiểu và mình chỉ mất vài phút để chọn được tuyến phù hợp.',
				'rating' => '4.9',
			),
			array(
				'name'   => 'Thu Hằng',
				'route'  => 'Đà Nẵng & Hội An',
				'quote'  => 'Phần hình ảnh và cách sắp tour khiến mình tin hơn. Hotline gọi lại cũng rất nhẹ nhàng và đúng nhu cầu.',
				'rating' => '5.0',
			),
		),
	);
}

/**
 * Shared content for the about page.
 */
function tam_get_about_page_sections() {
	return array(
		'mission_title'       => 'Thiết kế hành trình dễ hiểu, dễ tin và dễ chốt hơn',
		'mission_description' => 'Bản WordPress này không sao chép nguyên app tham chiếu, nhưng lấy lại tinh thần: mỗi trang đều phải kể chuyện thương hiệu, khơi gợi niềm tin và dẫn khách đến một hành động rõ ràng.',
		'values'              => array(
			array(
				'title'       => 'Minh bạch từ thông tin cơ bản',
				'description' => 'Tên tour, giá từ, điểm khởi hành và cách liên hệ đều được đẩy lên sớm để người xem không phải đoán.',
			),
			array(
				'title'       => 'Ưu tiên mobile trước',
				'description' => 'Những khu vực quan trọng như CTA, summary và form đều được giữ dễ đọc trên màn hình nhỏ.',
			),
			array(
				'title'       => 'Dễ chuyển giao cho team nội dung',
				'description' => 'Các phần copy chính vẫn bám WordPress editor, còn section động được đóng gói thành template rõ ràng.',
			),
		),
		'stats'               => array(
			array(
				'value' => '12+',
				'label' => 'mẫu section đã đồng bộ',
			),
			array(
				'value' => '3',
				'label' => 'điểm chạm lead chính',
			),
			array(
				'value' => '100%',
				'label' => 'giao diện responsive',
			),
			array(
				'value' => 'V1',
				'label' => 'sẵn sàng mở rộng booking',
			),
		),
		'reasons'             => array(
			array(
				'title'       => 'Trang chủ không còn là blog mặc định',
				'description' => 'Hero, ưu đãi, tour nổi bật, điểm đến và CTA cuối trang đã được ghép thành một luồng bán hàng rõ nét hơn.',
			),
			array(
				'title'       => 'Giới thiệu có chiều sâu hơn',
				'description' => 'Bổ sung mission, chỉ số, đội ngũ và cam kết để thương hiệu có cá tính hơn thay vì chỉ có vài đoạn mô tả ngắn.',
			),
			array(
				'title'       => 'Liên hệ mang tính vận hành',
				'description' => 'Ngoài form, trang còn có FAQ, newsletter và các kênh liên hệ nhanh để hỗ trợ quá trình chốt khách.',
			),
		),
		'team'                => array(
			array(
				'name'        => 'Minh Anh',
				'role'        => 'Frontend Developer',
				'description' => 'Phụ trách trải nghiệm người dùng, tối ưu giao diện đặt tour để mọi thao tác từ xem tour đến thanh toán đều rõ ràng, dễ dùng.',
				'avatar'      => get_theme_file_uri( '/assets/images/team/team-minh.jpg' ),
				'socials'     => array(
					array(
						'network' => 'facebook',
						'url'     => 'https://facebook.com/',
						'label'   => 'Facebook của Minh Anh',
					),
					array(
						'network' => 'linkedin',
						'url'     => 'https://linkedin.com/',
						'label'   => 'LinkedIn của Minh Anh',
					),
				),
			),
			array(
				'name'        => 'Linh Chi',
				'role'        => 'UI/UX Designer',
				'description' => 'Thiết kế visual system theo phong cách du lịch hiện đại, đảm bảo card tour, testimonial và checkout đều có nhịp thở thoáng và nhất quán.',
				'avatar'      => get_theme_file_uri( '/assets/images/team/team-linh.jpg' ),
				'socials'     => array(
					array(
						'network' => 'facebook',
						'url'     => 'https://facebook.com/',
						'label'   => 'Facebook của Linh Chi',
					),
					array(
						'network' => 'linkedin',
						'url'     => 'https://linkedin.com/',
						'label'   => 'LinkedIn của Linh Chi',
					),
				),
			),
			array(
				'name'        => 'Hoàng Sơn',
				'role'        => 'Backend Developer',
				'description' => 'Xử lý dữ liệu tour, luồng đặt chỗ và các form WordPress để website không chỉ đẹp mà còn vận hành ổn định trong quá trình demo và mở rộng.',
				'avatar'      => get_theme_file_uri( '/assets/images/team/team-hoang.jpg' ),
				'socials'     => array(
					array(
						'network' => 'facebook',
						'url'     => 'https://facebook.com/',
						'label'   => 'Facebook của Hoàng Sơn',
					),
					array(
						'network' => 'linkedin',
						'url'     => 'https://linkedin.com/',
						'label'   => 'LinkedIn của Hoàng Sơn',
					),
				),
			),
		),
		'cta_title'           => 'Muốn chuyển bộ khung này thành website tour chạy nội dung thật?',
		'cta_description'     => 'Bạn có thể tiếp tục đổ dữ liệu tour, cấu hình SMTP và sau đó mở rộng sang booking thật mà không phải làm lại toàn bộ phần giao diện.',
	);
}

/**
 * Shared FAQ content for the contact page.
 */
function tam_get_contact_faq_items() {
	return array(
		array(
			'question' => 'Website này đã có booking online đầy đủ chưa?',
			'answer'   => 'Chưa. Bản hiện tại ưu tiên giao diện bán hàng, danh sách tour, chi tiết tour và luồng tư vấn. Cấu trúc đã được chuẩn bị để gắn thêm booking sau.',
		),
		array(
			'question' => 'Sau khi gửi form thì đội ngũ xử lý nhu cầu như thế nào?',
			'answer'   => 'Mọi nhu cầu gửi từ form đều được lưu trong WordPress và chuyển về đội ngũ để liên hệ lại. Khi cần gấp, khách có thể gọi hotline hoặc chat nhanh ngay trên website.',
		),
		array(
			'question' => 'Form có lưu dữ liệu nếu email chưa gửi được không?',
			'answer'   => 'Có. Yêu cầu vẫn được lưu trong WordPress để đội ngũ xử lý lại sau. Nếu chưa cấu hình SMTP, thông báo trên giao diện sẽ cho biết email tự động chưa được gửi.',
		),
		array(
			'question' => 'Có thể đổi hotline, email và kênh chat mà không sửa code không?',
			'answer'   => 'Có. Các thông tin này đang nằm trong Customizer của child theme, nên có thể đổi trực tiếp từ khu vực quản trị.',
		),
	);
}

/**
 * Return a formatted public code for an inquiry post.
 *
 * @param int $post_id Inquiry post ID.
 */
function tam_get_inquiry_reference( $post_id ) {
	return 'YC-' . str_pad( (string) (int) $post_id, 5, '0', STR_PAD_LEFT );
}

/**
 * Create a lightweight inquiry record from a public form.
 *
 * @param array $args Inquiry arguments.
 */
function tam_create_inquiry_request( $args = array() ) {
	$defaults = array(
		'type'          => 'consultation',
		'name'          => '',
		'phone'         => '',
		'email'         => '',
		'tour_interest' => '',
		'message'       => '',
		'source'        => '',
		'status'        => 'new',
	);

	$args       = wp_parse_args( $args, $defaults );
	$title_root = $args['name'] ? $args['name'] : ( $args['email'] ? $args['email'] : __( 'Khách mới', 'travel-agency-modern' ) );
	$title_tail = 'newsletter' === $args['type'] ? __( 'Đăng ký nhận tin', 'travel-agency-modern' ) : ( $args['tour_interest'] ? $args['tour_interest'] : __( 'Yêu cầu tư vấn', 'travel-agency-modern' ) );
	$post_id    = wp_insert_post(
		array(
			'post_type'   => 'tour_inquiry',
			'post_status' => 'publish',
			'post_title'  => $title_root . ' - ' . $title_tail,
		),
		true
	);

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return 0;
	}

	update_post_meta( $post_id, '_tam_inquiry_type', sanitize_key( $args['type'] ) );
	update_post_meta( $post_id, '_tam_inquiry_status', sanitize_key( $args['status'] ) );
	update_post_meta( $post_id, '_tam_inquiry_name', sanitize_text_field( $args['name'] ) );
	update_post_meta( $post_id, '_tam_inquiry_phone', sanitize_text_field( $args['phone'] ) );
	update_post_meta( $post_id, '_tam_inquiry_phone_normalized', tam_normalize_phone( $args['phone'] ) );
	update_post_meta( $post_id, '_tam_inquiry_email', sanitize_email( $args['email'] ) );
	update_post_meta( $post_id, '_tam_inquiry_tour_interest', sanitize_text_field( $args['tour_interest'] ) );
	update_post_meta( $post_id, '_tam_inquiry_message', sanitize_textarea_field( $args['message'] ) );
	update_post_meta( $post_id, '_tam_inquiry_source', esc_url_raw( $args['source'] ) );
	update_post_meta( $post_id, '_tam_inquiry_submitted_at', current_time( 'mysql' ) );

	return (int) $post_id;
}

/**
 * Get a stable front-end URL for a page by slug.
 *
 * @param string $path Page path.
 * @param string $fallback Fallback path.
 */
function tam_get_page_url_by_path( $path, $fallback = '/' ) {
	$page = get_page_by_path( $path );

	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}

	return home_url( $fallback );
}

/**
 * Get the posts page URL.
 */
function tam_get_posts_page_url() {
	$page_for_posts = (int) get_option( 'page_for_posts' );

	if ( $page_for_posts ) {
		return get_permalink( $page_for_posts );
	}

	return home_url( '/cam-nang-du-lich/' );
}

/**
 * Build a stable current front-end URL.
 */
function tam_get_current_public_url() {
	$current_path = '/';

	if ( isset( $GLOBALS['wp'] ) && is_object( $GLOBALS['wp'] ) && isset( $GLOBALS['wp']->request ) ) {
		$current_path = (string) $GLOBALS['wp']->request;
	}

	$current_url = home_url( add_query_arg( array(), $current_path ) );

	if ( is_singular() ) {
		$permalink = get_permalink();

		if ( $permalink ) {
			$current_url = $permalink;
		}
	}

	return $current_url;
}

/**
 * Generate a unique WordPress username from the public registration form.
 *
 * @param string $name Full name.
 * @param string $email Email address.
 */
function tam_generate_unique_username( $name, $email ) {
	$name_slug = sanitize_user( remove_accents( $name ), true );
	$name_slug = preg_replace( '/[\s_]+/', '', (string) $name_slug );
	$email_slug = sanitize_user( strstr( $email, '@', true ), true );
	$base       = $name_slug ? $name_slug : $email_slug;

	if ( ! $base ) {
		$base = 'dnatraveler';
	}

	$username = $base;
	$counter  = 1;

	while ( username_exists( $username ) ) {
		$username = $base . $counter;
		$counter++;
	}

	return $username;
}

/**
 * Return a themed fallback visual for a tour when no featured image exists.
 *
 * @param int $post_id Tour post ID.
 */
function tam_get_tour_fallback_image_url( $post_id ) {
	$slug       = (string) get_post_field( 'post_name', $post_id );
	$candidates = array( $slug );
	$terms      = tam_get_tour_destinations( $post_id );
	$map        = array(
		'ha-giang' => '/assets/images/tours/ha-giang.svg',
		'ban-gioc' => '/assets/images/tours/ha-giang.svg',
		'phu-quoc' => '/assets/images/tours/phu-quoc.svg',
		'da-nang'  => '/assets/images/tours/da-nang.svg',
		'hoi-an'   => '/assets/images/tours/da-nang.svg',
	);

	foreach ( $terms as $term ) {
		$candidates[] = $term->slug;
	}

	foreach ( $candidates as $candidate ) {
		foreach ( $map as $needle => $asset_path ) {
			if ( $candidate && false !== strpos( $candidate, $needle ) ) {
				return get_theme_file_uri( $asset_path );
			}
		}
	}

	return get_theme_file_uri( '/assets/images/tours/default.svg' );
}

/**
 * Get the best available tour visual URL.
 *
 * @param int    $post_id Tour post ID.
 * @param string $size    Image size name.
 */
function tam_get_tour_image_url( $post_id, $size = 'tam-tour-card' ) {
	if ( $post_id && has_post_thumbnail( $post_id ) ) {
		$image_url = get_the_post_thumbnail_url( $post_id, $size );

		if ( $image_url ) {
			return (string) $image_url;
		}
	}

	if ( function_exists( 'tam_backend_api_get_tour_image_for_post' ) ) {
		$api_image_url = tam_backend_api_get_tour_image_for_post( $post_id );

		if ( $api_image_url ) {
			return $api_image_url;
		}
	}

	return tam_get_tour_fallback_image_url( $post_id );
}

/**
 * Get a representative image for a destination term.
 *
 * @param WP_Term|int $term Destination term object or ID.
 */
function tam_get_destination_image_url( $term ) {
	if ( is_numeric( $term ) ) {
		$term = get_term( (int) $term, 'tour_destination' );
	}

	if ( ! ( $term instanceof WP_Term ) ) {
		return get_theme_file_uri( '/assets/images/tours/default.svg' );
	}

	$destination_query = new WP_Query(
		array(
			'post_type'      => 'tour',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => array(
				array(
					'taxonomy' => 'tour_destination',
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				),
			),
		)
	);

	if ( ! empty( $destination_query->posts ) ) {
		return tam_get_tour_image_url( (int) $destination_query->posts[0], 'tam-tour-card' );
	}

	return get_theme_file_uri( '/assets/images/tours/default.svg' );
}

/**
 * Get a safe hero image URL for the current page.
 */
function tam_get_hero_image_url( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	if ( 'tour' === get_post_type( $post_id ) ) {
		return tam_get_tour_image_url( $post_id, 'tam-hero-large' );
	}

	if ( $post_id && has_post_thumbnail( $post_id ) ) {
		return (string) get_the_post_thumbnail_url( $post_id, 'tam-hero-large' );
	}

	$header_image = get_header_image();

	if ( $header_image ) {
		return (string) $header_image;
	}

	return get_parent_theme_file_uri( '/images/banner-img.jpg' );
}

/**
 * Get normalized tour meta.
 *
 * @param int $post_id Tour post ID.
 */
function tam_get_tour_meta( $post_id ) {
	$meta = array();

	foreach ( tam_tour_meta_fields() as $key => $field ) {
		$meta[ $key ] = get_post_meta( $post_id, '_tam_tour_' . $key, true );
	}

	return $meta;
}

/**
 * Format a raw tour price.
 *
 * @param string $price Raw price string.
 */
function tam_format_tour_price( $price ) {
	$price = trim( (string) $price );

	if ( '' === $price ) {
		return __( 'Liên hệ', 'travel-agency-modern' );
	}

	$numeric = preg_replace( '/[^\d]/', '', $price );

	if ( '' !== $numeric ) {
		return number_format_i18n( (int) $numeric ) . 'd';
	}

	return $price;
}

/**
 * Return destination terms for a tour.
 *
 * @param int $post_id Tour post ID.
 */
function tam_get_tour_destinations( $post_id ) {
	$terms = get_the_terms( $post_id, 'tour_destination' );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	return $terms;
}

/**
 * Split textarea lines into a clean array.
 *
 * @param string $value Raw textarea value.
 */
function tam_split_lines( $value ) {
	$value = trim( str_replace( array( "\r\n", "\r" ), "\n", (string) $value ) );

	if ( '' === $value ) {
		return array();
	}

	$lines = array_map( 'trim', explode( "\n", $value ) );

	return array_values(
		array_filter(
			$lines,
			static function ( $line ) {
				return '' !== $line;
			}
		)
	);
}

/**
 * Parse structured textarea rows separated by pipes.
 *
 * @param string $value            Raw textarea value.
 * @param int    $expected_columns Number of expected columns.
 */
function tam_parse_structured_rows( $value, $expected_columns = 3 ) {
	$rows = array();

	foreach ( tam_split_lines( $value ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		$parts = array_pad( $parts, $expected_columns, '' );
		$parts = array_slice( $parts, 0, $expected_columns );

		if ( ! array_filter( $parts, 'strlen' ) ) {
			continue;
		}

		$rows[] = $parts;
	}

	return $rows;
}

/**
 * Infer a visual theme for a tour based on slug and destinations.
 *
 * @param int $post_id Tour post ID.
 */
function tam_get_tour_visual_theme( $post_id ) {
	$candidates = array( (string) get_post_field( 'post_name', $post_id ) );

	foreach ( tam_get_tour_destinations( $post_id ) as $term ) {
		$candidates[] = $term->slug;
	}

	$map = array(
		'coast'    => array( 'phu-quoc', 'nha-trang', 'beach', 'bien' ),
		'mountain' => array( 'ha-giang', 'ban-gioc', 'sapa', 'tay-bac', 'mountain' ),
		'heritage' => array( 'da-nang', 'hoi-an', 'hue', 'mien-trung' ),
		'bay'      => array( 'ha-long', 'cat-ba', 'ninh-binh', 'vinh' ),
	);

	foreach ( $candidates as $candidate ) {
		foreach ( $map as $theme => $needles ) {
			foreach ( $needles as $needle ) {
				if ( $candidate && false !== strpos( $candidate, $needle ) ) {
					return $theme;
				}
			}
		}
	}

	return 'default';
}

/**
 * Photo library for the tour detail gallery fallback.
 */
function tam_get_tour_detail_image_library() {
	return array(
		'coast'    => array(
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/beach-phu-quoc.png' ),
				'alt' => __( 'Bãi biển nước trong xanh cho tour nghỉ dưỡng', 'travel-agency-modern' ),
			),
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/coastal-view.jpg' ),
				'alt' => __( 'Cảnh quan biển và đồi ven biển', 'travel-agency-modern' ),
			),
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/halong-bay.png' ),
				'alt' => __( 'Khoảnh khắc lênh đênh giữa làn nước xanh', 'travel-agency-modern' ),
			),
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/hue-heritage.jpg' ),
				'alt' => __( 'Điểm đến văn hóa trong hành trình miền Trung', 'travel-agency-modern' ),
			),
		),
		'mountain' => array(
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/mountain-sapa.jpg' ),
				'alt' => __( 'Ruộng bậc thang và núi xanh hùng vĩ', 'travel-agency-modern' ),
			),
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/golden-bridge.jpg' ),
				'alt' => __( 'Cầu ngắm cảnh giữa mây núi', 'travel-agency-modern' ),
			),
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/coastal-view.jpg' ),
				'alt' => __( 'Góc nhìn cao rộng trên cung đường du lịch', 'travel-agency-modern' ),
			),
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/halong-bay.png' ),
				'alt' => __( 'Phong cảnh thiên nhiên ấn tượng', 'travel-agency-modern' ),
			),
		),
		'heritage' => array(
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/golden-bridge.jpg' ),
				'alt' => __( 'Điểm check-in nổi bật tại miền Trung', 'travel-agency-modern' ),
			),
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/hue-heritage.jpg' ),
				'alt' => __( 'Di sản văn hóa và kiến trúc đặc trưng', 'travel-agency-modern' ),
			),
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/coastal-view.jpg' ),
				'alt' => __( 'Biển xanh và bầu trời thoáng đãng', 'travel-agency-modern' ),
			),
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/beach-phu-quoc.png' ),
				'alt' => __( 'Khoảnh khắc thư giãn ven biển', 'travel-agency-modern' ),
			),
		),
		'bay'      => array(
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/halong-bay.png' ),
				'alt' => __( 'Kỳ quan vịnh xanh trong nắng đẹp', 'travel-agency-modern' ),
			),
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/beach-phu-quoc.png' ),
				'alt' => __( 'Sóng nhẹ và bãi cát sáng màu', 'travel-agency-modern' ),
			),
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/coastal-view.jpg' ),
				'alt' => __( 'Điểm nhìn ven biển nhiều trải nghiệm', 'travel-agency-modern' ),
			),
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/hue-heritage.jpg' ),
				'alt' => __( 'Thêm chiều sâu văn hóa cho hành trình', 'travel-agency-modern' ),
			),
		),
		'default'  => array(
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/halong-bay.png' ),
				'alt' => __( 'Phong cảnh du lịch nổi bật', 'travel-agency-modern' ),
			),
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/beach-phu-quoc.png' ),
				'alt' => __( 'Khung cảnh biển thư giãn', 'travel-agency-modern' ),
			),
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/mountain-sapa.jpg' ),
				'alt' => __( 'Khung cảnh thiên nhiên xanh mát', 'travel-agency-modern' ),
			),
			array(
				'url' => get_theme_file_uri( '/assets/images/tour-detail/hue-heritage.jpg' ),
				'alt' => __( 'Điểm đến đậm bản sắc văn hóa', 'travel-agency-modern' ),
			),
		),
	);
}

/**
 * Build the gallery images for the tour detail page.
 *
 * @param int $post_id Tour post ID.
 */
function tam_get_tour_gallery_images( $post_id ) {
	$gallery = array();
	$seen    = array();

	$append_image = static function ( $url, $alt = '' ) use ( &$gallery, &$seen ) {
		$url = trim( (string) $url );

		if ( '' === $url || isset( $seen[ $url ] ) ) {
			return;
		}

		$seen[ $url ] = true;
		$gallery[]    = array(
			'url' => $url,
			'alt' => $alt ? $alt : get_bloginfo( 'name' ),
		);
	};

	if ( $post_id && has_post_thumbnail( $post_id ) ) {
		$append_image(
			get_the_post_thumbnail_url( $post_id, 'tam-hero-large' ),
			get_the_title( $post_id )
		);
	}

	$attachment_ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_parent'    => $post_id,
			'post_mime_type' => 'image',
			'posts_per_page' => 6,
			'fields'         => 'ids',
			'orderby'        => 'menu_order ID',
			'order'          => 'ASC',
		)
	);

	foreach ( $attachment_ids as $attachment_id ) {
		$append_image(
			wp_get_attachment_image_url( $attachment_id, 'tam-hero-large' ),
			get_post_meta( $attachment_id, '_wp_attachment_image_alt', true )
		);
	}

	$library          = tam_get_tour_detail_image_library();
	$theme            = tam_get_tour_visual_theme( $post_id );
	$fallback_gallery = isset( $library[ $theme ] ) ? $library[ $theme ] : $library['default'];

	foreach ( $fallback_gallery as $image ) {
		$append_image( $image['url'], $image['alt'] );
	}

	if ( empty( $gallery ) ) {
		$append_image( tam_get_tour_fallback_image_url( $post_id ), get_the_title( $post_id ) );
	}

	return array_slice( $gallery, 0, 5 );
}

/**
 * Get available departure dates for a tour.
 *
 * @param array $tour_meta Normalized tour meta.
 */
function tam_get_tour_departure_options( $tour_meta ) {
	$options = array();

	foreach ( tam_parse_structured_rows( isset( $tour_meta['departure_dates'] ) ? $tour_meta['departure_dates'] : '', 2 ) as $row ) {
		$value = $row[0];
		$label = $row[1];

		if ( '' === $value ) {
			continue;
		}

		if ( '' === $label ) {
			$timestamp = strtotime( $value );
			$label     = $timestamp ? wp_date( 'd/m/Y', $timestamp ) : $value;
		}

		$options[] = array(
			'value' => $value,
			'label' => $label,
		);
	}

	if ( ! empty( $options ) ) {
		return $options;
	}

	$first_departure = strtotime( 'next saturday', current_time( 'timestamp' ) );

	if ( ! $first_departure ) {
		$first_departure = current_time( 'timestamp' );
	}

	for ( $index = 0; $index < 4; $index++ ) {
		$timestamp = strtotime( '+' . ( $index * 7 ) . ' days', $first_departure );

		$options[] = array(
			'value' => wp_date( 'Y-m-d', $timestamp ),
			'label' => sprintf(
				/* translators: %s: departure date */
				__( 'Khởi hành %s', 'travel-agency-modern' ),
				wp_date( 'd/m/Y', $timestamp )
			),
		);
	}

	return $options;
}

/**
 * Return highlights for a tour or a default list.
 *
 * @param int   $post_id   Tour post ID.
 * @param array $tour_meta Normalized tour meta.
 * @param array $terms     Tour destination terms.
 */
function tam_get_tour_highlights( $post_id, $tour_meta, $terms = array() ) {
	$items = tam_split_lines( isset( $tour_meta['highlights'] ) ? $tour_meta['highlights'] : '' );

	if ( ! empty( $items ) ) {
		return $items;
	}

	$destination_name = ! empty( $terms ) ? $terms[0]->name : __( 'điểm đến nổi bật', 'travel-agency-modern' );

	return array(
		sprintf( __( 'Lịch trình gọn gàng, tập trung vào những trải nghiệm đẹp nhất tại %s.', 'travel-agency-modern' ), $destination_name ),
		__( 'Kết hợp tham quan, ăn uống và khoảng nghỉ hợp lý để không bị quá tải.', 'travel-agency-modern' ),
		__( 'Phù hợp cho nhóm bạn, gia đình nhỏ hoặc khách muốn đi tour trọn gói dễ quyết định.', 'travel-agency-modern' ),
		__( 'Đội ngũ tư vấn của ADN Travel hỗ trợ trước chuyến đi và theo sát đến lúc khởi hành.', 'travel-agency-modern' ),
	);
}

/**
 * Build a readable itinerary for the tour detail page.
 *
 * @param int   $post_id   Tour post ID.
 * @param array $tour_meta Normalized tour meta.
 * @param array $terms     Tour destination terms.
 */
function tam_get_tour_itinerary( $post_id, $tour_meta, $terms = array() ) {
	$structured_rows = tam_parse_structured_rows( isset( $tour_meta['itinerary'] ) ? $tour_meta['itinerary'] : '', 3 );

	if ( ! empty( $structured_rows ) ) {
		return array_map(
			static function ( $row ) {
				return array(
					'label'       => $row[0] ? $row[0] : __( 'Day', 'travel-agency-modern' ),
					'title'       => $row[1] ? $row[1] : __( 'Hoạt động trong ngày', 'travel-agency-modern' ),
					'description' => $row[2],
				);
			},
			$structured_rows
		);
	}

	$destination_name = ! empty( $terms ) ? $terms[0]->name : __( 'điểm đến', 'travel-agency-modern' );
	$transport        = ! empty( $tour_meta['transport'] ) ? $tour_meta['transport'] : __( 'xe du lịch', 'travel-agency-modern' );
	$days             = 3;

	if ( ! empty( $tour_meta['duration'] ) && preg_match( '/(\d+)/', $tour_meta['duration'], $matches ) ) {
		$days = max( 2, min( 6, (int) $matches[1] ) );
	}

	$items = array();

	for ( $day = 1; $day <= $days; $day++ ) {
		if ( 1 === $day ) {
			$items[] = array(
				'label'       => 'Day 1',
				'title'       => sprintf( __( 'Khởi hành và chạm nhịp %s', 'travel-agency-modern' ), $destination_name ),
				'description' => sprintf( __( 'Đón khách, di chuyển bằng %1$s, check-in nơi lưu trú và bắt đầu những trải nghiệm đầu tiên trong ngày để cả đoàn làm quen nhịp điệu chuyến đi.', 'travel-agency-modern' ), $transport ),
			);
			continue;
		}

		if ( $day === $days ) {
			$items[] = array(
				'label'       => 'Day ' . $day,
				'title'       => __( 'Thư giãn, mua quà và trở về', 'travel-agency-modern' ),
				'description' => __( 'Buổi sáng tự do chụp ảnh, mua đặc sản hoặc nghỉ ngơi. Sau đó đoàn làm thủ tục trả phòng và quay về điểm đón ban đầu.', 'travel-agency-modern' ),
			);
			continue;
		}

		$items[] = array(
			'label'       => 'Day ' . $day,
			'title'       => __( 'Khám phá điểm nhấn trong hành trình', 'travel-agency-modern' ),
			'description' => __( 'Dành trọn ngày để tham quan cảnh đẹp, thưởng thức ẩm thực địa phương, check-in các điểm nổi bật và giữ lại khoảng nghỉ vừa đủ để lịch trình luôn thoải mái.', 'travel-agency-modern' ),
		);
	}

	return $items;
}

/**
 * Get the include/exclude lists for a tour.
 *
 * @param array  $tour_meta Meta values.
 * @param string $type      List type.
 */
function tam_get_tour_service_list( $tour_meta, $type = 'includes' ) {
	$key   = 'excludes' === $type ? 'excludes' : 'includes';
	$items = tam_split_lines( isset( $tour_meta[ $key ] ) ? $tour_meta[ $key ] : '' );

	if ( ! empty( $items ) ) {
		return $items;
	}

	if ( 'excludes' === $type ) {
		return array(
			__( 'Chi phí cá nhân ngoài chương trình', 'travel-agency-modern' ),
			__( 'Đồ uống ngoài bữa ăn và các dịch vụ tự chọn', 'travel-agency-modern' ),
			__( 'Thuế VAT và tip cho tài xế/hướng dẫn viên nếu phát sinh', 'travel-agency-modern' ),
			__( 'Vé tham quan ngoài danh mục đã thống nhất trước chuyến đi', 'travel-agency-modern' ),
		);
	}

	return array(
		__( 'Xe đưa đón hoặc phương tiện theo đúng lịch trình công bố', 'travel-agency-modern' ),
		__( 'Lưu trú tiêu chuẩn 3-4 sao hoặc tương đương', 'travel-agency-modern' ),
		__( 'Bữa ăn theo chương trình và nước suối trên xe', 'travel-agency-modern' ),
		__( 'Hướng dẫn viên đồng hành và hỗ trợ xuyên suốt chuyến đi', 'travel-agency-modern' ),
		__( 'Bảo hiểm du lịch cơ bản và vé vào cổng các điểm chính', 'travel-agency-modern' ),
	);
}

/**
 * Build review cards for the tour detail page.
 *
 * @param int   $post_id   Tour post ID.
 * @param array $tour_meta Normalized meta.
 */
function tam_get_tour_reviews( $post_id, $tour_meta ) {
	if ( function_exists( 'tam_backend_api_get_reviews_for_post' ) ) {
		$api_reviews = tam_backend_api_get_reviews_for_post( $post_id );

		if ( ! empty( $api_reviews ) ) {
			return $api_reviews;
		}
	}

	$structured_rows = tam_parse_structured_rows( isset( $tour_meta['review_snippets'] ) ? $tour_meta['review_snippets'] : '', 3 );

	if ( ! empty( $structured_rows ) ) {
		return array_map(
			static function ( $row ) {
				return array(
					'name'    => $row[0],
					'rating'  => $row[1] ? $row[1] : '5.0',
					'comment' => $row[2],
				);
			},
			$structured_rows
		);
	}

	$reviews       = tam_get_home_reviews_content();
	$default_items = isset( $reviews['items'] ) ? $reviews['items'] : array();

	return array_map(
		function ( $item ) use ( $post_id ) {
			return array(
				'name'    => $item['name'],
				'rating'  => $item['rating'],
				'comment' => $item['quote'],
				'route'   => get_the_title( $post_id ),
			);
		},
		array_slice( $default_items, 0, 3 )
	);
}

/**
 * Return a default published tour ID for prefilled checkout pages.
 */
function tam_get_default_checkout_tour_id() {
	$featured_ids = get_posts(
		array(
			'post_type'      => 'tour',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_tam_tour_featured',
			'meta_value'     => '1',
			'no_found_rows'  => true,
		)
	);

	if ( ! empty( $featured_ids ) ) {
		return (int) $featured_ids[0];
	}

	$default_ids = get_posts(
		array(
			'post_type'      => 'tour',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	return ! empty( $default_ids ) ? (int) $default_ids[0] : 0;
}

/**
 * Payment options for the checkout page.
 */
function tam_get_checkout_payment_methods() {
	return array(
		'vnpay'   => array(
			'label'       => __( 'VNPay', 'travel-agency-modern' ),
			'description' => __( 'Thanh toan qua cong VNPay voi trai nghiem redirect nhanh, quen thuoc va tin cay.', 'travel-agency-modern' ),
			'icon'        => 'VNPAY',
			'icon_class'  => 'fa-solid fa-wallet',
			'tone'        => '#0f7cff',
			'badge'       => __( 'Pho bien', 'travel-agency-modern' ),
		),
		'momo'    => array(
			'label'       => __( 'MoMo', 'travel-agency-modern' ),
			'description' => __( 'Phu hop mobile-first, thanh toan trong vai thao tac va nhan thong bao nhanh.', 'travel-agency-modern' ),
			'icon'        => 'MOMO',
			'icon_class'  => 'fa-solid fa-mobile-screen-button',
			'tone'        => '#b0006d',
			'badge'       => __( 'Mobile', 'travel-agency-modern' ),
		),
		'zalopay' => array(
			'label'       => __( 'ZaloPay', 'travel-agency-modern' ),
			'description' => __( 'Toi uu cho khach hang tre, thanh toan nhanh va quen thuoc trong he sinh thai Zalo.', 'travel-agency-modern' ),
			'icon'        => 'ZALO',
			'icon_class'  => 'fa-solid fa-bolt',
			'tone'        => '#0068ff',
			'badge'       => __( 'Nhanh', 'travel-agency-modern' ),
		),
		'bank'    => array(
			'label'       => __( 'Chuyen khoan ngan hang', 'travel-agency-modern' ),
			'description' => __( 'Phu hop voi booking gia tri lon, hien thi thong tin tai khoan de doi soat de dang.', 'travel-agency-modern' ),
			'icon'        => 'BANK',
			'icon_class'  => 'fa-solid fa-building-columns',
			'tone'        => '#1a7f64',
			'badge'       => __( 'Doanh nghiep', 'travel-agency-modern' ),
		),
		'card'    => array(
			'label'       => __( 'The quoc te', 'travel-agency-modern' ),
			'description' => __( 'Visa, Mastercard, JCB va cac loai the quoc te khac cho khach nuoc ngoai.', 'travel-agency-modern' ),
			'icon'        => 'CARD',
			'icon_class'  => 'fa-regular fa-credit-card',
			'tone'        => '#6a42ff',
			'badge'       => __( 'Visa / MC', 'travel-agency-modern' ),
		),
	);

	return array(
		'cod'    => array(
			'label'       => __( 'Thanh toán khi đến (COD)', 'travel-agency-modern' ),
			'description' => __( 'Giữ chỗ trước, thanh toán khi gặp nhân viên xác nhận hoặc tại điểm hẹn.', 'travel-agency-modern' ),
			'icon'        => 'COD',
		),
		'bank'   => array(
			'label'       => __( 'Chuyển khoản ngân hàng', 'travel-agency-modern' ),
			'description' => __( 'Giả lập chuyển khoản để đội ngũ gọi lại xác nhận và gửi hướng dẫn chi tiết.', 'travel-agency-modern' ),
			'icon'        => 'BANK',
		),
		'wallet' => array(
			'label'       => __( 'Ví điện tử', 'travel-agency-modern' ),
			'description' => __( 'MoMo, ZaloPay hoặc VNPay. Giao diện hiển thị như một lựa chọn nhanh.', 'travel-agency-modern' ),
			'icon'        => 'WALLET',
		),
	);
}

/**
 * Build the prefilled checkout context from the current request.
 */
function tam_get_checkout_context() {
	$tour_id = isset( $_GET['tour_id'] ) ? absint( wp_unslash( $_GET['tour_id'] ) ) : 0;

	if ( ! $tour_id || 'tour' !== get_post_type( $tour_id ) || 'publish' !== get_post_status( $tour_id ) ) {
		$tour_id = tam_get_default_checkout_tour_id();
	}

	if ( ! $tour_id ) {
		return array(
			'has_tour' => false,
		);
	}

	$tour_meta         = tam_get_tour_meta( $tour_id );
	$destinations      = tam_get_tour_destinations( $tour_id );
	$departure_options = tam_get_tour_departure_options( $tour_meta );
	$requested_people  = isset( $_GET['party_size'] ) ? absint( wp_unslash( $_GET['party_size'] ) ) : 2;
	$people            = max( 1, min( 30, $requested_people ) );
	$requested_children = isset( $_GET['children'] ) ? absint( wp_unslash( $_GET['children'] ) ) : 0;
	$children          = max( 0, min( 20, $requested_children ) );
	$adults            = max( 1, min( 20, $people - $children ) );
	$people            = $adults + $children;
	$selected_date     = isset( $_GET['travel_date'] ) ? sanitize_text_field( wp_unslash( $_GET['travel_date'] ) ) : '';
	$date_lookup       = array();

	foreach ( $departure_options as $option ) {
		$date_lookup[ $option['value'] ] = $option['label'];
	}

	if ( '' === $selected_date && ! empty( $departure_options ) ) {
		$selected_date = $departure_options[0]['value'];
	}

	if ( $selected_date && ! isset( $date_lookup[ $selected_date ] ) ) {
		$date_lookup[ $selected_date ] = $selected_date;
		$departure_options[]           = array(
			'value' => $selected_date,
			'label' => $selected_date,
		);
	}

	$price_raw     = (int) preg_replace( '/[^\d]/', '', (string) $tour_meta['price_from'] );
	$price_display = tam_format_tour_price( $tour_meta['price_from'] );
	$total_raw     = $price_raw > 0 ? $price_raw * $people : 0;
	$api_tour_id   = function_exists( 'tam_backend_api_get_tour_id_for_post' ) ? tam_backend_api_get_tour_id_for_post( $tour_id ) : 0;

	return array(
		'has_tour'          => true,
		'post_id'           => $tour_id,
		'api_tour_id'       => $api_tour_id,
		'can_checkout_api'  => $api_tour_id > 0,
		'title'             => get_the_title( $tour_id ),
		'visual'            => tam_get_tour_image_url( $tour_id, 'tam-tour-card' ),
		'destination'       => ! empty( $destinations ) ? implode( ' • ', wp_list_pluck( $destinations, 'name' ) ) : __( 'Đang cập nhật', 'travel-agency-modern' ),
		'duration'          => ! empty( $tour_meta['duration'] ) ? $tour_meta['duration'] : __( 'Đang cập nhật', 'travel-agency-modern' ),
		'departure_label'   => ! empty( $tour_meta['departure'] ) ? $tour_meta['departure'] : __( 'Liên hệ để chốt điểm đón', 'travel-agency-modern' ),
		'price_raw'         => $price_raw,
		'price_display'     => $price_display,
		'total_raw'         => $total_raw,
		'total_display'     => $total_raw ? tam_format_tour_price( (string) $total_raw ) : $price_display,
		'people'            => $people,
		'adults'            => $adults,
		'children'          => $children,
		'selected_date'     => $selected_date,
		'selected_date_text'=> isset( $date_lookup[ $selected_date ] ) ? $date_lookup[ $selected_date ] : $selected_date,
		'departure_options' => $departure_options,
		'payment_methods'   => tam_get_checkout_payment_methods(),
	);
}

/**
 * Render a shared page hero section.
 *
 * @param array $args Intro arguments.
 */
function tam_render_page_intro( $args = array() ) {
	$defaults = array(
		'eyebrow'     => __( 'Đặt tour du lịch', 'travel-agency-modern' ),
		'title'       => get_the_title(),
		'description' => '',
		'image'       => tam_get_hero_image_url(),
	);

	$args = wp_parse_args( $args, $defaults );
	?>
	<section class="tam-page-hero">
		<div class="tam-page-hero__backdrop" style="background-image:url('<?php echo esc_url( $args['image'] ); ?>');background-size:cover;background-position:center;"></div>
		<div class="tam-container tam-page-hero__content">
			<div class="tam-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></div>
			<h1 class="tam-page-hero__title"><?php echo esc_html( $args['title'] ); ?></h1>
			<?php if ( $args['description'] ) : ?>
				<p class="tam-section-subtitle"><?php echo wp_kses_post( $args['description'] ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Get contact form notice markup based on current URL state.
 */
function tam_get_form_notice_markup() {
	if ( empty( $_GET['form_status'] ) ) {
		return '';
	}

	$status = sanitize_key( wp_unslash( $_GET['form_status'] ) );
	$map    = array(
		'success'       => array(
			'class'   => 'tam-form-notice tam-form-notice--success',
			'message' => __( 'Yêu cầu đã được gửi. Chúng tôi sẽ liên hệ với bạn sớm nhất có thể.', 'travel-agency-modern' ),
		),
		'missing'       => array(
			'class'   => 'tam-form-notice tam-form-notice--error',
			'message' => __( 'Vui lòng điền đầy đủ họ tên, số điện thoại và nội dung cần tư vấn.', 'travel-agency-modern' ),
		),
		'invalid_email' => array(
			'class'   => 'tam-form-notice tam-form-notice--error',
			'message' => __( 'Địa chỉ email chưa hợp lệ. Vui lòng kiểm tra lại.', 'travel-agency-modern' ),
		),
		'invalid_nonce' => array(
			'class'   => 'tam-form-notice tam-form-notice--error',
			'message' => __( 'Phiên gửi form hết hạn. Vui lòng thử lại.', 'travel-agency-modern' ),
		),
		'mail_failed'   => array(
			'class'   => 'tam-form-notice tam-form-notice--error',
			'message' => __( 'Form đã tiếp nhận dữ liệu nhưng máy chủ chưa gửi được email. Hãy cấu hình SMTP để nhận thông báo tự động.', 'travel-agency-modern' ),
		),
	);

	if ( ! isset( $map[ $status ] ) ) {
		return '';
	}

	return sprintf(
		'<div class="%1$s">%2$s</div>',
		esc_attr( $map[ $status ]['class'] ),
		esc_html( $map[ $status ]['message'] )
	);
}

/**
 * Render the shared contact form.
 *
 * @param string $tour_interest Prefilled tour interest value.
 */
function tam_render_contact_form( $tour_interest = '' ) {
	$current_url = home_url( add_query_arg( array(), $GLOBALS['wp']->request ) );

	if ( is_singular( 'tour' ) ) {
		$current_url = trailingslashit( get_permalink() ) . '#tour-inquiry';
	}

	?>
	<div class="tam-contact-form">
		<?php echo wp_kses_post( tam_get_form_notice_markup() ); ?>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<?php wp_nonce_field( 'tam_contact_form', 'tam_contact_nonce' ); ?>
			<input type="hidden" name="action" value="tam_submit_contact_form" />
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( $current_url ); ?>" />
			<div class="tam-contact-form__grid">
				<div class="tam-field">
					<label for="tam-name"><?php esc_html_e( 'Họ tên', 'travel-agency-modern' ); ?></label>
					<input type="text" id="tam-name" name="name" required />
				</div>
				<div class="tam-field">
					<label for="tam-phone"><?php esc_html_e( 'Số điện thoại', 'travel-agency-modern' ); ?></label>
					<input type="text" id="tam-phone" name="phone" required />
				</div>
				<div class="tam-field tam-field--full">
					<label for="tam-email"><?php esc_html_e( 'Email', 'travel-agency-modern' ); ?></label>
					<input type="email" id="tam-email" name="email" />
				</div>
				<div class="tam-field tam-field--full">
					<label for="tam-tour-interest"><?php esc_html_e( 'Tour quan tâm', 'travel-agency-modern' ); ?></label>
					<input type="text" id="tam-tour-interest" name="tour_interest" value="<?php echo esc_attr( $tour_interest ); ?>" />
				</div>
				<div class="tam-field tam-field--full">
					<label for="tam-message"><?php esc_html_e( 'Nội dung cần tư vấn', 'travel-agency-modern' ); ?></label>
					<textarea id="tam-message" name="message" required></textarea>
				</div>
			</div>
			<div style="margin-top:18px;">
				<button type="submit"><?php esc_html_e( 'Gửi yêu cầu tư vấn', 'travel-agency-modern' ); ?></button>
			</div>
		</form>
	</div>
	<?php
}

/**
 * Redirect after form submission with status.
 *
 * @param string $status Status key.
 * @param string $redirect_url Redirect URL.
 */
function tam_redirect_with_status( $status, $redirect_url ) {
	$redirect_url = remove_query_arg( 'form_status', $redirect_url );
	$redirect_url = add_query_arg( 'form_status', $status, $redirect_url );

	wp_safe_redirect( $redirect_url );
	exit;
}

/**
 * Process the contact form submission.
 */
function tam_handle_contact_form() {
	$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : tam_get_page_url_by_path( 'lien-he' );

	if ( ! isset( $_POST['tam_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tam_contact_nonce'] ) ), 'tam_contact_form' ) ) {
		tam_redirect_with_status( 'invalid_nonce', $redirect_to );
	}

	$name          = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$phone         = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$email         = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$tour_interest = isset( $_POST['tour_interest'] ) ? sanitize_text_field( wp_unslash( $_POST['tour_interest'] ) ) : '';
	$message       = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	if ( '' === $name || '' === $phone || '' === $message ) {
		tam_redirect_with_status( 'missing', $redirect_to );
	}

	if ( isset( $_POST['email'] ) && '' !== trim( (string) wp_unslash( $_POST['email'] ) ) && ! is_email( $email ) ) {
		tam_redirect_with_status( 'invalid_email', $redirect_to );
	}

	$contact_details = tam_get_contact_details();
	$recipient       = $contact_details['email'];
	$site_name       = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject         = sprintf( '[%1$s] Yêu cầu tư vấn từ %2$s', $site_name, $name );
	$body            = implode(
		"\n",
		array(
			'Họ tên: ' . $name,
			'Số điện thoại: ' . $phone,
			'Email: ' . ( $email ? $email : __( 'Không cung cấp', 'travel-agency-modern' ) ),
			'Tour quan tâm: ' . ( $tour_interest ? $tour_interest : __( 'Không xác định', 'travel-agency-modern' ) ),
			'Trang gửi: ' . home_url( add_query_arg( array(), $GLOBALS['wp']->request ) ),
			'Nội dung:',
			$message,
		)
	);

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

	if ( $email ) {
		$headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
	}

	$sent = wp_mail( $recipient, $subject, $body, $headers );

	tam_redirect_with_status( $sent ? 'success' : 'mail_failed', $redirect_to );
}
add_action( 'admin_post_nopriv_tam_submit_contact_form', 'tam_handle_contact_form' );
add_action( 'admin_post_tam_submit_contact_form', 'tam_handle_contact_form' );

/**
 * Read a public status notice from the current URL.
 *
 * @param string $query_key Query-string key.
 * @param array  $map Notice configuration.
 */
function tam_get_public_status_notice_markup( $query_key, $map ) {
	if ( empty( $_GET[ $query_key ] ) ) {
		return '';
	}

	$status = sanitize_key( wp_unslash( $_GET[ $query_key ] ) );

	if ( ! isset( $map[ $status ] ) ) {
		return '';
	}

	return sprintf(
		'<div class="%1$s">%2$s</div>',
		esc_attr( $map[ $status ]['class'] ),
		esc_html( $map[ $status ]['message'] )
	);
}

/**
 * Get public contact form notice markup.
 */
function tam_get_public_form_notice_markup() {
	return tam_get_public_status_notice_markup(
		'lead_status',
		array(
			'success'       => array(
				'class'   => 'tam-form-notice tam-form-notice--success',
				'message' => __( 'Yêu cầu đã được gửi. Chúng tôi sẽ liên hệ với bạn sớm nhất có thể.', 'travel-agency-modern' ),
			),
			'saved_no_mail' => array(
				'class'   => 'tam-form-notice tam-form-notice--success',
				'message' => __( 'Yêu cầu đã được lưu thành công. Email tự động chưa gửi được vì máy chủ chưa cấu hình SMTP, nhưng đội ngũ vẫn có thể xử lý từ khu vực quản trị.', 'travel-agency-modern' ),
			),
			'missing'       => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Vui lòng điền đầy đủ họ tên, số điện thoại và nội dung cần tư vấn.', 'travel-agency-modern' ),
			),
			'invalid_email' => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Địa chỉ email chưa hợp lệ. Vui lòng kiểm tra lại.', 'travel-agency-modern' ),
			),
			'invalid_nonce' => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Phiên gửi form đã hết hạn. Vui lòng thử lại.', 'travel-agency-modern' ),
			),
			'mail_failed'   => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Hệ thống chưa gửi được email thông báo. Vui lòng thử lại sau hoặc liên hệ nhanh qua hotline để đội ngũ hỗ trợ ngay.', 'travel-agency-modern' ),
			),
		)
	);
}

/**
 * Get public newsletter notice markup.
 */
function tam_get_public_newsletter_notice_markup() {
	return tam_get_public_status_notice_markup(
		'newsletter_status',
		array(
			'success'       => array(
				'class'   => 'tam-form-notice tam-form-notice--success',
				'message' => __( 'Bạn đã được thêm vào danh sách nhận tin. Chúng tôi sẽ gửi những cập nhật phù hợp nhất khi có lịch khởi hành và ưu đãi mới.', 'travel-agency-modern' ),
			),
			'missing'       => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Vui lòng nhập email để đăng ký nhận tin.', 'travel-agency-modern' ),
			),
			'invalid_email' => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Email chưa hợp lệ. Vui lòng kiểm tra lại trước khi đăng ký.', 'travel-agency-modern' ),
			),
			'invalid_nonce' => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Phiên đăng ký đã hết hạn. Vui lòng thử lại.', 'travel-agency-modern' ),
			),
		)
	);
}

/**
 * Get checkout page notice markup.
 */
function tam_get_checkout_notice_markup() {
	$notice = tam_get_public_status_notice_markup(
		'checkout_status',
		array(
			'success'       => array(
				'class'   => 'tam-form-notice tam-form-notice--success',
				'message' => __( 'Yêu cầu thanh toán đã được ghi nhận. ADN Travel sẽ liên hệ để xác nhận và giữ chỗ cho bạn.', 'travel-agency-modern' ),
			),
			'saved_no_mail' => array(
				'class'   => 'tam-form-notice tam-form-notice--success',
				'message' => __( 'Đơn đặt tour đã được lưu. Email tự động chưa gửi được vì hệ thống mail chưa sẵn sàng, nhưng yêu cầu vẫn có trong quản trị.', 'travel-agency-modern' ),
			),
			'missing'       => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Vui lòng nhập đầy đủ họ tên, email, số điện thoại và thông tin đặt tour.', 'travel-agency-modern' ),
			),
			'invalid_email' => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Email chưa hợp lệ. Vui lòng kiểm tra lại trước khi tiếp tục.', 'travel-agency-modern' ),
			),
			'terms_missing' => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Bạn cần đồng ý với điều khoản trước khi thanh toán.', 'travel-agency-modern' ),
			),
			'invalid_nonce' => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Phiên gửi thanh toán đã hết hạn. Vui lòng thử lại.', 'travel-agency-modern' ),
			),
			'mail_failed'   => array(
				'class'   => 'tam-form-notice tam-form-notice--error',
				'message' => __( 'Hệ thống chưa gửi được email xác nhận. Vui lòng thử lại hoặc liên hệ hotline để được hỗ trợ nhanh.', 'travel-agency-modern' ),
			),
		)
	);

	if ( ! $notice || empty( $_GET['booking_ref'] ) ) {
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
 * Redirect a public form back to its source page with status.
 *
 * @param string $query_key Query-string key.
 * @param string $status Status key.
 * @param string $redirect_url Redirect URL.
 */
function tam_public_redirect_with_status( $query_key, $status, $redirect_url ) {
	$fragment = '';

	if ( false !== strpos( $redirect_url, '#' ) ) {
		list( $redirect_url, $fragment ) = explode( '#', $redirect_url, 2 );
		$fragment = '#' . $fragment;
	}

	$redirect_url = remove_query_arg( $query_key, $redirect_url );
	$redirect_url = add_query_arg( $query_key, $status, $redirect_url );

	wp_safe_redirect( $redirect_url . $fragment );
	exit;
}

/**
 * Render the global auth modal for guests.
 */
function tam_render_auth_modal() {
	if ( is_user_logged_in() || tam_backend_api_is_authenticated() ) {
		return;
	}

	$current_url = tam_get_current_public_url();
	?>
	<div class="tam-auth-modal" data-auth-modal hidden aria-hidden="true">
		<div class="tam-auth-modal__overlay" data-auth-close></div>
		<div class="tam-auth-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tam-auth-modal-title">
			<button class="tam-auth-modal__close" type="button" data-auth-close aria-label="<?php esc_attr_e( 'Đóng cửa sổ đăng nhập', 'travel-agency-modern' ); ?>">
				<i class="fa-solid fa-xmark" aria-hidden="true"></i>
			</button>

			<div class="tam-auth-modal__content">
				<div class="tam-auth-modal__intro">
					<span class="tam-auth-modal__eyebrow"><?php esc_html_e( 'DNA Travel', 'travel-agency-modern' ); ?></span>
					<h2 id="tam-auth-modal-title"><?php esc_html_e( 'Đăng nhập để tiếp tục hành trình', 'travel-agency-modern' ); ?></h2>
					<p><?php esc_html_e( 'Lưu tour yêu thích, nhận ưu đãi mới và đặt chỗ nhanh hơn với tài khoản cá nhân của bạn.', 'travel-agency-modern' ); ?></p>
				</div>

				<div class="tam-auth-modal__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Chọn biểu mẫu đăng nhập hoặc đăng ký', 'travel-agency-modern' ); ?>">
					<button id="tam-auth-tab-login" class="tam-auth-modal__tab is-active" type="button" role="tab" aria-selected="true" aria-controls="tam-auth-panel-login" data-auth-tab="login">
						<?php esc_html_e( 'Đăng nhập', 'travel-agency-modern' ); ?>
					</button>
					<button id="tam-auth-tab-register" class="tam-auth-modal__tab" type="button" role="tab" aria-selected="false" aria-controls="tam-auth-panel-register" data-auth-tab="register">
						<?php esc_html_e( 'Đăng ký', 'travel-agency-modern' ); ?>
					</button>
				</div>

				<div class="tam-auth-modal__viewport">
					<div class="tam-auth-modal__track" data-auth-track>
						<section id="tam-auth-panel-login" class="tam-auth-modal__panel" role="tabpanel" aria-labelledby="tam-auth-tab-login" data-auth-panel="login">
							<form class="tam-auth-form" data-auth-form="login" novalidate>
								<input type="hidden" name="action" value="tam_auth_login" />
								<input type="hidden" name="tam_auth_nonce" value="<?php echo esc_attr( wp_create_nonce( 'tam_auth_login' ) ); ?>" />
								<input type="hidden" name="redirect_to" value="<?php echo esc_url( $current_url ); ?>" />

								<div class="tam-auth-form__message" data-auth-message aria-live="polite"></div>

								<div class="tam-auth-form__field" data-auth-field>
									<label for="tam-login-email"><?php esc_html_e( 'Email', 'travel-agency-modern' ); ?></label>
									<div class="tam-auth-form__control">
										<span class="tam-auth-form__icon"><i class="fa-regular fa-envelope" aria-hidden="true"></i></span>
										<input type="email" id="tam-login-email" name="login_email" placeholder="<?php esc_attr_e( 'ban@email.com', 'travel-agency-modern' ); ?>" autocomplete="email" required />
									</div>
									<p class="tam-auth-form__error" data-auth-error-for="login_email"></p>
								</div>

								<div class="tam-auth-form__field" data-auth-field>
									<label for="tam-login-password"><?php esc_html_e( 'Mật khẩu', 'travel-agency-modern' ); ?></label>
									<div class="tam-auth-form__control">
										<span class="tam-auth-form__icon"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
										<input type="password" id="tam-login-password" name="login_password" placeholder="<?php esc_attr_e( 'Nhập mật khẩu', 'travel-agency-modern' ); ?>" autocomplete="current-password" required />
										<button class="tam-auth-form__toggle" type="button" data-password-toggle aria-label="<?php esc_attr_e( 'Hiện hoặc ẩn mật khẩu', 'travel-agency-modern' ); ?>">
											<i class="fa-regular fa-eye" aria-hidden="true"></i>
										</button>
									</div>
									<p class="tam-auth-form__error" data-auth-error-for="login_password"></p>
								</div>

								<div class="tam-auth-form__meta">
									<label class="tam-auth-form__check">
										<input type="checkbox" name="remember_login" value="1" />
										<span><?php esc_html_e( 'Ghi nhớ đăng nhập', 'travel-agency-modern' ); ?></span>
									</label>
									<a href="<?php echo esc_url( wp_lostpassword_url( $current_url ) ); ?>"><?php esc_html_e( 'Quên mật khẩu?', 'travel-agency-modern' ); ?></a>
								</div>

								<button class="tam-button tam-auth-form__submit" type="submit" data-auth-submit data-loading-text="<?php esc_attr_e( 'Đang đăng nhập...', 'travel-agency-modern' ); ?>">
									<span class="tam-auth-form__spinner" aria-hidden="true"></span>
									<span class="tam-auth-form__submit-label"><?php esc_html_e( 'Đăng nhập', 'travel-agency-modern' ); ?></span>
								</button>

								<p class="tam-auth-form__switch">
									<?php esc_html_e( 'Chưa có tài khoản?', 'travel-agency-modern' ); ?>
									<button type="button" data-auth-tab="register"><?php esc_html_e( 'Tạo tài khoản ngay', 'travel-agency-modern' ); ?></button>
								</p>
							</form>
						</section>

						<section id="tam-auth-panel-register" class="tam-auth-modal__panel" role="tabpanel" aria-labelledby="tam-auth-tab-register" aria-hidden="true" data-auth-panel="register">
							<form class="tam-auth-form" data-auth-form="register" novalidate>
								<input type="hidden" name="action" value="tam_auth_register" />
								<input type="hidden" name="tam_auth_nonce" value="<?php echo esc_attr( wp_create_nonce( 'tam_auth_register' ) ); ?>" />
								<input type="hidden" name="redirect_to" value="<?php echo esc_url( $current_url ); ?>" />

								<div class="tam-auth-form__message" data-auth-message aria-live="polite"></div>

								<div class="tam-auth-form__field" data-auth-field>
									<label for="tam-register-name"><?php esc_html_e( 'Họ tên', 'travel-agency-modern' ); ?></label>
									<div class="tam-auth-form__control">
										<span class="tam-auth-form__icon"><i class="fa-regular fa-user" aria-hidden="true"></i></span>
										<input type="text" id="tam-register-name" name="register_name" placeholder="<?php esc_attr_e( 'Nhập họ tên của bạn', 'travel-agency-modern' ); ?>" autocomplete="name" required />
									</div>
									<p class="tam-auth-form__error" data-auth-error-for="register_name"></p>
								</div>

								<div class="tam-auth-form__field" data-auth-field>
									<label for="tam-register-phone"><?php esc_html_e( 'Số điện thoại', 'travel-agency-modern' ); ?></label>
									<div class="tam-auth-form__control">
										<span class="tam-auth-form__icon"><i class="fa-solid fa-phone" aria-hidden="true"></i></span>
										<input type="text" id="tam-register-phone" name="register_phone" placeholder="<?php esc_attr_e( 'Ví dụ: 0901234567', 'travel-agency-modern' ); ?>" autocomplete="tel" inputmode="tel" required />
									</div>
									<p class="tam-auth-form__error" data-auth-error-for="register_phone"></p>
								</div>

								<div class="tam-auth-form__field" data-auth-field>
									<label for="tam-register-email"><?php esc_html_e( 'Email', 'travel-agency-modern' ); ?></label>
									<div class="tam-auth-form__control">
										<span class="tam-auth-form__icon"><i class="fa-regular fa-envelope" aria-hidden="true"></i></span>
										<input type="email" id="tam-register-email" name="register_email" placeholder="<?php esc_attr_e( 'ban@email.com', 'travel-agency-modern' ); ?>" autocomplete="email" required />
									</div>
									<p class="tam-auth-form__error" data-auth-error-for="register_email"></p>
								</div>

								<div class="tam-auth-form__field" data-auth-field>
									<label for="tam-register-password"><?php esc_html_e( 'Mật khẩu', 'travel-agency-modern' ); ?></label>
									<div class="tam-auth-form__control">
										<span class="tam-auth-form__icon"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
										<input type="password" id="tam-register-password" name="register_password" placeholder="<?php esc_attr_e( 'Tối thiểu 6 ký tự', 'travel-agency-modern' ); ?>" autocomplete="new-password" required />
										<button class="tam-auth-form__toggle" type="button" data-password-toggle aria-label="<?php esc_attr_e( 'Hiện hoặc ẩn mật khẩu', 'travel-agency-modern' ); ?>">
											<i class="fa-regular fa-eye" aria-hidden="true"></i>
										</button>
									</div>
									<p class="tam-auth-form__error" data-auth-error-for="register_password"></p>
								</div>

								<div class="tam-auth-form__field" data-auth-field>
									<label for="tam-register-confirm-password"><?php esc_html_e( 'Xác nhận mật khẩu', 'travel-agency-modern' ); ?></label>
									<div class="tam-auth-form__control">
										<span class="tam-auth-form__icon"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
										<input type="password" id="tam-register-confirm-password" name="register_confirm_password" placeholder="<?php esc_attr_e( 'Nhập lại mật khẩu', 'travel-agency-modern' ); ?>" autocomplete="new-password" required />
										<button class="tam-auth-form__toggle" type="button" data-password-toggle aria-label="<?php esc_attr_e( 'Hiện hoặc ẩn mật khẩu', 'travel-agency-modern' ); ?>">
											<i class="fa-regular fa-eye" aria-hidden="true"></i>
										</button>
									</div>
									<p class="tam-auth-form__error" data-auth-error-for="register_confirm_password"></p>
								</div>

								<button class="tam-button tam-button--accent tam-auth-form__submit" type="submit" data-auth-submit data-loading-text="<?php esc_attr_e( 'Đang tạo tài khoản...', 'travel-agency-modern' ); ?>">
									<span class="tam-auth-form__spinner" aria-hidden="true"></span>
									<span class="tam-auth-form__submit-label"><?php esc_html_e( 'Đăng ký', 'travel-agency-modern' ); ?></span>
								</button>

								<p class="tam-auth-form__switch">
									<?php esc_html_e( 'Đã có tài khoản?', 'travel-agency-modern' ); ?>
									<button type="button" data-auth-tab="login"><?php esc_html_e( 'Quay lại đăng nhập', 'travel-agency-modern' ); ?></button>
								</p>
							</form>
						</section>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'tam_render_auth_modal', 30 );

/**
 * Handle AJAX login from the auth modal.
 */
function tam_handle_auth_login() {
	if ( tam_backend_api_is_authenticated() ) {
		wp_send_json_success(
			array(
				'message'     => __( 'Bạn đã đăng nhập sẵn rồi.', 'travel-agency-modern' ),
				'redirectUrl' => tam_get_current_public_url(),
			)
		);
	}

	$nonce = isset( $_POST['tam_auth_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['tam_auth_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'tam_auth_login' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.', 'travel-agency-modern' ),
			),
			403
		);
	}

	$email       = isset( $_POST['login_email'] ) ? sanitize_email( wp_unslash( $_POST['login_email'] ) ) : '';
	$password    = isset( $_POST['login_password'] ) ? (string) wp_unslash( $_POST['login_password'] ) : '';
	$remember    = ! empty( $_POST['remember_login'] );
	$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : tam_get_current_public_url();
	$errors      = array();

	if ( '' === trim( $email ) ) {
		$errors['login_email'] = __( 'Vui lòng nhập email.', 'travel-agency-modern' );
	} elseif ( ! is_email( $email ) ) {
		$errors['login_email'] = __( 'Email chưa đúng định dạng.', 'travel-agency-modern' );
	}

	if ( '' === $password ) {
		$errors['login_password'] = __( 'Vui lòng nhập mật khẩu.', 'travel-agency-modern' );
	}

	if ( ! empty( $errors ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Vui lòng kiểm tra lại thông tin đăng nhập.', 'travel-agency-modern' ),
				'errors'  => $errors,
			),
			422
		);
	}

	$user = get_user_by( 'email', $email );

	if ( ! $user instanceof WP_User ) {
		wp_send_json_error(
			array(
				'message' => __( 'Email hoặc mật khẩu chưa chính xác.', 'travel-agency-modern' ),
			),
			401
		);
	}

	$signon = wp_signon(
		array(
			'user_login'    => $user->user_login,
			'user_password' => $password,
			'remember'      => $remember,
		),
		is_ssl()
	);

	if ( is_wp_error( $signon ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Email hoặc mật khẩu chưa chính xác.', 'travel-agency-modern' ),
			),
			401
		);
	}

	wp_send_json_success(
		array(
			'message'     => __( 'Đăng nhập thành công. Đang tải lại trang...', 'travel-agency-modern' ),
			'redirectUrl' => $redirect_to ? $redirect_to : tam_get_current_public_url(),
		)
	);
}
add_action( 'wp_ajax_nopriv_tam_auth_login', 'tam_handle_auth_login' );
add_action( 'wp_ajax_tam_auth_login', 'tam_handle_auth_login' );

/**
 * Handle AJAX registration from the auth modal.
 */
function tam_handle_auth_register() {
	if ( tam_backend_api_is_authenticated() ) {
		wp_send_json_success(
			array(
				'message'     => __( 'Tài khoản của bạn đã sẵn sàng.', 'travel-agency-modern' ),
				'redirectUrl' => tam_get_current_public_url(),
			)
		);
	}

	$nonce = isset( $_POST['tam_auth_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['tam_auth_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'tam_auth_register' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.', 'travel-agency-modern' ),
			),
			403
		);
	}

	$name             = isset( $_POST['register_name'] ) ? sanitize_text_field( wp_unslash( $_POST['register_name'] ) ) : '';
	$email            = isset( $_POST['register_email'] ) ? sanitize_email( wp_unslash( $_POST['register_email'] ) ) : '';
	$password         = isset( $_POST['register_password'] ) ? (string) wp_unslash( $_POST['register_password'] ) : '';
	$confirm_password = isset( $_POST['register_confirm_password'] ) ? (string) wp_unslash( $_POST['register_confirm_password'] ) : '';
	$redirect_to      = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : tam_get_current_public_url();
	$errors           = array();

	if ( '' === trim( $name ) ) {
		$errors['register_name'] = __( 'Vui lòng nhập họ tên.', 'travel-agency-modern' );
	}

	if ( '' === trim( $email ) ) {
		$errors['register_email'] = __( 'Vui lòng nhập email.', 'travel-agency-modern' );
	} elseif ( ! is_email( $email ) ) {
		$errors['register_email'] = __( 'Email chưa đúng định dạng.', 'travel-agency-modern' );
	} elseif ( email_exists( $email ) ) {
		$errors['register_email'] = __( 'Email này đã được sử dụng.', 'travel-agency-modern' );
	}

	if ( '' === $password ) {
		$errors['register_password'] = __( 'Vui lòng nhập mật khẩu.', 'travel-agency-modern' );
	} elseif ( strlen( $password ) < 6 ) {
		$errors['register_password'] = __( 'Mật khẩu cần tối thiểu 6 ký tự.', 'travel-agency-modern' );
	}

	if ( '' === $confirm_password ) {
		$errors['register_confirm_password'] = __( 'Vui lòng xác nhận mật khẩu.', 'travel-agency-modern' );
	} elseif ( $password !== $confirm_password ) {
		$errors['register_confirm_password'] = __( 'Mật khẩu xác nhận chưa khớp.', 'travel-agency-modern' );
	}

	if ( ! empty( $errors ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Vui lòng kiểm tra lại thông tin đăng ký.', 'travel-agency-modern' ),
				'errors'  => $errors,
			),
			422
		);
	}

	$username = tam_generate_unique_username( $name, $email );
	$user_id  = wp_create_user( $username, $password, $email );

	if ( is_wp_error( $user_id ) ) {
		wp_send_json_error(
			array(
				'message' => $user_id->get_error_message(),
			),
			400
		);
	}

	wp_update_user(
		array(
			'ID'           => $user_id,
			'display_name' => $name,
			'first_name'   => $name,
			'nickname'     => $name,
		)
	);

	$signon = wp_signon(
		array(
			'user_login'    => $username,
			'user_password' => $password,
			'remember'      => true,
		),
		is_ssl()
	);

	if ( is_wp_error( $signon ) ) {
		wp_send_json_success(
			array(
				'message'     => __( 'Tạo tài khoản thành công. Bạn có thể đăng nhập ngay bây giờ.', 'travel-agency-modern' ),
				'switchTab'   => 'login',
				'prefillEmail' => $email,
			)
		);
	}

	wp_send_json_success(
		array(
			'message'     => __( 'Tạo tài khoản thành công. Đang đăng nhập...', 'travel-agency-modern' ),
			'redirectUrl' => $redirect_to ? $redirect_to : tam_get_current_public_url(),
		)
	);
}
add_action( 'wp_ajax_nopriv_tam_auth_register', 'tam_handle_auth_register' );
add_action( 'wp_ajax_tam_auth_register', 'tam_handle_auth_register' );

/**
 * Render the new public-facing contact form.
 *
 * @param string $tour_interest Prefilled tour interest value.
 */
function tam_render_contact_capture_form( $tour_interest = '' ) {
	$current_url = home_url( add_query_arg( array(), $GLOBALS['wp']->request ) );

	if ( is_singular( 'tour' ) ) {
		$current_url = trailingslashit( get_permalink() ) . '#tour-inquiry';
	}

	?>
	<div class="tam-contact-form">
		<?php echo wp_kses_post( tam_get_public_form_notice_markup() ); ?>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<?php wp_nonce_field( 'tam_contact_capture_form', 'tam_contact_capture_nonce' ); ?>
			<input type="hidden" name="action" value="tam_submit_contact_capture_form" />
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( $current_url ); ?>" />
			<input type="hidden" id="tam-lead-preferred-date" name="preferred_date" value="" data-tour-inquiry-date />
			<input type="hidden" id="tam-lead-party-size" name="party_size" value="" data-tour-inquiry-party />
			<div class="tam-contact-form__grid">
				<div class="tam-field">
					<label for="tam-lead-name"><?php esc_html_e( 'Họ tên', 'travel-agency-modern' ); ?></label>
					<input type="text" id="tam-lead-name" name="name" required />
				</div>
				<div class="tam-field">
					<label for="tam-lead-phone"><?php esc_html_e( 'Số điện thoại', 'travel-agency-modern' ); ?></label>
					<input type="text" id="tam-lead-phone" name="phone" required />
				</div>
				<div class="tam-field tam-field--full">
					<label for="tam-lead-email"><?php esc_html_e( 'Email', 'travel-agency-modern' ); ?></label>
					<input type="email" id="tam-lead-email" name="email" />
				</div>
				<div class="tam-field tam-field--full">
					<label for="tam-lead-tour-interest"><?php esc_html_e( 'Tour quan tâm', 'travel-agency-modern' ); ?></label>
					<input type="text" id="tam-lead-tour-interest" name="tour_interest" value="<?php echo esc_attr( $tour_interest ); ?>" data-tour-interest-field />
				</div>
				<div class="tam-field tam-field--full">
					<label for="tam-lead-message"><?php esc_html_e( 'Nội dung cần tư vấn', 'travel-agency-modern' ); ?></label>
					<textarea id="tam-lead-message" name="message" required data-tour-inquiry-message></textarea>
				</div>
			</div>
			<div style="margin-top:18px;">
				<button type="submit"><?php esc_html_e( 'Gửi yêu cầu tư vấn', 'travel-agency-modern' ); ?></button>
			</div>
		</form>
	</div>
	<?php
}

/**
 * Render the public newsletter signup form.
 *
 * @param string $source Source identifier.
 */
function tam_render_newsletter_capture_form( $source = 'website' ) {
	$current_url = home_url( add_query_arg( array(), $GLOBALS['wp']->request ) ) . '#newsletter-signup';
	?>
	<div class="tam-newsletter-form-wrap">
		<?php echo wp_kses_post( tam_get_public_newsletter_notice_markup() ); ?>
		<form class="tam-newsletter-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<?php wp_nonce_field( 'tam_newsletter_capture_form', 'tam_newsletter_capture_nonce' ); ?>
			<input type="hidden" name="action" value="tam_submit_newsletter_capture_form" />
			<input type="hidden" name="source" value="<?php echo esc_attr( $source ); ?>" />
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( $current_url ); ?>" />
			<label class="screen-reader-text" for="tam-newsletter-capture-email"><?php esc_html_e( 'Email nhận tin', 'travel-agency-modern' ); ?></label>
			<input type="email" id="tam-newsletter-capture-email" name="email" placeholder="<?php esc_attr_e( 'Email của bạn', 'travel-agency-modern' ); ?>" required />
			<button type="submit"><?php esc_html_e( 'Đăng ký ngay', 'travel-agency-modern' ); ?></button>
		</form>
	</div>
	<?php
}

/**
 * Process the public contact capture form.
 */
function tam_handle_contact_capture_form() {
	$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : tam_get_page_url_by_path( 'lien-he' );

	if ( ! isset( $_POST['tam_contact_capture_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tam_contact_capture_nonce'] ) ), 'tam_contact_capture_form' ) ) {
		tam_public_redirect_with_status( 'lead_status', 'invalid_nonce', $redirect_to );
	}

	$name          = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$phone         = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$email         = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$tour_interest = isset( $_POST['tour_interest'] ) ? sanitize_text_field( wp_unslash( $_POST['tour_interest'] ) ) : '';
	$message       = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
	$preferred_date = isset( $_POST['preferred_date'] ) ? sanitize_text_field( wp_unslash( $_POST['preferred_date'] ) ) : '';
	$party_size     = isset( $_POST['party_size'] ) ? absint( wp_unslash( $_POST['party_size'] ) ) : 0;
	$current_page  = home_url( add_query_arg( array(), $GLOBALS['wp']->request ) );
	$booking_context = array();

	if ( '' !== $preferred_date ) {
		$booking_context[] = 'Ngày khởi hành mong muốn: ' . $preferred_date;
	}

	if ( $party_size > 0 ) {
		$booking_context[] = 'Số lượng khách: ' . $party_size;
	}

	$composed_message = $message;

	if ( ! empty( $booking_context ) ) {
		$composed_message .= "\n\n" . implode( "\n", $booking_context );
	}

	if ( '' === $name || '' === $phone || '' === $message ) {
		tam_public_redirect_with_status( 'lead_status', 'missing', $redirect_to );
	}

	if ( isset( $_POST['email'] ) && '' !== trim( (string) wp_unslash( $_POST['email'] ) ) && ! is_email( $email ) ) {
		tam_public_redirect_with_status( 'lead_status', 'invalid_email', $redirect_to );
	}

	$inquiry_id = tam_create_inquiry_request(
		array(
			'type'          => 'consultation',
			'name'          => $name,
			'phone'         => $phone,
			'email'         => $email,
			'tour_interest' => $tour_interest,
			'message'       => $composed_message,
			'source'        => $current_page,
			'status'        => 'new',
		)
	);

	$contact_details = tam_get_contact_details();
	$recipient       = $contact_details['email'];
	$site_name       = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject         = sprintf( '[%1$s] Yêu cầu tư vấn từ %2$s', $site_name, $name );
	$body            = implode(
		"\n",
		array(
			'Họ tên: ' . $name,
			'Số điện thoại: ' . $phone,
			'Email: ' . ( $email ? $email : __( 'Không cung cấp', 'travel-agency-modern' ) ),
			'Tour quan tâm: ' . ( $tour_interest ? $tour_interest : __( 'Không xác định', 'travel-agency-modern' ) ),
			'Ngày khởi hành mong muốn: ' . ( $preferred_date ? $preferred_date : __( 'Chưa chọn', 'travel-agency-modern' ) ),
			'Số lượng khách: ' . ( $party_size ? $party_size : __( 'Chưa chọn', 'travel-agency-modern' ) ),
			'Mã yêu cầu: ' . ( $inquiry_id ? tam_get_inquiry_reference( $inquiry_id ) : __( 'Chưa tạo được', 'travel-agency-modern' ) ),
			'Trang gửi: ' . $current_page,
			'Nội dung:',
			$composed_message,
		)
	);

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

	if ( $email ) {
		$headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
	}

	$sent = wp_mail( $recipient, $subject, $body, $headers );

	if ( $sent ) {
		tam_public_redirect_with_status( 'lead_status', 'success', $redirect_to );
	}

	tam_public_redirect_with_status( 'lead_status', $inquiry_id ? 'saved_no_mail' : 'mail_failed', $redirect_to );
}
add_action( 'admin_post_nopriv_tam_submit_contact_capture_form', 'tam_handle_contact_capture_form' );
add_action( 'admin_post_tam_submit_contact_capture_form', 'tam_handle_contact_capture_form' );

/**
 * Process the checkout form submission.
 */
function tam_handle_checkout_form() {
	$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : tam_get_page_url_by_path( 'thanh-toan', '/thanh-toan/' );

	if ( ! isset( $_POST['tam_checkout_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tam_checkout_nonce'] ) ), 'tam_checkout_form' ) ) {
		tam_public_redirect_with_status( 'checkout_status', 'invalid_nonce', $redirect_to );
	}

	$name           = isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '';
	$email          = isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : '';
	$phone          = isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '';
	$note           = isset( $_POST['customer_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['customer_note'] ) ) : '';
	$tour_id        = isset( $_POST['tour_id'] ) ? absint( wp_unslash( $_POST['tour_id'] ) ) : 0;
	$tour_title     = isset( $_POST['tour_title'] ) ? sanitize_text_field( wp_unslash( $_POST['tour_title'] ) ) : '';
	$departure_date = isset( $_POST['departure_date'] ) ? sanitize_text_field( wp_unslash( $_POST['departure_date'] ) ) : '';
	$people         = isset( $_POST['people'] ) ? absint( wp_unslash( $_POST['people'] ) ) : 0;
	$payment_method = isset( $_POST['payment_method'] ) ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : 'cod';
	$accepted_terms = ! empty( $_POST['accept_terms'] );
	$payment_methods = tam_get_checkout_payment_methods();

	if ( '' === $name || '' === $email || '' === $phone || '' === $tour_title || '' === $departure_date || $people < 1 ) {
		tam_public_redirect_with_status( 'checkout_status', 'missing', $redirect_to );
	}

	if ( ! is_email( $email ) ) {
		tam_public_redirect_with_status( 'checkout_status', 'invalid_email', $redirect_to );
	}

	if ( ! $accepted_terms ) {
		tam_public_redirect_with_status( 'checkout_status', 'terms_missing', $redirect_to );
	}

	if ( ! isset( $payment_methods[ $payment_method ] ) ) {
		$payment_method = 'cod';
	}

	$actual_price = 0;

	if ( $tour_id && 'tour' === get_post_type( $tour_id ) ) {
		$meta         = tam_get_tour_meta( $tour_id );
		$actual_price = (int) preg_replace( '/[^\d]/', '', (string) $meta['price_from'] );
		$tour_title   = get_the_title( $tour_id );
	}

	$total_price = $actual_price > 0 ? $actual_price * $people : 0;
	$message     = implode(
		"\n",
		array_filter(
			array(
				'Khách đặt tour: ' . $name,
				'Email: ' . $email,
				'Số điện thoại: ' . $phone,
				'Tour: ' . $tour_title,
				'Ngày khởi hành: ' . $departure_date,
				'Số lượng người: ' . $people,
				'Giá mỗi người: ' . ( $actual_price ? tam_format_tour_price( (string) $actual_price ) : __( 'Liên hệ', 'travel-agency-modern' ) ),
				'Tổng tiền dự kiến: ' . ( $total_price ? tam_format_tour_price( (string) $total_price ) : __( 'Liên hệ', 'travel-agency-modern' ) ),
				'Phương thức thanh toán: ' . $payment_methods[ $payment_method ]['label'],
				$note ? 'Ghi chú: ' . $note : '',
			)
		)
	);

	$inquiry_id = tam_create_inquiry_request(
		array(
			'type'          => 'booking',
			'name'          => $name,
			'phone'         => $phone,
			'email'         => $email,
			'tour_interest' => $tour_title,
			'message'       => $message,
			'source'        => $redirect_to,
			'status'        => 'new',
		)
	);

	$contact_details = tam_get_contact_details();
	$recipient       = $contact_details['email'];
	$site_name       = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject         = sprintf( '[%1$s] Đơn thanh toán từ %2$s', $site_name, $name );
	$headers         = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);
	$sent            = wp_mail( $recipient, $subject, $message, $headers );
	$booking_ref     = $inquiry_id ? tam_get_inquiry_reference( $inquiry_id ) : '';
	$status          = $sent ? 'success' : ( $inquiry_id ? 'saved_no_mail' : 'mail_failed' );
	$fragment        = '';

	if ( false !== strpos( $redirect_to, '#' ) ) {
		list( $redirect_to, $fragment ) = explode( '#', $redirect_to, 2 );
		$fragment = '#' . $fragment;
	}

	$redirect_to = remove_query_arg( array( 'checkout_status', 'booking_ref' ), $redirect_to );
	$redirect_to = add_query_arg(
		array(
			'checkout_status' => $status,
			'booking_ref'     => $booking_ref,
		),
		$redirect_to
	);

	wp_safe_redirect( $redirect_to . $fragment );
	exit;
}
add_action( 'admin_post_nopriv_tam_submit_checkout_form', 'tam_handle_checkout_form' );
add_action( 'admin_post_tam_submit_checkout_form', 'tam_handle_checkout_form' );

/**
 * Process the newsletter capture form.
 */
function tam_handle_newsletter_capture_form() {
	$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/' );

	if ( ! isset( $_POST['tam_newsletter_capture_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tam_newsletter_capture_nonce'] ) ), 'tam_newsletter_capture_form' ) ) {
		tam_public_redirect_with_status( 'newsletter_status', 'invalid_nonce', $redirect_to );
	}

	$email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : 'website';

	if ( '' === trim( (string) $email ) ) {
		tam_public_redirect_with_status( 'newsletter_status', 'missing', $redirect_to );
	}

	if ( ! is_email( $email ) ) {
		tam_public_redirect_with_status( 'newsletter_status', 'invalid_email', $redirect_to );
	}

	tam_create_inquiry_request(
		array(
			'type'    => 'newsletter',
			'email'   => $email,
			'message' => 'Nguồn đăng ký: ' . $source,
			'source'  => $source,
			'status'  => 'new',
		)
	);

	tam_public_redirect_with_status( 'newsletter_status', 'success', $redirect_to );
}
add_action( 'admin_post_nopriv_tam_submit_newsletter_capture_form', 'tam_handle_newsletter_capture_form' );
add_action( 'admin_post_tam_submit_newsletter_capture_form', 'tam_handle_newsletter_capture_form' );

/**
 * Build query args for the public tours listing.
 *
 * @param string $search_term   Search term from the public filter.
 * @param string $selected_dest Selected destination slug.
 * @param int    $paged         Current page.
 * @return array<string, mixed>
 */
function tam_get_tour_query_args( $search_term = '', $selected_dest = '', $paged = 1 ) {
	$query_args = array(
		'post_type'      => 'tour',
		'post_status'    => 'publish',
		'posts_per_page' => 10,
		'paged'          => max( 1, (int) $paged ),
		's'              => sanitize_text_field( $search_term ),
	);

	$selected_dest = sanitize_title( $selected_dest );

	if ( '' !== $selected_dest ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'tour_destination',
				'field'    => 'slug',
				'terms'    => $selected_dest,
			),
		);
	}

	return $query_args;
}

/**
 * Build the public tours results section markup.
 *
 * @param WP_Query $tour_query Query object with tour posts.
 * @param array    $args       Rendering context.
 * @return string
 */
function tam_get_tour_results_markup( $tour_query, $args = array() ) {
	if ( ! $tour_query instanceof WP_Query ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'search_term'   => '',
			'selected_dest' => '',
			'base_url'      => tam_get_page_url_by_path( 'tour' ),
			'current_page'  => 1,
		)
	);

	$results_label = sprintf(
		/* translators: %s is the number of matching tours. */
		_n( '%s tour phù hợp', '%s tour phù hợp', $tour_query->found_posts, 'travel-agency-modern' ),
		number_format_i18n( $tour_query->found_posts )
	);

	ob_start();

	if ( $tour_query->have_posts() ) :
		?>
		<div class="tam-section-head tam-section-head--results">
			<div>
				<div class="tam-eyebrow"><?php esc_html_e( 'Kết quả hiện có', 'travel-agency-modern' ); ?></div>
				<h2 class="tam-section-title"><?php echo esc_html( $results_label ); ?></h2>
				<p class="tam-section-subtitle"><?php esc_html_e( 'Danh sách tour được cập nhật ngay khi bạn nhập từ khóa hoặc đổi điểm đến để trải nghiệm tìm kiếm liền mạch hơn.', 'travel-agency-modern' ); ?></p>
			</div>
		</div>
		<div class="tam-tour-grid">
			<?php
			while ( $tour_query->have_posts() ) :
				$tour_query->the_post();
				get_template_part( 'template-parts/tour-card' );
			endwhile;
			?>
		</div>
		<?php
		tam_render_pagination(
			$tour_query,
			array(
				'add_args' => array_filter(
					array(
						'search_tour' => sanitize_text_field( $args['search_term'] ),
						'destination' => sanitize_title( $args['selected_dest'] ),
					)
				),
				'base_url' => esc_url_raw( $args['base_url'] ),
				'current'  => max( 1, (int) $args['current_page'] ),
			)
		);
	else :
		?>
		<div class="tam-empty-state">
			<strong><?php esc_html_e( 'Không tìm thấy tour phù hợp', 'travel-agency-modern' ); ?></strong>
			<p><?php esc_html_e( 'Hãy thử đổi từ khóa hoặc xóa bộ lọc điểm đến để xem thêm hành trình khác.', 'travel-agency-modern' ); ?></p>
		</div>
		<?php
	endif;

	wp_reset_postdata();

	return trim( ob_get_clean() );
}

/**
 * Render pagination for a WP_Query instance.
 *
 * @param WP_Query|null $query   The query to paginate.
 * @param array         $options Optional render options.
 */
function tam_render_pagination( $query = null, $options = array() ) {
	$query = $query instanceof WP_Query ? $query : $GLOBALS['wp_query'];

	if ( ! $query instanceof WP_Query || $query->max_num_pages < 2 ) {
		return;
	}

	$options = wp_parse_args(
		$options,
		array(
			'add_args' => array(),
			'base_url' => '',
			'current'  => 0,
		)
	);

	$current  = ! empty( $options['current'] ) ? max( 1, (int) $options['current'] ) : max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	$big      = 999999999;
	$add_args = is_array( $options['add_args'] ) ? $options['add_args'] : array();

	if ( empty( $add_args ) ) {
		if ( isset( $_GET['search_tour'] ) && '' !== $_GET['search_tour'] ) {
			$add_args['search_tour'] = sanitize_text_field( wp_unslash( $_GET['search_tour'] ) );
		}

		if ( isset( $_GET['destination'] ) && '' !== $_GET['destination'] ) {
			$add_args['destination'] = sanitize_text_field( wp_unslash( $_GET['destination'] ) );
		}
	}

	$base = str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) );

	if ( ! empty( $options['base_url'] ) ) {
		$base = str_replace( $big, '%#%', esc_url( add_query_arg( 'paged', $big, $options['base_url'] ) ) );
	}

	$links = paginate_links(
		array(
			'base'      => $base,
			'format'    => '',
			'current'   => $current,
			'total'     => (int) $query->max_num_pages,
			'type'      => 'list',
		'prev_text' => __( 'Trước', 'travel-agency-modern' ),
		'next_text' => __( 'Sau', 'travel-agency-modern' ),
			'add_args'  => $add_args,
		)
	);

	if ( $links ) {
		echo '<nav class="tam-pagination" aria-label="' . esc_attr__( 'Phân trang', 'travel-agency-modern' ) . '">' . wp_kses_post( $links ) . '</nav>';
	}
}
/**
 * Return live tour results for the public filters.
 */
function tam_handle_tour_filter_request() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'tam_tour_filter' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Phiên tìm kiếm đã hết hạn. Vui lòng tải lại trang và thử lại.', 'travel-agency-modern' ),
			),
			403
		);
	}

	$search_term   = isset( $_POST['search_tour'] ) ? sanitize_text_field( wp_unslash( $_POST['search_tour'] ) ) : '';
	$selected_dest = isset( $_POST['destination'] ) ? sanitize_title( wp_unslash( $_POST['destination'] ) ) : '';
	$current_page  = isset( $_POST['paged'] ) ? max( 1, absint( wp_unslash( $_POST['paged'] ) ) ) : 1;
	$base_url      = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : tam_get_page_url_by_path( 'tour' );

	if ( function_exists( 'tam_backend_api_get_tour_archive_payload' ) ) {
		$backend_payload = tam_backend_api_get_tour_archive_payload( $search_term, $selected_dest, $current_page, $base_url );

		if ( ! empty( $backend_payload['success'] ) ) {
			wp_send_json_success(
				array(
					'html'        => isset( $backend_payload['html'] ) ? $backend_payload['html'] : '',
					'foundPosts'  => isset( $backend_payload['found_posts'] ) ? (int) $backend_payload['found_posts'] : 0,
					'currentPage' => isset( $backend_payload['current_page'] ) ? (int) $backend_payload['current_page'] : $current_page,
					'summary'     => isset( $backend_payload['summary'] ) ? $backend_payload['summary'] : '',
				)
			);
		}
	}

	$tour_query    = new WP_Query( tam_get_tour_query_args( $search_term, $selected_dest, $current_page ) );
	$found_posts   = (int) $tour_query->found_posts;

	wp_send_json_success(
		array(
			'html'        => tam_get_tour_results_markup(
				$tour_query,
				array(
					'search_term'   => $search_term,
					'selected_dest' => $selected_dest,
					'base_url'      => $base_url,
					'current_page'  => $current_page,
				)
			),
			'foundPosts'  => $found_posts,
			'currentPage' => $current_page,
			'summary'     => $found_posts
				? sprintf(
					/* translators: %s is the number of matching tours. */
					_n( '%s tour phù hợp', '%s tour phù hợp', $found_posts, 'travel-agency-modern' ),
					number_format_i18n( $found_posts )
				)
				: __( 'Không tìm thấy tour phù hợp', 'travel-agency-modern' ),
		)
	);
}
add_action( 'wp_ajax_nopriv_tam_filter_tours', 'tam_handle_tour_filter_request' );
add_action( 'wp_ajax_tam_filter_tours', 'tam_handle_tour_filter_request' );

