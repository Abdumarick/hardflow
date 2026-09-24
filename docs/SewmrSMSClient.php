<?php

class SewmrSMSClient
{
    private $baseUrl;

    private $accessToken;

    private $headers;

    private $defaultSenderId;

    public function __construct(
        $accessToken = null,
        $defaultSenderId = null
    ) {
        // API base URL
        $this->baseUrl = rtrim(defined('SMS_API_URL') ? SMS_API_URL : 'https://api.sewmrsms.co.tz/api/v1', '/').'/';
        $this->accessToken = $accessToken ?? (defined('SMS_API_TOKEN') ? SMS_API_TOKEN : '');
        $this->defaultSenderId = $defaultSenderId ?? (defined('SMS_SENDER_ID') ? SMS_SENDER_ID : 'SEWMR SMS');

        // Headers
        $this->headers = [
            "Authorization: Bearer {$this->accessToken}",
            'Content-Type: application/json',
        ];
    }

    /**
     * Send quick SMS to one or multiple recipients.
     */
    public function sendQuickSMS($message, $recipients = [], $senderId = null, $schedule = false, $scheduledFor = null, $scheduleName = null)
    {
        $url = $this->baseUrl.'sms/quick-send';

        // Use default sender ID if none provided
        $senderId = $senderId ?? $this->defaultSenderId;

        $payload = [
            'sender_id' => $senderId,
            'message' => $message,
            'recipients' => implode("\n", $recipients),
            'schedule' => $schedule,
        ];

        if ($schedule && $scheduledFor) {
            $payload['scheduled_for'] = $scheduledFor;
        }

        if ($scheduleName) {
            $payload['schedule_name'] = $scheduleName;
        }

        return $this->sendRequest('POST', $url, $payload);
    }

    /**
     * Send SMS to a contact group.
     */
    public function sendGroupSMS($message, $groupUuid, $senderId = null, $schedule = false, $scheduledFor = null, $scheduleName = null)
    {
        $url = $this->baseUrl.'sms/quick-send/group';

        // Use default sender ID if none provided
        $senderId = $senderId ?? $this->defaultSenderId;

        $payload = [
            'sender_id' => $senderId,
            'message' => $message,
            'group_uuid' => $groupUuid,
            'schedule' => $schedule,
        ];

        if ($schedule && $scheduledFor) {
            $payload['scheduled_for'] = $scheduledFor;
        }

        if ($scheduleName) {
            $payload['schedule_name'] = $scheduleName;
        }

        return $this->sendRequest('POST', $url, $payload);
    }

    /**
     * Fetch all sender IDs.
     */
    public function getSenderIds()
    {
        $url = $this->baseUrl.'sender-ids';

        return $this->sendRequest('GET', $url);
    }

    /**
     * Reusable function to handle API requests.
     */
    private function sendRequest($method, $url, $payload = null)
    {
        $ch = curl_init();

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);

            return [
                'success' => false,
                'error' => $error,
            ];
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);
        if (! is_array($decoded)) {
            return [
                'success' => false,
                'error' => 'Invalid SMS provider response.',
                'http_code' => $httpCode,
                'raw' => $response,
            ];
        }

        $decoded['http_code'] = $httpCode;

        return $decoded;
    }
}
