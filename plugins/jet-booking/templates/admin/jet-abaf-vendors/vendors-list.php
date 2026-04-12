<div class="jet-abaf-vendors-list">
	<cx-vui-list-table
		:is-empty="!Object.keys( vendors ).length"
		empty-message="<?php esc_html_e( 'No vendors found.', 'jet-booking' ); ?>"
	>
		<cx-vui-list-table-heading slot="heading" :slots="[ 'id', 'username', 'name', 'email', 'posts', 'bookings', 'profile' ]">
			<span slot="id"><?php esc_html_e( 'ID', 'jet-booking' ); ?></span>
			<span slot="username"><?php esc_html_e( 'Username', 'jet-booking' ); ?></span>
			<span slot="name"><?php esc_html_e( 'Name', 'jet-booking' ); ?></span>
			<span slot="email"><?php esc_html_e( 'Email', 'jet-booking' ); ?></span>
			<span slot="posts"><?php esc_html_e( 'Posts', 'jet-booking' ); ?></span>
			<span slot="bookings"><?php esc_html_e( 'Bookings', 'jet-booking' ); ?></span>
			<span slot="profile"><?php esc_html_e( 'Profile', 'jet-booking' ); ?></span>
		</cx-vui-list-table-heading>

		<cx-vui-list-table-item
			slot="items"
			:slots="[ 'id', 'username', 'name', 'email', 'posts', 'bookings', 'profile' ]"
			v-for="( vendor, id ) in vendors"
			:key="id"
		>
			<template slot="id">{{ id }}</template>
			<template slot="username">{{ vendor.username }}</template>
			<template slot="name">{{ vendor.name }}</template>
			<template slot="email">
				<a :href="'mailto:' + vendor.email">{{ vendor.email }}</a>
			</template>

			<template slot="posts">
				<span v-for="( post, postType ) in vendor.posts" :key="postType">
					<template v-if="post.url?.length">
						{{ post.name + ': ' }}
						<a :href="post.url" target="_blank">{{ post.count }}</a>
					</template>
					<template v-else>{{ post.name + ': ' + post.count }}</template>
					<br>
				</span>
			</template>

			<template slot="bookings">
				<a v-if="vendor.bookings.url?.length" :href="vendor.bookings.url" target="_blank">{{ vendor.bookings.count }}</a>
				<template v-else>{{ vendor.bookings.count }}</template>
			</template>

			<template slot="profile">
				<a :href="vendor.profile" target="_blank">
					<span class="dashicons dashicons-external"></span>
				</a>
			</template>
		</cx-vui-list-table-item>
	</cx-vui-list-table>
</div>