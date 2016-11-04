<?php

namespace Rhymix\Framework\Drivers\SMS;

/**
 * The CoolSMS SMS driver.
 */
class CoolSMS extends Base implements \Rhymix\Framework\Drivers\SMSInterface
{
	/**
	 * API specifications.
	 */
	protected static $_spec = array(
		'max_recipients' => 1000,
		'sms_max_length' => 90,
		'sms_max_length_in_charset' => 'CP949',
		'lms_supported' => true,
		'lms_supported_country_codes' => array(82),
		'lms_max_length' => 2000,
		'lms_max_length_in_charset' => 'CP949',
		'lms_subject_supported' => true,
		'lms_subject_max_length' => 40,
		'mms_supported' => true,
		'mms_supported_country_codes' => array(82),
		'mms_max_length' => 2000,
		'mms_max_length_in_charset' => 'CP949',
		'mms_subject_supported' => true,
		'mms_subject_max_length' => 40,
		'image_allowed_types' => array('jpg', 'gif', 'png'),
		'image_max_dimensions' => array(2048, 2048),
		'image_max_filesize' => 300000,
		'delay_supported' => true,
	);
	
	/**
	 * Config keys used by this driver are stored here.
	 */
	protected static $_required_config = array('api_key', 'api_secret', 'sender_key');
	
	/**
	 * Send a message.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @param array $messages
	 * @return bool
	 */
	public function send(array $messages)
	{
		try
		{
			$sender = new \Nurigo\Api\Message($this->_config['api_key'], $this->_config['api_secret']);
			$status = true;
			
			foreach ($messages as $i => $message)
			{
				$options = new \stdClass;
				$options->type = $message->type;
				$options->from = $message->from;
				$options->to = implode(',', $message->to);
				$options->text = $message->content ?: 'SMS';
				$options->charset = 'utf8';
				if ($message->delay && $message->delay > time())
				{
					$options->datetime = gmdate('YmdHis', $message->delay + (3600 * 9));
				}
				if ($message->country && $message->country != 82)
				{
					$options->country = $message->country;
				}
				if ($message->subject)
				{
					$options->subject = $message->subject;
				}
				if ($message->image)
				{
					$options->image = $message->image;
				}
				
				$result = $sender->send($options);
				if (!$result->success_count)
				{
					$error_codes = implode(', ', $result->error_list ?: array('Unknown'));
					$message->errors[] = 'Error (' . $error_codes . ') while sending message ' . $i . ' of ' . count($messages) . ' to ' . $options->to;
					$status = false;
				}
			}
			
			return $status;
		}
		catch (\Nurigo\Exceptions\CoolsmsException $e)
		{
			$message->errors[] = class_basename($e) . ': ' . $e->getMessage();
			return false;
		}
	}
}
