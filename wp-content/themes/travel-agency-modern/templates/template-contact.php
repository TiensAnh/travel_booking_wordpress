<?php
/**
 * Template Name: Liên hệ nổi bật
 *
 * @package Travel_Agency_Modern
 */

get_header();

$contact = tam_get_contact_details();
$faqs    = tam_get_contact_faq_items();

while ( have_posts() ) :
	the_post();

	tam_render_page_intro(
		array(
			'eyebrow'     => __( 'Liên hệ', 'travel-agency-modern' ),
			'title'       => get_the_title(),
			'description' => has_excerpt() ? get_the_excerpt() : __( 'Liên hệ với ADN Travel để nhận tư vấn nhanh, báo giá phù hợp và hỗ trợ chọn hành trình theo nhu cầu của bạn.', 'travel-agency-modern' ),
			'image'       => tam_get_hero_image_url( get_the_ID() ),
		)
	);
	?>
	<main id="main-content" class="site-main">
		<section class="tam-section tam-section--compact">
			<div class="tam-container">
				<div class="tam-contact-grid">
					<div class="tam-contact-card">
						<strong><?php esc_html_e( 'Hotline', 'travel-agency-modern' ); ?></strong>
						<p><a href="<?php echo esc_url( $contact['tel_url'] ); ?>"><?php echo esc_html( $contact['phone'] ); ?></a></p>
					</div>
					<div class="tam-contact-card">
						<strong><?php esc_html_e( 'Email', 'travel-agency-modern' ); ?></strong>
						<p><a href="mailto:<?php echo esc_attr( $contact['email'] ); ?>"><?php echo esc_html( $contact['email'] ); ?></a></p>
					</div>
					<div class="tam-contact-card">
						<strong><?php esc_html_e( 'Chat nhanh', 'travel-agency-modern' ); ?></strong>
						<p><a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $contact['chat_url'] ); ?>"><?php esc_html_e( 'Mở kênh chat / Zalo / WhatsApp', 'travel-agency-modern' ); ?></a></p>
					</div>
					<div class="tam-contact-card">
						<strong><?php esc_html_e( 'Văn phòng', 'travel-agency-modern' ); ?></strong>
						<p><?php echo esc_html( $contact['address'] ); ?></p>
					</div>
				</div>

				<div class="tam-layout--wide">
					<div class="tam-content-card tam-rich-content">
						<?php the_content(); ?>
						<div style="margin-top:28px;">
							<div class="tam-eyebrow"><?php esc_html_e( 'Form tư vấn', 'travel-agency-modern' ); ?></div>
							<h2 class="tam-section-title"><?php esc_html_e( 'Gửi nhu cầu trực tiếp cho đội ngũ', 'travel-agency-modern' ); ?></h2>
							<p class="tam-section-subtitle"><?php esc_html_e( 'Form mới vừa gửi email thông báo, vừa lưu yêu cầu vào WordPress để đội ngũ có thể theo dõi lại kể cả khi SMTP chưa sẵn sàng.', 'travel-agency-modern' ); ?></p>
						</div>
						<div style="margin-top:20px;">
							<?php tam_render_contact_capture_form(); ?>
						</div>
					</div>

					<div class="tam-sidebar-stack" style="margin-top:24px;">
						<div class="tam-value-card">
							<strong><?php esc_html_e( 'Giờ làm việc gợi ý', 'travel-agency-modern' ); ?></strong>
							<p><?php esc_html_e( 'Thứ 2 - Thứ 7: 08:00 - 20:00. Chủ nhật và ngoài giờ, khách vẫn có thể để lại form hoặc chat nhanh.', 'travel-agency-modern' ); ?></p>
						</div>
						<div class="tam-value-card">
							<strong><?php esc_html_e( 'Quy trình tiếp nhận rõ ràng', 'travel-agency-modern' ); ?></strong>
							<p><?php esc_html_e( 'Mọi nhu cầu gửi từ form đều được lưu lại trong WordPress để đội ngũ gọi lại, xử lý và bổ sung thông tin nhanh hơn.', 'travel-agency-modern' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</section>

		<section class="tam-section tam-section--compact">
			<div class="tam-container">
				<div class="tam-section-head">
					<div>
						<div class="tam-eyebrow"><?php esc_html_e( 'FAQ', 'travel-agency-modern' ); ?></div>
						<h2 class="tam-section-title"><?php esc_html_e( 'Những câu hỏi khách thường hỏi trước khi chốt tour', 'travel-agency-modern' ); ?></h2>
						<p class="tam-section-subtitle"><?php esc_html_e( 'Khu vực này được thêm theo bản tham chiếu để trang Liên hệ không chỉ là một form, mà còn giúp khách tự gỡ bớt băn khoăn trước khi nhắn.', 'travel-agency-modern' ); ?></p>
					</div>
				</div>
				<div class="tam-faq-grid">
					<?php foreach ( $faqs as $index => $faq ) : ?>
						<details class="tam-faq-item" <?php echo 0 === $index ? 'open' : ''; ?>>
							<summary><?php echo esc_html( $faq['question'] ); ?></summary>
							<div class="tam-faq-item__body">
								<p><?php echo esc_html( $faq['answer'] ); ?></p>
							</div>
						</details>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section id="newsletter-signup" class="tam-section tam-section--compact">
			<div class="tam-container">
				<div class="tam-newsletter-panel tam-newsletter-panel--soft">
					<div class="tam-newsletter-panel__copy">
						<div class="tam-eyebrow"><?php esc_html_e( 'Bản tin', 'travel-agency-modern' ); ?></div>
						<h2 class="tam-section-title"><?php esc_html_e( 'Giữ liên lạc với khách chưa chốt ngay lần đầu', 'travel-agency-modern' ); ?></h2>
						<p class="tam-section-subtitle"><?php esc_html_e( 'Ngoài form tư vấn, đây là lớp giao diện giúp website có thêm nhịp chăm sóc mềm hơn cho những người vẫn đang cân nhắc hành trình.', 'travel-agency-modern' ); ?></p>
						<?php tam_render_newsletter_capture_form( 'trang-lien-he' ); ?>
					</div>
					<div class="tam-newsletter-panel__visual" aria-hidden="true">
						<div class="tam-newsletter-note">
							<strong><?php esc_html_e( 'Dùng tốt cho giai đoạn đổ nội dung thật', 'travel-agency-modern' ); ?></strong>
							<p><?php esc_html_e( 'Khi chưa chạy booking online, newsletter vẫn là một điểm chạm hữu ích để giữ lại khách đang tham khảo tour và nuôi lại về sau.', 'travel-agency-modern' ); ?></p>
						</div>
						<div class="tam-chip-list">
							<span class="tam-chip tam-chip--light"><?php esc_html_e( 'Lịch mới', 'travel-agency-modern' ); ?></span>
							<span class="tam-chip tam-chip--light"><?php esc_html_e( 'Ưu đãi kín', 'travel-agency-modern' ); ?></span>
							<span class="tam-chip tam-chip--light"><?php esc_html_e( 'Tour cuối tuần', 'travel-agency-modern' ); ?></span>
						</div>
					</div>
				</div>
			</div>
		</section>
	</main>
<?php endwhile; ?>
<?php
get_footer();
