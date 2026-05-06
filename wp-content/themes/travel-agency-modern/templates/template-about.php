<?php
/**
 * Template Name: Giới thiệu nổi bật
 *
 * @package Travel_Agency_Modern
 */

get_header();

$about = tam_get_about_page_sections();

while ( have_posts() ) :
	the_post();

	tam_render_page_intro(
		array(
			'eyebrow'     => __( 'Giới thiệu', 'travel-agency-modern' ),
			'title'       => get_the_title(),
			'description' => has_excerpt() ? get_the_excerpt() : __( 'Trang giới thiệu được mở rộng theo tinh thần của dự án tham chiếu: kể chuyện thương hiệu rõ hơn, nhiều điểm tin cậy hơn và có CTA cụ thể hơn.', 'travel-agency-modern' ),
			'image'       => tam_get_hero_image_url( get_the_ID() ),
		)
	);
	?>
	<main id="main-content" class="site-main">
		<section class="tam-section tam-section--compact">
			<div class="tam-container tam-layout--wide">
				<div class="tam-content-card tam-rich-content">
					<?php the_content(); ?>
				</div>
				<div class="tam-sidebar-stack">
					<div class="tam-value-card">
						<strong><?php esc_html_e( 'Tư duy của phiên bản này', 'travel-agency-modern' ); ?></strong>
						<p><?php esc_html_e( 'Tập trung vào bộ khung bán hàng cần thiết nhất: homepage để chốt hướng, page Tour để duyệt nhanh, single tour để lấy lead và contact page để liên hệ thương hiệu.', 'travel-agency-modern' ); ?></p>
					</div>
					<div class="tam-value-card">
						<strong><?php esc_html_e( 'Nội dung dễ cập nhật', 'travel-agency-modern' ); ?></strong>
						<p><?php esc_html_e( 'Toàn bộ phần văn bản chính của trang giới thiệu vẫn dùng WordPress editor, nên bạn có thể sửa thông điệp thương hiệu mà không cần sửa code.', 'travel-agency-modern' ); ?></p>
					</div>
				</div>
			</div>
		</section>

		<section class="tam-section tam-section--compact">
			<div class="tam-container">
				<div class="tam-mission-grid">
					<div class="tam-mission-panel">
						<div class="tam-eyebrow"><?php esc_html_e( 'Mission', 'travel-agency-modern' ); ?></div>
						<h2 class="tam-section-title"><?php echo esc_html( $about['mission_title'] ); ?></h2>
						<p class="tam-section-subtitle"><?php echo esc_html( $about['mission_description'] ); ?></p>
					</div>
					<div class="tam-value-grid tam-value-grid--three">
						<?php foreach ( $about['values'] as $value ) : ?>
							<div class="tam-value-card">
								<strong><?php echo esc_html( $value['title'] ); ?></strong>
								<p><?php echo esc_html( $value['description'] ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</section>

		<section class="tam-section tam-section--compact">
			<div class="tam-container">
				<div class="tam-section-head">
					<div>
						<div class="tam-eyebrow"><?php esc_html_e( 'Chỉ số', 'travel-agency-modern' ); ?></div>
						<h2 class="tam-section-title"><?php esc_html_e( 'Những gì đã được làm rõ hơn trong bản hoàn thiện này', 'travel-agency-modern' ); ?></h2>
					</div>
				</div>
				<div class="tam-stats-grid">
					<?php foreach ( $about['stats'] as $stat ) : ?>
						<div class="tam-stat-card">
							<strong><?php echo esc_html( $stat['value'] ); ?></strong>
							<span><?php echo esc_html( $stat['label'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="tam-section tam-section--compact">
			<div class="tam-container">
				<div class="tam-section-head">
					<div>
						<div class="tam-eyebrow"><?php esc_html_e( 'Vì sao chọn', 'travel-agency-modern' ); ?></div>
						<h2 class="tam-section-title"><?php esc_html_e( 'Những lớp giao diện mới giúp website thuyết phục hơn', 'travel-agency-modern' ); ?></h2>
					</div>
				</div>
				<div class="tam-value-grid tam-value-grid--three">
					<?php foreach ( $about['reasons'] as $reason ) : ?>
						<div class="tam-value-card">
							<strong><?php echo esc_html( $reason['title'] ); ?></strong>
							<p><?php echo esc_html( $reason['description'] ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="tam-section tam-section--compact">
			<div class="tam-container">
				<div class="tam-section-head">
					<div>
						<div class="tam-eyebrow"><?php esc_html_e( 'Đội ngũ', 'travel-agency-modern' ); ?></div>
						<h2 class="tam-section-title"><?php esc_html_e( 'Đội ngũ của chúng tôi', 'travel-agency-modern' ); ?></h2>
						<p class="tam-section-subtitle"><?php esc_html_e( 'Ba thành viên giữ cho DNA Travel vừa có giao diện chỉn chu, vừa có luồng đặt tour mượt và dễ mở rộng về sau.', 'travel-agency-modern' ); ?></p>
					</div>
				</div>
				<div class="tam-team-grid">
					<?php foreach ( $about['team'] as $member ) : ?>
						<article class="tam-team-card">
							<div class="tam-team-card__avatar-wrap">
								<?php if ( ! empty( $member['avatar'] ) ) : ?>
									<img class="tam-team-card__avatar" src="<?php echo esc_url( $member['avatar'] ); ?>" alt="<?php echo esc_attr( $member['name'] ); ?>" loading="lazy" />
								<?php else : ?>
									<div class="tam-avatar-badge" aria-hidden="true"><?php echo esc_html( tam_get_initials( $member['name'] ) ); ?></div>
								<?php endif; ?>
							</div>
							<div class="tam-team-card__body">
								<h3><?php echo esc_html( $member['name'] ); ?></h3>
								<p class="tam-team-card__role"><?php echo esc_html( $member['role'] ); ?></p>
								<p class="tam-team-card__description"><?php echo esc_html( $member['description'] ); ?></p>
							</div>
							<?php if ( ! empty( $member['socials'] ) ) : ?>
								<div class="tam-team-card__socials">
									<?php foreach ( $member['socials'] as $social ) : ?>
										<a class="tam-team-card__social-link" href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $social['label'] ); ?>">
											<?php if ( 'facebook' === $social['network'] ) : ?>
												<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
													<path d="M13.5 21v-7.2h2.4l.36-2.8H13.5V9.21c0-.81.23-1.36 1.39-1.36h1.48V5.34c-.26-.03-1.13-.1-2.14-.1-2.12 0-3.58 1.3-3.58 3.68V11H8.25v2.8h2.45V21h2.8Z"/>
												</svg>
											<?php elseif ( 'linkedin' === $social['network'] ) : ?>
												<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
													<path d="M6.94 8.5A1.69 1.69 0 1 0 6.93 5.1a1.69 1.69 0 0 0 .01 3.38ZM5.5 9.75h2.87V19.5H5.5V9.75Zm4.67 0h2.75v1.33h.04c.38-.72 1.32-1.49 2.72-1.49 2.91 0 3.45 1.92 3.45 4.41v5.5h-2.87v-4.87c0-1.16-.02-2.66-1.62-2.66-1.62 0-1.87 1.27-1.87 2.58v4.95h-2.87V9.75Z"/>
												</svg>
											<?php endif; ?>
										</a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="tam-section tam-section--compact">
			<div class="tam-container">
				<div class="tam-section-head">
					<div>
						<div class="tam-eyebrow"><?php esc_html_e( 'Quy trình', 'travel-agency-modern' ); ?></div>
						<h2 class="tam-section-title"><?php esc_html_e( 'Khách hàng đi từ xem tour đến gửi nhu cầu như thế nào', 'travel-agency-modern' ); ?></h2>
					</div>
				</div>
				<div class="tam-process-grid">
					<div class="tam-process-card">
						<strong><?php esc_html_e( '1. Tìm tour nhanh', 'travel-agency-modern' ); ?></strong>
						<p><?php esc_html_e( 'Khách vào trang Tour và lọc theo điểm đến hoặc từ khóa quan tâm.', 'travel-agency-modern' ); ?></p>
					</div>
					<div class="tam-process-card">
						<strong><?php esc_html_e( '2. Xem thông tin cần thiết', 'travel-agency-modern' ); ?></strong>
						<p><?php esc_html_e( 'Single tour ưu tiên summary panel để khách thấy giá, lịch trình và CTA sớm.', 'travel-agency-modern' ); ?></p>
					</div>
					<div class="tam-process-card">
						<strong><?php esc_html_e( '3. Để lại lead và nhận tư vấn', 'travel-agency-modern' ); ?></strong>
						<p><?php esc_html_e( 'Form tư vấn, hotline và chat nhanh giúp đội ngũ tiếp nhận nhu cầu linh hoạt hơn ngay từ lần chạm đầu.', 'travel-agency-modern' ); ?></p>
					</div>
				</div>
			</div>
		</section>

		<section class="tam-section tam-section--compact">
			<div class="tam-container">
				<div class="tam-cta-banner">
					<div class="tam-cta-banner__inner">
						<div>
							<h2><?php echo esc_html( $about['cta_title'] ); ?></h2>
							<p><?php echo esc_html( $about['cta_description'] ); ?></p>
						</div>
						<div class="tam-home-hero__actions">
							<a class="tam-button tam-button--ghost" href="<?php echo esc_url( tam_get_page_url_by_path( 'lien-he' ) ); ?>"><?php esc_html_e( 'Trang Liên hệ', 'travel-agency-modern' ); ?></a>
							<a class="tam-button tam-button--secondary" href="<?php echo esc_url( tam_get_page_url_by_path( 'tour' ) ); ?>"><?php esc_html_e( 'Danh sách tour', 'travel-agency-modern' ); ?></a>
						</div>
					</div>
				</div>
			</div>
		</section>
	</main>
<?php endwhile; ?>
<?php
get_footer();
