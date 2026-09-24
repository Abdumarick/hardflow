<?php

class SmsSender
{
    // Define constants for the API URLs
    const API_URL = 'https://api.sewmrsms.co.tz/api/v1'; // SMS Sending URL

    const TOKEN_URL = 'https://api.sewmrsms.co.tz/api/v1/auth/login'; // Token Generation URL

    // Define constants for username, password, and sender ID
    const USERNAME = 'abdurafiuabdumarick';

    const PASSWORD = 'Abdu@2026';

    const SENDER_ID = 'SEWMR SMS';

    private $accessToken;

    // Constructor to initialize access token
    public function __construct()
    {
        $this->accessToken = $this->generateAccessToken();
    }

    // Getter for accessToken (to access private property)
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    // Method to generate the access token
    private function generateAccessToken()
    {
        // Prepare the authorization header for Basic Auth
        $credentials = base64_encode(self::USERNAME.':'.self::PASSWORD);

        // Initialize cURL to request access token
        $ch = curl_init(self::TOKEN_URL);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic '.$credentials, // Basic Auth header
            'Content-Type: application/json',
        ]);

        // Execute the cURL request and get the response
        $response = curl_exec($ch);

        // Check for cURL errors
        if ($response === false) {
            return json_encode([
                'success' => false,
                'message' => 'cURL Error: '.curl_error($ch),
            ]);
        }

        // Decode the JSON response
        $responseData = json_decode($response, true);

        // Check if token generation was successful
        if (isset($responseData['success']) && $responseData['success'] == true) {
            return $responseData['access_token']; // Return the access token
        } else {
            return json_encode([
                'success' => false,
                'message' => 'Failed to generate token: '.$responseData['message'],
            ]);
        }

        // Close the cURL session
        curl_close($ch);
    }

    // Method to send SMS using the generated access token
    public function sendSms($destination, $message)
    {
        if (! $this->getAccessToken()) {
            return json_encode([
                'success' => false,
                'message' => 'Access token is missing or invalid.',
            ]);
        }

        // Prepare the POST data
        $data = [
            'access_token' => $this->getAccessToken(),
            'source' => self::SENDER_ID,  // Use constant for sender ID
            'destination' => $destination,
            'message' => $message,

        ];

        // Convert the data array to JSON format
        $jsonData = json_encode($data);

        // Initialize cURL to send SMS
        $ch = curl_init(self::API_URL);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->getAccessToken(), // Add the Authorization header
        ]);

        // Execute the cURL request and get the response
        $response = curl_exec($ch);

        // Check for cURL errors
        if ($response === false) {
            return json_encode([
                'success' => false,
                'message' => 'cURL Error: '.curl_error($ch),
            ]);
        }

        // Decode the JSON response from the API
        $responseData = json_decode($response, true);

        // Check if the SMS was sent successfully
        if (isset($responseData['success']) && $responseData['success'] == true) {
            return json_encode([
                'success' => true,
                'message' => 'SMS sent successfully!',
            ]);
        } else {
            return json_encode([
                'success' => false,
                'message' => 'Failed to send SMS: '.$responseData['message'],
            ]);
        }

        // Close the cURL session
        curl_close($ch);
    }
}
