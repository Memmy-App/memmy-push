<?php

namespace App\Lemmy;

use Illuminate\Support\Facades\Http;

class LemmyHelper
{
    private string $username;
    private string $instance;
    private string $authToken;

    private array $headers;
    private string $baseUrl;

    public function setup(string $username, string $instance, string $authToken): void {
        $this->username = $username;
        $this->instance = $instance;
        $this->authToken = $authToken;

        $this->headers = [
            "User-Agent" => "Memmy Push/0.1 on behalf of $this->username",
        ];

        $this->baseUrl = "https://$this->instance/api/v3";
    }

    public function authenticate(): bool {
        $data = [
            "auth" => $this->authToken,
            "username" => "$this->username@$this->instance",
        ];

        $response = Http::withHeaders($this->headers)->acceptJson()->get("$this->baseUrl/user/mention", $data);

        error_log(json_encode($response));

        if($response->status() !== 200) {
            return false;
        }

        return true;
    }

    public function getLastReply(): bool|array {
        $data = [
            "auth" => $this->authToken,
            "limit" => 1,
            "unread_only" => "true",
        ];

        try {

            $response = Http::withHeaders($this->headers)->acceptJson()->timeout(7)->get("$this->baseUrl/user/replies", $data)->json();

            if (!$response || !$response["replies"] || !$response["replies"][0]) return false;

            return [
                "id" => $response["replies"][0]["comment_reply"]["id"],
                "user" => $response["replies"][0]["creator"]["name"],
                "content" => $response["replies"][0]["comment"]["content"],
            ];
        } catch(\Exception $e) {
            error_log($e);
            return false;
        }
    }
}
