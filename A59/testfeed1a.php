<?php
    /*
    Template Name: TestFeed-1-A
    */
?>
<pre>
<?php
var_export(wp_remote_get('https://script.google.com/a/area59aa.org/macros/s/AKfycbySkcu1R3oO2P-R76bPzPx2ZnMtT0RY-02OmEKZUA/exec?id=42',['timeout' => 30,'sslverify' => false]));
<?php
    /*
    Template Name: TestFeed-2-A
    */
?>
<pre>
<?php
$data_source_url = 'https://script.google.com/a/area59aa.org/macros/s/AKfycbySkcu1R3oO2P-R76bPzPx2ZnMtT0RY-02OmEKZUA/exec?id=42';
$response = wp_remote_get($data_source_url, ['timeout' => 30, 'sslverify' => false,]);
if (is_array($response) && !empty($response['body']) && ($body = json_decode($response['body'], true))) {
 var_export($body);
}
else {
  var_export(json_last_error());
}