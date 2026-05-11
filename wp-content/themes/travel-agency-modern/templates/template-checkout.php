<?php
/**
 * Template Name: Thanh toán tour
 *
 * @package Travel_Agency_Modern
 */

get_header();

$api_user = function_exists( 'tam_backend_api_get_current_user_profile' )
	? tam_backend_api_get_current_user_profile()
	: ( function_exists( 'tam_backend_api_get_auth_user' ) ? tam_backend_api_get_auth_user() : null );

$country_options = array(
	'Vietnam',
	'Thailand',
	'Singapore',
	'Malaysia',
	'Indonesia',
	'Philippines',
	'South Korea',
	'Japan',
	'China',
	'Australia',
	'France',
	'Germany',
	'United Kingdom',
	'United States',
	'Canada',
);

while ( have_posts() ) :
	the_post();

	$checkout        = tam_get_checkout_context();
	$result_context  = function_exists( 'tam_backend_api_get_checkout_result_context' ) ? tam_backend_api_get_checkout_result_context() : null;
	$is_result_step  = ! empty( $result_context );
	$summary_payload = ! empty( $result_context['summary'] ) && is_array( $result_context['summary'] ) ? $result_context['summary'] : array();
	$payment_methods = ! empty( $checkout['payment_methods'] ) && is_array( $checkout['payment_methods'] ) ? $checkout['payment_methods'] : array();
	$default_method  = ! empty( $payment_methods ) ? array_key_first( $payment_methods ) : 'vnpay';
	$return_url      = tam_get_page_url_by_path( 'thanh-toan', home_url( '/thanh-toan/' ) );
	$return_url      = add_query_arg(
		array_filter(
			array(
				'tour_id'     => ! empty( $checkout['post_id'] ) ? (int) $checkout['post_id'] : 0,
				'travel_date' => ! empty( $checkout['selected_date'] ) ? $checkout['selected_date'] : '',
				'party_size'  => ! empty( $checkout['people'] ) ? (int) $checkout['people'] : 0,
				'children'    => isset( $checkout['children'] ) ? (int) $checkout['children'] : 0,
			)
		),
		$return_url
	);
	$adult_price     = ! empty( $checkout['price_raw'] ) ? (int) $checkout['price_raw'] : 0;
	$child_price     = (int) round( $adult_price * 0.7 );
	$adult_count     = ! empty( $checkout['adults'] ) ? (int) $checkout['adults'] : max( 1, (int) $checkout['people'] );
	$child_count     = ! empty( $checkout['children'] ) ? (int) $checkout['children'] : 0;
	$subtotal        = ( $adult_price * $adult_count ) + ( $child_price * $child_count );
	$tax_amount      = (int) round( $subtotal * 0.08 );
	$fee_amount      = $subtotal > 0 ? 39000 : 0;
	$total_amount    = max( 0, $subtotal + $tax_amount + $fee_amount );
	$deposit_rate    = 0.3;
	$selected_country = ! empty( $api_user['country'] )
		? sanitize_text_field( $api_user['country'] )
		: 'Vietnam';

	if ( ! in_array( $selected_country, $country_options, true ) ) {
		array_unshift( $country_options, $selected_country );
		$country_options = array_values( array_unique( $country_options ) );
	}
	?>
	<main id="main-content" class="site-main">
		<section class="tam-section tam-section--compact tam-booking-flow">
			<div class="tam-container">
				<header class="tam-booking-flow__hero tam-glass-card">
					<div class="tam-booking-flow__hero-copy">
						<div class="tam-eyebrow"><?php esc_html_e( 'Booking Experience', 'travel-agency-modern' ); ?></div>
						<h1 class="tam-section-title"><?php the_title(); ?></h1>
						<p class="tam-section-subtitle">
							<?php esc_html_e( 'Hoàn tất booking trong 4 bước gọn gàng: điền thông tin, xác nhận đơn, thanh toán toàn bộ qua VNPay và theo dõi trạng thái xác nhận từ đội ngũ vận hành.', 'travel-agency-modern' ); ?>
						</p>
					</div>

					<div class="tam-booking-flow__hero-meta">
						<?php if ( ! empty( $checkout['has_tour'] ) ) : ?>
							<a class="tam-booking-flow__hero-link" href="<?php echo esc_url( get_permalink( $checkout['post_id'] ) ); ?>">
								<i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i>
								<span><?php esc_html_e( 'Quay lại tour', 'travel-agency-modern' ); ?></span>
							</a>
						<?php endif; ?>

						<?php if ( ! empty( $api_user ) ) : ?>
							<div class="tam-booking-flow__hero-badge">
								<i class="fa-regular fa-circle-check" aria-hidden="true"></i>
								<span>
									<?php
									printf(
										/* translators: %s: customer name */
										esc_html__( 'Đang giữ session cho %s', 'travel-agency-modern' ),
										esc_html( $api_user['name'] )
									);
									?>
								</span>
							</div>
						<?php else : ?>
							<button class="tam-booking-flow__hero-link tam-booking-flow__hero-link--button" type="button" data-auth-open="login">
								<i class="fa-regular fa-user" aria-hidden="true"></i>
								<span><?php esc_html_e( 'Đăng nhập để thanh toán', 'travel-agency-modern' ); ?></span>
							</button>
						<?php endif; ?>
					</div>
				</header>

				<?php
				echo wp_kses_post(
					function_exists( 'tam_backend_api_get_checkout_notice_markup' )
						? tam_backend_api_get_checkout_notice_markup()
						: tam_get_checkout_notice_markup()
				);
				?>

				<?php if ( empty( $checkout['has_tour'] ) ) : ?>
					<div class="tam-empty-state">
						<strong><?php esc_html_e( 'Chưa có tour để thanh toán.', 'travel-agency-modern' ); ?></strong>
						<p><?php esc_html_e( 'Hãy chọn một tour trước, sau đó quay lại đây để hoàn tất thông tin booking.', 'travel-agency-modern' ); ?></p>
						<a class="tam-button" href="<?php echo esc_url( tam_get_page_url_by_path( 'tour' ) ); ?>">
							<?php esc_html_e( 'Khám phá tour', 'travel-agency-modern' ); ?>
						</a>
					</div>
				<?php else : ?>
					<?php if ( empty( $checkout['can_checkout_api'] ) ) : ?>
						<div class="tam-form-notice tam-form-notice--error">
							<?php esc_html_e( 'Tour này hiện chưa sẵn sàng cho thanh toán trực tuyến. Vui lòng chọn tour khác hoặc liên hệ ADN Travel để được hỗ trợ giữ chỗ.', 'travel-agency-modern' ); ?>
						</div>
					<?php endif; ?>

					<div
						class="tam-booking-flow__shell"
						data-booking-wizard
						data-current-step="<?php echo $is_result_step ? 4 : 1; ?>"
						data-tour-id="<?php echo esc_attr( $checkout['post_id'] ); ?>"
						data-api-tour-id="<?php echo esc_attr( ! empty( $checkout['api_tour_id'] ) ? $checkout['api_tour_id'] : 0 ); ?>"
						data-return-url="<?php echo esc_url( $return_url ); ?>"
						data-account-url="<?php echo esc_url( function_exists( 'tam_backend_api_get_account_url' ) ? tam_backend_api_get_account_url() : home_url( '/tai-khoan/' ) ); ?>"
						data-default-payment="<?php echo esc_attr( $default_method ); ?>"
						data-authenticated="<?php echo ! empty( $api_user ) ? 'true' : 'false'; ?>"
						data-last-transaction-code="<?php echo esc_attr( ! empty( $result_context['transactionCode'] ) ? (string) $result_context['transactionCode'] : '' ); ?>"
						data-base-price="<?php echo esc_attr( $adult_price ); ?>"
						data-child-price="<?php echo esc_attr( $child_price ); ?>"
						data-tax-rate="0.08"
						data-service-fee="39000"
						data-can-checkout="<?php echo ! empty( $checkout['can_checkout_api'] ) ? 'true' : 'false'; ?>"
					>
						<div class="tam-booking-flow__progress tam-glass-card" aria-label="<?php esc_attr_e( 'Tiến trình booking', 'travel-agency-modern' ); ?>">
							<div class="tam-booking-flow__progress-bar">
								<span class="tam-booking-flow__progress-fill" data-booking-progress-fill style="width: <?php echo esc_attr( $is_result_step ? 100 : 25 ); ?>%;"></span>
							</div>
							<ol class="tam-booking-flow__steps">
								<li class="tam-booking-flow__step<?php echo $is_result_step ? ' is-complete' : ' is-active'; ?>" data-step-indicator="1">
									<span>01</span>
									<strong><?php esc_html_e( 'Khách hàng', 'travel-agency-modern' ); ?></strong>
									<small><?php esc_html_e( 'Thông tin liên hệ', 'travel-agency-modern' ); ?></small>
								</li>
								<li class="tam-booking-flow__step<?php echo $is_result_step ? ' is-complete' : ''; ?>" data-step-indicator="2">
									<span>02</span>
									<strong><?php esc_html_e( 'Xác nhận', 'travel-agency-modern' ); ?></strong>
									<small><?php esc_html_e( 'Đơn hàng & coupon', 'travel-agency-modern' ); ?></small>
								</li>
								<li class="tam-booking-flow__step<?php echo $is_result_step ? ' is-complete' : ''; ?>" data-step-indicator="3">
									<span>03</span>
									<strong><?php esc_html_e( 'Thanh toán', 'travel-agency-modern' ); ?></strong>
									<small><?php esc_html_e( 'Cổng thanh toán', 'travel-agency-modern' ); ?></small>
								</li>
								<li class="tam-booking-flow__step<?php echo $is_result_step ? ' is-active' : ''; ?>" data-step-indicator="4">
									<span>04</span>
									<strong><?php esc_html_e( 'Hoàn tất', 'travel-agency-modern' ); ?></strong>
									<small><?php esc_html_e( 'Theo dõi xác nhận', 'travel-agency-modern' ); ?></small>
								</li>
							</ol>
						</div>

						<div class="tam-booking-flow__layout">
							<form class="tam-booking-flow__main" novalidate data-booking-form>
								<section class="tam-booking-step tam-glass-card<?php echo ! $is_result_step ? ' is-active' : ''; ?>" data-step-panel="1" aria-hidden="<?php echo $is_result_step ? 'true' : 'false'; ?>">
									<div class="tam-booking-step__head">
										<div>
											<div class="tam-eyebrow"><?php esc_html_e( 'Step 1', 'travel-agency-modern' ); ?></div>
											<h2><?php esc_html_e( 'Nhập thông tin khách hàng', 'travel-agency-modern' ); ?></h2>
										</div>
										<p><?php esc_html_e( 'Chúng tôi dùng dữ liệu này để giữ chỗ, gửi xác nhận và đồng bộ lịch sử booking trên tài khoản của bạn.', 'travel-agency-modern' ); ?></p>
									</div>

									<?php if ( empty( $api_user ) ) : ?>
										<div class="tam-booking-flow__inline-notice">
											<i class="fa-regular fa-bell" aria-hidden="true"></i>
											<div>
												<strong><?php esc_html_e( 'Đăng nhập trước khi thanh toán', 'travel-agency-modern' ); ?></strong>
												<p><?php esc_html_e( 'Bạn vẫn có thể điền thông tin trước. Khi sang bước thanh toán, hệ thống sẽ yêu cầu đăng nhập để tạo booking bằng JWT session hiện tại.', 'travel-agency-modern' ); ?></p>
											</div>
											<button class="tam-button tam-button--ghost" type="button" data-auth-open="login"><?php esc_html_e( 'Mở đăng nhập', 'travel-agency-modern' ); ?></button>
										</div>
									<?php endif; ?>

									<div class="tam-booking-form-sections">
										<section class="tam-booking-form-section">
											<div class="tam-booking-form-section__head">
												<span>01</span>
												<div>
													<h3><?php esc_html_e( 'Thông tin liên hệ', 'travel-agency-modern' ); ?></h3>
													<p><?php esc_html_e( 'Nhập thông tin người đại diện để nhận xác nhận booking, hóa đơn và cập nhật lịch khởi hành.', 'travel-agency-modern' ); ?></p>
												</div>
											</div>

											<div class="tam-booking-flow__grid">
												<div class="tam-booking-field" data-booking-field="contact_name">
													<label for="tam-booking-name"><?php esc_html_e( 'Họ tên', 'travel-agency-modern' ); ?></label>
													<input type="text" id="tam-booking-name" name="contact_name" value="<?php echo esc_attr( ! empty( $api_user['name'] ) ? $api_user['name'] : '' ); ?>" autocomplete="name" required />
													<p class="tam-booking-field__error" data-field-error></p>
												</div>

												<div class="tam-booking-field" data-booking-field="contact_phone">
													<label for="tam-booking-phone"><?php esc_html_e( 'Số điện thoại', 'travel-agency-modern' ); ?></label>
													<input type="tel" id="tam-booking-phone" name="contact_phone" value="<?php echo esc_attr( ! empty( $api_user['phone'] ) ? $api_user['phone'] : '' ); ?>" autocomplete="tel" inputmode="tel" required />
													<p class="tam-booking-field__error" data-field-error></p>
												</div>

												<div class="tam-booking-field" data-booking-field="contact_email">
													<label for="tam-booking-email"><?php esc_html_e( 'Email', 'travel-agency-modern' ); ?></label>
													<input type="email" id="tam-booking-email" name="contact_email" value="<?php echo esc_attr( ! empty( $api_user['email'] ) ? $api_user['email'] : '' ); ?>" autocomplete="email" required />
													<p class="tam-booking-field__error" data-field-error></p>
												</div>

												<div class="tam-booking-field" data-booking-field="contact_country">
													<label for="tam-booking-country"><?php esc_html_e( 'Quốc gia', 'travel-agency-modern' ); ?></label>
													<select id="tam-booking-country" name="contact_country" required>
														<?php foreach ( $country_options as $country ) : ?>
															<option value="<?php echo esc_attr( $country ); ?>" <?php selected( $selected_country, $country ); ?>>
																<?php echo esc_html( $country ); ?>
															</option>
														<?php endforeach; ?>
													</select>
													<p class="tam-booking-field__error" data-field-error></p>
												</div>
											</div>
										</section>

										<section class="tam-booking-form-section">
											<div class="tam-booking-form-section__head">
												<span>02</span>
												<div>
													<h3><?php esc_html_e( 'Hành khách & lịch khởi hành', 'travel-agency-modern' ); ?></h3>
													<p><?php esc_html_e( 'Chọn ngày đi phù hợp và số lượng người lớn, trẻ em để hệ thống cập nhật giá theo thời gian thực.', 'travel-agency-modern' ); ?></p>
												</div>
											</div>

											<div class="tam-booking-flow__grid">
												<div class="tam-booking-field tam-booking-field--compact" data-booking-field="adults_count">
													<label for="tam-booking-adults"><?php esc_html_e( 'Người lớn', 'travel-agency-modern' ); ?></label>
													<input type="number" id="tam-booking-adults" name="adults_count" min="1" max="20" value="<?php echo esc_attr( $adult_count ); ?>" required />
													<p class="tam-booking-field__hint"><?php esc_html_e( 'Từ 12 tuổi trở lên', 'travel-agency-modern' ); ?></p>
													<p class="tam-booking-field__error" data-field-error></p>
												</div>

												<div class="tam-booking-field tam-booking-field--compact" data-booking-field="children_count">
													<label for="tam-booking-children"><?php esc_html_e( 'Trẻ em', 'travel-agency-modern' ); ?></label>
													<input type="number" id="tam-booking-children" name="children_count" min="0" max="20" value="<?php echo esc_attr( $child_count ); ?>" />
													<p class="tam-booking-field__hint"><?php esc_html_e( 'Tự động tính 70% giá người lớn', 'travel-agency-modern' ); ?></p>
													<p class="tam-booking-field__error" data-field-error></p>
												</div>

												<div class="tam-booking-field tam-booking-field--wide" data-booking-field="travel_date">
													<label for="tam-booking-date"><?php esc_html_e( 'Ngày khởi hành', 'travel-agency-modern' ); ?></label>
													<select id="tam-booking-date" name="travel_date" required>
														<?php foreach ( $checkout['departure_options'] as $option ) : ?>
															<option value="<?php echo esc_attr( $option['value'] ); ?>" <?php selected( $checkout['selected_date'], $option['value'] ); ?>>
																<?php echo esc_html( $option['label'] ); ?>
															</option>
														<?php endforeach; ?>
													</select>
													<p class="tam-booking-field__error" data-field-error></p>
												</div>
											</div>
										</section>

										<section class="tam-booking-form-section tam-booking-form-section--note">
											<div class="tam-booking-form-section__head">
												<span>03</span>
												<div>
													<h3><?php esc_html_e( 'Yêu cầu đặc biệt', 'travel-agency-modern' ); ?></h3>
													<p><?php esc_html_e( 'Thêm thông tin cho đội vận hành nếu bạn cần hỗ trợ ghế ngồi, ăn chay, xe đón hoặc lịch trình cho gia đình có trẻ nhỏ.', 'travel-agency-modern' ); ?></p>
												</div>
											</div>

											<div class="tam-booking-field tam-booking-field--full" data-booking-field="special_requests">
												<label for="tam-booking-note"><?php esc_html_e( 'Ghi chú đặc biệt', 'travel-agency-modern' ); ?></label>
												<textarea id="tam-booking-note" name="special_requests" rows="5" placeholder="<?php esc_attr_e( 'Ví dụ: cần ghế ngồi gần nhau, ăn chay, hỗ trợ xe đón hoặc tư vấn lịch trình gia đình có trẻ nhỏ.', 'travel-agency-modern' ); ?>"></textarea>
												<p class="tam-booking-field__hint"><?php esc_html_e( 'Thông tin này sẽ xuất hiện trong booking để đội vận hành xử lý trước ngày khởi hành.', 'travel-agency-modern' ); ?></p>
											</div>
										</section>
									</div>

									<div class="tam-booking-step__actions">
										<button class="tam-button tam-button--accent" type="button" data-step-next="2">
											<span><?php esc_html_e( 'Tiếp tục', 'travel-agency-modern' ); ?></span>
											<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
										</button>
									</div>
								</section>

								<section class="tam-booking-step tam-glass-card" data-step-panel="2" aria-hidden="true">
									<div class="tam-booking-step__head">
										<div>
											<div class="tam-eyebrow"><?php esc_html_e( 'Step 2', 'travel-agency-modern' ); ?></div>
											<h2><?php esc_html_e( 'Xác nhận đơn hàng', 'travel-agency-modern' ); ?></h2>
										</div>
										<p><?php esc_html_e( 'Kiểm tra lại lịch khởi hành, số lượng khách, phụ phí và áp dụng mã giảm giá trước khi vào cổng thanh toán.', 'travel-agency-modern' ); ?></p>
									</div>

									<div class="tam-booking-flow__review-grid">
										<div class="tam-booking-flow__stack">
											<article class="tam-booking-card tam-booking-card--tour">
												<div class="tam-booking-card__media" style="<?php echo esc_attr( '--tam-tour-image: url(\'' . esc_url( $checkout['visual'] ) . '\');' ); ?>">
													<img src="<?php echo esc_url( $checkout['visual'] ); ?>" alt="<?php echo esc_attr( $checkout['title'] ); ?>" loading="lazy" decoding="async" />
												</div>
												<div class="tam-booking-card__copy">
													<div class="tam-booking-card__tags">
														<span><?php echo esc_html( $checkout['duration'] ); ?></span>
														<span data-booking-summary-date><?php echo esc_html( $checkout['selected_date_text'] ); ?></span>
													</div>
													<h3><?php echo esc_html( $checkout['title'] ); ?></h3>
													<p><?php echo esc_html( $checkout['destination'] ); ?></p>
													<ul class="tam-booking-card__facts">
														<li>
															<i class="fa-solid fa-users" aria-hidden="true"></i>
															<span data-booking-summary-travellers><?php echo esc_html( $checkout['people'] ); ?></span>
														</li>
														<li>
															<i class="fa-solid fa-location-dot" aria-hidden="true"></i>
															<span><?php echo esc_html( $checkout['departure_label'] ); ?></span>
														</li>
													</ul>
												</div>
											</article>

											<div class="tam-booking-card">
												<div class="tam-booking-card__title">
													<h3><?php esc_html_e( 'Thông tin khách đặt', 'travel-agency-modern' ); ?></h3>
													<p><?php esc_html_e( 'Rà soát lại thông tin liên hệ để đảm bảo xác nhận booking và vé điện tử được gửi đúng người nhận.', 'travel-agency-modern' ); ?></p>
												</div>
												<div class="tam-booking-card__info-list">
													<div><span><?php esc_html_e( 'Khách liên hệ', 'travel-agency-modern' ); ?></span><strong data-review-contact-name><?php echo esc_html( ! empty( $api_user['name'] ) ? $api_user['name'] : '' ); ?></strong></div>
													<div><span><?php esc_html_e( 'Email', 'travel-agency-modern' ); ?></span><strong data-review-contact-email><?php echo esc_html( ! empty( $api_user['email'] ) ? $api_user['email'] : '' ); ?></strong></div>
													<div><span><?php esc_html_e( 'Điện thoại', 'travel-agency-modern' ); ?></span><strong data-review-contact-phone><?php echo esc_html( ! empty( $api_user['phone'] ) ? $api_user['phone'] : '' ); ?></strong></div>
													<div><span><?php esc_html_e( 'Quốc gia', 'travel-agency-modern' ); ?></span><strong data-review-contact-country><?php echo esc_html( $selected_country ); ?></strong></div>
												</div>
											</div>
										</div>
									</div>

									<div class="tam-booking-step__actions">
										<button class="tam-button tam-button--ghost" type="button" data-step-back="1">
											<i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i>
											<span><?php esc_html_e( 'Quay lại', 'travel-agency-modern' ); ?></span>
										</button>
										<button class="tam-button tam-button--accent" type="button" data-step-next="3">
											<span><?php esc_html_e( 'Tiếp tục thanh toán', 'travel-agency-modern' ); ?></span>
											<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
										</button>
									</div>
								</section>

								<section class="tam-booking-step tam-glass-card" data-step-panel="3" aria-hidden="true">
									<div class="tam-booking-step__head">
										<div>
											<div class="tam-eyebrow"><?php esc_html_e( 'Step 3', 'travel-agency-modern' ); ?></div>
											<h2><?php esc_html_e( 'Thanh toán online', 'travel-agency-modern' ); ?></h2>
										</div>
										<p><?php esc_html_e( 'Website hiện hỗ trợ thanh toán toàn bộ qua VNPay. Sau khi giao dịch thành công, booking sẽ được ghi nhận đã thanh toán và chờ nhân viên xác nhận.', 'travel-agency-modern' ); ?></p>
									</div>

									<div class="tam-booking-card">
										<div class="tam-booking-card__title">
											<h3><?php esc_html_e( 'Hình thức thanh toán', 'travel-agency-modern' ); ?></h3>
											<p><?php esc_html_e( 'Hệ thống chỉ nhận thanh toán 100% giá trị booking trong một lần.', 'travel-agency-modern' ); ?></p>
										</div>
										<div class="tam-booking-flow__payment-grid">
											<label class="tam-booking-payment is-selected" data-payment-plan-option>
												<input type="radio" name="payment_plan" value="FULL" checked data-payment-plan />
												<span class="tam-booking-payment__icon" style="--payment-tone:#ff6b00;">
													<i class="fa-solid fa-wallet" aria-hidden="true"></i>
												</span>
												<span class="tam-booking-payment__copy">
													<strong><?php esc_html_e( 'Thanh toán toàn bộ', 'travel-agency-modern' ); ?></strong>
													<small><?php esc_html_e( 'Thanh toán 100% giá trị booking qua VNPay.', 'travel-agency-modern' ); ?></small>
												</span>
												<em><?php esc_html_e( 'Full', 'travel-agency-modern' ); ?></em>
											</label>
										</div>
									</div>

									<div class="tam-booking-flow__payment-grid">
										<?php foreach ( $payment_methods as $method_key => $method ) : ?>
											<label class="tam-booking-payment<?php echo $default_method === $method_key ? ' is-selected' : ''; ?>" data-payment-option>
												<input type="radio" name="payment_method" value="<?php echo esc_attr( $method_key ); ?>" <?php checked( $default_method, $method_key ); ?> data-payment-method />
												<span class="tam-booking-payment__icon" style="--payment-tone:<?php echo esc_attr( ! empty( $method['tone'] ) ? $method['tone'] : '#0ea5e9' ); ?>;">
													<i class="<?php echo esc_attr( ! empty( $method['icon_class'] ) ? $method['icon_class'] : 'fa-regular fa-credit-card' ); ?>" aria-hidden="true"></i>
												</span>
												<span class="tam-booking-payment__copy">
													<strong><?php echo esc_html( $method['label'] ); ?></strong>
													<small><?php echo esc_html( $method['description'] ); ?></small>
												</span>
												<?php if ( ! empty( $method['badge'] ) ) : ?>
													<em><?php echo esc_html( $method['badge'] ); ?></em>
												<?php endif; ?>
											</label>
										<?php endforeach; ?>
									</div>

									<div class="tam-booking-card tam-booking-card--security">
										<div class="tam-booking-card__title">
											<h3><?php esc_html_e( 'Secure checkout', 'travel-agency-modern' ); ?></h3>
										</div>
										<ul class="tam-booking-flow__security-list">
											<li><i class="fa-solid fa-shield-heart" aria-hidden="true"></i><span><?php esc_html_e( 'JWT session được xác minh trước khi tạo booking.', 'travel-agency-modern' ); ?></span></li>
											<li><i class="fa-solid fa-receipt" aria-hidden="true"></i><span><?php esc_html_e( 'Mỗi lần nhấn thanh toán dùng request ID riêng để chống submit trùng.', 'travel-agency-modern' ); ?></span></li>
											<li><i class="fa-solid fa-key" aria-hidden="true"></i><span><?php esc_html_e( 'Callback được ký signature trước khi cập nhật payment status. Booking sẽ chuyển sang chờ xác nhận thay vì tự auto confirm.', 'travel-agency-modern' ); ?></span></li>
										</ul>

										<label class="tam-booking-flow__terms" data-booking-field="accept_terms">
											<input type="checkbox" name="accept_terms" value="1" required />
											<span><?php esc_html_e( 'Tôi đồng ý với điều khoản đặt tour, chính sách thanh toán toàn bộ qua VNPay và chính sách xác nhận booking.', 'travel-agency-modern' ); ?></span>
										</label>
										<p class="tam-booking-field__error" data-field-error></p>
									</div>

									<div class="tam-booking-step__actions">
										<button class="tam-button tam-button--ghost" type="button" data-step-back="2">
											<i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i>
											<span><?php esc_html_e( 'Quay lại', 'travel-agency-modern' ); ?></span>
										</button>
										<button class="tam-button tam-button--accent tam-booking-flow__pay-button" type="submit" data-submit-payment <?php disabled( empty( $checkout['can_checkout_api'] ) ); ?>>
											<span class="tam-booking-flow__pay-label"><?php esc_html_e( 'Thanh toán ngay', 'travel-agency-modern' ); ?></span>
											<span class="tam-booking-flow__pay-loader" aria-hidden="true"></span>
										</button>
									</div>
								</section>

								<section class="tam-booking-step tam-glass-card<?php echo $is_result_step ? ' is-active' : ''; ?>" data-step-panel="4" aria-hidden="<?php echo $is_result_step ? 'false' : 'true'; ?>">
									<?php if ( $is_result_step && ! empty( $summary_payload ) ) : ?>
										<?php
										$booking_code   = ! empty( $summary_payload['booking']['code'] ) ? $summary_payload['booking']['code'] : '';
										$payment_method = ! empty( $summary_payload['payment']['method'] ) ? $summary_payload['payment']['method'] : '';
										$payment_status = ! empty( $summary_payload['payment']['status'] ) ? $summary_payload['payment']['status'] : '';
										$booking_status = ! empty( $summary_payload['booking']['status'] ) ? $summary_payload['booking']['status'] : '';
										$payment_plan   = ! empty( $summary_payload['payment']['paymentPlan'] ) ? $summary_payload['payment']['paymentPlan'] : ( ! empty( $summary_payload['booking']['paymentPlan'] ) ? $summary_payload['booking']['paymentPlan'] : 'FULL' );
										$is_confirmed   = ! empty( $result_context['isConfirmed'] );
										?>
										<div class="tam-booking-result tam-booking-result--<?php echo esc_attr( $result_context['result'] ); ?>">
											<div class="tam-booking-result__hero">
												<div class="tam-booking-result__animation" aria-hidden="true">
													<span></span>
													<span></span>
													<span></span>
													<i class="fa-solid <?php echo 'success' === $result_context['result'] ? 'fa-check' : 'fa-xmark'; ?>"></i>
												</div>
												<div class="tam-booking-result__copy">
													<div class="tam-eyebrow"><?php echo 'success' === $result_context['result'] ? esc_html__( 'Payment Received', 'travel-agency-modern' ) : esc_html__( 'Payment Pending', 'travel-agency-modern' ); ?></div>
													<h2>
														<?php
														echo 'success' === $result_context['result']
															? (
																$is_confirmed
																	? esc_html__( 'Booking của bạn đã được xác nhận', 'travel-agency-modern' )
																	: esc_html__( 'Thanh toán thành công, đang chờ xác nhận', 'travel-agency-modern' )
															)
															: esc_html__( 'Giao dịch chưa hoàn tất', 'travel-agency-modern' );
														?>
													</h2>
													<p><?php echo esc_html( $result_context['message'] ); ?></p>
												</div>
											</div>

											<div class="tam-booking-result__grid">
												<div class="tam-booking-card tam-booking-card--result">
													<div class="tam-booking-card__title">
														<h3><?php echo $is_confirmed ? esc_html__( 'Booking pass', 'travel-agency-modern' ) : esc_html__( 'Trạng thái booking', 'travel-agency-modern' ); ?></h3>
													</div>
													<div class="tam-booking-result__pass">
														<div class="tam-booking-result__pass-copy">
															<span><?php esc_html_e( 'Mã booking', 'travel-agency-modern' ); ?></span>
															<strong><?php echo esc_html( $booking_code ); ?></strong>
															<small><?php echo esc_html( tam_backend_api_get_payment_method_label( $payment_method ) ); ?> · <?php echo esc_html( tam_backend_api_get_payment_status_label( $payment_status ) ); ?></small>
														</div>
														<?php if ( ! empty( $result_context['qrMarkup'] ) ) : ?>
															<div class="tam-booking-result__qr">
																<?php echo $result_context['qrMarkup']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
															</div>
														<?php endif; ?>
													</div>
													<?php if ( ! $is_confirmed ) : ?>
														<p class="tam-booking-flow__coupon-status"><?php esc_html_e( 'Booking này đã thanh toán thành công nhưng vẫn cần nhân viên kiểm tra công suất, ngày đi và xác nhận cuối cùng trước khi gửi vé/QR chính thức.', 'travel-agency-modern' ); ?></p>
													<?php endif; ?>
												</div>

												<div class="tam-booking-card">
													<div class="tam-booking-card__title">
														<h3><?php esc_html_e( 'Chi tiết đơn', 'travel-agency-modern' ); ?></h3>
													</div>
													<div class="tam-booking-card__info-list">
														<div><span><?php esc_html_e( 'Tour', 'travel-agency-modern' ); ?></span><strong><?php echo esc_html( ! empty( $summary_payload['tour']['title'] ) ? $summary_payload['tour']['title'] : '' ); ?></strong></div>
														<div><span><?php esc_html_e( 'Khởi hành', 'travel-agency-modern' ); ?></span><strong><?php echo esc_html( ! empty( $summary_payload['booking']['travelDate'] ) ? $summary_payload['booking']['travelDate'] : '' ); ?></strong></div>
														<div><span><?php esc_html_e( 'Hành khách', 'travel-agency-modern' ); ?></span><strong><?php echo esc_html( ! empty( $summary_payload['booking']['travellers'] ) ? $summary_payload['booking']['travellers'] : 0 ); ?></strong></div>
														<div><span><?php esc_html_e( 'Trạng thái booking', 'travel-agency-modern' ); ?></span><strong><?php echo esc_html( tam_backend_api_get_booking_status_label( $booking_status ) ); ?></strong></div>
														<div><span><?php esc_html_e( 'Trạng thái thanh toán', 'travel-agency-modern' ); ?></span><strong><?php echo esc_html( tam_backend_api_get_payment_status_label( $payment_status ) ); ?></strong></div>
														<div><span><?php esc_html_e( 'Hình thức', 'travel-agency-modern' ); ?></span><strong><?php esc_html_e( 'Thanh toán toàn bộ', 'travel-agency-modern' ); ?></strong></div>
														<div><span><?php esc_html_e( 'Đã thanh toán', 'travel-agency-modern' ); ?></span><strong><?php echo esc_html( tam_backend_api_get_summary_amount_display( $summary_payload, 'pricing.payableNowAmount' ) ); ?></strong></div>
														<div><span><?php esc_html_e( 'Còn lại', 'travel-agency-modern' ); ?></span><strong><?php echo esc_html( tam_backend_api_get_summary_amount_display( $summary_payload, 'pricing.remainingAmount' ) ); ?></strong></div>
														<div><span><?php esc_html_e( 'Tổng tiền', 'travel-agency-modern' ); ?></span><strong><?php echo esc_html( tam_backend_api_get_summary_amount_display( $summary_payload, 'pricing.totalAmount' ) ); ?></strong></div>
													</div>
												</div>
											</div>

											<div class="tam-booking-result__actions">
												<?php if ( 'success' === $result_context['result'] && ! empty( $result_context['invoiceUrl'] ) ) : ?>
													<a class="tam-button tam-button--accent" href="<?php echo esc_url( $result_context['invoiceUrl'] ); ?>">
														<i class="fa-regular fa-file-pdf" aria-hidden="true"></i>
														<span><?php esc_html_e( 'Download invoice PDF', 'travel-agency-modern' ); ?></span>
													</a>
												<?php endif; ?>

												<a class="tam-button tam-button--ghost" href="<?php echo esc_url( ! empty( $result_context['accountUrl'] ) ? $result_context['accountUrl'] : home_url( '/tai-khoan/' ) ); ?>">
													<i class="fa-regular fa-clock" aria-hidden="true"></i>
													<span><?php esc_html_e( 'Xem lịch sử booking', 'travel-agency-modern' ); ?></span>
												</a>

												<?php if ( 'failed' === $result_context['result'] ) : ?>
													<button class="tam-button tam-button--ghost" type="button" data-step-back="3">
														<i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
														<span><?php esc_html_e( 'Quay lại thanh toán', 'travel-agency-modern' ); ?></span>
													</button>
												<?php endif; ?>
											</div>
										</div>
									<?php else : ?>
										<div class="tam-booking-result tam-booking-result--idle">
											<div class="tam-booking-result__hero">
												<div class="tam-booking-result__animation" aria-hidden="true">
													<span></span>
													<span></span>
													<span></span>
													<i class="fa-regular fa-hourglass-half"></i>
												</div>
												<div class="tam-booking-result__copy">
													<div class="tam-eyebrow"><?php esc_html_e( 'Step 4', 'travel-agency-modern' ); ?></div>
													<h2><?php esc_html_e( 'Chờ kết quả thanh toán', 'travel-agency-modern' ); ?></h2>
													<p><?php esc_html_e( 'Sau khi callback hoàn tất, màn hình này sẽ hiển thị biên nhận thanh toán và trạng thái chờ xác nhận từ nhân viên.', 'travel-agency-modern' ); ?></p>
												</div>
											</div>
										</div>
									<?php endif; ?>
								</section>
							</form>

							<aside class="tam-booking-flow__aside">
								<section class="tam-booking-summary tam-glass-card" data-booking-summary-card aria-live="polite">
									<div class="tam-booking-summary__cover" style="<?php echo esc_attr( '--tam-tour-image: url(\'' . esc_url( $checkout['visual'] ) . '\');' ); ?>">
										<img src="<?php echo esc_url( $checkout['visual'] ); ?>" alt="<?php echo esc_attr( $checkout['title'] ); ?>" loading="lazy" decoding="async" />
										<div class="tam-booking-summary__overlay">
											<span><?php esc_html_e( 'Premium checkout', 'travel-agency-modern' ); ?></span>
											<strong><?php echo esc_html( $checkout['title'] ); ?></strong>
										</div>
									</div>

									<div class="tam-booking-summary__body">
										<div class="tam-booking-summary__headline">
											<div>
												<div class="tam-eyebrow"><?php esc_html_e( 'Order summary', 'travel-agency-modern' ); ?></div>
												<h2><?php esc_html_e( 'Tổng thanh toán', 'travel-agency-modern' ); ?></h2>
											</div>
											<span class="tam-booking-summary__status" data-booking-summary-status><?php esc_html_e( 'Đã đồng bộ giá', 'travel-agency-modern' ); ?></span>
										</div>

										<div class="tam-booking-summary__metrics">
											<div class="tam-booking-summary__metric">
												<span><?php esc_html_e( 'Ngày đi', 'travel-agency-modern' ); ?></span>
												<strong data-summary-date><?php echo esc_html( $checkout['selected_date_text'] ); ?></strong>
											</div>
											<div class="tam-booking-summary__metric">
												<span><?php esc_html_e( 'Khách', 'travel-agency-modern' ); ?></span>
												<strong data-summary-headcount><?php echo esc_html( $checkout['people'] ); ?></strong>
											</div>
										</div>

										<ul class="tam-booking-summary__list">
											<li><span><?php esc_html_e( 'Hình thức', 'travel-agency-modern' ); ?></span><strong data-summary-payment-plan><?php esc_html_e( 'Thanh toán toàn bộ', 'travel-agency-modern' ); ?></strong></li>
											<li><span><?php esc_html_e( 'Giá người lớn', 'travel-agency-modern' ); ?></span><strong data-summary-adult-price><?php echo esc_html( tam_format_tour_price( (string) $adult_price ) ); ?></strong></li>
											<li><span><?php esc_html_e( 'Giá trẻ em', 'travel-agency-modern' ); ?></span><strong data-summary-child-price><?php echo esc_html( tam_format_tour_price( (string) $child_price ) ); ?></strong></li>
											<li><span><?php esc_html_e( 'Tạm tính', 'travel-agency-modern' ); ?></span><strong data-summary-subtotal><?php echo esc_html( tam_format_tour_price( (string) $subtotal ) ); ?></strong></li>
											<li><span><?php esc_html_e( 'Thuế', 'travel-agency-modern' ); ?></span><strong data-summary-tax><?php echo esc_html( tam_format_tour_price( (string) $tax_amount ) ); ?></strong></li>
											<li><span><?php esc_html_e( 'Phụ phí', 'travel-agency-modern' ); ?></span><strong data-summary-fee><?php echo esc_html( tam_format_tour_price( (string) $fee_amount ) ); ?></strong></li>
											<li class="is-discount"><span><?php esc_html_e( 'Giảm giá', 'travel-agency-modern' ); ?></span><strong data-summary-discount><?php echo esc_html( tam_format_tour_price( '0' ) ); ?></strong></li>
											<li><span><?php esc_html_e( 'Thanh toán hôm nay', 'travel-agency-modern' ); ?></span><strong data-summary-pay-now><?php echo esc_html( tam_format_tour_price( (string) $total_amount ) ); ?></strong></li>
											<li><span><?php esc_html_e( 'Còn lại', 'travel-agency-modern' ); ?></span><strong data-summary-remaining><?php echo esc_html( tam_format_tour_price( '0' ) ); ?></strong></li>
										</ul>

										<div class="tam-booking-summary__total">
											<span><?php esc_html_e( 'Tổng giá trị booking', 'travel-agency-modern' ); ?></span>
											<strong data-summary-total><?php echo esc_html( tam_format_tour_price( (string) $total_amount ) ); ?></strong>
										</div>

										<div class="tam-booking-summary__footnote">
											<i class="fa-solid fa-sparkles" aria-hidden="true"></i>
											<p data-booking-summary-message><?php esc_html_e( 'Giá sẽ được tính realtime mỗi khi bạn đổi ngày đi, số lượng khách hoặc coupon.', 'travel-agency-modern' ); ?></p>
										</div>
									</div>

									<div class="tam-booking-summary__skeleton" data-booking-summary-skeleton aria-hidden="true">
										<span></span>
										<span></span>
										<span></span>
									</div>
								</section>

								<section class="tam-booking-card tam-booking-sidebar-card tam-booking-sidebar-card--coupon">
									<div class="tam-booking-card__title">
										<h3><?php esc_html_e( 'Mã giảm giá', 'travel-agency-modern' ); ?></h3>
										<p><?php esc_html_e( 'Thử ADN10, SUMMER300 hoặc FAMILY5 để xem tổng tiền cập nhật tức thì trước khi thanh toán.', 'travel-agency-modern' ); ?></p>
									</div>
									<div class="tam-booking-flow__coupon-row">
										<input type="text" name="coupon_code" value="" maxlength="24" placeholder="<?php esc_attr_e( 'Nhập coupon', 'travel-agency-modern' ); ?>" data-booking-coupon />
										<button class="tam-button tam-button--ghost" type="button" data-apply-coupon><?php esc_html_e( 'Áp dụng', 'travel-agency-modern' ); ?></button>
									</div>
									<p class="tam-booking-flow__coupon-status" data-booking-coupon-status><?php esc_html_e( 'Tổng tiền sẽ cập nhật ngay khi bạn áp dụng mã.', 'travel-agency-modern' ); ?></p>
								</section>

								<section class="tam-booking-card tam-booking-sidebar-card tam-booking-sidebar-card--policy">
									<div class="tam-booking-card__title">
										<h3><?php esc_html_e( 'Chính sách thanh toán', 'travel-agency-modern' ); ?></h3>
										<p><?php esc_html_e( 'Quy trình được tối ưu để hạn chế lỗi thanh toán và xác nhận booking nhanh hơn.', 'travel-agency-modern' ); ?></p>
									</div>
									<ul class="tam-booking-flow__policy-list">
										<li>
											<i class="fa-regular fa-hourglass-half" aria-hidden="true"></i>
											<div>
												<strong><?php esc_html_e( 'Giữ chỗ & nhận thanh toán', 'travel-agency-modern' ); ?></strong>
												<p><?php esc_html_e( 'Booking được tạo trước khi chuyển cổng thanh toán. Sau callback thành công, hệ thống ghi nhận thanh toán toàn bộ qua VNPay ngay lập tức.', 'travel-agency-modern' ); ?></p>
											</div>
										</li>
										<li>
											<i class="fa-solid fa-shield-check" aria-hidden="true"></i>
											<div>
												<strong><?php esc_html_e( 'Xác minh giao dịch', 'travel-agency-modern' ); ?></strong>
												<p><?php esc_html_e( 'Callback hợp lệ sẽ đối soát transaction, chữ ký và payment status trước khi booking đi vào hàng chờ xác nhận.', 'travel-agency-modern' ); ?></p>
											</div>
										</li>
										<li>
											<i class="fa-regular fa-envelope-open-text" aria-hidden="true"></i>
											<div>
												<strong><?php esc_html_e( 'Xác nhận thủ công', 'travel-agency-modern' ); ?></strong>
												<p><?php esc_html_e( 'Email biên nhận được gửi ngay sau payment. Vé, QR và invoice chính thức chỉ gửi sau khi nhân viên confirm booking.', 'travel-agency-modern' ); ?></p>
											</div>
										</li>
									</ul>
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
