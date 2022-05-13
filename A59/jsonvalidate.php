<?php
/*
    Template Name: JSON-Validate
*/

$qs = preg_replace('/^\s*url=(.+)\s*$/i', '\1', urldecode($_SERVER['QUERY_STRING']));
$url = esc_url_raw($qs, ['http', 'https']);
if (preg_match('/^\s*http.{1,5}\/\/[a-z0-9]{0,10}.?area59aa\.org/i', $url)) $url = '';
$count = ($url != '') ? 0 : -1;

if ($url != '') {
  $resp = wp_remote_get($url, ['timeout' => 30, 'sslverify' => false]);
  if (!is_array($resp) || empty($resp['body'])) {
    $msg = 'INCOMPLETE response returned by feed.';
    $err = $resp;
  }  else if (preg_match('/google.+export.+format=csv/i', $url) && ($json = csv_json($resp['body']))) {
    if (array_key_exists('slug', $json[0]) || array_key_exists('Slug', $json[0])) {
      $count = count($json);
    } else if (count($json) > 1) {
      $msg = 'INVALID data format returned by feed.';
      $err = $json;
    } else {
      $msg = 'ERROR loading Google Sheet.';
      $err = trim(htmlspecialchars(str_replace(">", ">\n", $resp['body'])));
    }
  } else if ($json = json_decode($resp['body'], true)) {
    if (array_key_exists('slug', $json[0])) {
      $count = count($json);
    } else {
      $msg = 'ERROR parsing feed data. | ' . print_r($json['error'], true);
      $err = $resp;
    }
  } else {
    $msg = 'JSON Error: <b>' . json_last_error() . '</b> | ' . json_last_error_msg();
    $err = trim(htmlspecialchars($resp['body']));
  }
}

function csv_json($arr)
{
  $csv =  explode("\n", $arr);
  $h = str_getcsv(array_shift($csv));
  $data = array_map(fn ($r) => array_combine($h, str_getcsv($r)), $csv);
  $json = json_decode(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE), true);
  foreach ($json as $k => $v) {
    if (array_key_exists('types', $json[$k])) $json[$k]['types'] = mtgtypes_json($v['types']);
    else if (array_key_exists('Types', $json[$k])) $json[$k]['Types'] = mtgtypes_json($v['Types']);
  }
  return $json;
}

function mtgtypes_json($v)
{
  $types = [];
  foreach (explode(",", $v) as $t) array_push($types, trim($t));
  return $types;
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
  <style type="text/ccc">aside {display:none !important;}</style>
  <script>
    window.onload = function() {
      history.pushState({}, null, unescape(location.href));
    }
  </script>
</head>

<body class="validate">

  <nav class="navbar navbar-expand-lg fixed-top navbar-dark" id="navbar">
    <div class="container">
      <a class="navbar-brand" href="/">Area 59 JSON Validator</a>
      <div class="collapse navbar-collapse" id="main-nav"> </div>
    </div>
  </nav>

  <main>
    <div class="container page">
      <div class="row">
        <div class="col-md-12">
          <p class="lead" style="margin-top:-15px; font-weight:350" ; style="margin-top:-10px">This validator will check if a JSON feed is valid and visible to the <span style="font-weight:450">Area 59</span> website.</p>
          <p>For more info on the Meeting Guide API, check out the <a href="https://github.com/meeting-guide/spec" zoompage-fontsize="17">specification</a>.</p>
          <form action="/jsonvalidate" method="get">
            <div class="input-group">
              <input type="url" name="url" class="form-control" value="<?php echo $url; ?>" placeholder="https://distirctwebsite.org/jsonfeed/">
              <div class="input-group-append">
                <input type="submit" class="btn btn-outline-secondary" value="Check Feed">
              </div>
            </div>
          </form>
          <?php if ($count > 0) {
            echo '<div class="alert alert-success" style="font-weight:500; font-size:17px">The feed is <b>valid</b> and returned <b>' . $count . '</b> meetings</div>';
            echo '<div class="lead" style="font-size:13px; margin:-10px 0 5px 0; line-height:1.1em">' . $url . '</div>';
            echo '<pre id="output"><code class="language-json">' . print_r($json, true) . '</code></pre>';
          } else if ($count == 0) {
            echo '<div class="alert alert-danger" style="font-weight:500; font-size:17px">' . $msg . '</div>';
            echo '<div class="lead" style="font-size:13px; margin:-10px 0 5px 0; line-height:1.1em">' . $url . '</div>';
            echo '<pre id="output"><code class="language-html">' . print_r($err, true) . '</code></pre>';
          } ?>
        </div>
      </div>
    </div>
  </main>

</body>

</html>