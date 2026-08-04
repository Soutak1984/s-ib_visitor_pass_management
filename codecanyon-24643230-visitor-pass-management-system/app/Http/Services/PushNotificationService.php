<?php

namespace App\Http\Services;

use Google\Client;
use App\Models\User;


class PushNotificationService
{
    public function sendPushNotification($visitingDetails)
    {
     

        $url = 'https://fcm.googleapis.com/v1/projects/' . setting('projectId') . '/messages:send';

        $data = [
            "message" => [
                "notification" => [
                    "title" => "New Visitor #" . $visitingDetails->visitor->name,
                    "body"  => 'You have a new visitor named ' . $visitingDetails->visitor->name . ' The visitor phone number is ' . $visitingDetails->visitor->phone,
                ]
            ]
        ];

        $encodedData = json_encode($data);

        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken(),
            'Content-Type: application/json'
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);
        $result = curl_exec($ch);
        
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }

        curl_close($ch);
        return $result;
    }


    public function fcmSubscribe($request)
    {

        $deviceToken = $request->device_token;
        $topic = env('FCM_TOPIC') . '_' . str_replace(['@', '.', '+'], ['_', '_', ''], $request->topic);


        $headers = array(
            'Authorization: key=' . env('FCM_SECRET_KEY'),
            'Content-Type: application/json'
        );
        $this->fcmGlobalSubscribe($request);
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://iid.googleapis.com/iid/v1/$deviceToken/rel/topics/$topic");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            return response()->json([
                'status' => 200,
                'message' => 'Subscribed',
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'status'  => 401,
                'message' => $exception,
            ], 401);
        }
    }


    public function fcmGlobalSubscribe($request)
    {
        $deviceToken = $request->device_token;
        $topic = env('FCM_TOPIC');

        $headers = array(
            'Authorization: key=' . env('FCM_SECRET_KEY'),
            'Content-Type: application/json'
        );

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://iid.googleapis.com/iid/v1/$deviceToken/rel/topics/$topic");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            return response()->json([
                'status' => 200,
                'message' => 'Global Subscription',
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'status'  => 401,
                'message' => $exception,
            ], 401);
        }
    }


    public function fcmUnsubscribe($request)
    {
        $request->validate([
            'device_token' => 'required',
            'topic' => 'nullable',
        ]);

        $deviceToken = $request->token;

        $headers = array(
            'Authorization: key=' . env('FCM_SECRET_KEY'),
            'Content-Type: application/json'
        );

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://iid.googleapis.com/v1/web/iid/$deviceToken");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);

            return response()->json([
                'status' => 200,
                'message' => 'Unsubscribed',
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'status'  => 401,
                'message' => $exception,
            ], 401);
        }
    }

    public function sendWebNotification($visitingDetails)
    {
        $user_id  = $visitingDetails->employee->user->id;
        $FcmToken = User::where('id', $user_id)->whereNotNull('web_token')->pluck('web_token')->toArray();
        $url      = 'https://fcm.googleapis.com/v1/projects/' . setting('projectId') . '/messages:send';
        $data = [
            "message" => [
                "token"        => $FcmToken[0],
                "notification" => [
                    "title" => 'New Visitor #' . $visitingDetails->visitor->name,
                    "body"  => 'You have a new visitor named ' . $visitingDetails->visitor->name . ' The visitor phone number is ' . $visitingDetails->visitor->phone,
                ]
            ]
        ];
        $encodedData = json_encode($data);
        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken(),
            'Content-Type: application/json'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);
        $result = curl_exec($ch);
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }
        curl_close($ch);
        return true;
    }

    public function getAccessToken()
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/firebase/service-account-file.json'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->useApplicationDefaultCredentials();

        $token = $client->fetchAccessTokenWithAssertion()['access_token'];

        return $token;
    }
}
