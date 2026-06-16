<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ZohoController extends Controller
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'https://www.zohoapis.com/crm/v2/';
    }

    private function getAccessToken()
    {
        $url = 'https://accounts.zoho.com/oauth/v2/token';
        
        // Prepare the POST fields
        $postFields = [
            'refresh_token' => '1000.ea33e5ba4638351940044b8a4ca63a1e.9627ef29310cd6de1ee96c731ab6106d',
            'client_id' => '1000.B8P5NRRYW50I37087ZFD7XD56DHZ7E',
            'client_secret' => '11ae5181b2220cb530152d3fd678bbefc8ff44bc78',
            'grant_type' => 'refresh_token',
        ];

        // Initialize cURL session
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        // Execute cURL request
        $response = curl_exec($ch);

        // Check if there was a cURL error
        if (curl_errno($ch)) {
            $errorMessage = curl_error($ch);
            logger()->error('cURL Error:', ['message' => $errorMessage]);
        }

        // Get HTTP status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Close cURL session
        curl_close($ch);

        // Log the entire response for debugging
        logger()->info('Token Request Response', [
            'status' => $httpCode,
            'response' => $response,
        ]);

        // Decode the JSON response
        $responseData = json_decode($response, true);

        // Check for errors in response data
        if (isset($responseData['error'])) {
            logger()->error('Token Request Error', [
                'error' => $responseData['error'],
                'error_description' => $responseData['error_description'] ?? 'No description provided',
            ]);
            return null;
        }

        // Return access token if present
        return $responseData['access_token'] ?? null;
    }

    public function callbackForm(Request $request)
    {
        //return $request;
        $validatedData = $request->validate([
            'First_Name' => 'required|string|max:255',
            'Last_Name' => 'required|string|max:255',
            'Parent_Email' => 'required|email|max:255',

            'Student_First_Name' => 'required|string|max:255',
            'Student_Last_Name' => 'required|string|max:255',

        ]);

        $data = [

            // 'Inquiry_Source' => $request->Inquiry_Source,
            // 'Training_Inquiry_Status' => $request->Training_Inquiry_Status,
            // 'Training_Enrollment_Status' => $request->Training_Enrollment_Status,
            'Inquiry_Source' => $request->Inquiry_Source,
            //'Training_Inquiry_Status' => $request->Training_Inquiry_Status,
            //'Training_Enrollment_Status' => $request->Training_Enrollment_Status,
            'Date_of_Entry' => $request->Date_of_Entry,

            'First_Name' => $validatedData['First_Name'],
            'Last_Name' => $validatedData['Last_Name'],
            'Parent_Email' => $validatedData['Parent_Email'],
            'Phone_No' => $request->Phone_No,
            'Parent_Country_of_Residence' => $request->Parent_Country_of_Residence,

            'Student_First_Name' => $validatedData['Student_First_Name'],
            'Student_Last_Name' => $validatedData['Student_Last_Name'],
            'Student_DOB' => $request->Student_DOB,
            'Preferred_Start_Date' => $request->Preferred_Start_Date,
            'Key_Stage' => $request->Key_Stage,
            'How_To_Help' => $request->How_To_Help,
            'Query' => $request->Query,
            
        ];

        $accessToken = $this->getAccessToken();
        // echo '<pre>';
        // print_r($accessToken);
        // echo '</pre>';
        // exit;
        // $accessToken = '1000.b1c5d0b203f888b6baf7b31f0b8e07e7.33c54c7a3b71fce709da3751f81715f4';
        if (!$accessToken) {
            return 'Failed: Unable to fetch access token';
        }

        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
        ])->post($this->baseUrl . 'Online_School', [
            'data' => [$data],
        ]);

        $responseData = $response->json();

        if (isset($responseData['data'][0]['code']) && $responseData['data'][0]['code'] === 'SUCCESS') {
            return response()->json(['status' => 'success', 'message' => 'We have successfully received your query. Thank you for contacting us!']);
        } else {
            $errorMessage = isset($responseData['data'][0]['message']) ? $responseData['data'][0]['message'] : 'Your query could not be submitted. Please check your information and try again.';
            return response()->json(['status' => 'error', 'message' => 'Failed: ' . $errorMessage], 400);
        }
    }

    function contactUs(Request $request){
        //return $request;

        $validatedData = $request->validate([
            'Student_First_Name' => 'required|string|max:255',
            'Student_Last_Name' => 'required|string|max:255',
            'Parent_Email' => 'required|email|max:255',
        ]);

        $data = [

            'Inquiry_Source' => $request->Inquiry_Source,
            'Date_of_Entry' => $request->Date_of_Entry,
            'Parent_Email' => $validatedData['Parent_Email'],
            'Phone_No' => $request->Phone_No,
            'Student_First_Name' => $validatedData['Student_First_Name'],
            'Student_Last_Name' => $validatedData['Student_Last_Name'],
            'Query' => $request->Query,
            
        ];

        $accessToken = $this->getAccessToken();
       
        if (!$accessToken) {
            return 'Failed: Unable to fetch access token';
        }

        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
        ])->post($this->baseUrl . 'Online_School', [
            'data' => [$data],
        ]);

        $responseData = $response->json();

        if (isset($responseData['data'][0]['code']) && $responseData['data'][0]['code'] === 'SUCCESS') {
            return response()->json(['status' => 'success', 'message' => 'We have successfully received your query. Thank you for contacting us!']);
        } else {
            $errorMessage = isset($responseData['data'][0]['message']) ? $responseData['data'][0]['message'] : 'Your query could not be submitted. Please check your information and try again.';
            return response()->json(['status' => 'error', 'message' => 'Failed: ' . $errorMessage], 400);
        }
    }

    function sendJobApplication(Request $request){
        //return $request;

        $validatedData = $request->validate([
            'Student_First_Name' => 'required|string|max:255',
            'Student_Last_Name' => 'required|string|max:255',
            'Parent_Email' => 'required|email|max:255',
        ]);

        $data = [

            'Inquiry_Source' => $request->Inquiry_Source,
            'Date_of_Entry' => $request->Date_of_Entry,
            'Parent_Email' => $validatedData['Parent_Email'],
            'Phone_No' => $request->Phone_No,
            'Student_First_Name' => $validatedData['Student_First_Name'],
            'Student_Last_Name' => $validatedData['Student_Last_Name'],
            'Query' => $request->Query,
            
        ];

        $accessToken = $this->getAccessToken();
       
        if (!$accessToken) {
            return 'Failed: Unable to fetch access token';
        }

        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
        ])->post($this->baseUrl . 'Online_School', [
            'data' => [$data],
        ]);

        $responseData = $response->json();

        if (isset($responseData['data'][0]['code']) && $responseData['data'][0]['code'] === 'SUCCESS') {
            return response()->json(['status' => 'success', 'message' => 'We have successfully received your query. Thank you for contacting us!']);
        } else {
            $errorMessage = isset($responseData['data'][0]['message']) ? $responseData['data'][0]['message'] : 'Your query could not be submitted. Please check your information and try again.';
            return response()->json(['status' => 'error', 'message' => 'Failed: ' . $errorMessage], 400);
        }

    }
}
