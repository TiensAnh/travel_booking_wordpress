<?php
/**
 * Single tour template.
 */

get_header();

while ( have_posts() ) :
	the_post();

	$tour_meta         = tam_get_tour_meta( get_the_ID() );
	$destinations      = tam_get_tour_destinations( get_the_ID() );
	$contact           = tam_get_contact_details();
	$gallery_images    = tam_get_tour_gallery_images( get_the_ID() );
	$departure_options = tam_get_tour_departure_options( $tour_meta );
	$highlights        = tam_get_tour_highlights( get_the_ID(), $tour_meta, $destinations );
	$itinerary         = tam_get_tour_itinerary( get_the_ID(), $tour_meta, $destinations );
	$includes          = tam_get_tour_service_list( $tour_meta, 'includes' );
	$excludes          = tam_get_tour_service_list( $tour_meta, 'excludes' );
	$reviews           = tam_get_tour_reviews( get_the_ID(), $tour_meta );
	$primary_term      = ! empty( $destinations ) ? $destinations[0] : null;
	$destination_names = ! empty( $destinations ) ? implode( ' • ', wp_list_pluck( $destinations, 'name' ) ) : __( 'Đang cập nhật', 'travel-agency-modern' );
	// Phuong an C: uu tien backend description day du, fallback sang WP excerpt/content.
	$api_description = function_exists( 'tam_backend_api_get_tour_id_for_post' ) && tam_backend_api_get_tour_id_for_post( get_the_ID() ) > 0
		? trim( (string) get_post_meta( get_the_ID(), '_tam_api_description', true ) )
		: '';
	$intro_text = $api_description !== ''
		? $api_description
		: ( has_excerpt() ? get_the_excerpt() : wp_strip_all_tags( get_post_field( 'post_content', get_the_ID() ) ) );
	// Rating lấy từ backend API (average_rating, total_reviews) — không hardcode.
	$api_tour_id       = function_exists( 'tam_backend_api_get_tour_id_for_post' ) ? tam_backend_api_get_tour_id_for_post( get_the_ID() ) : 0;
	$api_average_rating = $api_tour_id > 0 ? (float) get_post_meta( get_the_ID(), '_tam_api_average_rating', true ) : 0;
	$api_total_reviews  = $api_tour_id > 0 ? (int) get_post_meta( get_the_ID(), '_tam_api_total_reviews', true ) : 0;
	$rating_value   = $api_average_rating > 0 ? max( 1, min( 5, $api_average_rating ) ) : 0;
	$rating_display = $rating_value > 0 ? number_format_i18n( $rating_value, 1 ) : '';
	$review_count   = $rating_value > 0 ? $api_total_reviews : 0;
	$has_reviews     = $rating_value > 0 && $review_count > 0;
	$has_departure_options = ! empty( $departure_options );
	$price_numeric     = (int) preg_replace( '/[^\d]/', '', (string) $tour_meta['price_from'] );
	$price_display     = tam_format_tour_price( $tour_meta['price_from'] );
	$duration_label    = ! empty( $tour_meta['duration'] ) ? $tour_meta['duration'] : __( 'Đang cập nhật', 'travel-agency-modern' );
	$departure_label   = ! empty( $tour_meta['departure'] ) ? $tour_meta['departure'] : __( 'Liên hệ để chốt điểm đón', 'travel-agency-modern' );
	$group_size_label  = ! empty( $tour_meta['group_size'] ) ? $tour_meta['group_size'] : __( 'Linh hoạt theo đoàn', 'travel-agency-modern' );
	$season_label      = ! empty( $tour_meta['season'] ) ? $tour_meta['season'] : __( 'Quanh năm', 'travel-agency-modern' );
	$transport_label   = ! empty( $tour_meta['transport'] ) ? $tour_meta['transport'] : __( 'Theo lịch trình', 'travel-agency-modern' );
	$main_visual       = ! empty( $gallery_images ) ? $gallery_images[0] : array(
		'url' => tam_get_tour_image_url( get_the_ID(), 'tam-hero-large' ),
		'alt' => get_the_title(),
	);
	$content_available = '' !== trim( wp_strip_all_tags( get_post_field( 'post_content', get_the_ID() ) ) );
	$related_args      = array(
		'post_type'      => 'tour',
		'post_status'    => 'publish',
		'posts_per_page' => 3,
		'post__not_in'   => array( get_the_ID() ),
	);

	if ( $primary_term instanceof WP_Term ) {
		$related_args['tax_query'] = array(
			array(
				'taxonomy' => 'tour_destination',
				'field'    => 'term_id',
				'terms'    => $primary_term->term_id,
			),
		);
	}

	$related_tours = new WP_Query( $related_args );
	$account_url   = function_exists( 'tam_backend_api_get_account_url' ) ? tam_backend_api_get_account_url() : home_url( '/tai-khoan/' );
	$api_user      = function_exists( 'tam_backend_api_get_auth_user' ) ? tam_backend_api_get_auth_user() : null;
	$review_notice = function_exists( 'tam_backend_api_get_review_notice_markup' ) ? tam_backend_api_get_review_notice_markup() : '';
	$reviewable_bookings = function_exists( 'tam_backend_api_get_reviewable_bookings_for_post' )
		? tam_backend_api_get_reviewable_bookings_for_post( get_the_ID() )
		: array();
	?>
	<main id="main-content" class="site-main">
		<section class="tam-section tam-section--compact tam-tour-detail">
			<div class="tam-container">
				<div class="tam-tour-detail__layout">
					<article class="tam-tour-detail__main">
						<section class="tam-tour-detail__section tam-tour-detail__gallery tam-content-card">
							<div class="tam-tour-detail__section-head">
								<div>
									<div class="tam-eyebrow"><?php esc_html_e( 'Ảnh tour', 'travel-agency-modern' ); ?></div>
									<h1 class="tam-tour-detail__title"><?php the_title(); ?></h1>
								</div>
								<a class="tam-tour-detail__backlink" href="<?php echo esc_url( tam_get_page_url_by_path( 'tour' ) ); ?>">
									<?php esc_html_e( 'Tất cả tour', 'travel-agency-modern' ); ?>
								</a>
							</div>

							<div class="tam-tour-detail__gallery-main">
								<div class="tam-tour-detail__gallery-frame" style="<?php echo esc_attr( '--tam-tour-image: url(\'' . esc_url( $main_visual['url'] ) . '\');' ); ?>">
									<img
										src="<?php echo esc_url( $main_visual['url'] ); ?>"
										alt="<?php echo esc_attr( $main_visual['alt'] ); ?>"
										decoding="async"
										data-tour-gallery-main
									/>
								</div>
							</div>

							<?php if ( count( $gallery_images ) > 1 ) : ?>
								<div class="tam-tour-detail__thumbs" aria-label="Tour gallery">
									<?php foreach ( $gallery_images as $index => $image ) : ?>
										<button class="tam-tour-detail__thumb <?php echo 0 === $index ? 'is-active' : ''; ?>" type="button" data-tour-gallery-thumb data-image-url="<?php echo esc_url( $image['url'] ); ?>" data-image-alt="<?php echo esc_attr( $image['alt'] ); ?>" style="<?php echo esc_attr( '--tam-tour-image: url(\'' . esc_url( $image['url'] ) . '\');' ); ?>">
											<img src="<?php echo esc_url( $image['url'] ); ?>" alt="<?php echo esc_attr( $image['alt'] ); ?>" loading="lazy" decoding="async" />
										</button>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

						</section>

						<section class="tam-tour-detail__section tam-tour-detail__info tam-content-card">
							<div class="tam-tour-detail__badges">
								<?php if ( $primary_term instanceof WP_Term ) : ?>
									<a class="tam-tour-detail__badge tam-tour-detail__badge--primary" href="<?php echo esc_url( add_query_arg( 'destination', $primary_term->slug, tam_get_page_url_by_path( 'tour' ) ) ); ?>">
										<?php echo esc_html( $primary_term->name ); ?>
									</a>
								<?php endif; ?>
								<span class="tam-tour-detail__badge"><?php esc_html_e( 'Tour chi tiết', 'travel-agency-modern' ); ?></span>
							</div>

							<div class="tam-tour-detail__rating-row">
								<?php if ( $has_reviews ) : ?>
									<div class="tam-tour-detail__stars" aria-label="<?php echo esc_attr( sprintf( __( 'Danh gia %s tren 5', 'travel-agency-modern' ), $rating_display ) ); ?>">
										<?php for ( $star = 1; $star <= 5; $star++ ) : ?>
											<span class="<?php echo $star <= (int) round( $rating_value ) ? 'is-filled' : ''; ?>">*</span>
										<?php endfor; ?>
									</div>
									<strong><?php echo esc_html( $has_reviews ? $rating_display : __( 'Chua co danh gia', 'travel-agency-modern' ) ); ?></strong>
									<span><?php echo esc_html( sprintf( _n( '%d danh gia', '%d danh gia', $review_count, 'travel-agency-modern' ), $review_count ) ); ?></span>
								<?php else : ?>
									<span class="tam-empty-state--text"><?php esc_html_e( 'Chua co danh gia', 'travel-agency-modern' ); ?></span>
								<?php endif; ?>
							</div>

							<?php if ( $intro_text ) : ?>
								<p class="tam-tour-detail__intro"><?php echo esc_html( $intro_text ); ?></p>
							<?php endif; ?>

							<div class="tam-tour-detail__meta-grid">
								<div class="tam-tour-detail__meta-card">
									<span><?php esc_html_e( 'Địa điểm', 'travel-agency-modern' ); ?></span>
									<strong><?php echo esc_html( $destination_names ); ?></strong>
								</div>
								<div class="tam-tour-detail__meta-card">
									<span><?php esc_html_e( 'Thời gian', 'travel-agency-modern' ); ?></span>
									<strong><?php echo esc_html( $duration_label ); ?></strong>
								</div>
								<div class="tam-tour-detail__meta-card">
									<span><?php esc_html_e( 'Phương tiện', 'travel-agency-modern' ); ?></span>
									<strong><?php echo esc_html( $transport_label ); ?></strong>
								</div>
								<div class="tam-tour-detail__meta-card">
									<span><?php esc_html_e( 'Giá từ', 'travel-agency-modern' ); ?></span>
									<strong><?php echo esc_html( $price_display ); ?></strong>
								</div>
							</div>
						</section>

						<section class="tam-tour-detail__section tam-tour-detail__description tam-content-card">
							<div class="tam-tour-detail__section-head">
								<div>
									<div class="tam-eyebrow"><?php esc_html_e( 'Mô tả tour', 'travel-agency-modern' ); ?></div>
									<h2><?php esc_html_e( 'Giới thiệu ngắn và điểm nổi bật', 'travel-agency-modern' ); ?></h2>
								</div>
							</div>

											<div class="tam-tour-detail__copy">
					<?php if ( $intro_text ) : ?>
						<div class="tam-tour-detail__description-full">
							<?php echo wp_kses_post( wpautop( $intro_text ) ); ?>
						</div>
					<?php else : ?>
						<p class="tam-tour-detail__description-empty"><?php esc_html_e( 'Hành trình đang được cập nhật nội dung chi tiết.', 'travel-agency-modern' ); ?></p>
					<?php endif; ?>
				</div>

							<ul class="tam-tour-detail__highlight-list">
								<?php foreach ( $highlights as $highlight ) : ?>
									<li><?php echo esc_html( $highlight ); ?></li>
								<?php endforeach; ?>
							</ul>

							<?php if ( $content_available ) : ?>
								<div class="tam-tour-detail__richtext tam-rich-content">
									<?php the_content(); ?>
								</div>
							<?php endif; ?>
						</section>

						<section class="tam-tour-detail__section tam-tour-detail__timeline tam-content-card">
							<div class="tam-tour-detail__section-head">
								<div>
									<div class="tam-eyebrow"><?php esc_html_e( 'Lịch trình', 'travel-agency-modern' ); ?></div>
									<h2><?php esc_html_e( 'Kế hoạch theo từng ngày', 'travel-agency-modern' ); ?></h2>
								</div>
							</div>

							<div class="tam-tour-detail__timeline-list">
								<?php if ( empty( $itinerary ) ) : ?>
									<div class="tam-empty-state tam-empty-state--inline">
										<strong><?php esc_html_e( 'Lịch trình đang được cập nhật', 'travel-agency-modern' ); ?></strong>
										<p><?php esc_html_e( 'Vui lòng liên hệ để biết chi tiết lịch trình.', 'travel-agency-modern' ); ?></p>
									</div>
								<?php else : ?>
									<?php foreach ( $itinerary as $item ) : ?>
										<article class="tam-tour-detail__timeline-item">
											<div class="tam-tour-detail__timeline-day"><?php echo esc_html( $item['label'] ); ?></div>
											<div class="tam-tour-detail__timeline-card">
												<h3><?php echo esc_html( $item['title'] ); ?></h3>
												<p><?php echo esc_html( $item['description'] ); ?></p>
											</div>
										</article>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</section>

						<section class="tam-tour-detail__section tam-tour-detail__services tam-content-card">
							<div class="tam-tour-detail__section-head">
								<div>
									<div class="tam-eyebrow"><?php esc_html_e( 'Dịch vụ', 'travel-agency-modern' ); ?></div>
									<h2><?php esc_html_e( 'Bao gồm và không bao gồm', 'travel-agency-modern' ); ?></h2>
								</div>
							</div>

							<div class="tam-tour-detail__service-grid">
								<div class="tam-tour-detail__service-card">
									<h3><?php esc_html_e( 'Bao gồm', 'travel-agency-modern' ); ?></h3>
									<?php if ( empty( $includes ) ) : ?>
										<p class="tam-empty-state--text"><?php esc_html_e( 'Đang cập nhật...', 'travel-agency-modern' ); ?></p>
									<?php else : ?>
										<ul>
											<?php foreach ( $includes as $item ) : ?>
												<li><?php echo esc_html( $item ); ?></li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</div>

								<div class="tam-tour-detail__service-card tam-tour-detail__service-card--alt">
									<h3><?php esc_html_e( 'Không bao gồm', 'travel-agency-modern' ); ?></h3>
									<?php if ( empty( $excludes ) ) : ?>
										<p class="tam-empty-state--text"><?php esc_html_e( 'Đang cập nhật...', 'travel-agency-modern' ); ?></p>
									<?php else : ?>
										<ul>
											<?php foreach ( $excludes as $item ) : ?>
												<li><?php echo esc_html( $item ); ?></li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</div>
							</div>
						</section>

						<section class="tam-tour-detail__section tam-tour-detail__reviews tam-content-card">
							<div class="tam-tour-detail__section-head">
								<div>
									<div class="tam-eyebrow"><?php esc_html_e( 'Đánh giá khách hàng', 'travel-agency-modern' ); ?></div>
									<h2><?php esc_html_e( 'Cảm nhận sau chuyến đi', 'travel-agency-modern' ); ?></h2>
								</div>
							</div>

							<div class="tam-tour-detail__review-layout">
								<div class="tam-tour-detail__review-summary">
									<span><?php esc_html_e( 'Điểm trung bình', 'travel-agency-modern' ); ?></span>
									<strong><?php echo esc_html( $rating_display ); ?></strong>
									<div class="tam-tour-detail__review-stars" aria-hidden="true">
										<span>★</span>
										<span>★</span>
										<span>★</span>
										<span>★</span>
										<span>★</span>
									</div>
									<p><?php echo esc_html( sprintf( _n( '%d đánh giá', '%d đánh giá', $review_count, 'travel-agency-modern' ), $review_count ) ); ?></p>
								</div>

								<div class="tam-tour-detail__review-grid">
									<?php if ( empty( $reviews ) ) : ?>
										<div class="tam-empty-state tam-empty-state--inline">
											<strong><?php esc_html_e( 'Chua co danh gia nao.', 'travel-agency-modern' ); ?></strong>
											<p><?php esc_html_e( 'Khi backend co review that cho tour nay, danh sach se hien thi tai day.', 'travel-agency-modern' ); ?></p>
										</div>
									<?php else : ?>
									<?php foreach ( $reviews as $review ) : ?>
										<article class="tam-tour-detail__review-card">
											<div class="tam-tour-detail__review-top">
												<div class="tam-tour-detail__review-avatar"><?php echo esc_html( tam_get_initials( $review['name'] ) ); ?></div>
												<div class="tam-tour-detail__review-meta">
													<strong><?php echo esc_html( $review['name'] ); ?></strong>
													<div class="tam-tour-detail__review-rating">
														<span class="tam-tour-detail__review-rating-star" aria-hidden="true">★</span>
														<span><?php echo esc_html( ! empty( $review['rating'] ) ? $review['rating'] : $rating_display ); ?></span>
													</div>
													<small><?php echo esc_html( ! empty( $review['route'] ) ? $review['route'] : get_the_title() ); ?></small>
												</div>
											</div>
											<p class="tam-tour-detail__review-copy"><?php echo esc_html( $review['comment'] ); ?></p>
										</article>
									<?php endforeach; ?>
									<?php endif; ?>
								</div>
							</div>

							<div id="tour-review-form" class="tam-review-form-wrap">
								<?php echo wp_kses_post( $review_notice ); ?>

								<?php if ( empty( $api_user ) ) : ?>
									<div class="tam-form-notice tam-form-notice--info">
										<?php esc_html_e( 'Đăng nhập tài khoản backend để gửi đánh giá sau chuyến đi của bạn.', 'travel-agency-modern' ); ?>
									</div>
								<?php elseif ( empty( $reviewable_bookings ) ) : ?>
									<div class="tam-form-notice tam-form-notice--info">
										<?php esc_html_e( 'Bạn có thể gửi đánh giá khi có booking đã hoàn thành cho tour này.', 'travel-agency-modern' ); ?>
										<a class="tam-review-inline-link" href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'Xem trang tài khoản', 'travel-agency-modern' ); ?></a>
									</div>
								<?php else : ?>
									<form class="tam-review-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
										<?php wp_nonce_field( 'tam_submit_review', 'tam_review_nonce' ); ?>
										<input type="hidden" name="action" value="tam_submit_tour_review" />
										<input type="hidden" name="redirect_to" value="<?php echo esc_url( trailingslashit( get_permalink() ) . '#tour-review-form' ); ?>" />

										<div class="tam-review-form__head">
											<div>
												<div class="tam-eyebrow"><?php esc_html_e( 'Đánh giá thật', 'travel-agency-modern' ); ?></div>
												<h3><?php esc_html_e( 'Chia sẻ trải nghiệm với ADN Travel', 'travel-agency-modern' ); ?></h3>
											</div>
											<p><?php esc_html_e( 'Form này gửi trực tiếp vào backend review API của dự án booking.', 'travel-agency-modern' ); ?></p>
										</div>

										<div class="tam-review-form__grid">
											<div class="tam-field">
												<label for="tam-review-booking"><?php esc_html_e( 'Booking đã hoàn thành', 'travel-agency-modern' ); ?></label>
												<select id="tam-review-booking" name="booking_id" required>
													<?php foreach ( $reviewable_bookings as $booking ) : ?>
														<option value="<?php echo esc_attr( $booking['id'] ); ?>">
															<?php
															echo esc_html(
																sprintf(
																	/* translators: 1: booking id, 2: travel date, 3: people count */
																	__( '#%1$s - %2$s - %3$s khách', 'travel-agency-modern' ),
																	$booking['id'],
																	function_exists( 'tam_backend_api_format_date' ) ? tam_backend_api_format_date( $booking['travel_date'] ) : $booking['travel_date'],
																	$booking['number_of_people']
																)
															);
															?>
														</option>
													<?php endforeach; ?>
												</select>
											</div>

											<div class="tam-field">
												<label for="tam-review-rating"><?php esc_html_e( 'Số sao', 'travel-agency-modern' ); ?></label>
												<select id="tam-review-rating" name="rating" required>
													<option value="5"><?php esc_html_e( '5 sao - Rất hài lòng', 'travel-agency-modern' ); ?></option>
													<option value="4"><?php esc_html_e( '4 sao - Tốt', 'travel-agency-modern' ); ?></option>
													<option value="3"><?php esc_html_e( '3 sao - Ổn', 'travel-agency-modern' ); ?></option>
													<option value="2"><?php esc_html_e( '2 sao - Cần cải thiện', 'travel-agency-modern' ); ?></option>
													<option value="1"><?php esc_html_e( '1 sao - Chưa hài lòng', 'travel-agency-modern' ); ?></option>
												</select>
											</div>

											<div class="tam-field tam-field--full">
												<label for="tam-review-comment"><?php esc_html_e( 'Nội dung đánh giá', 'travel-agency-modern' ); ?></label>
												<textarea id="tam-review-comment" name="comment" rows="5" required placeholder="<?php esc_attr_e( 'Điều gì làm bạn ấn tượng nhất trong chuyến đi này?', 'travel-agency-modern' ); ?>"></textarea>
											</div>
										</div>

										<div class="tam-review-form__actions">
											<button type="submit" class="tam-button tam-button--accent"><?php esc_html_e( 'Gửi đánh giá', 'travel-agency-modern' ); ?></button>
											<a class="tam-button tam-button--ghost" href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'Xem lịch sử booking', 'travel-agency-modern' ); ?></a>
										</div>
									</form>
								<?php endif; ?>
							</div>
						</section>

						<section id="tour-inquiry" class="tam-tour-detail__section tam-tour-detail__inquiry tam-content-card">
							<div class="tam-tour-detail__section-head">
								<div>
									<div class="tam-eyebrow"><?php esc_html_e( 'Đặt tour', 'travel-agency-modern' ); ?></div>
									<h2><?php esc_html_e( 'Để lại thông tin để ADN Travel giữ chỗ cho bạn', 'travel-agency-modern' ); ?></h2>
								</div>
							</div>

							<div class="tam-tour-detail__booking-sync" data-booking-summary>
								<?php esc_html_e( 'Chọn ngày khởi hành và số lượng khách ở box bên phải để đội ngũ chuẩn bị báo giá chính xác hơn.', 'travel-agency-modern' ); ?>
							</div>

							<?php tam_render_contact_capture_form( get_the_title() ); ?>
						</section>
					</article>

					<aside class="tam-tour-detail__aside">
						<div class="tam-tour-detail__booking-card tam-summary-card">
							<div class="tam-tour-detail__booking-price">
								<span><?php esc_html_e( 'Giá tour / khách', 'travel-agency-modern' ); ?></span>
								<strong><?php echo esc_html( $price_display ); ?></strong>
								<small><?php esc_html_e( 'Giá tham khảo, đã bao gồm các dịch vụ chính theo lịch trình.', 'travel-agency-modern' ); ?></small>
							</div>

							<form class="tam-tour-detail__booking-form" data-tour-booking-box data-tour-title="<?php echo esc_attr( get_the_title() ); ?>" data-tour-id="<?php echo esc_attr( get_the_ID() ); ?>" data-checkout-url="<?php echo esc_url( tam_get_page_url_by_path( 'thanh-toan', '/thanh-toan/' ) ); ?>" data-base-price="<?php echo esc_attr( $price_numeric ); ?>" data-authenticated="<?php echo ! empty( $api_user ) ? 'true' : 'false'; ?>">
								<div class="tam-tour-detail__field tam-tour-detail__departure-field" data-departure-picker>
									<span><?php esc_html_e( 'Chọn ngày khởi hành', 'travel-agency-modern' ); ?></span>
									<input class="tam-tour-detail__date-input" type="date" name="departure_date" data-booking-date min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>" required aria-label="<?php esc_attr_e( 'Chọn ngày khởi hành', 'travel-agency-modern' ); ?>" />
									<small class="tam-tour-detail__date-hint"><?php esc_html_e( 'Bạn có thể tự chọn ngày đi phù hợp với kế hoạch của mình.', 'travel-agency-modern' ); ?></small>

									<?php if ( $has_departure_options ) : ?>
										<div class="tam-tour-detail__date-suggestions">
											<span class="tam-tour-detail__date-suggestions-label"><?php esc_html_e( 'Ngày gợi ý', 'travel-agency-modern' ); ?></span>
											<div class="tam-tour-detail__date-options tam-tour-detail__date-options--suggestions" role="group" aria-label="<?php esc_attr_e( 'Danh sách ngày khởi hành gợi ý', 'travel-agency-modern' ); ?>">
												<?php foreach ( $departure_options as $option ) : ?>
													<?php
													$departure_timestamp    = strtotime( $option['value'] );
													$departure_day          = $departure_timestamp ? wp_date( 'd', $departure_timestamp ) : '';
													$departure_month        = $departure_timestamp ? wp_date( 'm/Y', $departure_timestamp ) : '';
													$departure_display_date = $departure_timestamp ? wp_date( 'd/m/Y', $departure_timestamp ) : $option['value'];
													?>
													<button type="button" class="tam-tour-detail__date-option" data-departure-option data-value="<?php echo esc_attr( $option['value'] ); ?>" data-label="<?php echo esc_attr( $option['label'] ); ?>" aria-pressed="false">
														<?php if ( $departure_day ) : ?>
															<span class="tam-tour-detail__date-calendar" aria-hidden="true">
																<strong><?php echo esc_html( $departure_day ); ?></strong>
																<small><?php echo esc_html( $departure_month ); ?></small>
															</span>
														<?php endif; ?>
														<span class="tam-tour-detail__date-copy">
															<strong><?php echo esc_html( $option['label'] ); ?></strong>
															<small><?php echo esc_html( sprintf( __( 'Ngày đi: %s', 'travel-agency-modern' ), $departure_display_date ) ); ?></small>
														</span>
													</button>
												<?php endforeach; ?>
											</div>
										</div>
									<?php endif; ?>
								</div>

								<label class="tam-tour-detail__field">
									<span><?php esc_html_e( 'Số lượng người', 'travel-agency-modern' ); ?></span>
									<input type="number" name="party_size" min="1" max="30" value="2" data-booking-people />
								</label>

								<div class="tam-tour-detail__fact-list">
									<div>
										<span><?php esc_html_e( 'Điểm đón', 'travel-agency-modern' ); ?></span>
										<strong><?php echo esc_html( $departure_label ); ?></strong>
									</div>
									<div>
										<span><?php esc_html_e( 'Quy mô đoàn', 'travel-agency-modern' ); ?></span>
										<strong><?php echo esc_html( $group_size_label ); ?></strong>
									</div>
									<div>
										<span><?php esc_html_e( 'Mùa đẹp', 'travel-agency-modern' ); ?></span>
										<strong><?php echo esc_html( $season_label ); ?></strong>
									</div>
								</div>

								<div class="tam-tour-detail__estimate">
									<span><?php esc_html_e( 'Tạm tính', 'travel-agency-modern' ); ?></span>
									<strong data-booking-total><?php echo esc_html( $price_numeric ? tam_format_tour_price( (string) ( $price_numeric * 2 ) ) : $price_display ); ?></strong>
									<small><?php esc_html_e( 'Giá cuối cùng có thể thay đổi theo ngày khởi hành, loại phòng và số lượng khách thực tế.', 'travel-agency-modern' ); ?></small>
								</div>

								<button type="button" class="tam-button tam-button--accent tam-tour-detail__booking-button" data-booking-submit>
									<?php esc_html_e( 'Đặt ngay', 'travel-agency-modern' ); ?>
								</button>

								<div class="tam-tour-detail__aside-actions">
									<a class="tam-button tam-button--ghost" href="<?php echo esc_url( $contact['tel_url'] ); ?>">
										<?php esc_html_e( 'Gọi tư vấn', 'travel-agency-modern' ); ?>
									</a>
									<a class="tam-button tam-button--ghost" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $contact['chat_url'] ); ?>">
										<?php esc_html_e( 'Chat nhanh', 'travel-agency-modern' ); ?>
									</a>
								</div>
							</form>
						</div>
					</aside>
				</div>
			</div>
		</section>

		<section class="tam-section tam-section--compact">
			<div class="tam-container">
				<div class="tam-section-head">
					<div>
						<div class="tam-eyebrow"><?php esc_html_e( 'Tour liên quan', 'travel-agency-modern' ); ?></div>
						<h2 class="tam-section-title"><?php esc_html_e( 'Bạn có thể muốn xem thêm', 'travel-agency-modern' ); ?></h2>
					</div>
				</div>

				<?php if ( $related_tours->have_posts() ) : ?>
					<div class="tam-tour-grid">
						<?php while ( $related_tours->have_posts() ) : ?>
							<?php $related_tours->the_post(); ?>
							<?php get_template_part( 'template-parts/tour-card' ); ?>
						<?php endwhile; ?>
					</div>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
					<div class="tam-empty-state">
						<strong><?php esc_html_e( 'Chưa có tour liên quan.', 'travel-agency-modern' ); ?></strong>
						<p><?php esc_html_e( 'Khi bạn thêm nhiều tour hơn và gắn điểm đến phù hợp, khu vực này sẽ tự động hiển thị các gợi ý gần nhất.', 'travel-agency-modern' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</section>
	</main>
<?php endwhile; ?>
<?php
get_footer();
