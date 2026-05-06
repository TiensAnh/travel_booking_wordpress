<?php
/**
 * 404 template.
 */

get_header();

tam_render_page_intro(
	array(
		'eyebrow'     => __( '404', 'travel-agency-modern' ),
		'title'       => __( 'Không tìm thấy trang bạn đang cần', 'travel-agency-modern' ),
		'description' => __( 'Đường dẫn này có thể đã thay đổi hoặc nội dung đã được cập nhật. Bạn thử quay lại danh sách tour hoặc trang liên hệ để tiếp tục.', 'travel-agency-modern' ),
		'image'       => tam_get_hero_image_url(),
	)
);
?>
<main id="main-content" class="site-main">
	<section class="tam-section tam-section--compact">
		<div class="tam-container">
			<div class="tam-empty-state">
				<strong><?php esc_html_e( 'Trang hiện tại không tồn tại.', 'travel-agency-modern' ); ?></strong>
				<p><?php esc_html_e( 'Hãy thử xem trang Tour để duyệt các lịch trình, hoặc mở trang Liên hệ để nhận tư vấn trực tiếp.', 'travel-agency-modern' ); ?></p>
				<div class="tam-home-hero__actions" style="justify-content:center;margin-top:24px;">
					<a class="tam-button" href="<?php echo esc_url( tam_get_page_url_by_path( 'tour' ) ); ?>"><?php esc_html_e( 'Đến trang Tour', 'travel-agency-modern' ); ?></a>
					<a class="tam-button tam-button--ghost" href="<?php echo esc_url( tam_get_page_url_by_path( 'lien-he' ) ); ?>"><?php esc_html_e( 'Trang Liên hệ', 'travel-agency-modern' ); ?></a>
				</div>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
