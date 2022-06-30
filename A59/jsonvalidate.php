<?php
/*
    Template Name: JSON-Validate
*/

$proxy = $_GET['proxy'];
$ipprx = $_GET['ipprx'];
$qs = preg_replace('/[\?&]*(?:proxy=.{0,3}|ipprx=.{0,22})(?:&|$)/i', '', urldecode($_SERVER['QUERY_STRING']));
$url = preg_replace('/[\?&]*url=(.+)/i', '\1', $qs);
if ($url == 'url=') $url = '';
if (preg_match('/^\s*http.{1,5}\/\/[a-z0-9]{0,10}.?area59aa\.org/i', $url)) $url = '';
$count = ($url != '') ? 0 : -1;

$ipsrc = file_get_contents('https://ipecho.net/plain', false) . ($proxy == '1' ? '&nbsp; ➜ &nbsp;' . $ipprx : '');
$ipdst = gethostbyname(parse_url($url, PHP_URL_HOST));
$ipinfo = ' | &nbsp;&nbsp;&nbsp;'  . $ipsrc . '&nbsp; ➜ &nbsp;' . $ipdst;

if ($url != '') {
  if ($proxy != '1') {
    $resp = wp_remote_get($url, ['timeout' => 30, 'sslverify' => false, 'httpversion' => '1.1']);
    $rc = wp_remote_retrieve_response_code($resp);
    if (!is_wp_error($resp)) {
      if (!is_array($resp)) $resp = ['error' => '', 'body' => $resp];
      if ($rc == '200' || $rc == '301' || $rc == '302') $rc = '200';
      else {
        $resp['error'] = print_r($resp['body'], true);
        if (empty($resp['http_response'])) $resp['http_response'] = $resp['body'];
      }
    }
  } else {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if ($ipprx != '') curl_setopt($ch, CURLOPT_PROXY, $ipprx);
    $resp = ['error' => '', 'body' => curl_exec($ch)];
    $rc = curl_errno($ch);
    if ($rc == '0') $rc = '200';
    else {
      $msg = curl_error($ch);
      $resp['error']  = 'cURL: ' . $msg;
      if (empty($resp['http_response'])) $resp['http_response'] = $resp;
      if ($rc == '22') $rc = substr($msg, -3);
    }
    curl_close($ch);
  }

  // general error
  if (is_wp_error($resp)) {
    $msg = "ERROR response from feed. | " . print_r($resp->get_error_message(), true);
    $err = text_clean(print_r($resp, true));
  }
  // specific http errors
  else if ($rc == '404') {
    $msg = 'ERROR 404 | Page not found.';
    $err = text_clean(print_r($resp['http_response'], true));
  } else if ($rc == '401') {
    $msg = 'ERROR 401 | Unauthorized.';
    $err = text_clean(print_r($resp['http_response'], true));
  }
  // other http errors
  else if ($rc != '200') {
    $msg = 'ERROR ' . $rc . ' | ' . text_clean(print_r($resp['error'], true));
    $err = text_clean(print_r($resp['http_response'], true));
  }
  // google sheet
  else if (preg_match('/google.+export.+format=csv/i', $url) && ($json = csv_json($resp['body']))) {
    // check for "slug" in feed
    if (array_key_exists('slug', $json[0]) || array_key_exists('Slug', $json[0])) {
      $count = count($json);
    }
    // multiple rows; likely data format error
    else if (count($json) > 1) {
      $msg = 'INVALID data format returned by feed.';
      $err = $json;
    }
    // single row; generic error
    else {
      $msg = 'ERROR loading Google Sheet.';
      $err = text_clean(print_r($resp['body'], true), true);
    }
  }
  // no JSON found
  else if (substr($resp['body'], 0, 2) != '[{') {
    $msg = 'INVALID data format | No JSON data found.';
    $err = text_clean(print_r($resp['body'], true), true);
  }
  // JSON feed data
  else if ($json = json_decode($resp['body'], true)) {
    // check for "slug" in feed
    if (array_key_exists('slug', $json[0]) || array_key_exists('Slug', $json[0])) {
      $count = count($json);
    }
    // invalid JSON data format
    else {
      $msg = 'ERROR parsing feed data.  ' . print_r($json['error'], true);
      $err = text_clean(print_r($resp['body'], true));
    }
  }
  // JSON parse error
  else {
    $msg = 'JSON Error Code: <b>' . json_last_error() . '</b> | ' . json_last_error_msg();
    $err = text_clean(print_r($resp['body'], true), true);
  }
}

