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
	$intro_text        = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', get_the_ID() ) ), 36 );
	$rating_value      = ! empty( $tour_meta['rating'] ) ? (float) str_replace( ',', '.', $tour_meta['rating'] ) : 4.9;
	$rating_value      = max( 1, min( 5, $rating_value ) );
	$rating_display    = number_format_i18n( $rating_value, 1 );
	$review_count      = ! empty( $tour_meta['review_count'] ) ? absint( preg_replace( '/\D+/', '', (string) $tour_meta['review_count'] ) ) : 0;
	$review_count      = max( $review_count, count( $reviews ) ? count( $reviews ) * 27 : 64 );
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
	?>
	<main id="main-content" class="site-main">
		<section class="tam-section tam-section--compact tam-tour-detail">
			<div class="tam-container">
				<div class="tam-tour-detail__layout">
					<article class="tam-tour-detail__main">
						<section class="tam-tour-detail__section tam-tour-detail__gallery tam-content-card">
							<div class="tam-tour-detail__section-head">
								<div>
									<div class="tam-eyebrow"><?php esc_html_e( 'Gallery ảnh', 'travel-agency-modern' ); ?></div>
									<h1 class="tam-tour-detail__title"><?php the_title(); ?></h1>
								</div>
								<a class="tam-tour-detail__backlink" href="<?php echo esc_url( tam_get_page_url_by_path( 'tour' ) ); ?>">
									<?php esc_html_e( 'Tất cả tour', 'travel-agency-modern' ); ?>
								</a>
							</div>

							<div class="tam-tour-detail__gallery-main">
								<div class="tam-tour-detail__gallery-frame">
									<img
										src="<?php echo esc_url( $main_visual['url'] ); ?>"
										alt="<?php echo esc_attr( $main_visual['alt'] ); ?>"
										data-tour-gallery-main
									/>
								</div>
							</div>

							<?php if ( ! empty( $gallery_images ) ) : ?>
								<div class="tam-tour-detail__thumbs" role="list">
									<?php foreach ( $gallery_images as $index => $image ) : ?>
										<button
											type="button"
											class="tam-tour-detail__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>"
											data-tour-gallery-thumb
											data-image-url="<?php echo esc_url( $image['url'] ); ?>"
											data-image-alt="<?php echo esc_attr( $image['alt'] ); ?>"
											aria-label="<?php echo esc_attr( sprintf( __( 'Xem ảnh %d', 'travel-agency-modern' ), $index + 1 ) ); ?>"
										>
											<img src="<?php echo esc_url( $image['url'] ); ?>" alt="<?php echo esc_attr( $image['alt'] ); ?>" loading="lazy" />
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
								<div class="tam-tour-detail__stars" aria-label="<?php echo esc_attr( sprintf( __( 'Đánh giá %s trên 5', 'travel-agency-modern' ), $rating_display ) ); ?>">
									<?php for ( $star = 1; $star <= 5; $star++ ) : ?>
										<span class="<?php echo $star <= (int) round( $rating_value ) ? 'is-filled' : ''; ?>">★</span>
									<?php endfor; ?>
								</div>
								<strong><?php echo esc_html( $rating_display ); ?></strong>
								<span><?php echo esc_html( sprintf( _n( '%d đánh giá', '%d đánh giá', $review_count, 'travel-agency-modern' ), $review_count ) ); ?></span>
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
								<p><?php echo esc_html( $intro_text ? $intro_text : __( 'Hành trình đang được cập nhật nội dung chi tiết. ADN Travel sẽ tinh chỉnh lịch trình theo mùa và nhu cầu thực tế của từng nhóm khách.', 'travel-agency-modern' ) ); ?></p>
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
								<?php foreach ( $itinerary as $item ) : ?>
									<article class="tam-tour-detail__timeline-item">
										<div class="tam-tour-detail__timeline-day"><?php echo esc_html( $item['label'] ); ?></div>
										<div class="tam-tour-detail__timeline-card">
											<h3><?php echo esc_html( $item['title'] ); ?></h3>
											<p><?php echo esc_html( $item['description'] ); ?></p>
										</div>
									</article>
								<?php endforeach; ?>
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
									<ul>
										<?php foreach ( $includes as $item ) : ?>
											<li><?php echo esc_html( $item ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>

								<div class="tam-tour-detail__service-card tam-tour-detail__service-card--alt">
									<h3><?php esc_html_e( 'Không bao gồm', 'travel-agency-modern' ); ?></h3>
									<ul>
										<?php foreach ( $excludes as $item ) : ?>
											<li><?php echo esc_html( $item ); ?></li>
										<?php endforeach; ?>
									</ul>
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
								</div>
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

							<form class="tam-tour-detail__booking-form" data-tour-booking-box data-tour-title="<?php echo esc_attr( get_the_title() ); ?>" data-tour-id="<?php echo esc_attr( get_the_ID() ); ?>" data-checkout-url="<?php echo esc_url( tam_get_page_url_by_path( 'thanh-toan', '/thanh-toan/' ) ); ?>" data-base-price="<?php echo esc_attr( $price_numeric ); ?>">
								<label class="tam-tour-detail__field">
									<span><?php esc_html_e( 'Chọn ngày khởi hành', 'travel-agency-modern' ); ?></span>
									<select name="departure_date" data-booking-date>
										<?php foreach ( $departure_options as $option ) : ?>
											<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['label'] ); ?></option>
										<?php endforeach; ?>
									</select>
								</label>

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
