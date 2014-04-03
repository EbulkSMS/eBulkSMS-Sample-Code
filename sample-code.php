<?php
$json_url = "http://api.ebulksms.com/sendsms.json";
$xml_url = "http://api.ebulksms.com/sendsms.xml";
$username = '';
$apikey = '';

if (isset($_POST['button'])) {
    $username = $_POST['username'];
    $apikey = $_POST['apikey'];
    $sendername = substr($_POST['sender_name'], 0, 11);
    $recipients = $_POST['telephone'];
    $message = $_POST['message'];
    $flash = 0;
    if (get_magic_quotes_gpc()) {
        $message = stripslashes($_POST['message']);
    }
    $message = substr($_POST['message'], 0, 160);
#Use the next line for HTTP POST with JSON
    $result = useJSON($json_url, $username, $apikey, $flash, $sendername, $message, $recipients);
#Uncomment the next line and comment the one above if you want to use HTTP POST with XML
    //$result = useXML($xml_url, $username, $apikey, $flash, $sendername, $message, $recipients);
}

function useJSON($url, $username, $apikey, $flash, $sendername, $messagetext, $recipients) {
    $gsm = array();
    $country_code = '234';
    $arr_recipient = explode(',', $recipients);
    foreach ($arr_recipient as $recipient) {
        $mobilenumber = trim($recipient);
        if (substr($mobilenumber, 0, 1) == '0')
            $mobilenumber = $country_code . substr($mobilenumber, 1);
        elseif (substr($mobilenumber, 0, 1) == '+')
            $mobilenumber = substr($mobilenumber, 1);
        $generated_id = uniqid('int_', false);
        $generated_id = substr($generated_id, 0, 30);
        $gsm['gsm'][] = array('msidn' => $mobilenumber, 'msgid' => $generated_id);
    }
    $message = array(
        'sender' => $sendername,
        'messagetext' => $messagetext,
        'flash' => "{$flash}",
    );

    $request = array('SMS' => array(
            'auth' => array(
                'username' => $username,
                'apikey' => $apikey
            ),
            'message' => $message,
            'recipients' => $gsm
    ));
    $json_data = json_encode($request);
    if ($json_data) {
        $response = doPostRequest($url, $json_data, array('Content-Type: application/json'));
        $result = json_decode($response);
        return $result->response->status;
    } else {
        return false;
    }
}

function useXML($url, $username, $apikey, $flash, $sendername, $messagetext, $recipients) {
    $country_code = '234';
    $arr_recipient = explode(',', $recipients);
    $count = count($arr_recipient);
    $msg_ids = array();
    $recipients = '';

    $xml = new SimpleXMLElement('<SMS></SMS>');
    $auth = $xml->addChild('auth');
    $auth->addChild('username', $username);
    $auth->addChild('apikey', $apikey);

    $msg = $xml->addChild('message');
    $msg->addChild('sender', $sendername);
    $msg->addChild('messagetext', $messagetext);
    $msg->addChild('flash', $flash);

    $rcpt = $xml->addChild('recipients');
    for ($i = 0; $i < $count; $i++) {
        $generated_id = uniqid('int_', false);
        $generated_id = substr($generated_id, 0, 30);
        $mobilenumber = trim($arr_recipient[$i]);
        if (substr($mobilenumber, 0, 1) == '0') {
            $mobilenumber = $country_code . substr($mobilenumber, 1);
        } elseif (substr($mobilenumber, 0, 1) == '+') {
            $mobilenumber = substr($mobilenumber, 1);
        }
        $gsm = $rcpt->addChild('gsm');
        $gsm->addchild('msidn', $mobilenumber);
        $gsm->addchild('msgid', $generated_id);
    }
    $xmlrequest = $xml->asXML();

    if ($xmlrequest) {
        $result = doPostRequest($url, $xmlrequest, array('Content-Type: application/xml'));
        $xmlresponse = new SimpleXMLElement($result);
        return $xmlresponse->status;
    }
    return false;
}

//Function to connect to SMS sending server using HTTP POST
function doPostRequest($url, $data, $headers = array('Content-Type: application/x-www-form-urlencoded')) {
    $php_errormsg = '';
    if (is_array($data)) {
        $data = http_build_query($data, '', '&');
    }
    $params = array('http' => array(
            'method' => 'POST',
            'content' => $data)
    );
    if ($headers !== null) {
        $params['http']['header'] = $headers;
    }
    $ctx = stream_context_create($params);
    $fp = fopen($url, 'rb', false, $ctx);
    if (!$fp) {
        return "Error: gateway is inaccessible";
    }
    //stream_set_timeout($fp, 0, 250);
    try {
        $response = stream_get_contents($fp);
        if ($response === false) {
            throw new Exception("Problem reading data from $url, $php_errormsg");
        }
        return $response;
    } catch (Exception $e) {
        $response = $e->getMessage();
        return $response;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>eBulkSMS Send SMS JSON API</title>
    </head>

    <body>
        <h2 style="text-align: center">eBulk SMS Integration Sample Code</h2>
        <div style="border: 1px solid #333; padding: 5px 10px; width: 40%; margin: 0 auto">
        <form id="form1" name="form1" method="post" action="">
            
                <?php
                if (!empty($_POST)) {
                    if ($result == 'SUCCESS') {?>
                    <p style="border: 1px dotted #333; background: #33ff33; padding: 5px;">Message sent</p>
                    <?php
                     }
                    else {?>
                    <p style="border: 1px dotted #333; background: #FFDACC; padding: 5px;">Message not sent</p>
                    <?php
                    }
                }
                ?>
            
            <p>
                <label>Username:
                    <input name="username" type="text" id="username"/>
                </label>
            </p>
            <p>
                <label>API Key:
                    <input name="apikey" type="password" id="passwd" />
                </label>
            </p>
            <p>
                <label>Sender name:
                    <input name="sender_name" type="text" id="name" value="Integration" />
                </label>
            </p>
            <p>
                <label>Recipients
                    <textarea name="telephone" id="telephone" cols="45" rows="2"></textarea>
                </label>
            </p>
            <p>
                <label>Message
                    <textarea name="message" id="message" cols="45" rows="5"></textarea>
                </label>
            </p>
            <p>
                <label>
                    <input type="submit" name="button" id="button" value="Submit" />
                </label>
                <label>
                    <input type="reset" name="button2" id="button2" value="Reset" />
                </label>
            </p>
        </form>
        </div>
    </body>
</html>