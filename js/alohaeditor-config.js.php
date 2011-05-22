<?
	header('Content-type: text/javascript');
    $dir = dirname( __FILE__ ) . '/../php';
	require_once $dir . '/alohaeditor.php';
	FEE_AlohaEditor::printAlohaEditorConfiguration();
?>