<template v-if="'send-email' === item.actions[ index ].action_id">
	<cx-vui-input
		label="<?php esc_html_e( 'Send to', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Email address of the recipient.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth', 'has-macros' ]"
		size="fullwidth"
		:value="item.actions[ index ].email_to"
		@on-input-change="setActionProp( index, 'email_to', $event.target.value )"
		ref="email_to"
	>
		<jet-abaf-settings-macros-inserter @input="addActionMacros( index, 'email_to', $event )"></jet-abaf-settings-macros-inserter>
	</cx-vui-input>

	<cx-vui-input
		label="<?php esc_html_e( 'Subject', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Subject line for the email.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth', 'has-macros' ]"
		size="fullwidth"
		:value="item.actions[ index ].email_subject"
		@on-input-change="setActionProp( index, 'email_subject', $event.target.value )"
		ref="email_subject"
	>
		<jet-abaf-settings-macros-inserter @input="addActionMacros( index, 'email_subject', $event )"></jet-abaf-settings-macros-inserter>
	</cx-vui-input>

	<cx-vui-input
		label="<?php esc_html_e( 'Send from', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Email address that will appear as the sender of the email.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth', 'has-macros' ]"
		size="fullwidth"
		:value="item.actions[ index ].email_from"
		@on-input-change="setActionProp( index, 'email_from', $event.target.value )"
		ref="email_from"
	>
		<jet-abaf-settings-macros-inserter @input="addActionMacros( index, 'email_from', $event )"></jet-abaf-settings-macros-inserter>
	</cx-vui-input>

	<cx-vui-input
		label="<?php esc_html_e( 'Send from name', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Name that will appear as the sender of the email.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth', 'has-macros' ]"
		size="fullwidth"
		:value="item.actions[ index ].email_from_name"
		@on-input-change="setActionProp( index, 'email_from_name', $event.target.value )"
		ref="email_from_name"
	>
		<jet-abaf-settings-macros-inserter @input="addActionMacros( index, 'email_from_name', $event )"></jet-abaf-settings-macros-inserter>
	</cx-vui-input>

	<cx-vui-textarea
		label="<?php esc_html_e( 'Message', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Main content of the email. This can include text, HTML, or a combination of both.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth', 'has-macros' ]"
		size="fullwidth"
		rows="6"
		:value="item.actions[ index ].email_message"
		@on-input-change="setActionProp( index, 'email_message', $event.target.value )"
		ref="email_message"
	>
		<jet-abaf-settings-macros-inserter @input="addActionMacros( index, 'email_message', $event )"></jet-abaf-settings-macros-inserter>
	</cx-vui-textarea>
</template>