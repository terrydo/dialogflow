<?php
    get_header()
?>
<div id="bl-chatbot-box">
    <div class="tab-content">
        <div id="faq-chat" class="tab-pane fade in active">
            <div class="topbar container-fluid" id="chat-text" style="background-color: #fafafa">
            </div>
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
<?php get_footer() ?>