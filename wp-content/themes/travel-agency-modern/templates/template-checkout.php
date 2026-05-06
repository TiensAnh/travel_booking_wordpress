<?php
/**
 * Template Name: Thanh toán tour
 *
 * @package Travel_Agency_Modern
 */

get_header();

while ( have_posts() ) :
	the_post();

	$checkout = tam_get_checkout_context();
	$current_redirect_url = tam_get_page_url_by_path( 'thanh-toan', '/thanh-toan/' );

	if ( ! empty( $checkout['has_tour'] ) ) {
		$current_redirect_url = add_query_arg(
			array(
				'tour_id'     => $checkout['post_id'],
				'travel_date' => $checkout['selected_date'],
				'party_size'  => $checkout['people'],
			),
			$current_redirect_url
		);
	}
	?>
	<main id="main-content" class="site-main">
		<section class="tam-section tam-section--compact tam-checkout">
			<div class="tam-container">
				<div class="tam-checkout__header">
					<div>
						<div class="tam-eyebrow"><?php esc_html_e( 'Checkout', 'travel-agency-modern' ); ?></div>
						<h1 class="tam-section-title"><?php the_title(); ?></h1>
						<p class="tam-section-subtitle">
							<?php esc_html_e( 'Xác nhận nhanh thông tin khách hàng, kiểm tra lại tour và chọn phương thức thanh toán phù hợp trước khi ADN Travel giữ chỗ cho bạn.', 'travel-agency-modern' ); ?>
						</p>
					</div>
					<?php if ( ! empty( $checkout['has_tour'] ) ) : ?>
						<a class="tam-checkout__backlink" href="<?php echo esc_url( get_permalink( $checkout['post_id'] ) ); ?>">
							<?php esc_html_e( 'Quay lại chi tiết tour', 'travel-agency-modern' ); ?>
						</a>
					<?php endif; ?>
				</div>

				<?php echo wp_kses_post( tam_get_checkout_notice_markup() ); ?>

				<?php if ( empty( $checkout['has_tour'] ) ) : ?>
					<div class="tam-empty-state">
						<strong><?php esc_html_e( 'Chưa có tour để thanh toán.', 'travel-agency-modern' ); ?></strong>
						<p><?php esc_html_e( 'Bạn hãy chọn một tour trước, sau đó quay lại trang này để hoàn tất thông tin đặt chỗ.', 'travel-agency-modern' ); ?></p>
						<a class="tam-button" href="<?php echo esc_url( tam_get_page_url_by_path( 'tour' ) ); ?>"><?php esc_html_e( 'Xem danh sách tour', 'travel-agency-modern' ); ?></a>
					</div>
				<?php else : ?>
					<form class="tam-checkout__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" novalidate data-checkout-form>
						<?php wp_nonce_field( 'tam_checkout_form', 'tam_checkout_nonce' ); ?>
						<input type="hidden" name="action" value="tam_submit_checkout_form" />
						<input type="hidden" name="redirect_to" value="<?php echo esc_url( $current_redirect_url ); ?>" />
						<input type="hidden" name="tour_id" value="<?php echo esc_attr( $checkout['post_id'] ); ?>" />
						<input type="hidden" name="tour_title" value="<?php echo esc_attr( $checkout['title'] ); ?>" />

						<div class="tam-checkout__layout">
							<div class="tam-checkout__main">
								<section class="tam-checkout__card">
									<div class="tam-checkout__card-head">
										<div class="tam-eyebrow"><?php esc_html_e( 'Thông tin khách hàng', 'travel-agency-modern' ); ?></div>
										<h2><?php esc_html_e( 'Điền thông tin liên hệ', 'travel-agency-modern' ); ?></h2>
									</div>

									<div class="tam-checkout__grid">
										<div class="tam-checkout__field" data-checkout-field>
											<label for="tam-checkout-name"><?php esc_html_e( 'Họ tên', 'travel-agency-modern' ); ?></label>
											<input type="text" id="tam-checkout-name" name="customer_name" required data-checkout-required />
											<small><?php esc_html_e( 'Tên người đại diện nhận xác nhận từ ADN Travel.', 'travel-agency-modern' ); ?></small>
										</div>

										<div class="tam-checkout__field" data-checkout-field>
											<label for="tam-checkout-email"><?php esc_html_e( 'Email', 'travel-agency-modern' ); ?></label>
											<input type="email" id="tam-checkout-email" name="customer_email" required data-checkout-required />
											<small><?php esc_html_e( 'Dùng để nhận xác nhận đặt chỗ và hướng dẫn tiếp theo.', 'travel-agency-modern' ); ?></small>
										</div>

										<div class="tam-checkout__field tam-checkout__field--full" data-checkout-field>
											<label for="tam-checkout-phone"><?php esc_html_e( 'Số điện thoại', 'travel-agency-modern' ); ?></label>
											<input type="text" id="tam-checkout-phone" name="customer_phone" required data-checkout-required />
											<small><?php esc_html_e( 'Nhân viên sẽ gọi để xác nhận lịch khởi hành và số lượng khách.', 'travel-agency-modern' ); ?></small>
										</div>

										<div class="tam-checkout__field tam-checkout__field--full" data-checkout-field>
											<label for="tam-checkout-note"><?php esc_html_e( 'Ghi chú', 'travel-agency-modern' ); ?></label>
											<textarea id="tam-checkout-note" name="customer_note" rows="5" placeholder="<?php esc_attr_e( 'Ví dụ: cần hỗ trợ phòng gia đình, muốn ghép đoàn hoặc cần tư vấn thêm về phương tiện đón trả.', 'travel-agency-modern' ); ?>"></textarea>
										</div>
									</div>
								</section>

								<section class="tam-checkout__card">
									<div class="tam-checkout__card-head">
										<div class="tam-eyebrow"><?php esc_html_e( 'Thông tin đặt tour', 'travel-agency-modern' ); ?></div>
										<h2><?php esc_html_e( 'Kiểm tra lại hành trình', 'travel-agency-modern' ); ?></h2>
									</div>

									<div class="tam-checkout__tour-card">
										<div class="tam-checkout__tour-media">
											<img src="<?php echo esc_url( $checkout['visual'] ); ?>" alt="<?php echo esc_attr( $checkout['title'] ); ?>" />
										</div>
										<div class="tam-checkout__tour-copy">
											<h3><?php echo esc_html( $checkout['title'] ); ?></h3>
											<p><?php echo esc_html( $checkout['destination'] ); ?></p>
											<div class="tam-checkout__tour-meta">
												<span><?php echo esc_html( $checkout['duration'] ); ?></span>
												<span><?php echo esc_html( $checkout['departure_label'] ); ?></span>
											</div>
										</div>
									</div>

									<div class="tam-checkout__grid">
										<div class="tam-checkout__field tam-checkout__field--full" data-checkout-field>
											<label for="tam-checkout-date"><?php esc_html_e( 'Ngày khởi hành', 'travel-agency-modern' ); ?></label>
											<select id="tam-checkout-date" name="departure_date" required data-checkout-required data-checkout-date>
												<?php foreach ( $checkout['departure_options'] as $option ) : ?>
													<option value="<?php echo esc_attr( $option['value'] ); ?>" <?php selected( $checkout['selected_date'], $option['value'] ); ?>>
														<?php echo esc_html( $option['label'] ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</div>

										<div class="tam-checkout__field" data-checkout-field>
											<label for="tam-checkout-people"><?php esc_html_e( 'Số lượng người', 'travel-agency-modern' ); ?></label>
											<input type="number" id="tam-checkout-people" name="people" min="1" max="30" value="<?php echo esc_attr( $checkout['people'] ); ?>" required data-checkout-required data-checkout-people />
										</div>

										<div class="tam-checkout__field" data-checkout-field>
											<label><?php esc_html_e( 'Giá mỗi người', 'travel-agency-modern' ); ?></label>
											<div class="tam-checkout__readonly" data-checkout-price><?php echo esc_html( $checkout['price_display'] ); ?></div>
											<input type="hidden" name="price_each" value="<?php echo esc_attr( $checkout['price_raw'] ); ?>" />
										</div>
									</div>
								</section>

								<section class="tam-checkout__card">
									<div class="tam-checkout__card-head">
										<div class="tam-eyebrow"><?php esc_html_e( 'Phương thức thanh toán', 'travel-agency-modern' ); ?></div>
										<h2><?php esc_html_e( 'Chọn cách thanh toán phù hợp', 'travel-agency-modern' ); ?></h2>
									</div>

									<div class="tam-checkout__payment-list">
										<?php foreach ( $checkout['payment_methods'] as $method_key => $method ) : ?>
											<label class="tam-checkout__payment-option<?php echo 'cod' === $method_key ? ' is-selected' : ''; ?>" data-checkout-payment-option>
												<input type="radio" name="payment_method" value="<?php echo esc_attr( $method_key ); ?>" <?php checked( 'cod', $method_key ); ?> data-checkout-payment />
												<span class="tam-checkout__payment-icon"><?php echo esc_html( $method['icon'] ); ?></span>
												<span class="tam-checkout__payment-copy">
													<strong><?php echo esc_html( $method['label'] ); ?></strong>
													<small><?php echo esc_html( $method['description'] ); ?></small>
												</span>
												<?php if ( 'wallet' === $method_key ) : ?>
													<span class="tam-checkout__wallets">
														<em>MoMo</em>
														<em>ZaloPay</em>
														<em>VNPay</em>
													</span>
												<?php endif; ?>
											</label>
										<?php endforeach; ?>
									</div>
								</section>

								<section class="tam-checkout__agree" data-checkout-field>
									<label class="tam-checkout__agree-label">
										<input type="checkbox" name="accept_terms" value="1" required data-checkout-required />
										<span><?php esc_html_e( 'Tôi đồng ý với điều khoản đặt tour và chính sách thanh toán của DNA Travel.', 'travel-agency-modern' ); ?></span>
									</label>
								</section>

								<div class="tam-checkout__error" data-checkout-error></div>
							</div>

							<aside class="tam-checkout__aside">
								<section class="tam-checkout__summary tam-summary-card">
									<div class="tam-checkout__summary-head">
										<div class="tam-eyebrow"><?php esc_html_e( 'Order summary', 'travel-agency-modern' ); ?></div>
										<h2><?php esc_html_e( 'Tóm tắt đơn hàng', 'travel-agency-modern' ); ?></h2>
									</div>

									<div class="tam-checkout__summary-tour">
										<div class="tam-checkout__summary-thumb">
											<img src="<?php echo esc_url( $checkout['visual'] ); ?>" alt="<?php echo esc_attr( $checkout['title'] ); ?>" />
										</div>
										<div>
											<strong><?php echo esc_html( $checkout['title'] ); ?></strong>
											<span data-checkout-summary-date><?php echo esc_html( $checkout['selected_date_text'] ); ?></span>
										</div>
									</div>

									<ul class="tam-checkout__summary-list">
										<li>
											<span><?php esc_html_e( 'Giá / khách', 'travel-agency-modern' ); ?></span>
											<strong data-checkout-summary-price><?php echo esc_html( $checkout['price_display'] ); ?></strong>
										</li>
										<li>
											<span><?php esc_html_e( 'Số lượng', 'travel-agency-modern' ); ?></span>
											<strong data-checkout-summary-people><?php echo esc_html( $checkout['people'] ); ?></strong>
										</li>
										<li>
											<span><?php esc_html_e( 'Phương thức', 'travel-agency-modern' ); ?></span>
											<strong data-checkout-summary-payment><?php echo esc_html( $checkout['payment_methods']['cod']['label'] ); ?></strong>
										</li>
									</ul>

									<div class="tam-checkout__summary-total">
										<span><?php esc_html_e( 'Tổng tiền', 'travel-agency-modern' ); ?></span>
										<strong data-checkout-total data-base-price="<?php echo esc_attr( $checkout['price_raw'] ); ?>"><?php echo esc_html( $checkout['total_display'] ); ?></strong>
									</div>

									<button type="submit" class="tam-button tam-button--accent tam-checkout__submit">
										<?php esc_html_e( 'Thanh toán', 'travel-agency-modern' ); ?>
									</button>

									<p class="tam-checkout__summary-note">
										<?php esc_html_e( 'Sau khi gửi, ADN Travel sẽ xác nhận lại lịch đi, tổng giá và hướng dẫn thanh toán chi tiết theo phương thức bạn đã chọn.', 'travel-agency-modern' ); ?>
									</p>
								</section>
							</aside>
						</div>
					</form>
				<?php endif; ?>
			</div>
		</section>
	</main>
<?php endwhile; ?>
<?php
get_footer();
