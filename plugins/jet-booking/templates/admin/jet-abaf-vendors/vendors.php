<div class="jet-abaf-wrap">
	<header class="jet-abaf-header">
		<h1 class="jet-abaf-title">
			<?php esc_html_e( 'Vendors', 'jet-booking' ); ?>
		</h1>

		<cx-vui-button
			button-style="accent"
			size="mini"
			tag-name="a"
			url="<?php echo esc_url( add_query_arg( [ 'role' => jet_abaf()->vendors->role ], admin_url( 'user-new.php' ) ) ); ?>"
			target="_blank"
		>
			<template slot="label">
				<?php esc_html_e( 'Add New', 'jet-booking' ); ?>
			</template>
		</cx-vui-button>
	</header>

	<jet-abaf-vendors-list></jet-abaf-vendors-list>
</div>