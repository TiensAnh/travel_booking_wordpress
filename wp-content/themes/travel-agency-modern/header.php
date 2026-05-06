<?php
/**
 * Site header template.
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#main-content"><?php esc_html_e( 'Bỏ qua và đến nội dung chính', 'travel-agency-modern' ); ?></a>
<div id="page" class="site">
	<header class="site-header">
		<div class="tam-container site-header__inner">
			<div class="site-branding">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php endif; ?>
				<div class="site-branding__text">
					<?php if ( is_front_page() ) : ?>
						<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
					<?php else : ?>
						<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></p>
					<?php endif; ?>
					<?php if ( get_bloginfo( 'description' ) ) : ?>
						<p class="site-description"><?php bloginfo( 'description' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<nav class="site-header__nav primary-menu" aria-label="<?php esc_attr_e( 'Điều hướng chính', 'travel-agency-modern' ); ?>">
				<?php if ( has_nav_menu( 'primary' ) ) : ?>
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

			<div class="site-header__actions">
				<?php if ( is_user_logged_in() ) : ?>
					<a class="tam-button tam-button--ghost tam-auth-user-link" href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>">
						<?php esc_html_e( 'Tài khoản', 'travel-agency-modern' ); ?>
					</a>
				<?php else : ?>
					<button class="tam-button tam-auth-trigger" type="button" data-auth-open="login">
						<?php esc_html_e( 'Đăng nhập / Đăng ký', 'travel-agency-modern' ); ?>
					</button>
				<?php endif; ?>
				<button class="menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-controls="mobile-panel">
					<?php esc_html_e( 'Menu', 'travel-agency-modern' ); ?>
				</button>
			</div>
		</div>

		<div id="mobile-panel" class="site-mobile-panel" data-mobile-panel>
			<button class="menu-close" type="button" data-menu-close><?php esc_html_e( 'Đóng', 'travel-agency-modern' ); ?></button>
			<nav class="primary-menu" aria-label="<?php esc_attr_e( 'Điều hướng trên di động', 'travel-agency-modern' ); ?>">
				<?php if ( has_nav_menu( 'primary' ) ) : ?>
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
			<div class="site-mobile-panel__actions">
				<?php if ( is_user_logged_in() ) : ?>
					<a class="tam-button tam-button--ghost tam-auth-user-link" href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>">
						<?php esc_html_e( 'Tài khoản', 'travel-agency-modern' ); ?>
					</a>
				<?php else : ?>
					<button class="tam-button tam-auth-trigger" type="button" data-auth-open="login">
						<?php esc_html_e( 'Đăng nhập / Đăng ký', 'travel-agency-modern' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>
	</header>
