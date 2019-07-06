<?php
session_start();
require 'Meli/meli.php';
require 'configApp.php';

$domain = $_SERVER['HTTP_HOST'];
$appName = explode('.', $domain)[0];


//consuming the form submission of the secret key
if(isset($_POST['secret_key'])){
    $_SESSION['secret_key'] = $_POST['secret_key'];
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="HITECH Inegration PHP SDK for Mercado Libre's API.">
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/bootstrap.css" />
    <script src="js/script.js"></script>
</head>
<body>
    <main class="container">
        <div class="row">
            <div class="col-sm-6 col-md-6">
                <h3>Authentication OAuth 2.0</h3>
                <form action="index.php" method="POST">
                    <div>
                        <label for="app_id">APP ID</label>
                        <?php  
                            echo '<input class="form-control" type="text" name="app_id" value="' . $appId . '">'
                        ?>
                    </div>
                    <div>
                        <label for="secret_key">Secret Key</label>
                        <input class="form-control" type="password" name="secret_key">
                    </div>
                    <div>
                        <button class="btn btn-primary" type="submit">Submit</button>
                    </div>

                </form>

                <?php
                
                $meli = new Meli($appId, $_SESSION['secret_key']);

                if($_GET['code'] || $_SESSION['access_token']) {

                    // If code exist and session is empty
                    if($_GET['code'] && !($_SESSION['access_token'])) {
                        // If the code was in get parameter we authorize
                        $user = $meli->authorize($_GET['code'], $redirectURI);

                        // Now we create the sessions with the authenticated user
                        $_SESSION['access_token'] = $user['body']->access_token;
                        $_SESSION['expires_in'] = time() + $user['body']->expires_in;
                        $_SESSION['refresh_token'] = $user['body']->refresh_token;
                    } else {
                        // We can check if the access token in invalid checking the time
                        if($_SESSION['expires_in'] < time()) {
                            try {
                                // Make the refresh proccess
                                $refresh = $meli->refreshAccessToken();

                                // Now we create the sessions with the new parameters
                                $_SESSION['access_token'] = $refresh['body']->access_token;
                                $_SESSION['expires_in'] = time() + $refresh['body']->expires_in;
                                $_SESSION['refresh_token'] = $refresh['body']->refresh_token;
                            } catch (Exception $e) {
                                echo "Exception: ",  $e->getMessage(), "\n";
                            }
                        }
                    }

                    // echo '<pre>';      
                    //     print_r($_SESSION);             
                    // echo '</pre>';

                }
                echo '<p><a alt="Login using MercadoLibre oAuth 2.0" class="btn" href="' . $meli->getAuthUrl($redirectURI, Meli::$AUTH_URL[$siteId]) . '">Authenticate</a></p>';
                
                ?>

            </div>
        </div>
    
        <div class="row">
            <div class="col-md-12">
                <h3>List Items</h3>

                <?php

                    function active_items($user_id) {

                        //request pattern /users/{Cust_id}/items/search?search_type=scan&access_token=$ACCESS_TOKEN   
                        $items = $GLOBALS['meli']->get('/users/' . $user_id . '/items/search', array('access_token' => $_SESSION['access_token']));
                        $itemsIds = $items["body"]->results;
                        $itemsInfo = array();
                        
                        foreach ($itemsIds as $item) {
                            $tmpItemInfo = null;
                            $tmpItemInfo = $GLOBALS['meli']->get('/items/' . $item, array('access_token' => $_SESSION['access_token']));

                            //if the item is active we push that into the items info array
                            if ($tmpItemInfo['body']->status == 'active'){
                                array_push($itemsInfo, $tmpItemInfo);
                                
                                // debugging
                                // echo '<p>Item ' . $item .' </p>';
                                // echo '<p>Status ' . $tmpItemInfo['body']->status .' </p>';
                            } 
                        }

                        return $itemsInfo;
                    }

                    if($_GET['code']) {


                        // We can check if the access token in invalid checking the time
                        if($_SESSION['expires_in'] + 1 < time()) {
                            try {
                                print_r($meli->refreshAccessToken());
                            } catch (Exception $e) {
                                echo "Exception: ",  $e->getMessage(), "\n";
                            }
                        }

                        // unset($_SESSION['items_exc']);
                        if(!isset($_SESSION['items_exc'])){
                            $_SESSION['items_info'] = active_items($userId);
                            $_SESSION['items_exc'] = time();
                        }else if(time() > $_SESSION['items_exc'] + 60*60*24){
                            $_SESSION['items_info'] = active_items($userId);
                        }
                        
                        echo "<h4>Active Itens Info</h4>";
                        echo '<div class="row">';
                        foreach ($_SESSION['items_info'] as $item => $key) {
                            echo '<div class="col-md-3">
                                    <div class="card">
                                      <img class="card-img-top" src="'. $key['body']->pictures[0]->url . '" alt="Card image cap" height="200">
                                      <div class="card-body">
                                        <h5 class="card-title">' . $key['body']->title . '</h5>
                                        <p class="card-text">Available quantity: ' . $key['body']->available_quantity  . '</p>
                                        <a href="' . $key['body']->permalink . '" target="_blank" class="btn btn-primary">Buy now</a>
                                      </div>
                                    </div>
                                </div>';
                        }

                        echo '</div>';

                        // echo '<pre>';
                        // print_r($_SESSION['items_info']);
                        // echo '</pre>';

                    } else {
                        echo '<p><a alt="Login using MercadoLibre oAuth 2.0" class="btn" href="' . $meli->getAuthUrl($redirectURI, Meli::$AUTH_URL[$siteId]) . '">Authenticate</a></p>';

                    }
                ?>

            </div>
            <div class="col-md-12">
                <h3>Publish an Item</h3>

                <?php
                $meli = new Meli($appId, $_SESSION['secret_key']);

                if($_GET['code'] && $_GET['publish_item']) {

                    // If the code was in get parameter we authorize
                    $user = $meli->authorize($_GET['code'], $redirectURI);

                    // Now we create the sessions with the authenticated user
                    // $_SESSION['access_token'] = $user['body']->access_token;
                    // $_SESSION['expires_in'] = $user['body']->expires_in;
                    // $_SESSION['refresh_token'] = $user['body']->refresh_token;

                    // We can check if the access token in invalid checking the time
                    if($_SESSION['expires_in'] + time() + 1 < time()) {
                        try {
                            print_r($meli->refreshAccessToken());
                        } catch (Exception $e) {
                            echo "Exception: ",  $e->getMessage(), "\n";
                        }
                    }

                    // We construct the item to POST
                    $item = array(
                        "title" => "Item De Teste - Por Favor, Não Ofertar! --kc:off",
                        "category_id" => "MLB1227",
                        "price" => 110,
                        "currency_id" => "BRL",
                        "available_quantity" => 90,
                        "buying_mode" => "buy_it_now",
                        "listing_type_id" => "gold",
                        "condition" => "new",
                        "description" => "Mi Band 2",
                        "pictures" => array(
                            array(
                                "source" => "https://cdn.pji.nu/product/standard/280/3808360.jpg"
                            ),
                        )
                    );
                    
                    $response = $meli->post('/items', $item, array('access_token' => $_SESSION['access_token']));

                    // We call the post request to list a item
                    echo "<h4>Response</h4>";
                    echo '<pre class="pre-item">';
                    print_r ($response);
                    echo '</pre>';

                    echo "<h4>Success! Your test item was listed!</h4>";
                    echo "<p>Go to the permalink to see how it's looking in our site.</p>";
                    echo '<a target="_blank" href="'.$response["body"]->permalink.'">'.$response["body"]->permalink.'</a><br />';

                } else if($_GET['code']) {
                    echo '<p><a alt="Publish Item" class="btn" href="./?code='.$_GET['code'].'&publish_item=ok">Publish Item</a></p>';
                } else {
                    echo '<p><a alt="Publish Item" class="btn disable" href="#">Publish Item</a> </p>';
                }
                ?>

            </div>
        </div>

        <div class="row">
            <h3>Your Credentials</h3>
            <div class="row">
                <div class="row-info col-sm-3 col-md-3">
                    <b>App_Id: </b>
                    <?php echo $appId; ?>
                </div>
                <div class="row-info col-sm-3 col-md-3">
                    <b>Secret_Key: </b>
                    <?php echo $_SESSION['secret_key']; ?>
                </div>
                <div class="row-info col-sm-3 col-md-3">
                    <b>Redirect_URI: </b>
                    <?php echo $redirectURI; ?>
                </div>
                <div class="row-info col-sm-3 col-md-3">
                    <b>Site_Id: </b>
                    <?php echo $siteId; ?>
                </div>
            </div>
        </div>
        <hr>
    </main>
</body>
</html>