function csv_json($arr)
{
  $csv =  explode("\n", $arr);
  $h = str_getcsv(array_shift($csv));
  $data = array_map(fn ($r) => array_combine($h, str_getcsv($r)), $csv);
  $json = json_decode(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE), true);
  foreach ($json as &$j) {
    foreach (array_filter(['Types', 'types'], fn ($x) =>  array_key_exists($x, $j)) as $t) {
      $types = [];
      foreach (explode(",", $j[$t]) as $x) array_push($types, trim($x));
      $j[$t] = $types;
    }
  }
  return $json;
}

function text_clean($s, $full = false)
{
  if ($full) $s = str_replace(">", ">\n", str_replace(" \n", "", str_replace("  ", " ", str_replace(" ", " ", $s))));
  return trim(htmlspecialchars($s));
}

?>

<!DOCTYPE html>
<html>

<head>
  <title>Area 59 JSON Feed Validator</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="https://meetingguide.org/css/app.css">
  <link rel="mask-icon" href="https://meetingguide.org/img/meeting-guide-favicon.png" color="#00437c">
  <link rel="icon" type="image/png" href="https://meetingguide.org/img/meeting-guide-favicon.png">
  <style type="text/css">
    aside {
      display: none !important;
    }
  </style>
  <script>
    window.onload = function() {
      history.pushState({}, null, unescape(location.href));
    }

    function proxyClick() {
      chk = document.getElementById('proxy').checked;
      document.getElementById('divProxy').style.display = chk ? "block" : "none";
      document.getElementById('proxy').value = chk ? "1" : "";
      if (!chk) document.getElementById('ipprx').value = "";
    }
  </script>
</head>

<body class="validate">

  <nav class="navbar navbar-expand-lg fixed-top navbar-dark" id="navbar">
    <div class="container" style="line-height:0;">
      <a class="navbar-brand" href="/">Area 59 JSON Validator</a>
      <div style="height:20px; margin-left:55px; color: white; font-size:20px"><?php echo $ipsrc ?>
      </div>
      <div class="collapse navbar-collapse" id="main-nav"> </div>
    </div>
  </nav>

  <main>
    <div class="container page">
      <div class="row">
        <div class="col-md-12">
          <p class="lead" style="margin-top:-10px; margin-bottom:10px; font-weight:350; line-height:25px;" ; style="margin-top:-10px">
            This validator checks if the <span style="font-weight:450">Area 59</span> website can load a JSON feed.</p>
          <p style="line-height:25px;">For more info on the Meeting Guide API, check out the <a href="https://github.com/meeting-guide/spec" zoompage-fontsize="17">specification</a>.</p>
          <form action="/jsonvalidate" method="get">
            <div class="input-group">
              <input type="url" name="url" class="form-control" value="<?php echo $url; ?>" placeholder="https://distirctwebsite.org/jsonfeed/">
              <div class="input-group-append">
                <input type="submit" class="btn btn-outline-secondary" value="Check Feed">
              </div>
              <div class="input-group-append" style="margin-left:15px; width:25px">
                <input type="checkbox" id="proxy" name="proxy" onclick="proxyClick();" class="form-control" <?php echo $proxy == 1 ? "checked" : "";  ?> value="<?php echo $proxy; ?>" placeholder="ipaddr:port">
              </div>
            </div>
            <div id="divProxy" style="margin:10px 0 10px; display:<?php echo $proxy == 1 ? 'block' : 'none'; ?>;">
              <input style="display:inline-block; width:185px;" type=" text" id="ipprx" name="ipprx" class="form-control" value="<?php echo $ipprx; ?>" placeholder="ipaddr:port">
            </div>
          </form>
          <?php if ($count > 0) {
            echo '<div class="alert alert-success" style="font-weight:500; font-size:17px">The feed is <b>valid</b> and returned <b>' . $count . '</b> meetings</div>';
            echo '<div class="lead" style="font-size:13px; margin:-10px 0 5px 0; line-height:1.1em">' . $url . $ipinfo . '</div>';
            echo '<pre id="output"><code class="language-json">' . print_r($json, true) . '</code></pre>';
          } else if ($count == 0) {
            echo '<div class="alert alert-danger" style="font-weight:500; font-size:17px">' . $msg . $ipinfo . '</div>';
            echo '<div class="lead" style="font-size:13px; margin:-10px 0 5px 0; line-height:1.1em">' . $url . '</div>';
            echo '<pre id="output"><code class="language-html">' . print_r($err, true) . '</code></pre>';
          } ?>
        </div>
      </div>
    </div>
  </main>

</body>

</html>