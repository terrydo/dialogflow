<?php
/**
 * Template Name: Chat Process
 */

require __DIR__.'/vendor/autoload.php';

const CREDENTIALS_DIR = __DIR__ . '\client-key.json';
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . CREDENTIALS_DIR);

/**
 * Add product carousel to message data
 * @param [Array] $args Query arguments
 * @param [Array] $messages The Messages object to add carousel data
 * @param [Array] $push_data
 */
function createChatbotProductCarousel ($args, &$messages) {
    $my_query = null;
    $my_query = new WP_Query($args);

    // For later use when current customer chooses a product from carousel.
    if ( empty($_SESSION['latestProductCarousel']) ) {
        $_SESSION['latestProductCarousel'] = [];
    }

    $push_data = [
        "items" => [],
        "platform" => "ACTIONS_ON_GOOGLE",
        "type" => "carousel_card"
    ];

    if ($my_query->have_posts()) : while ($my_query->have_posts()) : $my_query->the_post();
        
        $product = wc_get_product( get_the_ID() );
        $product_name = get_the_title();
        $image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'single-post-thumbnail' )[0];
        $price = get_woocommerce_currency_symbol() . $product->get_price();

        $push_data["items"][] = [
            "ID" => $product->get_id(),
            "description" => $price,
            "permalink" => get_the_permalink(),
            "image" => [
                "url" => $image,
                "accessibilityText" => $product_name
            ],
            "optionInfo" => [
                "key" => "itemOne",
                "synonyms" => [
                    "thing one",
                    "object one"
                ]
            ],
            "title" => $product_name
        ];
    endwhile;wp_reset_query();endif;

    $messages[] = $push_data;
    
    $_SESSION["latestProductCarousel"] = $push_data["items"];

    return $push_data;
}

