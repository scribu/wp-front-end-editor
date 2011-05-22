<?
	header('Content-type: text/javascript');
    $dir = dirname( __FILE__ ) . '/../php';
	require_once $dir . '/alohaeditor-0.9.3-provider.php';
	FEE_AlohaEditor::printAlohaEditorConfiguration();
?>