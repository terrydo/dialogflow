/**
 * Created by aravind on 4/28/17.
 */

(function($){

    var customQuery = null;

    var baseUrl = $('base').attr('href');
    var data = {
        description: 'TEst'
    };
    var converter = new showdown.Converter();
    var dialogForm = $('#dialogflow-form');

    dialogForm.on('submit', function (e) {
        e.preventDefault();

        var query = customQuery ? customQuery : $("#message").val();
        

        guid = ($("#sessionId").text()).trim();
        
        if (!customQuery) {
            showUserText();
        }

        customQuery = null;

        $.ajax({
            type: 'post',
            url: baseUrl + '/chat-process',
            data: {submit:true, message:query, sessionid: guid},
            success: function (response) {
                $("#message").removeAttr("disabled");
                $('#message').focus();
                var responseObj = JSON.parse(response);
                var defaultResponse = null;
                if(responseObj.defaultResponse){
                    defaultResponse = responseObj.defaultResponse;
                }
                // var speech = responseObj.speech;
                var messages = responseObj.messages;
                var eoc = responseObj.isEndOfConversation;

                var answerRow = jQuery('<div/>',{
                    'class':'row'
                });
                var answerCol = jQuery('<div/>',{
                    'class':'col-xs-12'
                });
                var answerContainerDiv = jQuery('<div/>',{
                    'class':"float-right",
                    tabindex:0
                });

                $('#chat-text').append(answerRow);
                $(answerRow).append(answerCol);
                $(answerCol).append(answerContainerDiv);


                var textFromDefaultResponse = defaultResponse;
                if (textFromDefaultResponse && textFromDefaultResponse.trim()!==''){
                    renderDefaultResponse(textFromDefaultResponse,answerContainerDiv);
                }

                renderRichControls(messages, answerContainerDiv);

                // var isDisabled = $('#message').prop('disabled');
                if(eoc){
                    $('#message').attr("disabled","disabled");
                    $('#chat-text').append('<hr/>');
                    var divMessage = $('<div/>',{
                        class:'d-flex justify-content-center'
                    });
                    var btnStartOver = $('<button/>',{
                        class:'btn btn-sm',
                        text:'Start Over'
                    });
                    var textStartOver = $('<h5/>',{
                        html:'End of Conversation'
                    });
                    $(divMessage).append(textStartOver);
                    $(btnStartOver).css('margin-left','10px');
                    $(divMessage).append(btnStartOver);
                    $('#chat-text').append(divMessage);
                    $(btnStartOver).click(function(){
                        var textToSubmit = 'start over';
                        $("#message").val(textToSubmit);
                        dialogForm.trigger( "submit" );
                        $(divMessage).addClass('disabledbutton')
                    });
                }
                var objDiv = document.getElementById("chat-text");
                objDiv.scrollTop = objDiv.scrollHeight;
            }
        });

    });

    function renderDefaultResponse(textFromDefaultResponse,parent){
        var simpleResponseRow = jQuery('<div/>',{
            class:'row'
        });
        var simpleResponseDiv = jQuery('<div/>',{
            class:'textResponse'
        });
        $(simpleResponseRow).append(simpleResponseDiv);
        $(simpleResponseDiv).html(md2html(textFromDefaultResponse));
        parent.append(simpleResponseRow);
    }

    function renderRating(ratingData,parent) {
        var ratingDiv = jQuery('<div/>', {
            class: 'rating'
        });

        var ratingText = jQuery('<div/>', {
            class: 'rating__text'
        }).css({
            'text-align': 'right'
        });

        var ratingStarContainer = jQuery('<div/>', {
            class: 'rating__starContainer'
        }).css({
            'margin-top': '7px',
            'text-align': 'right'
        });

        ratingText.text(ratingData.rating);
        ratingDiv.append(ratingText);

        var sessionId = $("#sessionId").text().trim();

        var ratingStars = [];

        for (var i = 1; i <= 5; i++) {
            (function(i){
                var ratingStar = jQuery('<a/>', {
                    class: 'rating__star'
                }).css({
                    'padding-right': '8px',
                    'font-size': '1.2em'
                }).html('<i class="fa fa-star"></i>');
                
                ratingStars.push(ratingStar[0]);

                ratingStar.css({
                    color: '#000',
                    transition: 'none',
                    display: 'inline-block',
                    cursor: 'pointer'
                });

                ratingStar.hover(function(e){
                    e.stopPropagation();

                    $(ratingStars).css('color', '#000');
                    for (var j = 0; j < i; j++) {
                        (function(j){
                            $(ratingStars[j]).css('color', '#d14');
                        })(j)
                    }
                })

                ratingStar.mouseleave(function(){
                    $(ratingStars).css('color', '#000');
                })


                var spinnerDOM = $('<i/>').addClass('fa fa-spinner fa-spin');

                ratingStar.click(function(e){
                    e.stopPropagation();

                    $.ajax({
                        url: wp.ajax.settings.url,
                        data: {
                            action: 'send_chatbot_rating',
                            sessionId: sessionId,
                            star: i
                        },
                        method: 'POST',
                        beforeSend: function() {
                            ratingDiv.remove();
                            parent.append(spinnerDOM);
                        },
                        success: function() {
                            spinnerDOM.remove();
                            var thankyou = jQuery('<div/>').css({'text-align': 'right'}).text('Thank you for your feedback!');
                            parent.append(thankyou);
                        }
                    })
                })
    
                ratingStarContainer.append(ratingStar);
            })(i)
        }

        ratingDiv.append(ratingStarContainer);
        parent.append(ratingDiv);

        guid = ($("#sessionId").text()).trim();
    }

    function renderRichControls(data, parent){
        var i,len = data.length;
        console.log(data, 'data');
        for(i=0;i<len;i++){
            if(data[i] && data[i].hasOwnProperty('platform')){
                if(data[i]['platform']==='ACTIONS_ON_GOOGLE'){
                    if(data[i].hasOwnProperty('simpleResponses')){
                        renderSimpleResponse(data[i],parent);
                    }
                    if(data[i].hasOwnProperty('basicCard')){
                        renderBasicCard(data[i],parent);
                    }
                    if(data[i].hasOwnProperty('listSelect')){
                        renderList(data[i],parent);
                    }
                    if(data[i].hasOwnProperty('suggestions')){
                        renderSuggestionChips(data[i],parent);
                    }
                    if(data[i].hasOwnProperty('linkOutSuggestion')){
                        renderLinkOutSuggestion(data[i],parent);
                    }
                }
                if(data[i]['type']==='list_card' &&
                    data[i]['platform']==='ACTIONS_ON_GOOGLE'){
                    renderList(data[i],parent);
                }
                if(data[i]['type']==='carousel_card' &&
                    data[i]['platform']==='ACTIONS_ON_GOOGLE'){
                    renderCarousel(data[i],parent);
                }
            }

            if(data[i] && data[i]['rating']) {
                renderRating(data[i], parent);
            }
        }

        for(i=0;i<len;i++){
            if(data[i] && data[i].hasOwnProperty('type')){
                if(data[i]['type']==='suggestion_chips' &&
                    data[i]['platform']==='google'){
                    renderSuggestionChips(data[i],parent);
                }
            }
        }

    }

    function renderList(data,parent){
        data = data['listSelect'];
        var i, len = data['items'].length;
        var listGroup = jQuery('<div/>',{
            'class':'list-group card gaListGroup'
        });
        if(data['title']){
            var titleOfCard = data['title'];
            var listGroupHeading = jQuery('<div/>',{
                'class':'gaListHeader card-header deep-orange lighten-1 white-text',
                'html':titleOfCard
            });
            listGroup.append(listGroupHeading);
        }
        for(i=0;i<len;i++){
            var item = data['items'][i];
            if(item){
                var optionTitle = item["title"];
                var optionDescription = item["description"];
                var optionKey = item['info']['key'];
                var imageUrl;
                if(item["image"]){
                    imageUrl = item["image"]["imageUri"];
                }
                var anchor = jQuery('<a/>',{
                    'data-key':optionKey,
                    'class':'gaListItem list-group-item py-0 list-group-item-action flex-column ' +
                    'align-items-start'
                });
                anchor.click(function(){
                    if(window.currentSuggestionChips){
                        var buttonRow = window.currentSuggestionChips;
                        buttonRow.remove();
                        window.currentSuggestionChips = null;
                        $("#message").removeAttr("disabled");
                    }
                    var textToSubmit = $(this).attr('data-key');
                    $("#message").val(textToSubmit);
                    dialogForm.trigger( "submit" );
                    $(listGroup).addClass('disabledbutton');
                });
                var headingDiv = jQuery('<div/>',{
                });
                var heading = jQuery('<div/>',{
                    'class':'card-title',
                    'html':optionTitle
                });
                heading.css("font-weight","bold");
                var row = jQuery('<div/>',{
                    'class':'row'
                });
                var colSpanText = 'col';
                if(imageUrl) colSpanText = 'col-8';
                var colText = jQuery('<div/>',{
                    'class':colSpanText
                });
                var colImage =jQuery('<div/>',{
                    'class':'col-4'
                });
                var para = jQuery('<p/>',{
                    'class':'mb-1',
                    'html':optionDescription
                });

                if(imageUrl){
                    var img = jQuery('<img/>',{
                        'class':'img-fluid',
                        'src':imageUrl,
                        'width':'50px'
                    });
                    colImage.append(img);
                }
                row.append(colText);
                if(imageUrl) row.append(colImage);
                headingDiv.append(heading);
                headingDiv.append(para);
                colText.append(headingDiv);
                anchor.append(row);
                listGroup.append(anchor);
            }
        }
        parent.append(listGroup);
        $("#message").attr("disabled","disabled");
    }

    function renderCarousel(data,parent){
        var i, len = data['items'].length;
        var carouselContainer = jQuery('<div/>',{
            'width':'550px'
        });
        $(carouselContainer).addClass('gaCarousel');
        var listGroup = jQuery('<ul/>',{

        });
        for(i=0;i<len;i++){
            var item = data['items'][i];
            if(item){
                var optionTitle = truncateString(item["title"],20);
                var optionDescription = item["description"];
                var optionKey = item["optionInfo"]["key"];
                var imageUrl = item["image"]["url"];
                var listItem = jQuery('<li/>',{});
                var cardDiv = jQuery('<div/>',{
                    'width':'200px'
                });
                var permalink = item["permalink"];

                $(cardDiv).addClass('gaCarouselItem');
                var anchor = jQuery('<a/>',{
                    'data-key':optionKey,
                    'class':'list-group-item list-group-item-action flex-column '+
                    'align-items-start'
                });
                anchor.click(function(){
                    if(window.currentSuggestionChips){
                        var buttonRow = window.currentSuggestionChips;
                        buttonRow.remove();
                        window.currentSuggestionChips = null;
                        $("#message").removeAttr("disabled");
                    }

                    // User wants to redirect
                    if (permalink) {
                        window.open(
                            permalink,
                            '_blank'
                        );
                        return;
                    }

                    var textToSubmit = $(this).attr('data-key');
                    $("#message").val(textToSubmit);
                    dialogForm.trigger( "submit" );
                    $(carouselContainer).addClass('disabledbutton');
                });
                var heading = jQuery('<div/>',{
                    'class':'card-title',
                    'text': optionTitle
                });
                heading.css("font-weight","bold");
                var para = jQuery('<p/>',{
                    'class':'mb-1',
                    'html':md2html(optionDescription)
                });
                var divForImage = jQuery('<div/>',{
                    'class':'card-title'
                });
                divForImage.css("height","100px");
                var img = jQuery('<img/>',{
                    'class':'img-fluid',
                    'src':imageUrl,
                    'width':'100px'
                });
                img.css("margin-left","auto");
                img.css("margin-right","auto");
                img.css("display","block");
                divForImage.append(img);

                cardDiv.append(divForImage);
                anchor.append(heading);
                anchor.append(para);
                cardDiv.append(anchor);
                listItem.append(cardDiv);
                listGroup.append(listItem);
            }
        }
        parent.append(carouselContainer);
        carouselContainer.append(listGroup);
        if ($(listGroup).length) {
            $(listGroup).lightSlider({
                // autoWidth:true
                item:2,
                loop:false,
                slideMove:1,
                easing: 'cubic-bezier(0.25, 0, 0.25, 1)',
                speed:600,
                responsive : [
                    {
                        breakpoint:800,
                        settings: {
                            item:2,
                            slideMove:1,
                            slideMargin:6,
                          }
                    },
                    {
                        breakpoint:480,
                        settings: {
                            item:1,
                            slideMove:1
                          }
                    }
                ]
            })
        };

        // $("#message").attr("disabled","disabled");
    }

    function renderBasicCard(data,parent){
        data = data['basicCard'];
        var cardDiv = jQuery('<div/>',{
            'class':'card gaCard'
        });
        var img = jQuery('<img/>',{
            'class':'gaCardImage',
            'src':data['image']['imageUri']
        });
        var cardBodyDiv = jQuery('<div/>',{
            'class':'card-body'
        });
        var strTitle = truncateString(data['title'],28);
        var cardTitleContainerDiv = jQuery('<h5/>',{
            'class':'card-title',
            'html':md2html(strTitle)
        });

        var textContainerPara = jQuery('<p/>',{
            'class':'card-text',
            'html':md2html(data['formattedText'])
        });

        var linkDiv = $('<div/>');
        var buttons_array = data['buttons'];
        if (typeof buttons_array !== 'undefined' && buttons_array.length > 0) {
            // the array is defined and has at least one element
            var link = $("<a>");
            link.attr('href',(data['buttons'][0])['openUriAction']['uri']);
            link.attr("title",(data['buttons'][0])['title']);
            link.text((data['buttons'][0])['title']);
            link.addClass("card-link");
            linkDiv.append(link);
        }

        cardDiv.append(img);
        cardBodyDiv.append(cardTitleContainerDiv);
        cardBodyDiv.append(textContainerPara);
        cardBodyDiv.append(linkDiv);
        cardDiv.append(cardBodyDiv);
        parent.append(cardDiv);
    }

    function md2html(input){
        input = input.replace(/\n{2,}/g, m => m.replace(/\n/g, "<br/>"));
        input = input.replace(/<br\/>([^<])/g, "<br\/>\n\n$1");
        html      = converter.makeHtml(input);
        return html;
    }

    var description = data.description.replace(/\n{2,}/g, m => m.replace(/\n/g, "<br/>"));
    description = description.replace(/<br\/>([^<])/g, "<br\/>\n\n$1");
    var html = converter.makeHtml(description);

    function renderSimpleResponse(data, parent){
        var simpleResponseDiv = jQuery('<div/>',{
            'class':'row'
        });
        var simpleResponseInnerDiv = jQuery('<div/>',{
            'class':'textResponse gaSimpleResponse'
        });
        var simpleResponseText = jQuery('<p/>',{
            html:md2html(data['simpleResponses'][0]['textToSpeech']),
            tabindex:1
        });
        simpleResponseDiv.append(simpleResponseInnerDiv);
        simpleResponseInnerDiv.append(simpleResponseText);
        parent.append(simpleResponseDiv);
    }

    function renderLinkOutSuggestion(data, parent){
        data = data['linkOutSuggestion'];
        var linkoutDiv = jQuery('<div/>', {
            tabindex:1,
            'class': "card gaLinkOutSuggestion"
        });
        var linkoutInnerDiv = jQuery('<div/>',{
            'class':'card-body'
        });
        var linkOutAnchor = jQuery('<a/>',{
            text:data['destinationName']
        });
        $(linkOutAnchor).attr("href",data['uri']);
        $(linkOutAnchor).attr("target","_blank");
        $(linkOutAnchor).attr("title",data['destinationName']);
        linkoutDiv.append(linkoutInnerDiv);
        linkoutInnerDiv.append(linkOutAnchor);
        parent.append(linkoutDiv);
    }

    function renderSuggestionChips(data,parent){
        var suggestions = data['suggestions'];
        var i, len = suggestions.length;
        var buttonRowDiv = jQuery('<div/>',{
            class:'row'
        });
        var suggestionChipRowDiv = jQuery('<div/>',{
            class:'gaSuggestionChipRow'
        });

        for (i = 0; i < len; i++) {
            if (suggestions[i]) {
                //make a button for it
                var buttonText = suggestions[i]['title'];
                var button = jQuery('<button/>',{
                    type:'button',
                    class:'btn btn-primary btn-sm gaSuggestionChipButton',
                    text:buttonText
                });

                button.click(function(){
                    var textToSubmit = this.textContent;
                    suggestionChipRowDiv.remove();
                    window.currentSuggestionChips = null;
                    $("#message").removeAttr("disabled");
                    $("#message").val(textToSubmit);
                    dialogForm.trigger( "submit" );
                });
            }
            suggestionChipRowDiv.append(button);
        }

        $(buttonRowDiv).append(suggestionChipRowDiv);
        $(parent).append(buttonRowDiv);
        window.currentSuggestionChips = suggestionChipRowDiv;
        //also disable the manual input
        $("#message").attr("disabled","disabled");
    }

    function getValueByKey(key, data) {
        var i, len = data.length;

        for (i = 0; i < len; i++) {
            if (data[i] && data[i].hasOwnProperty(key)) {
                return data[i][key];
            }
        }

        return -1;
    }

    function sendGAEvent(category, action, label){

    }

    function showUserText(){
        var userMessageRow = jQuery('<div/>',{
            class:'row'
        });
        var div = jQuery('<div/>', {
            text: $("#message").val(),
            'class': "rounded-div",
            tabindex:1
        });
        $(userMessageRow).append(div);
        $("#chat-text" ).append(userMessageRow);
        $("#message").val('');
    }

    function truncateString(input, charLimit){
        if(input.length > charLimit) {
            return input.truncate(charLimit)+"...";
        }
        else{
            return input;
        }
    }

    String.prototype.truncate = String.prototype.truncate ||
        function (n){
            return this.slice(0,n);
        };

    +function initChatbotMsg() {
        customQuery = "Hello";
        dialogForm.trigger('submit');
    }()
})(jQuery)