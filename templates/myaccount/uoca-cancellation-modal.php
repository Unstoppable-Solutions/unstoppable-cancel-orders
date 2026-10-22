<?php
/**
 * Cancellation modal.
 *
 * Override by copying to yourtheme/woocommerce/myaccount/uoca-cancellation-modal.php.
 *
 * @package Unstoppable_Cancel_Orders
 *
 * @var string   $modal_title   Modal title.
 * @var string   $modal_message Modal message.
 * @var string   $submit_text   Submit button text.
 * @var string   $reason_field  off|optional|required.
 * @var string[] $reasons       Reasons.
 * @var string   $notes_field   off|optional|required.
 * @var string   $button_class  Extra button class for block themes.
 */

defined( 'ABSPATH' ) || exit;

$uoca_show_reasons = 'off' !== $reason_field && ! empty( $reasons );
$uoca_show_notes   = 'off' !== $notes_field;
?>
<div id="uoca-modal" class="uoca-modal" hidden>
	<div class="uoca-modal-backdrop" data-uoca-close></div>
	<div class="uoca-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="uoca-modal-title">
		<div class="uoca-modal-header">
			<h2 id="uoca-modal-title"><?php echo esc_html( $modal_title ); ?></h2>
			<button type="button" class="uoca-modal-close" data-uoca-close aria-label="<?php esc_attr_e( 'Close', 'unstoppable-cancel-orders' ); ?>">&times;</button>
		</div>

		<div class="uoca-modal-body uoca-done" hidden>
			<div class="uoca-success woocommerce-message" role="status"></div>
			<div class="uoca-modal-footer">
				<button type="button" class="<?php echo esc_attr( trim( 'button alt uoca-done-button ' . $button_class ) ); ?>" data-uoca-close><?php esc_html_e( 'Close', 'unstoppable-cancel-orders' ); ?></button>
			</div>
		</div>

		<form id="uoca-form" class="uoca-modal-body" novalidate>
			<p class="uoca-modal-message"><?php echo esc_html( $modal_message ); ?></p>

			<div class="uoca-error woocommerce-error" role="alert" hidden></div>

			<?php if ( $uoca_show_reasons ) : ?>
				<fieldset class="uoca-field uoca-reasons">
					<legend>
						<?php esc_html_e( 'Reason for cancellation', 'unstoppable-cancel-orders' ); ?>
						<?php if ( 'required' === $reason_field ) : ?>
							<abbr class="required" title="<?php esc_attr_e( 'required', 'unstoppable-cancel-orders' ); ?>">*</abbr>
						<?php endif; ?>
					</legend>
					<?php foreach ( $reasons as $uoca_index => $uoca_reason ) : ?>
						<label class="uoca-reason">
							<input type="radio" name="uoca_reason" value="<?php echo esc_attr( $uoca_reason ); ?>" />
							<span><?php echo esc_html( $uoca_reason ); ?></span>
						</label>
					<?php endforeach; ?>
				</fieldset>
			<?php endif; ?>

			<?php if ( $uoca_show_notes ) : ?>
				<p class="uoca-field uoca-notes">
					<label for="uoca-notes">
						<?php esc_html_e( 'Additional notes', 'unstoppable-cancel-orders' ); ?>
						<?php if ( 'required' === $notes_field ) : ?>
							<abbr class="required" title="<?php esc_attr_e( 'required', 'unstoppable-cancel-orders' ); ?>">*</abbr>
						<?php else : ?>
							<span class="optional"><?php esc_html_e( '(optional)', 'unstoppable-cancel-orders' ); ?></span>
						<?php endif; ?>
					</label>
					<textarea id="uoca-notes" name="uoca_notes" rows="4" maxlength="1000"></textarea>
				</p>
			<?php endif; ?>

			<div class="uoca-modal-footer">
				<button type="button" class="<?php echo esc_attr( trim( 'button uoca-keep-button ' . $button_class ) ); ?>" data-uoca-close><?php esc_html_e( 'Keep my order', 'unstoppable-cancel-orders' ); ?></button>
				<button type="submit" class="<?php echo esc_attr( trim( 'button alt uoca-submit-button ' . $button_class ) ); ?>"><?php echo esc_html( $submit_text ); ?></button>
			</div>
		</form>
	</div>
</div>
