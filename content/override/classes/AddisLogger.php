<?php
class AddisLogger extends ObjectModel {

	public static function log($component, $method, $ex, $info = '') {
		// $message = '';
		// $trace = '';
		// if (isset($ex) && $ex) {
		// 	$message = $ex->message();
		// 	$trace = $ex->StackTrace();
		// }

		// if (is_array($info)) {
		// 	$info = var_export($info, true);
		// }

		// $logFolder = _PS_ROOT_DIR_.'/logs';
		// $logFilename = 'ADDIS_'.date('Ymd').'.log';
		// if (!file_exists ($logFolder)) {
		// 	mkdir($logFolder);
		// }

		// if (!$trace && $method == "peticionput") {
		// 	$trace = new Exception('Traza');
		// }

		// $stream = @fopen($logFolder.'/'.$logFilename, 'a');
		// if ($stream) {
		// 	fwrite($stream, '<error>'."\r\n");
		// 	fwrite($stream, '<time>'.date('G:i:s').'</time>'."\r\n");
		// 	fwrite($stream, '<component>'.$component.'</component>'."\r\n");
		// 	fwrite($stream, '<method>'.$method.'</method>'."\r\n");
		// 	fwrite($stream, '<description><![CDATA['.$message.']]></description>'."\r\n");
		// 	fwrite($stream, '<info><![CDATA['.$info.']]></info>'."\r\n");
		// 	fwrite($stream, '<trace><![CDATA['.$trace.']]></trace>'."\r\n");
		// 	fwrite($stream, '</error>'."\r\n");
		// 	fclose($stream);
		// }
	}

}