<?php
    $to = 'ferdinando.primerano@unep-wcmc.org';

    $subject = 'UNBiodiversityLab';

    // form fields separated with commas, field name inform = field name in letter
    $fields_names = 'uname=First Name: ;uemail=Email Address: ;umessage=Message: ';


    $headers[] = 'From: UNBiodiversityLab';

    $redirect_url = '';

    $success_message = 'Message sent';

    // allowed mime types for attachment files
	$mime = 'image/gif,image/jpeg,image/png,image/svg+xml,text/plain';

    // if ajax = true you will get json success or error message instead of redirect
    $ajax = true;

    // important! submit button must have name mail_submit <input type="submit" value="Submit!" name="mail_submit"/>
