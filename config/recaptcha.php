<?php
require_once __DIR__ . '/secret_manager.php';

$RECAPTCHA_SITE_KEY = caSecretResolve('mask:v1:9tvk7nJhREVNimnowNnYLjcMS2Rf8rEoJp6x5vUJ2JIoGY61_qJapG0KzKfK4EW5KEhK8eSrVTvy57mOmKuNI5CIchE');
$RECAPTCHA_SECRET_KEY = caSecretResolve('mask:v1:fB6c_vBoe7RmnGFqls6-S9oaptCJoqiC4gj_uD-TIWcl5a3K5PBXw5qXyKkriijPPe-4I2oMWlYBRLAhjAh_eBgqbPg');
?>