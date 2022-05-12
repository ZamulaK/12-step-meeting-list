<?php
/*
    Template Name: JSON-Validate
*/

$count = -1;
$qs = preg_replace('/^\s*url=(.+)\s*$/i', '\1', urldecode($_SERVER['QUERY_STRING']));
$url = esc_url_raw($qs, ['http', 'https']);
if ($url !=  '') {
  $count = 0;
  $resp = wp_remote_get($url, ['timeout' => 30, 'sslverify' => false]);
  if (is_array($resp) && !empty($resp['body']) && ($body = json_decode($resp['body'], true))) {
    if (array_key_exists('slug', $body[0])) {
      $count = count($body);
    } else {
      $msg = "ERROR Code " .  http_response_code() . " | " . $body['error'];
    }
  } else if (!is_array($resp)) {
    $msg = "INVALID response returned by feed.";
  } elseif (empty($resp['body'])) {
    $msg = "EMPTY response returned by feed.";
  } else {
    $msg = "JSON Error/Response | " . json_last_error();
  }
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
  <script>
    window.onload = function() {
      history.pushState({}, null, unescape(location.href));
    }
  </script>
</head>

<body class="validate">

  <nav class="navbar navbar-expand-lg fixed-top navbar-dark" id="navbar">
    <div class="container">
      <a class="navbar-brand" href="/activity">Area 59 - JSON Feed Validator</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#main-nav" aria-controls="main-nav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="main-nav">
      </div>
    </div>
  </nav>

  <main>
    <div class="container page">
      <div class="row">
        <div class="col-md-12">
          <p class="lead" style="font-weight:350">Use this validator to check if a JSON is valid and visible to the <span style="font-weight:450">Area 59</span> website.</p>
          <p zoompage-fontsize="17">For more info on the Meeting Guide API, check out the <a href="https://github.com/meeting-guide/spec" zoompage-fontsize="17">specification</a>.</p>
          <form action="/jsonvalidate" method="get">
            <div class="input-group">
              <input type="url" name="url" class="form-control" value="<?php echo $url; ?>" placeholder="https://distirctwebsite.org/jsonfeed/">
              <div class="input-group-append" style="margin-left:10px">
                <input type="submit" class="btn btn-outline-secondary" value="Check Feed">
              </div>
            </div>
          </form>
          <div style="<? if ($count < 0) echo 'display:none'; ?>" class="alert alert-<? echo ($count > 0) ? 'success' : 'danger'; ?>">
            <? echo ($count > 0) ? 'SUCCESS:' : 'ERROR:'; ?>&nbsp;&nbsp;The feed returned <b><?php echo $count; ?></b> meetings.
            <?php if ($count == 0) echo "<div style='margin-top:10px'>" . $msg . "</div>"; ?>
          </div>
          <pre style="<? if ($count < 0) echo 'display:none'; ?>" class="rounded">
<code class="language-json"><?php print_r(($count > 0) ? $body : $resp); ?></code>
          </pre>
        </div>
      </div>
    </div>
  </main>

</body>

</html>