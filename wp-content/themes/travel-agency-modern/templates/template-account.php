<?php
/**
 * Template Name: Tài khoản backend
 *
 * @package Travel_Agency_Modern
 */

$account_url  = function_exists( 'tam_backend_api_get_account_url' ) ? tam_backend_api_get_account_url() : home_url( '/tai-khoan/' );
$api_user     = function_exists( 'tam_backend_api_get_current_user_profile' ) ? tam_backend_api_get_current_user_profile( true ) : null;
$logout_url   = function_exists( 'tam_backend_api_get_logout_url' ) ? tam_backend_api_get_logout_url( $account_url ) : wp_logout_url( $account_url );
$bookings     = $api_user && function_exists( 'tam_backend_api_get_my_bookings' ) ? tam_backend_api_get_my_bookings( true ) : array();
$my_reviews   = $api_user && function_exists( 'tam_backend_api_get_my_reviews' ) ? tam_backend_api_get_my_reviews( true ) : array();
$booking_id   = isset( $_GET['booking_id'] ) ? absint( wp_unslash( $_GET['booking_id'] ) ) : 0;
$detail_item  = $api_user && $booking_id && function_exists( 'tam_backend_api_get_my_booking' ) ? tam_backend_api_get_my_booking( $booking_id ) : null;
$payments     = $detail_item && function_exists( 'tam_backend_api_get_payments_for_booking' ) ? tam_backend_api_get_payments_for_booking( $booking_id ) : array();
$pending      = 0;
$confirmed    = 0;
$completed    = 0;

