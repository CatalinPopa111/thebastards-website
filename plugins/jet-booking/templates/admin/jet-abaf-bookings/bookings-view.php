<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly. ?>

<div class="jet-abaf-bookings-view">
	<cx-vui-button
		v-for="( view, key ) in views"
		:key="key"
		:button-style="viewButtonStyle( key )"
		size="mini"
		@click="updateView( key )"
	>
		<template slot="label">{{ view.label }}</template>
	</cx-vui-button>
</div>
