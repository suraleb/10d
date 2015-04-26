<?php
$email = @$_POST['email'];

if (strlen($email) < 4 || strpos($email, '@') === false) {
    header('location: /#wrong');
    exit();
}

$satus = @mail('dave@launch365.com', '10denza', "New mail to be notified: {$email}");
header('location: /#' . ($satus ? 'success' : 'failed'));

exit();
