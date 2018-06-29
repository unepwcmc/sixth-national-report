<?php
	//****************************************
	//edit here
	$senderName = 'WEB';
	$senderEmail = $_POST['senderEmail'];
	$targetEmail = $_POST['targetEmail'];
	$messageSubject = 'Message from web-site';
	$redirectToReferer = true;
	//****************************************

	// mail content
	$uname = $_POST['uname'];
	$uemail = $_POST['uemail'];
	$umessage = $_POST['umessage'];

	// collect interests data
	$interestsString = '';
	for($i = 0; $i < count($interests); $i++) {
		$interestsString .= $interests[$i].($i < count($interests) - 1 ? ', ' : '');
	}

	// prepare message text
	$messageText =	'Name: '.$uname."\n".
					'Email: '.$uemail."\n".
					'Your Message: '.$umessage."\n";

	if($interestsString) {
		$messageText .= 'Interests: '.$interestsString."\n";
	}

	// send email
	$senderName = "=?UTF-8?B?" . base64_encode($senderName) . "?=";
	$messageSubject = "=?UTF-8?B?" . base64_encode($messageSubject) . "?=";
	$messageHeaders = "From: " . $senderName . " <" . $senderEmail . ">\r\n"
				. "MIME-Version: 1.0" . "\r\n"
				. "Content-type: text/plain; charset=UTF-8" . "\r\n";

	if (preg_match('/^[_.0-9a-z-]+@([0-9a-z][0-9a-z-]+.)+[a-z]{2,4}$/',$targetEmail,$matches))
	mail($targetEmail, $messageSubject, $messageText, $messageHeaders);

	// redirect
	if($redirectToReferer) {
		header("Location: ".@$_SERVER['HTTP_REFERER'].'#sent');
	} else {
		header("Location: ".$redirectURL);
	}
?>