try {
    if(isset($_POST['submit'])){
        
        // create curl resource
        $ch = curl_init();
        $userquery = $_POST['message'];
        $query = curl_escape($ch,$_POST['message']);
        $sessionid = curl_escape($ch,$_POST['sessionid']);

        $client = new \Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->setScopes (['https://www.googleapis.com/auth/dialogflow']);
        $filename = CREDENTIALS_DIR;
        if (!file_exists($filename)) {
            $client_secret_file = getenv('CLIENT_SECRET');
            $auth_settings = json_decode($client_secret_file);
        }else{
            $auth_settings= json_decode(file_get_contents($filename));
        }

        $auth = [
            "type" => $auth_settings->type,
            "project_id" => $auth_settings->project_id,
            "private_key" => $auth_settings->private_key,
            "client_email" => $auth_settings->client_email,
            "client_id" => $auth_settings->client_id,
            "auth_uri" => $auth_settings->auth_uri,
            "token_uri" => $auth_settings->token_uri,
            "auth_provider_x509_cert_url" => $auth_settings->auth_provider_x509_cert_url,
            "client_x509_cert_url" => $auth_settings->client_x509_cert_url
        ];

        $client->setSubject($auth_settings->client_email);
        $client->setAuthConfig($auth);

        $httpClient = $client->authorize();

        $apiUrl = 'https://dialogflow.googleapis.com/v2/projects/'.$auth_settings->project_id.'/agent/sessions/'.$sessionid.':detectIntent';
        
        $response = $httpClient->request('POST',$apiUrl,[
            'json' => [
                'queryInput' => [
                    'text'=> [
                        'text'=> $userquery,
                        'languageCode'=>'en'
                    ]
                ],
                'queryParams'=>['timeZone'=>'Asia/Calcutta']
            ]
        ]);

        $contents = $response->getBody()->getContents();

        $dec = json_decode($contents);

        $defaultResponse = '';
        $hasDefaultResponse = false;
        if( isset( $dec->queryResult->fulfillmentText ) ){
            $hasDefaultResponse = true;
            $defaultResponse = $dec->queryResult->fulfillmentText;
        }

        $isEndOfConversation=0;
        if( isset( $dec->queryResult->diagnosticInfo->end_conversation ) ){
            $isEndOfConversation = 1;
        }

        $messages = $dec->queryResult->fulfillmentMessages;
        $action = $dec->queryResult->action;
        $intentid = $dec->queryResult->intent->name;
        $intentname = $dec->queryResult->intent->displayName;

        if ($action === "product.show") {
            
            $brand = $dec->queryResult->parameters->brand;
            $typeOfWatch = $dec->queryResult->parameters->typeOfWatch;

            // arg query to get products
            $args = array(
                'post_type'           => 'product',
                'showposts'           => 30,
            );

            // Users want to see all products
            if (!$typeOfWatch && !$brand) {
                $push_data = createChatbotProductCarousel($args, $messages);
            } else {
                $defaultResponse = "";
            }

            if ($brand) {
                $cat_args = array(
                    'orderby'    => 'name',
                    'order'      => 'asc',
                    'hide_empty' => false,
                );
                
                $product_categories = get_terms( 'product_cat', $cat_args );
                
                $messages[] = [
                    "simpleResponses" => [
                        [
                            "textToSpeech" => "What type of watch are you looking for?"
                        ]
                    ],
                    "platform" => "ACTIONS_ON_GOOGLE",
                ];

                $suggestions = [];

                foreach ($product_categories as $cat) {
                    if ($cat->term_id == 1) continue;

                    $suggestions[] = [
                        "title" => $cat->name
                    ];
                }

                $messages[] = [
                    "suggestions"=> $suggestions,
                    "platform" => "ACTIONS_ON_GOOGLE",
                ];
            } else if ($typeOfWatch) {
                $brands = get_terms( array(
                    'taxonomy' => 'brand',
                    'hide_empty' => false,
                ) );

                $messages[] = [
                    "simpleResponses" => [
                        [
                            "textToSpeech" => "What brand are you looking for?"
                        ]
                    ],
                    "platform" => "ACTIONS_ON_GOOGLE",
                ];

                $suggestions = [];

                foreach ($brands as $brand) {
                    $suggestions[] = [
                        "title" => $brand->name
                    ];
                }

                $messages[] = [
                    "suggestions"=> $suggestions,
                    "platform" => "ACTIONS_ON_GOOGLE",
                ];
            }
        }

        if ($action === "product.filterByType" || $action === "product.filterByBrand") {
            $brand = end($dec->queryResult->outputContexts)->parameters->brand;
            $typeOfWatch = end($dec->queryResult->outputContexts)->parameters->typeOfWatch;

            $args = array(
                'post_type'           => 'product',
                'showposts'           => -1,
                'tax_query'           => [
                    'relation' => 'AND',
                    [
                        'taxonomy' => 'product_cat',
                        'terms' => $typeOfWatch,
                        'field' => 'slug'
                    ],
                    [
                        'taxonomy' => 'brand',
                        'terms' => $brand,
                        'field' => 'slug'
                    ],
                ]
            );

            

            $push_data = createChatbotProductCarousel($args, $messages);
            $hasDefaultResponse = true;

            if (empty($push_data['items'])) {
                $defaultResponse = "Sorry. We could't find products that meet your requirements.";
            } else {
                $defaultResponse = "Here are some products.";

                $messages[] = [
                    "simpleResponses" => [
                        [
                            "textToSpeech" => "Do you want more recommendation?"
                        ]
                    ],
                    "platform" => "ACTIONS_ON_GOOGLE",
                ];
    
                $messages[] = [
                    "suggestions"=> [
                        [
                          "title"=> "Yes"
                        ],
                        [
                          "title"=> "No"
                        ],
                    ],
                    "platform" => "ACTIONS_ON_GOOGLE",
                ];
            }
        }

        // var_dump($action);

        if ($action === "product.selectNumber") {
            $selectNumbers = $dec->queryResult->parameters->number;

            // var_dump('NUMBA', $selectNumbers, $_SESSION);

            if (!empty($_SESSION['latestProductCarousel'])) {
                $productsAddedToCart = [];

                foreach ($selectNumbers as $number) {
                    $idx = $number - 1;
                    $userSelectedProduct = !empty($_SESSION['latestProductCarousel'][$idx])
                        ? $_SESSION['latestProductCarousel'][$idx]
                        : null;

                    if (!$userSelectedProduct) continue;

                    $productsAddedToCart[] = $userSelectedProduct;

                    WC()->cart->add_to_cart( $userSelectedProduct['ID'] );
                }

                if (empty($productsAddedToCart)) {
                    $defaultResponse = "Sorry. I can't find any corresponding products to your entered numbers";
                } else {
                    $defaultResponse = "I added ";

                    foreach ($productsAddedToCart as $product) {
                        $name = $product["title"];
                        $defaultResponse .= "\n{$name}";
                    }

                    $defaultResponse .="\nto your cart. If you've finished shopping, just tell me to make an order for you.";
                }
            } else {
                $defaultResponse = "Sorry I can't find that product.";
            }
        }

        if ($action === "searchProductsName") {
            $user_string = $dec->queryResult->queryText;

            // arg query to get products
            $args = array(
                'post_type'           => 'product',
                's'                   => $user_string,
                'showposts'           => -1
            );

            createChatbotProductCarousel($args, $messages);
        }

        if ($action === "order.create") {
            if (is_user_logged_in()) {           
                $current_user = wp_get_current_user();
                
                $billing_addr = get_user_meta($current_user->ID, 'billing_address_1', true);
                $billing_first_name = get_user_meta($current_user->ID, 'billing_first_name', true);
                $billing_last_name = get_user_meta($current_user->ID, 'billing_last_name', true);

                if (!$billing_addr) {
                    $edit_addr = wc_get_page_id( 'edit_address' );
                    if ( $edit_addr ) {
                        $link = get_permalink( $edit_addr );

                        $defaultResponse = 'You haven\'t entered your billing address. You can change it by clicking <a href="' . $link . '" target="_blank">here</a>';
                    } else {
                        throw new Exception("There's no account page?", 1);
                    }
                } else {
                    // var_dump($current_user, $billing_addr, $billing_first_name, $billing_last_name);

                    $address = array(
                        'first_name' => $billing_first_name,
                        'last_name'  => $billing_last_name,
                        'email'      => $current_user->data->user_email,
                        'address_1'  => $billing_addr,
                    );
                  
                    $cart = WC()->cart;
                    $checkout = WC()->checkout();
                    $order_id = $checkout->create_order([
                        'customer_id' => $current_user->ID
                    ]);

                    $order = wc_get_order( $order_id );
                    
                    $order->set_address( $address, 'billing' ); //
                    $order->calculate_totals();
                    // $order->update_status("Completed", 'Imported order', TRUE); 
                    $order->update_status('reviewing');
                    $cart->empty_cart();

                    $pageId = wc_get_page_id( 'view_order' );
                    $link = get_permalink( $pageId );

                    $defaultResponse = 'Your order ID is #' . $order->get_id() . '. You can view it <a href="' . $link . '">here</a>. Or you can type "Review order #' . $order->get_id() . '".';
                    
                    $messages[] = [
                        "key" => "response_time_rating",
                        "rating" => "How would you rate our Response Time?"
                    ];
            
                    $messages[] = [
                        "key" => "helpful_rating",
                        "rating" => "How helpful was the chatbot?"
                    ];
                    
                    $messages[] = [
                        "key" => "accuracy_rating",
                        "rating" => "How accurate were the results?"
                    ];
            
                    $messages[] = [
                        "key" => "satisfaction_rating",
                        "rating" => "How would you rate your experience with our chatbot service?"
                    ];
                }
            }
            else {
                $defaultResponse = "You must login first.";
            }
        }

        if ($action === "order.review") {
            if (is_user_logged_in()) {           
                $current_user = wp_get_current_user();

                $order_id = $dec->queryResult->parameters->orderId;

                $order = new WC_Order($order_id);

                if ($order->customer_id != $current_user->ID) {
                    $defaultResponse = "Order is not exist. Could you please recheck the order's ID in your account page?";
                } else {
                    $status = $order->get_status();
                    
                    switch ($status) {
                        case 'reviewing':
                            $defaultResponse = "Your order #{$order_id} is being reviewed. Waiting for shop's confirmation.";
                            break;
                        case 'pending':
                            $defaultResponse = "Your order #{$order_id} is confirmed, waiting for the payment.";
                            break;
                        case 'processing':
                            $defaultResponse = "Your order #{$order_id} is being shipped, please check your email for more information.";
                            break;
                        case 'completed':
                            $defaultResponse = "Your order #{$order_id} is completed.";
                            break;
                    }
                }
            } else {
                $defaultResponse = "You must login first.";
            }
        }

        // if ($action === "input.unknown") {
        //     $user_string = $dec->queryResult->queryText;

        //     $messages[] = [
        //         "simpleResponses" => [
        //             [
        //                 "textToSpeech" => "Do you want me to recommend you some products?"
        //             ]
        //         ],
        //         "platform" => "ACTIONS_ON_GOOGLE",
        //     ];

        //     $messages[] = [
        //         "suggestions"=> [
        //             [
        //               "title"=> "Okay"
        //             ],
        //             [
        //               "title"=> "No, thanks"
        //             ],
        //         ],
        //         "platform" => "ACTIONS_ON_GOOGLE",
        //     ];
        // }
        
        // check if is finalRecommendation action from chatbot
        if ($action === "finalRecommendation") {
            // get all options selected
            $botOptions = end($dec->queryResult->outputContexts)->parameters;

            // must lower case value from chatbot to match with slug in wordpress
            $category = strtolower($dec->queryResult->outputContexts[1]->parameters->typeOfWatch);
            $brand = strtolower($dec->queryResult->outputContexts[1]->parameters->brand);
            // $category = strtolower($botOptions->typeOfWatch);
            // $brand = strtolower($botOptions->brand);
            $material = strtolower($botOptions->material);
            $price_from = (int)$botOptions->price_from;
            $price_to = (int)$botOptions->price_to;
            $gender = strtolower($botOptions->gender);
            $interest = strtolower($botOptions->interest);
            $age = $botOptions->ageGroup;

            // arg query to get products
            $args = array(
                'post_type' => 'product',
                'tax_query' => array(
                    'relation' => 'AND',
                    [
                        'taxonomy' => 'product_cat',
                        'terms' => $category,
                        'field' => 'slug'
                    ],
                    [
                        'taxonomy' => 'brand',
                        'terms' => $brand,
                        'field' => 'slug'
                    ],
                    [
                        'taxonomy' => 'material',
                        'terms' => $material,
                        'field' => 'slug'
                    ],
                    [
                        'taxonomy' => 'interest',
                        'terms' => $interest,
                        'field' => 'slug'
                    ],
                    [
                        'taxonomy' => 'age',
                        'terms' => $age,
                        'field' => 'slug'
                    ],
                    [
                        'taxonomy' => 'gender',
                        'terms' => $gender,
                        'field' => 'slug'
                    ]
                ),
                'meta_query' => [
                    [
                        'key' => '_price',
                        'value' => [$price_from, $price_to],
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    ]
                ],
                'showposts'           => -1
            );

            $push_data = createChatbotProductCarousel($args, $messages);

            if (empty($push_data["items"])) {
                $defaultResponse = "Sorry. We can't find products that meet your requirements.";
            }
        }

        $speech = '';
        for($idx = 0; $idx < count($messages); $idx++){
            $obj = $messages[$idx];
            if(isset($obj->platform) && $obj->platform=='ACTIONS_ON_GOOGLE'){
                $simpleResponses = $obj->simpleResponses;
                $speech = $simpleResponses->simpleResponses[0]->textToSpeech;
            }
        }

        // NEW ADDED
        foreach ($messages as $msgKey => $message) {
            // Bỏ key payload, chuyển nội dung bên trong ra ngoài
            if (empty($message->payload)) {
                continue;
            }

            foreach ($message->payload as $msgType => $value) {
                $messages[] = [
                    $msgType => $value,
                    'platform' => 'ACTIONS_ON_GOOGLE'
                ];
            }

            unset($messages[$msgKey]);
        }

        $messages = array_values($messages); // Reset index
        // $messages[]  = [
        //   "suggestions"=> [
        //     [
        //       "title"=> "Swimming"
        //     ],
        //     [
        //       "title"=> "Fitness"
        //     ],
        //     [
        //       "title"=> "Casual Wear"
        //     ],
        //     [
        //       "title"=> "Gift"
        //     ]
        //   ]
        // ];
        // if ($payload) {
        //     foreach ($payload as $key => $value) {
        //         $arr = [
        //             'platform' => 'ACTIONS_ON_GOOGLE'
        //         ];

        //         $arr[$key] = $value;
        //         $messages[] = $arr;
        //     }
        // }

        // END NEW ADDED

        // switch ($intentname) {
        //     case 'Recommendation':
        //         $testSuggestions = new stdClass();
        //         $testSuggestions -> platform = "ACTIONS_ON_GOOGLE";
        //         $testSuggestions -> suggestions = [
        //             'suggestions' => [
        //                 [
        //                     "title" => "Say this"
        //                 ],
        //                 [
        //                     "title" => "or this"
        //                 ]
        //             ]
        //         ];

        //         $messages[] = $testSuggestions;
        //         break;
        //     default:
        //         break;
        // }

        $Parsedown = new Parsedown();
        $transformed= $Parsedown->text($speech);
        $response -> actionTest = $action;

        if($hasDefaultResponse){
            $response -> defaultResponse = $Parsedown->text($defaultResponse);
        }

        $response -> expectUserResponse = true;

        $response -> speech = $transformed;
        $response -> messages = $messages;
        $response -> isEndOfConversation = $isEndOfConversation;
        $response -> test = $dec;
        echo json_encode($response);
        // close curl resource to free up system resources
        curl_close($ch);
    }
}catch (Exception $e) {
    $speech = $e->getMessage();
    $fulfillment = new stdClass();
    $fulfillment->speech = $speech;
    $result = new stdClass();
    $result->fulfillment = $fulfillment;
    $response = new stdClass();
    $response->result = $result;
    echo json_encode($response);
}

?>