foreach ( $bookings as $booking ) {
	$status = strtoupper( isset( $booking['status'] ) ? (string) $booking['status'] : '' );

	if ( 'PENDING' === $status ) {
		++$pending;
	} elseif ( 'CONFIRMED' === $status ) {
		++$confirmed;
	} elseif ( 'COMPLETED' === $status ) {
		++$completed;
	}
}

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main id="main-content" class="site-main">
		<section class="tam-section tam-section--compact tam-account-page">
			<div class="tam-container">
				<div class="tam-account-page__header">
					<div>
						<div class="tam-eyebrow"><?php esc_html_e( 'Backend account', 'travel-agency-modern' ); ?></div>
						<h1 class="tam-section-title"><?php the_title(); ?></h1>
						<p class="tam-section-subtitle">
							<?php esc_html_e( 'Trang này lấy dữ liệu trực tiếp từ backend API của dự án travel-booking để quản lý booking, thanh toán và đánh giá.', 'travel-agency-modern' ); ?>
						</p>
					</div>
					<?php if ( ! empty( $api_user ) ) : ?>
						<a class="tam-button tam-button--ghost" href="<?php echo esc_url( $logout_url ); ?>">
							<?php esc_html_e( 'Đăng xuất backend', 'travel-agency-modern' ); ?>
						</a>
					<?php endif; ?>
				</div>

				<?php echo wp_kses_post( function_exists( 'tam_backend_api_get_account_notice_markup' ) ? tam_backend_api_get_account_notice_markup() : '' ); ?>

				<?php if ( empty( $api_user ) ) : ?>
					<div class="tam-empty-state">
						<strong><?php esc_html_e( 'Bạn chưa đăng nhập tài khoản backend.', 'travel-agency-modern' ); ?></strong>
						<p><?php esc_html_e( 'Đăng nhập để xem booking, theo dõi thanh toán và quản lý các đánh giá của bạn.', 'travel-agency-modern' ); ?></p>
						<button class="tam-button" type="button" data-auth-open="login"><?php esc_html_e( 'Đăng nhập ngay', 'travel-agency-modern' ); ?></button>
					</div>
				<?php else : ?>
					<div class="tam-account-shell">
						<section class="tam-summary-card tam-account-hero">
							<div>
								<div class="tam-eyebrow"><?php esc_html_e( 'Hồ sơ backend', 'travel-agency-modern' ); ?></div>
								<h2><?php echo esc_html( $api_user['name'] ); ?></h2>
								<p><?php esc_html_e( 'Thông tin dưới đây đang được đồng bộ theo phiên đăng nhập backend hiện tại.', 'travel-agency-modern' ); ?></p>
							</div>
							<ul class="tam-account-hero__meta">
								<li><strong><?php esc_html_e( 'Email', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( $api_user['email'] ); ?></span></li>
								<li><strong><?php esc_html_e( 'Số điện thoại', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( ! empty( $api_user['phone'] ) ? $api_user['phone'] : __( 'Đang cập nhật', 'travel-agency-modern' ) ); ?></span></li>
								<li><strong><?php esc_html_e( 'Vai trò', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( ! empty( $api_user['role'] ) ? $api_user['role'] : 'USER' ); ?></span></li>
							</ul>
						</section>

						<div class="tam-account-stats">
							<div class="tam-summary-card tam-account-stat">
								<span><?php esc_html_e( 'Tổng booking', 'travel-agency-modern' ); ?></span>
								<strong><?php echo esc_html( count( $bookings ) ); ?></strong>
							</div>
							<div class="tam-summary-card tam-account-stat">
								<span><?php esc_html_e( 'Đang chờ xử lý', 'travel-agency-modern' ); ?></span>
								<strong><?php echo esc_html( $pending ); ?></strong>
							</div>
							<div class="tam-summary-card tam-account-stat">
								<span><?php esc_html_e( 'Đã xác nhận', 'travel-agency-modern' ); ?></span>
								<strong><?php echo esc_html( $confirmed ); ?></strong>
							</div>
							<div class="tam-summary-card tam-account-stat">
								<span><?php esc_html_e( 'Đã hoàn thành', 'travel-agency-modern' ); ?></span>
								<strong><?php echo esc_html( $completed ); ?></strong>
							</div>
						</div>

						<div class="tam-account-layout">
							<div class="tam-account-main">
								<section class="tam-content-card tam-account-section">
									<div class="tam-account-section__head">
										<div>
											<div class="tam-eyebrow"><?php esc_html_e( 'Booking API', 'travel-agency-modern' ); ?></div>
											<h2><?php esc_html_e( 'Danh sách booking của bạn', 'travel-agency-modern' ); ?></h2>
										</div>
										<a class="tam-button tam-button--ghost" href="<?php echo esc_url( tam_get_page_url_by_path( 'tour' ) ); ?>">
											<?php esc_html_e( 'Tiếp tục xem tour', 'travel-agency-modern' ); ?>
										</a>
									</div>

									<?php if ( empty( $bookings ) ) : ?>
										<div class="tam-empty-state tam-empty-state--embedded">
											<strong><?php esc_html_e( 'Bạn chưa có booking nào trên backend.', 'travel-agency-modern' ); ?></strong>
											<p><?php esc_html_e( 'Hãy chọn một tour và đi tới checkout để backend tạo booking đầu tiên cho tài khoản của bạn.', 'travel-agency-modern' ); ?></p>
										</div>
									<?php else : ?>
										<div class="tam-account-bookings">
											<?php foreach ( $bookings as $booking ) : ?>
												<?php
												$detail_url   = add_query_arg( 'booking_id', absint( $booking['id'] ), $account_url ) . '#booking-detail';
												$tour_post_id = function_exists( 'tam_backend_api_get_post_id_by_api_tour_id' ) ? tam_backend_api_get_post_id_by_api_tour_id( isset( $booking['tour_id'] ) ? $booking['tour_id'] : 0 ) : 0;
												$review_link  = $tour_post_id ? trailingslashit( get_permalink( $tour_post_id ) ) . '#tour-review-form' : '';
												$payment      = isset( $booking['payment'] ) && is_array( $booking['payment'] ) ? $booking['payment'] : null;
												?>
												<article class="tam-account-booking-card">
													<div class="tam-account-booking-card__head">
														<div>
															<strong><?php echo esc_html( $booking['tour_title'] ); ?></strong>
															<span><?php echo esc_html( sprintf( '#%s', $booking['id'] ) ); ?></span>
														</div>
														<span class="tam-account-badge is-<?php echo esc_attr( strtolower( (string) $booking['status'] ) ); ?>">
															<?php echo esc_html( function_exists( 'tam_backend_api_get_booking_status_label' ) ? tam_backend_api_get_booking_status_label( $booking['status'] ) : $booking['status'] ); ?>
														</span>
													</div>

													<ul class="tam-account-booking-card__meta">
														<li><?php echo esc_html( function_exists( 'tam_backend_api_format_date' ) ? tam_backend_api_format_date( $booking['travel_date'] ) : $booking['travel_date'] ); ?></li>
														<li><?php echo esc_html( sprintf( _n( '%d khách', '%d khách', (int) $booking['number_of_people'], 'travel-agency-modern' ), (int) $booking['number_of_people'] ) ); ?></li>
														<li><?php echo esc_html( tam_format_tour_price( (string) $booking['total_price'] ) ); ?></li>
														<?php if ( $payment ) : ?>
															<li><?php echo esc_html( function_exists( 'tam_backend_api_get_payment_status_label' ) ? tam_backend_api_get_payment_status_label( $payment['status'] ) : $payment['status'] ); ?></li>
														<?php endif; ?>
													</ul>

													<div class="tam-account-booking-card__actions">
														<a class="tam-button tam-button--ghost" href="<?php echo esc_url( $detail_url ); ?>">
															<?php esc_html_e( 'Xem chi tiết', 'travel-agency-modern' ); ?>
														</a>

														<?php if ( ! empty( $booking['can_review'] ) && $review_link ) : ?>
															<a class="tam-button tam-button--ghost" href="<?php echo esc_url( $review_link ); ?>">
																<?php esc_html_e( 'Viết đánh giá', 'travel-agency-modern' ); ?>
															</a>
														<?php endif; ?>

														<?php if ( 'PENDING' === strtoupper( (string) $booking['status'] ) ) : ?>
															<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
																<?php wp_nonce_field( 'tam_account_cancel_booking', 'tam_account_nonce' ); ?>
																<input type="hidden" name="action" value="tam_cancel_booking" />
																<input type="hidden" name="booking_id" value="<?php echo esc_attr( $booking['id'] ); ?>" />
																<input type="hidden" name="redirect_to" value="<?php echo esc_url( $detail_url ); ?>" />
																<button type="submit" class="tam-button tam-button--ghost"><?php esc_html_e( 'Huỷ booking', 'travel-agency-modern' ); ?></button>
															</form>
														<?php endif; ?>
													</div>
												</article>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>
								</section>

								<section class="tam-content-card tam-account-section">
									<div class="tam-account-section__head">
										<div>
											<div class="tam-eyebrow"><?php esc_html_e( 'Review API', 'travel-agency-modern' ); ?></div>
											<h2><?php esc_html_e( 'Đánh giá bạn đã gửi', 'travel-agency-modern' ); ?></h2>
										</div>
									</div>

									<?php if ( empty( $my_reviews ) ) : ?>
										<div class="tam-empty-state tam-empty-state--embedded">
											<strong><?php esc_html_e( 'Bạn chưa có đánh giá nào.', 'travel-agency-modern' ); ?></strong>
											<p><?php esc_html_e( 'Khi có booking hoàn thành, bạn có thể quay lại trang tour để gửi review trực tiếp lên backend.', 'travel-agency-modern' ); ?></p>
										</div>
									<?php else : ?>
										<div class="tam-account-reviews">
											<?php foreach ( $my_reviews as $review ) : ?>
												<article class="tam-account-review-card">
													<div class="tam-account-review-card__top">
														<div>
															<strong><?php echo esc_html( $review['tourTitle'] ); ?></strong>
															<small><?php echo esc_html( function_exists( 'tam_backend_api_format_date' ) ? tam_backend_api_format_date( $review['travelDate'] ) : $review['travelDate'] ); ?></small>
														</div>
														<span class="tam-account-badge is-review">
															<?php echo esc_html( sprintf( '%s/5', $review['rating'] ) ); ?>
														</span>
													</div>
													<p><?php echo esc_html( $review['comment'] ); ?></p>
												</article>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>
								</section>
							</div>

							<aside id="booking-detail" class="tam-account-aside">
								<section class="tam-summary-card tam-account-section tam-account-detail">
									<div class="tam-account-section__head">
										<div>
											<div class="tam-eyebrow"><?php esc_html_e( 'Booking detail API', 'travel-agency-modern' ); ?></div>
											<h2><?php esc_html_e( 'Chi tiết booking', 'travel-agency-modern' ); ?></h2>
										</div>
									</div>

									<?php if ( ! $detail_item ) : ?>
										<div class="tam-empty-state tam-empty-state--embedded">
											<strong><?php esc_html_e( 'Chọn một booking để xem chi tiết.', 'travel-agency-modern' ); ?></strong>
											<p><?php esc_html_e( 'Khu vực này dùng API chi tiết booking và lịch sử thanh toán của backend.', 'travel-agency-modern' ); ?></p>
										</div>
									<?php else : ?>
										<div class="tam-account-detail__summary">
											<strong><?php echo esc_html( $detail_item['tour_title'] ); ?></strong>
											<span><?php echo esc_html( function_exists( 'tam_backend_api_get_booking_status_label' ) ? tam_backend_api_get_booking_status_label( $detail_item['status'] ) : $detail_item['status'] ); ?></span>
										</div>

										<ul class="tam-account-detail__facts">
											<li><strong><?php esc_html_e( 'Mã booking', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( function_exists( 'tam_backend_api_build_booking_ref' ) ? tam_backend_api_build_booking_ref( $detail_item['id'] ) : $detail_item['id'] ); ?></span></li>
											<li><strong><?php esc_html_e( 'Ngày đi', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( function_exists( 'tam_backend_api_format_date' ) ? tam_backend_api_format_date( $detail_item['travel_date'] ) : $detail_item['travel_date'] ); ?></span></li>
											<li><strong><?php esc_html_e( 'Số khách', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( $detail_item['number_of_people'] ); ?></span></li>
											<li><strong><?php esc_html_e( 'Tổng tiền', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( tam_format_tour_price( (string) $detail_item['total_price'] ) ); ?></span></li>
											<?php if ( ! empty( $detail_item['meeting_point'] ) ) : ?>
												<li><strong><?php esc_html_e( 'Điểm đón', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( $detail_item['meeting_point'] ); ?></span></li>
											<?php endif; ?>
											<?php if ( ! empty( $detail_item['transport'] ) ) : ?>
												<li><strong><?php esc_html_e( 'Phương tiện', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( $detail_item['transport'] ); ?></span></li>
											<?php endif; ?>
											<li><strong><?php esc_html_e( 'Tạo lúc', 'travel-agency-modern' ); ?></strong><span><?php echo esc_html( function_exists( 'tam_backend_api_format_datetime' ) ? tam_backend_api_format_datetime( $detail_item['created_at'] ) : $detail_item['created_at'] ); ?></span></li>
										</ul>

										<div class="tam-account-detail__payments">
											<h3><?php esc_html_e( 'Lịch sử thanh toán', 'travel-agency-modern' ); ?></h3>

											<?php if ( empty( $payments ) && ! empty( $detail_item['payment'] ) ) : ?>
												<?php $payments = array( $detail_item['payment'] ); ?>
											<?php endif; ?>

											<?php if ( empty( $payments ) ) : ?>
												<p><?php esc_html_e( 'Backend chưa ghi nhận giao dịch nào cho booking này.', 'travel-agency-modern' ); ?></p>
											<?php else : ?>
												<ul class="tam-account-payment-list">
													<?php foreach ( $payments as $payment ) : ?>
														<li>
															<strong><?php echo esc_html( function_exists( 'tam_backend_api_get_payment_method_label' ) ? tam_backend_api_get_payment_method_label( $payment['method'] ) : $payment['method'] ); ?></strong>
															<span><?php echo esc_html( tam_format_tour_price( (string) $payment['amount'] ) ); ?></span>
															<small>
																<?php
																echo esc_html(
																	sprintf(
																		/* translators: 1: payment status, 2: payment time */
																		__( '%1$s - %2$s', 'travel-agency-modern' ),
																		function_exists( 'tam_backend_api_get_payment_status_label' ) ? tam_backend_api_get_payment_status_label( $payment['status'] ) : $payment['status'],
																		! empty( $payment['paid_at'] ) && function_exists( 'tam_backend_api_format_datetime' ) ? tam_backend_api_format_datetime( $payment['paid_at'] ) : __( 'Chưa có thời gian xác nhận', 'travel-agency-modern' )
																	)
																);
																?>
															</small>
														</li>
													<?php endforeach; ?>
												</ul>
											<?php endif; ?>
										</div>
									<?php endif; ?>
								</section>
							</aside>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</section>
	</main>
<?php endwhile; ?>
<?php
get_footer();
