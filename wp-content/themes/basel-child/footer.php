<?php
/**
 * The template for displaying the footer
 *
 */
?>
<?php if (basel_needs_footer()): ?>
	<?php basel_page_bottom_part(); ?>

	<?php if ( basel_get_opt( 'prefooter_area' ) != '' ): ?>
		<div class="basel-prefooter">
			<div class="container">
				<?php echo do_shortcode( basel_get_opt( 'prefooter_area' ) ); ?>
			</div>
		</div>
	<?php endif ?>

	<!-- FOOTER -->
	<footer class="footer-container color-scheme-<?php echo esc_attr( basel_get_opt( 'footer-style' ) ); ?>">
		
		<?php 
		if ( basel_get_opt( 'disable_footer' ) ) {
			get_sidebar( 'footer' ); 
		}
		?>

		<?php if ( basel_get_opt( 'disable_copyrights' ) ): ?>
			<div class="copyrights-wrapper copyrights-<?php echo esc_attr( basel_get_opt( 'copyrights-layout' ) ); ?>">
				<div class="container">
					<div class="min-footer">
						<div class="col-left">
							<?php if ( basel_get_opt( 'copyrights' ) != ''): ?>
								<?php echo do_shortcode( basel_get_opt( 'copyrights' ) ); ?>
							<?php else: ?>
								<p>&copy; <?php echo date( 'Y' ); ?> <a href="<?php echo esc_url( home_url('/') ); ?>"><?php bloginfo( 'name' ); ?></a>. <?php _e( 'All rights reserved', 'basel' ) ?></p>
							<?php endif ?>
						</div>
						<?php if ( basel_get_opt( 'copyrights2' ) != ''): ?>
							<div class="col-right">
								<?php echo do_shortcode( basel_get_opt( 'copyrights2' ) ); ?>
							</div>
						<?php endif ?>
					</div>
				</div>
			</div>
		<?php endif ?>
		
	</footer>
<?php endif ?>
</div> <!-- end wrapper -->

<div class="basel-close-side"></div>
<div id="bl-chatbot-box">
    <div class="close-chat-box">
        <i class="fa fa-close"></i>
    </div>
    <div class="tab-content">
        <div id="faq-chat" class="tab-pane fade in active">
            <div class="topbar container-fluid" id="chat-text" style="background-color: #fafafa"></div>
            <form id="dialogflow-form">
                <span style="width:100%;" id="inputSpan">
                    <input class="inputbox"
                           placeholder="Write something and press Enter..." id="message" name="date" value="" x-webkit-speech>
                </span>
                <input name="submit" type="hidden" value="Submit">
            </form>
        </div>
        <div id="faq-bot" class="tab-pane fade">
            <iframe
                allow="microphone;"
                width="350"
                height="430"
                src="https://console.dialogflow.com/api-client/demo/embedded/7eb6f5f9-c299-4ebe-906e-1630e4ca8848">
            </iframe>
        </div>
    </div>
    <div id="bl-chatbot-select">
        <ul class="nav nav-tabs">
            <li class="active"><a data-toggle="tab" href="#faq-chat">Chat</a></li>
            <li><a data-toggle="tab" href="#faq-bot">FAQ</a></li>
        </ul>
    </div>
</div>

<?php
$event = '';
if (isset($_GET["course"])){
    $event = $_GET['course'];
}
$sessionID = bin2hex(random_bytes(16));
?>
<span style="display: none;" id="sessionId">
        <?php
        echo $sessionID;
        ?>
</span>
<span style="display: none;" id="course">
        <?php
        echo $event;
        ?>
</span>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/showdown/1.9.0/showdown.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>

    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            jQuery("#lightSlider").lightSlider();
            $('.close-chat-box').click(function(){
                $('#bl-chatbot-box').toggleClass('hide');
            })
        });
    </script>

<?php wp_footer(); ?>

</body>
</html>