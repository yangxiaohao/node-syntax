<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="script.js"></script>
<link rel="stylesheet" type="text/css" href="style.css"/>
<title>CMS-UTILS</title>
</head>
<body>

<?php
require('markup.php');
require('syntax.php');


if (isset($_POST['content'])) {
	$content_editor = $_POST['content'];
} else {
	$content = file_get_contents('samples.txt');
	$content_editor = '[!code=c]'."\r\n";
	$lines = explode(PHP_EOL, $content);
	foreach ($lines as $line) {
		$content_editor .= "\t".$line."\r\n";
	}
}

?>

<!-- editor example -->
<div id="editor">
<form action="./" method="post">
<textarea name="content"><?php echo htmlspecialchars($content_editor); ?></textarea>
<br />
<input type="submit" />
</form>
<script language="javascript">
NodeEditor.hook(document.getElementById("editor"));
</script>
</div>

<div id="content">
<?php
if (isset($_POST['content'])) {
	// markup parse example
	$content = $_POST['content'];
	$markup = new NodeMarkup();
	echo $markup->resolve($content);
} else {
	// code highlight example
	echo '<div class="code"><pre>';
	$content = file_get_contents('samples.txt');
	$content = htmlspecialchars($content);
	$nodesyntax = new NodeSyntax();
	$content = $nodesyntax->highlight($content, 'c');
	echo preg_replace('/\r\n/u', '<br />', $content);
	echo '</pre></div>';
}
?>
</div>

</body>
</html>

