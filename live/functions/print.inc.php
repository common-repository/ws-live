<?php
function zing_ws_print($title,$message) {
?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php echo $title ?></title>
</head>
<body onLoad="javascript:window.print()">
<?php echo $message; ?>
</body>
</html>

<?php }?>