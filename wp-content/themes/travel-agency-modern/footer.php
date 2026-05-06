<?php
/**
 * Site footer template.
 */

$contact = tam_get_contact_details();
?>
	<footer class="site-footer">
		<div class="tam-container">
			<div class="site-footer__grid">
				<div class="site-footer__brand">
					<h2 class="site-footer__heading"><?php bloginfo( 'name' ); ?></h2>
					<p><?php esc_html_e( 'Website tour du lịch có cấu trúc gọn gàng, dễ quản trị nội dung và sẵn sàng mở rộng thêm booking khi cần.', 'travel-agency-modern' ); ?></p>
				</div>
				<div>
					<h2 class="site-footer__heading"><?php esc_html_e( 'Điều hướng nhanh', 'travel-agency-modern' ); ?></h2>
					<nav class="footer-menu" aria-label="<?php esc_attr_e( 'Menu chân trang', 'travel-agency-modern' ); ?>">
						<?php if ( has_nav_menu( 'footer' ) ) : ?>
							<?php
							wp_nav_menu(
								array(
									'theme_location' => 'footer',
									'container'      => false,
									'menu_class'     => 'menu',
									'depth'          => 1,
								)
							);
							?>
						<?php elseif ( has_nav_menu( 'primary' ) ) : ?>
							<?php
							wp_nav_menu(
								array(
									'theme_location' => 'primary',
									'container'      => false,
									'menu_class'     => 'menu',
									'depth'          => 1,
								)
							);
							?>
						<?php else : ?>
							<ul class="menu">
								<?php wp_list_pages( array( 'title_li' => '' ) ); ?>
							</ul>
						<?php endif; ?>
					</nav>
				</div>
				<div class="site-footer__meta">
					<h2 class="site-footer__heading"><?php esc_html_e( 'Thông tin liên hệ', 'travel-agency-modern' ); ?></h2>
					<p><strong><?php esc_html_e( 'Điện thoại:', 'travel-agency-modern' ); ?></strong> <a href="<?php echo esc_url( $contact['tel_url'] ); ?>"><?php echo esc_html( $contact['phone'] ); ?></a></p>
					<p><strong><?php esc_html_e( 'Email:', 'travel-agency-modern' ); ?></strong> <a href="mailto:<?php echo esc_attr( $contact['email'] ); ?>"><?php echo esc_html( $contact['email'] ); ?></a></p>
					<p><strong><?php esc_html_e( 'Văn phòng:', 'travel-agency-modern' ); ?></strong> <?php echo esc_html( $contact['address'] ); ?></p>
					<p><a href="<?php echo esc_url( $contact['chat_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Mở kênh chat nhanh', 'travel-agency-modern' ); ?></a></p>
				</div>
			</div>

			<div class="site-footer__bottom">
				<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>.</p>
				<p><a href="<?php echo esc_url( tam_get_page_url_by_path( 'lien-he' ) ); ?>"><?php esc_html_e( 'Liên hệ hợp tác', 'travel-agency-modern' ); ?></a></p>
			</div>
		</div>
	</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
