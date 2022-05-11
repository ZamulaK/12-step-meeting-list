<?php
/*
    Template Name: TestFeed-2-A
    */
?>
<pre>
<?php
$url = 'https://script.google.com/a/area59aa.org/macros/s/AKfycbySkcu1R3oO2P-R76bPzPx2ZnMtT0RY-02OmEKZUA/exec?id=42';
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
