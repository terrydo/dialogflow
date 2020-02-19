<?php
/**
 * Template Name: Chat Process
 */

require __DIR__.'/vendor/autoload.php';

const CREDENTIALS_DIR = __DIR__ . '\client-key.json';
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . CREDENTIALS_DIR);

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

        if ($action === "showAllProduct") {
            // arg query to get products
            $args = array(
                'post_type'           => 'product',
                'showposts'           => -1
            );
            $my_query = null;
            $my_query = new WP_Query($args);
            $products_recommend = array();

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

        }

        if ($action === "searchProductsName") {
            $user_string = $dec->queryResult->queryText;

            // arg query to get products
            $args = array(
                'post_type'           => 'product',
                's'                   => $user_string,
                'showposts'           => -1
            );
            $my_query = null;
            $my_query = new WP_Query($args);
            $products_recommend = array();

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
        }

        if ($action === "input.unknown") {
            $user_string = $dec->queryResult->queryText;

            $messages[] = [
                "simpleResponses" => [
                    [
                        "textToSpeech" => "Do you want me to recommend you some products?"
                    ]
                ],
                "platform" => "ACTIONS_ON_GOOGLE",
            ];

            $messages[] = [
                "suggestions"=> [
                    [
                      "title"=> "Okay"
                    ],
                    [
                      "title"=> "No, thanks"
                    ],
                ],
                "platform" => "ACTIONS_ON_GOOGLE",
            ];
        }
        
        // check if is finalRecommendation action from chatbot
        if ($action === "finalRecommendation") {
            // get all options selected
            $botOptions = $dec->queryResult->outputContexts[0]->parameters;

            // must lower case value from chatbot to match with slug in wordpress
            $category = strtolower($botOptions->typeOfWatch);
            $brand = strtolower($botOptions->brand);
            $material = strtolower($botOptions->material);
            $price_from = (int)$botOptions->price_from;
            $price_to = (int)$botOptions->price_to;
            $gender = strtolower($botOptions->gender);
            $favorite = strtolower($botOptions->interest);
            $age = $botOptions->ageGroup;

            // arg query to get products
            $args = array(
                'post_type'           => 'product',
                'tax_query' => array(
                    'relation' => 'AND',
                    array(
                        'taxonomy' => 'product_cat',
                        'terms' => $category,
                        'field' => 'slug'
                    ),
                    array(
                        'taxonomy' => 'brand',
                        'terms' => $brand,
                        'field' => 'slug'
                    ),
                    array(
                        'taxonomy' => 'material',
                        'terms' => $material,
                        'field' => 'slug'
                    ),
                    array(
                        'taxonomy' => 'favorite',
                        'terms' => $material,
                        'field' => 'slug'
                    ),
                    array(
                        'taxonomy' => 'age',
                        'terms' => $age,
                        'field' => 'slug'
                    ),
                    array(
                        'taxonomy' => 'gender',
                        'terms' => $gender,
                        'field' => 'slug'
                    )
                ),
                // 'meta_query' => [
                //     'relation'      => 'AND',
                //     'min_price' => [
                //         'relation' => 'AND',
                //         [
                //             'key'     => 'min_price',
                //             'value'   => $price_from,
                //             'compare' => '>=',
                //             'type' => 'NUMERIC'
                //         ]
                //     ],
                //     'max_price' => [
                //         'relation' => 'AND',
                //         [
                //             'key'     => 'max_price',
                //             'value'   => $price_to,
                //             'compare' => '<=',
                //             'type' => 'NUMERIC'
                //         ],
                //         [
                //             'key'     => 'max_price',
                //             'value'   => '',
                //             'compare' => '!='
                //         ]
                //     ]
                // ],
                'showposts'           => -1
            );
            $my_query = null;
            $my_query = new WP_Query($args);
            $products_recommend = array();

            $push_data = [
                "items" => [],
                "platform" => "ACTIONS_ON_GOOGLE",
                "type" => "carousel_card"
            ];

            if ($my_query->have_posts()) : while ($my_query->have_posts()) : $my_query->the_post();
                
                $product = wc_get_product( get_the_ID() );
                $product_name = get_the_title();
                $image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'single-post-thumbnail' )[0];
                $price = $product->get_price_html();

                $push_data["items"][] = [
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


            if (!empty($push_data["items"])) {
                $messages[] = $push_data;
            } else {
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
        $response -> testpro = $products_recommend;
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