<?php
session_start();
require 'Meli/meli.php';
require 'configApp.php';

$domain = $_SERVER['HTTP_HOST'];
$appName = explode('.', $domain)[0];
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
                <h3>oAuth</h3>
                <p>First authenticate the app yourself</p>
                <form action="configApp.php" method="POST">
                    <div>
                        <label for="app_id">APP ID</label>
                        <input class="form-control" type="text" name="app_id">
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
                $meli = new Meli($appId, $secretKey);

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

                    echo '<pre>';                        
                        print_r($_SESSION);
                    echo '</pre>';

                }
                echo '<p><a alt="Login using MercadoLibre oAuth 2.0" class="btn" href="' . $meli->getAuthUrl($redirectURI, Meli::$AUTH_URL[$siteId]) . '">Authenticate</a></p>';
                
                ?>

            </div>
        </div>
    
        <div class="row">
            <div class="col-md-6">
                <h3>Publish an Item</h3>

                <?php
                $meli = new Meli($appId, $secretKey);

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
                                "source" => ""
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
            <div class="row-info col-sm-3 col-md-3">
                <b>App_Id: </b>
                <?php echo $appId; ?>
            </div>
            <div class="row-info col-sm-3 col-md-3">
                <b>Secret_Key: </b>
                <?php echo $secretKey; ?>
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
        <hr>
    </main>
</body>
</html>