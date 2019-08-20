<?php 
function sanitize($data) {
    $data   = trim($data);
    $data   = stripslashes($data);
    $data   = htmlspecialchars($data);
    return $data;
}
function CallAPI($method, $url, $data = false)
{
    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    // Optional Authentication:
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array( 
		'API_SECRET: ' . sanitize($_POST['ecourier_secret_key']),
		'API_KEY: ' . sanitize($_POST['ecourier_api_key']), 
		'USER_ID: ' . sanitize($_POST['ecourier_user_id']),
		'Content-Type: multipart/form-data',
	));

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}

$ecc_data = array(
	"parcel" => "insert",
	"recipient_name" => sanitize($_POST['recipient_name']),
	"recipient_mobile" => sanitize($_POST['recipient_mobile']),
	"recipient_city" => sanitize($_POST['recipient_city']),
	"recipient_area" => sanitize($_POST['recipient_area']),
	"recipient_address" => sanitize($_POST['recipient_address']),
	"package_code" => "#2415",
	"product_price" => sanitize($_POST['product_price']),
	"payment_method" => sanitize($_POST['payment_method']),
	"product_id" => sanitize($_POST['product_id']),
);


echo CallAPI('GET', 'http://ecourier.com.bd/apiv2/', $ecc_data);

?>