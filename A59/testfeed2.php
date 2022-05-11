<?php
    /*
    Template Name: TestFeed-2-A
    */
?>
<pre>
<?php
$data_source_url = 'https://padistrict42.org/wp-admin/admin-ajax.php?action=meetings&key=854aec833cc75bb085e64a72b899a2bb';
$response = wp_remote_get($data_source_url, ['timeout' => 30, 'sslverify' => false,]);
if (is_array($response) && !empty($response['body']) && ($body = json_decode($response['body'], true))) {
 var_export($body);
}
else {
  var_export(json_last_error());
}