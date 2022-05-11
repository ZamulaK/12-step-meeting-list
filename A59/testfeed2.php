<?php
/*
    Template Name: TestFeed-2
    */
?>
<pre>
<?php
$url = 'https://padistrict42.org/wp-admin/admin-ajax.php?action=meetings&key=854aec833cc75bb085e64a72b899a2bb';
$url_esc = trim(esc_url_raw($url, ['http', 'https']));
echo($url_esc);
$response = wp_remote_get($url, ['timeout' => 30, 'sslverify' => false]);
$body = $response['body'];
echo ("RAW:\n\n");
echo ($body);
if (is_array($response) && !empty($body) && ($json = json_decode($body, true, 512, JSON_INVALID_UTF8_IGNORE))) {
  echo ("\n\n\nJSON:\n\n");
  echo (json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE));
} else {
  echo (json_last_error());
}
