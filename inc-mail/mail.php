<?php
	require 'PHPMailer-lib/PHPMailerAutoload.php';
	
	require 'mail-config.php';
	
	if( isset( $_POST['mail_submit'] ) ){
	
		$email_object = new PHPMailer();
		
		$send_mail = new sensToMail( $to, $subject, $fields_names, $headers, $redirect_url, $success_message, $email_object, $mime, $ajax );
	
	}
	
	class sensToMail {
		function __construct( $to = '', $subject = '', $fields, $headers = array(), $redirect_url = '/thankyou.html', $success_message = '', $email_object, $mime_types, $ajax = false ){
			$message = $this->getMessageContent( $fields );
			
			$attachments = $this->getMessageAttachments( $mime_types );
			
			$this->mail_func( $to, $subject, $message, $headers, $attachments, $redirect_url, $success_message, $email_object, $ajax );
		}
		
		private function getMessageContent( $fields = '' ){
			
			$fields_array = explode( ';', $fields );
			
			$fields_names = array();
			
			foreach( $fields_array as $field ){
				$field_holder = explode( '=', $field );
				$fields_names[$field_holder[0]] = $field_holder[1];
			}
			
			$fields = array();
	
			if( ! empty( $fields_names ) ){
				foreach( $fields_names as $key => $field ){
					if( ! empty( $_POST[$key] ) ){
						$fields[$key] = $field;
					}
				}
			}
			
			$message = '';
			
			if( ! empty( $fields ) ){
				$count = 0;
				foreach( $fields as $key => $field ){
					
					if( is_array( $_POST[$key] ) ){
						
						$message .= $key . ' ';
						$item_count = 0;
						foreach( $_POST[$key] as $item ){
							$message .= $item . ( $item_count < count( $_POST[$key] ) - 1 ? ', ' : '' );
							$item_count++;
						}
						
						$message .= "\n";
						
					}else{
						$message .= $field . ' ' . $_POST[$key] . ( $count < count( $fields ) - 1 ? ', ' : '' ) . "\n";
					}
					$count++;
				}
				return $message;
			}else{
				return false;
			}
		}
		
		private function getMessageAttachments( $mime_types ){
			$attachments = isset( $_FILES['ufiles'] ) ? $_FILES['ufiles'] : array();
			
			$attachments_files = array();
			$names = array();
			$types = array();
			
			foreach( $attachments as $key => $attachment ){
				if( $key == 'name' ){
					foreach( $attachment as $key => $item ){
						if( ! empty( $item ) ){
							$names[$key] = realpath(dirname(__FILE__)) . '/PHPMailer-lib/temp/' . $item;
						}
					}
				}
				
			}
			
			$mime_types = $this->getAllowedMimeTipes( $mime_types );
			
			foreach( $names as $key => $name ){
				if( $attachments['error'][$key] == 0 && in_array( $attachments['type'][$key], $mime_types ) ){
					move_uploaded_file( $attachments['tmp_name'][$key], $name );
					$attachments_files[] = $name;
				}
			}

			return $attachments_files;
		
		}
		
		private function removeSentAttachments( $attachments = array() ){
			if ( !empty( $attachments ) ) {
				foreach ( $attachments as $attachment ){
					if( file_exists( $attachment ) ){
						unlink( $attachment );
					}
				}
			}
		}
		
		private function getAllowedMimeTipes( $mime_types ){
			$types = explode( ',', $mime_types );
			return $types;
		}
		
		public function mail_func( $to = '', $subject = '', $message = array(), $headers = array(), $attachments = array(), $redirect_url = '', $success_message = '', $email_object, $ajax = false ){
	
			if ( !empty( $headers ) ) {
				foreach ( ( array ) $headers as $header ) {
					if ( strpos( $header, ':' ) === false ) {
						if ( false !== stripos( $header, 'boundary=' ) ) {
							$parts = preg_split('/boundary=/i', trim( $header ) );
							$boundary = trim( str_replace( array( "'", '"' ), '', $parts[1] ) );
						}
						continue;
					}
					list( $name, $content ) = explode( ':', trim( $header ), 2 );
					
					$cc = $bcc = $reply_to = array();
	
					$name    = trim( $name    );
					$content = trim( $content );
	
					switch ( strtolower( $name ) ) {
						case 'from':
							$bracket_pos = strpos( $content, '<' );
							if ( $bracket_pos !== false ) {
								if ( $bracket_pos > 0 ) {
									$from_name = substr( $content, 0, $bracket_pos - 1 );
									$from_name = str_replace( '"', '', $from_name );
									$from_name = trim( $from_name );
								}
	
								$from_email = substr( $content, $bracket_pos + 1 );
								$from_email = str_replace( '>', '', $from_email );
								$from_email = trim( $from_email );
	
							} elseif ( '' !== trim( $content ) ) {
								$from_email = trim( $content );
							}
							break;
						case 'content-type':
							if ( strpos( $content, ';' ) !== false ) {
								list( $type, $charset_content ) = explode( ';', $content );
								$content_type = trim( $type );
								if ( false !== stripos( $charset_content, 'charset=' ) ) {
									$charset = trim( str_replace( array( 'charset=', '"' ), '', $charset_content ) );
								} elseif ( false !== stripos( $charset_content, 'boundary=' ) ) {
									$boundary = trim( str_replace( array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset_content ) );
									$charset = '';
								}
	
							} elseif ( '' !== trim( $content ) ) {
								$content_type = trim( $content );
							}
							break;
						case 'cc':
							$cc = array_merge( (array) $cc, explode( ',', $content ) );
							break;
						case 'bcc':
							$bcc = array_merge( (array) $bcc, explode( ',', $content ) );
							break;
						case 'reply-to':
							$reply_to = array_merge( (array) $reply_to, explode( ',', $content ) );
							break;
						default:
							$headers[trim( $name )] = trim( $content );
							break;
					}
				}
			}
			
			$email_object->ClearAllRecipients();
			$email_object->ClearAttachments();
			$email_object->ClearCustomHeaders();
			$email_object->ClearReplyTos();
			
			if ( !isset( $from_name ) ){
				$from_name = $_SERVER['HTTP_REFERER'];
			}
			
			if ( !isset( $from_email ) ) {
				$sitename = strtolower( $_SERVER['SERVER_NAME'] );
				if ( substr( $sitename, 0, 4 ) == 'www.' ) {
					$sitename = substr( $sitename, 4 );
				}
		
				$from_email = $sitename;
			}
			
			$email_object->setFrom( $from_email, $from_name, false );
		
			$email_object->Subject = $subject;
			$email_object->Body    = $message;
		
			$address_headers = compact( 'to', 'cc', 'bcc', 'reply_to' );
		
			foreach ( $address_headers as $address_header => $addresses ) {
				if ( empty( $addresses ) ) {
					continue;
				}
		
				foreach ( (array) $addresses as $address ) {
					try {
						$recipient_name = '';
		
						if ( preg_match( '/(.*)<(.+)>/', $address, $matches ) ) {
							if ( count( $matches ) == 3 ) {
								$recipient_name = $matches[1];
								$address        = $matches[2];
							}
						}
		
						switch ( $address_header ) {
							case 'to':
								$email_object->addAddress( $address, $recipient_name );
								break;
							case 'cc':
								$email_object->addCc( $address, $recipient_name );
								break;
							case 'bcc':
								$email_object->addBcc( $address, $recipient_name );
								break;
							case 'reply_to':
								$email_object->addReplyTo( $address, $recipient_name );
								break;
						}
					} catch ( phpmailerException $e ) {
						continue;
					}
				}
			}
	
			$email_object->IsMail();
		
			if ( !isset( $content_type ) ){
				$content_type = 'text/plain';
			}
		
			$email_object->ContentType = $content_type;
		
			if ( 'text/html' == $content_type )
				$email_object->IsHTML( true );
		
			if ( !isset( $charset ) ){
				$charset = 'UTF-8';
			}
		
			$email_object->CharSet = $charset;
		
			if ( !empty( $headers ) ) {
				foreach ( (array) $headers as $name => $content ) {
					$email_object->AddCustomHeader( sprintf( '%1$s: %2$s', $name, $content ) );
				}
		
				if ( false !== stripos( $content_type, 'multipart' ) && ! empty($boundary) ){
					$email_object->AddCustomHeader( sprintf( "Content-Type: %s;\n\t boundary=\"%s\"", $content_type, $boundary ) );
				}
			}
		
			if ( !empty( $attachments ) ) {
				foreach ( $attachments as $attachment ) {
					try {
						$email_object->addAttachment($attachment);
					} catch ( phpmailerException $e ) {
						continue;
					}
				}
			}
			
			try {
				$email_object->Send();
				$this->removeSentAttachments( $attachments );
				if( $ajax ){
					echo json_encode( $success_message );
					exit();
				}else{
					if( $redirect_url ){
						header( "Location: " . $redirect_url .'#' . urlencode( $success_message ) );
					}else{
						header( "Location: ".@$_SERVER['HTTP_REFERER'].'#' . urlencode( $success_message ) );
					}
				}
				
			} catch ( phpmailerException $e ) {
				$this->removeSentAttachments( $attachments );
				if( $ajax ){
					echo json_encode( "Mailer Error: " . $mail->ErrorInfo );
					exit();
				}else{
					if( $redirect_url ){
						header( "Location: " . $redirect_url . '#' . urlencode( "Mailer Error: " . $mail->ErrorInfo ) );
					}else{
						header( "Location: ".@$_SERVER['HTTP_REFERER'].'#' . urlencode( "Mailer Error: " . $mail->ErrorInfo ) );
					}
				}
			}
		}
	}
?>