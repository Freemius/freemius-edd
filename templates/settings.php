<?php
	/**
	 * @package     Freemius for EDD Add-On
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.0
	 */

	/**
	 * @var FS_Webhook $webhook
	 */
	$webhook = $VARS['webhook'];

	$developer    = $webhook->get_developer();
	$is_connected = $webhook->is_connected();
?>
<div class="wrap">
	<h2><?php printf( __fs( 'freemius-x-settings' ), WP_FS__COMMERCE_NAME ) ?></h2>

	<?php if ( ! $is_connected ) : ?>
		<p><?php printf(
				__fs( 'api-instructions' ),
				sprintf( '<a target="_blank" href="https://dashboard.freemius.com">%s</a>', __fs( 'login-to-fs' ) )
			) ?></p>
	<?php endif ?>

	<form method="post" action="">
		<input type="hidden" name="fs_action" value="save_settings">
		<?php wp_nonce_field( 'save_settings' ) ?>
		<table id="fs_api_settings" class="form-table">
			<tbody>
			<tr>
				<th><h3><?php _efs( 'api-settings' ) ?></h3></th>
				<td>
					<hr>
				</td>
			</tr>
			<tr>
				<th><?php _efs( 'id' ) ?></th>
				<td><input id="fs_id" name="fs_id" type="number"
				           value="<?php echo $developer->id ?>"<?php if ( $is_connected ) {
						echo ' readonly';
					} ?>/></td>
			</tr>
			<tr>
				<th><?php _efs( 'public-key' ) ?></th>
				<td><input name="fs_public_key" type="text" value="<?php echo $developer->public_key ?>"
				           placeholder="pk_XXXXXXXXXXXXXXXXXXXXXXXXXXXXX" maxlength="32"
				           style="width: 320px"<?php if ( $is_connected ) {
						echo ' readonly';
					} ?>/></td>
			</tr>
			<tr>
				<th><?php _efs( 'secret-key' ) ?></th>
				<td><input name="fs_secret_key" type="text" value="<?php echo $developer->secret_key ?>"
				           placeholder="sk_XXXXXXXXXXXXXXXXXXXXXXXXXXXXX" maxlength="32"
				           style="width: 320px"<?php if ( $is_connected ) {
						echo ' readonly';
					} ?>/></td>
			</tr>
			</tbody>
		</table>
		<table class="form-table" style="display: none;">
			<tbody>
			<tr>
				<th><h3><?php _efs( 'secure-webhook' ) ?></h3></th>
				<td>
					<hr>
				</td>
			</tr>
			<tr>
				<th><?php _efs( 'endpoint' ) ?></th>
				<td>
					<input type="hidden" id="fs_token" name="token" value="<?php echo $webhook->get_token() ?>"/>
					<input type="text" id="fs_endpoint"
					       value="<?php echo site_url( WP_FS__WEBHOOK_ENDPOINT ) ?>/?token=<?php echo $webhook->get_token() ?>"
					       readonly class="large-text"/>
					<button id="fs_regenerate" class="button"><? _efs( 'regenerate' ) ?></button>
				</td>
			</tr>
			</tbody>
		</table>
		<p class="submit"><input type="submit" name="submit" id="fs_submit" class="button<?php if ( ! $is_connected ) {
				echo ' button-primary';
			} ?>"
		                         value="<?php _efs( $is_connected ? 'edit-settings' : 'save-changes' ) ?>"/></p>
	</form>
</div>
<script>
	(function ($) {
		var inputs = $('#fs_api_settings input');

		inputs.on('keyup keypress', function () {
			var has_empty = false;
			for (var i = 0, len = inputs.length; i < len; i++) {
				if ('' === $(inputs[i]).val()) {
					has_empty = true;
					break;
				}
			}

			if (has_empty)
				$('#fs_submit').attr('disabled', 'disabled');
			else
				$('#fs_submit').removeAttr('disabled');
		});

		$(inputs[0]).keyup();

		$('#fs_submit').click(function () {
			if (!$(this).hasClass('button-primary')) {
				inputs.removeAttr('readonly');
				$(inputs[0]).focus().select();

				$(this)
					.addClass('button-primary')
					.val('<?php _efs('save-changes') ?>');

				return false;
			}

			return true;
		});

		$('#fs_regenerate').click(function () {
			// Show loading.
			$(this).html('<?php _efs( 'fetching-token' ) ?>');

			$.post(ajaxurl, {
				action: 'fs_get_secure_token'
			}, function (token) {
				$('#fs_token').val(token);
				$('#fs_endpoint').val('<?php echo site_url( WP_FS__WEBHOOK_ENDPOINT ) ?>/?token=' + token);

				// Recover button's label.
				$('#fs_regenerate').html('<?php _efs( 'regenerate' ) ?>');
			});

			return false;
		});
	})(jQuery);
</script>