<?php

if (isset($_FILES['image'])) {

    $servername = "localhost:3306";
    $username = "root";
    $password = "";
    $dbname = "testcarnet";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $errors = array();
    $file_name = $_FILES['image']['name'];
    $file_size = $_FILES['image']['size'];
    $file_tmp = $_FILES['image']['tmp_name'];
    $file_type = $_FILES['image']['type'];
    //   $file_ext=strtolower(end(explode('.',$_FILES['image']['name'])));

    // $expensions = array("jpeg", "jpg", "png");

    //   if(in_array($file_ext,$expensions)=== false){
    //      $errors[]="extension not allowed, please choose a JPEG or PNG file.";
    //   }

    //   if($file_size > 2097152){
    //      $errors[]='File size must be excately 2 MB';
    //   }

    if (empty($errors) == true) {
        move_uploaded_file($file_tmp, "images/" . $file_name);
        $handle = fopen("./images/" . $file_name, "r");
        $dataDump = fread($handle, filesize("./images/" . $file_name));
        $rows = explode("\r\n", $dataDump);
        foreach ($rows as $i => $row) {
            // echo $row . '<br/>';
            $dataRow = explode("\t", $row);

            if (isset($dataRow[0])) {
                $nom = trim($dataRow[0], '"') . "\n";
            } else {
                $nom = '';
            }
            if (isset($dataRow[1])) {
                $denom = trim($dataRow[1], '"') . "\n";
            } else {
                $denom = '';
            }
            if (isset($dataRow[2])) {
                $cp = trim($dataRow[2], '"') . "\n";
            } else {
                $cp = '';
            }
            if (isset($dataRow[3])) {
                $ville = trim($dataRow[3], '"') . "\n";
            } else {
                $ville = '';
            }
            if (isset($dataRow[4])) {
                $address = trim($dataRow[4], '"') . "\n";
            } else {
                $address = '';
            }

            $url = "https://api-adresse.data.gouv.fr/search/?q=$cp+$ville+$address&limit=1";

            $url_without_line = trim(preg_replace('/\n/', '', $url));
            $url_without_space = trim(str_replace(' ', '+', $url_without_line));

            // echo $url_without_line . "\n";

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url_without_space,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                // echo "REPONSE:\n\n";
                // echo var_dump(json_decode($response, true));
                // echo $response . '<br/>';

                $arr = json_decode($response, true);

                foreach ($arr['features'] as $feature) {
                    echo 'Nom: ' . $nom . '<br/>';
                    echo 'Denom: ' . $denom . '<br/>';
                    echo 'CP: ' . $feature['properties']['postcode'] . '<br />';
                    echo 'Ville: ' . $feature['properties']['city'] . '<br />';
                    echo 'Adresse: ' . $feature['properties']['name']  . '<br />';
                    echo 'Latitude: ' . $feature['geometry']['coordinates'][0] . '<br />';
                    echo 'Longitude: ' . $feature['geometry']['coordinates'][1] . '<br />';
                    echo '----------------------------------------------------------' . '<br/>';

                    $cp_ = $feature['properties']['postcode'];
                    $ville_ = $feature['properties']['city'];
                    $adresse_ = $feature['properties']['name'];
                    $latitude_ = $feature['geometry']['coordinates'][0];
                    $longitude_ = $feature['geometry']['coordinates'][1];

                    $sql_check_address = "SELECT * FROM adresses WHERE adresse ='" . addslashes($adresse_) . "' AND latitude='" . addslashes($latitude_) .
                        "' AND longitude='" . addslashes($longitude_) . "'";

                    $result_check_address = $conn->query($sql_check_address);

                    if($result_check_address->num_rows == 0 ){

                        $sql = "INSERT INTO adresses (nom, denom, cp, ville, adresse,latitude,longitude) " .
                        " VALUES ('" . addslashes($nom) . "', '" . addslashes($denom) . "', '" . addslashes($cp_) . "', '" . addslashes($ville_) . "', '" .
                        addslashes($adresse_) . "', '" . addslashes($latitude_) . "', '" . addslashes($longitude_) . "')";

                        if ($conn->query($sql) === TRUE) {
                        } else {
                            echo "Error: " . $sql . "<br>" . $conn->error;
                        }
                    }
                }
            }
        }
    } else {
        print_r($errors);
    }

    $conn->close();
}
?>
<html>

<body>

    <form action="" method="POST" enctype="multipart/form-data">
        <input type="file" name="image" />
        <input type="submit" />
    </form>

</body>

</html>